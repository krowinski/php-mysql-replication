<?php


namespace MySQLReplication\JsonBinaryDecoder;

use MySQLReplication\BinaryDataReader\BinaryDataReader;
use MySQLReplication\BinaryDataReader\BinaryDataReaderException;

/**
 * Class JsonBinaryDecoderService
 * @package MySQLReplication\JsonBinaryDecoder
 * @see https://github.com/mysql/mysql-server/blob/5.7/sql/json_binary.cc
 * @see https://github.com/shyiko/mysql-binlog-connector-java/blob/master/src/main/java/com/github/shyiko/mysql/binlog/event/deserialization/json/JsonBinary.java
 */
class JsonBinaryDecoderService
{
    const SMALL_OBJECT = 0;
    const LARGE_OBJECT = 1;
    const SMALL_ARRAY = 2;
    const LARGE_ARRAY = 3;
    const LITERAL = 4;
    const INT16 = 5;
    const UINT16 = 6;
    const INT32 = 7;
    const UINT32 = 8;
    const INT64 = 9;
    const UINT64 = 10;
    const DOUBLE = 11;
    const STRING = 12;
    const OPAQUE = 15;

    /**
     * @var BinaryDataReader
     */
    private $binaryDataReader;
    /**
     * @var JsonBinaryDecoderFormatter
     */
    private $jsonBinaryDecoderFormatter;

    /**
     * JsonBinaryDecoderService constructor.
     * @param BinaryDataReader $binaryDataReader
     * @param JsonBinaryDecoderFormatter $jsonBinaryDecoderFormatter
     */
    public function __construct(
        BinaryDataReader $binaryDataReader,
        JsonBinaryDecoderFormatter $jsonBinaryDecoderFormatter
    ) {
        $this->binaryDataReader = $binaryDataReader;
        $this->jsonBinaryDecoderFormatter = $jsonBinaryDecoderFormatter;
    }

    /**
     * @return string
     * @throws JsonBinaryDecoderException
     * @throws BinaryDataReaderException
     */
    public function parseToString()
    {
        $this->parseJson($this->binaryDataReader->readUInt8());

        return $this->jsonBinaryDecoderFormatter->getJsonString();
    }

    /**
     * @return int
     * @throws BinaryDataReaderException
     */
    private function readVariableInt()
    {
        $length = $this->binaryDataReader->getBinaryDataLength();
        $len = 0;
        for ($i = 0; $i < $length; $i++) {
            $size = $this->binaryDataReader->readUInt8();
            // Get the next 7 bits of the length.
            $len |= ($size & 127) << (7 * $i);
            if (($size & 128) === 0) {
                // This was the last byte. Return successfully.
                return $len;
            }
        }

        return $len;
    }

    /**
     * @param int $type
     * @throws JsonBinaryDecoderException
     * @throws BinaryDataReaderException
     */
    private function parseJson($type)
    {
        if (self::SMALL_OBJECT === $type) {
            $this->parseObject(BinaryDataReader::UNSIGNED_SHORT_LENGTH);
        } else if (self::LARGE_OBJECT === $type) {
            $this->parseObject(BinaryDataReader::UNSIGNED_INT32_LENGTH);
        } else if (self::SMALL_ARRAY === $type) {
            $this->parseArray(BinaryDataReader::UNSIGNED_SHORT_LENGTH);
        } else if (self::LARGE_ARRAY === $type) {
            $this->parseObject(BinaryDataReader::UNSIGNED_INT32_LENGTH);
        } else {
            $this->parseScalar($type);
        }
    }

    private function parseObject($intSize)
    {
        $elementCount = $this->binaryDataReader->readUIntBySize($intSize);
        $size = $this->binaryDataReader->readUIntBySize($intSize);

        // Read each key-entry, consisting of the offset and length of each key ...
        $keyLengths = [];
        for ($i = 0; $i !== $elementCount; ++$i) {
            $this->binaryDataReader->readUIntBySize($intSize); // $keyOffset unused
            $keyLengths[$i] = $this->binaryDataReader->readUInt16();
        }

        $entries = [];
        for ($i = 0; $i !== $elementCount; ++$i) {
            $entries[$i] = $this->parseValueType($size, $intSize);
        }

        // Read each key ...
        $keys = [];
        for ($i = 0; $i !== $elementCount; ++$i) {
            $keys[$i] = $this->binaryDataReader->read($keyLengths[$i]);
        }

        $this->jsonBinaryDecoderFormatter->formatBeginObject();

        for ($i = 0; $i !== $elementCount; ++$i) {
            if ($i !== 0) {
                $this->jsonBinaryDecoderFormatter->formatNextEntry();
            }

            $this->jsonBinaryDecoderFormatter->formatName($keys[$i]);

            /* @var JsonBinaryDecoderValue[] $entries */
            $this->assignValues($entries[$i]);
        }

        $this->jsonBinaryDecoderFormatter->formatEndObject();
    }

    /**
     * @param int $numBytes
     * @param int $intSize
     * @return JsonBinaryDecoderValue
     * @throws BinaryDataReaderException
     * @throws \LengthException
     */
    private function parseValueType($numBytes, $intSize)
    {
        $type = $this->binaryDataReader->readInt8();

        if (self::LITERAL === $type) {
            return new JsonBinaryDecoderValue(
                true,
                $this->readLiteral(),
                $type
            );
        } else if (self::INT16 === $type) {
            return new JsonBinaryDecoderValue(
                true,
                $this->binaryDataReader->readInt16(),
                $type
            );
        } else if (self::UINT16 === $type) {
            return new JsonBinaryDecoderValue(
                true,
                $this->binaryDataReader->readUInt16(),
                $type
            );
        } else if (BinaryDataReader::UNSIGNED_INT32_LENGTH === $intSize) {
            if (self::INT32 === $type) {
                return new JsonBinaryDecoderValue(
                    true,
                    $this->binaryDataReader->readInt32(),
                    $type
                );
            } else if (self::UINT32 === $type) {
                return new JsonBinaryDecoderValue(
                    true,
                    $this->binaryDataReader->readUInt32(),
                    $type
                );
            }
        } else {
            $offset = $this->binaryDataReader->readUIntBySize($intSize);
            if ($offset > $numBytes) {
                throw new \LengthException(
                    'The offset for the value in the JSON binary document is ' .
                    $offset .
                    ', which is larger than the binary form of the JSON document (' .
                    $numBytes . ' bytes)'
                );
            }

            return new JsonBinaryDecoderValue(
                false,
                null,
                $type
            );
        }
    }

    /**
     * @return bool|null
     * @throws BinaryDataReaderException
     */
    private function readLiteral()
    {
        $literal = ord($this->binaryDataReader->read(2));
        if (0 === $literal) {
            return null;
        } else if (1 === $literal) {
            return true;
        } else if (2 === $literal) {
            return false;
        }
    }

    /**
     * @param JsonBinaryDecoderValue $jsonBinaryDecoderValue
     * @throws JsonBinaryDecoderException
     * @throws BinaryDataReaderException
     */
    private function assignValues(JsonBinaryDecoderValue $jsonBinaryDecoderValue)
    {
        if (false === $jsonBinaryDecoderValue->isIsResolved()) {
            $this->parseJson($jsonBinaryDecoderValue->getType());
        } else {
            if (null === $jsonBinaryDecoderValue->getValue()) {
                $this->jsonBinaryDecoderFormatter->formatValueNull();
            } elseif (is_bool($jsonBinaryDecoderValue->getValue())) {
                $this->jsonBinaryDecoderFormatter->formatValueBool($jsonBinaryDecoderValue->getValue());
            } elseif (is_numeric($jsonBinaryDecoderValue->getValue())) {
                $this->jsonBinaryDecoderFormatter->formatValueNumeric($jsonBinaryDecoderValue->getValue());
            }
        }
    }

    private function parseArray($size)
    {
        $numElements = $this->binaryDataReader->readUInt16();
        $numBytes = $this->binaryDataReader->readUInt16();

        $entries = [];
        for ($i = 0; $i !== $numElements; ++$i) {
            $entries[$i] = $this->parseValueType($numBytes, $size);
        }

        $this->jsonBinaryDecoderFormatter->formatBeginArray();

        for ($i = 0; $i !== $numElements; ++$i) {
            if ($i !== 0) {
                $this->jsonBinaryDecoderFormatter->formatNextEntry();
            }

            /* @var JsonBinaryDecoderValue[] $entries */
            $this->assignValues($entries[$i]);
        }

        $this->jsonBinaryDecoderFormatter->formatEndArray();
    }

    private function parseBoolean()
    {
        $r = $this->readLiteral();
        if (null === $r) {
            $this->jsonBinaryDecoderFormatter->formatValueNull();
        } else {
            $this->jsonBinaryDecoderFormatter->formatValueBool($r);
        }
    }

    private function parseScalar($type)
    {
        if (self::LITERAL === $type) {
            $this->parseBoolean();
        } else if (self::INT16 === $type) {
            $this->jsonBinaryDecoderFormatter->formatValue($this->binaryDataReader->readInt16());
        } else if (self::INT32 === $type) {
            $this->jsonBinaryDecoderFormatter->formatValue($this->binaryDataReader->readInt32());
        } else if (self::INT64 === $type) {
            $this->jsonBinaryDecoderFormatter->formatValue($this->binaryDataReader->readInt64());
        } else if (self::UINT16 === $type) {
            $this->jsonBinaryDecoderFormatter->formatValue($this->binaryDataReader->readUInt16());
        } else if (self::UINT64 === $type) {
            $this->jsonBinaryDecoderFormatter->formatValue($this->binaryDataReader->readUInt64());
        } else if (self::DOUBLE === $type) {
            $this->jsonBinaryDecoderFormatter->formatValue($this->binaryDataReader->readDouble());
        } else if (self::STRING === $type) {
            $this->jsonBinaryDecoderFormatter->formatValue(
                $this->binaryDataReader->read($this->readVariableInt())
            );
        } /**
         * else if (self::OPAQUE === $type)
         * {
         *
         * }
         */
        else {
            throw new JsonBinaryDecoderException(
                JsonBinaryDecoderException::UNKNOWN_JSON_TYPE_MESSAGE . $type,
                JsonBinaryDecoderException::UNKNOWN_JSON_TYPE_CODE
            );
        }
    }
}