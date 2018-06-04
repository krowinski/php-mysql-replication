<?php
declare(strict_types=1);

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
    public const SMALL_OBJECT = 0;
    public const LARGE_OBJECT = 1;
    public const SMALL_ARRAY = 2;
    public const LARGE_ARRAY = 3;
    public const LITERAL = 4;
    public const INT16 = 5;
    public const UINT16 = 6;
    public const INT32 = 7;
    public const UINT32 = 8;
    public const INT64 = 9;
    public const UINT64 = 10;
    public const DOUBLE = 11;
    public const STRING = 12;
    public const OPAQUE = 15;

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
     * @throws \LengthException
     * @throws JsonBinaryDecoderException
     * @throws BinaryDataReaderException
     */
    public function parseToString(): string
    {
        $this->parseJson($this->binaryDataReader->readUInt8());

        return $this->jsonBinaryDecoderFormatter->getJsonString();
    }

    /**
     * @param int $type
     * @throws \LengthException
     * @throws JsonBinaryDecoderException
     * @throws BinaryDataReaderException
     */
    private function parseJson(int $type): void
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

    /**
     * @param int $intSize
     * @throws \LengthException
     * @throws BinaryDataReaderException
     * @throws JsonBinaryDecoderException
     */
    private function parseObject(int $intSize): void
    {
        $elementCount = $this->binaryDataReader->readUIntBySize($intSize);
        $size = $this->binaryDataReader->readUIntBySize($intSize);

        // Read each key-entry, consisting of the offset and length of each key ...
        $keyLengths = [];
        for ($i = 0; $i !== $elementCount; ++$i) {
            // $keyOffset unused
            $this->binaryDataReader->readUIntBySize($intSize);
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
    private function parseValueType(int $numBytes, int $intSize): JsonBinaryDecoderValue
    {
        $type = $this->binaryDataReader->readInt8();

        if (self::LITERAL === $type) {
            return new JsonBinaryDecoderValue(
                true,
                $this->readLiteral(),
                $type
            );
        }

        if (self::INT16 === $type) {
            return new JsonBinaryDecoderValue(
                true,
                $this->binaryDataReader->readInt16(),
                $type
            );
        }

        if (self::UINT16 === $type) {
            return new JsonBinaryDecoderValue(
                true,
                $this->binaryDataReader->readUInt16(),
                $type
            );
        }

        if (BinaryDataReader::UNSIGNED_INT32_LENGTH === $intSize) {
            if (self::INT32 === $type) {
                return new JsonBinaryDecoderValue(
                    true,
                    $this->binaryDataReader->readInt32(),
                    $type
                );
            }

            if (self::UINT32 === $type) {
                return new JsonBinaryDecoderValue(
                    true,
                    $this->binaryDataReader->readUInt32(),
                    $type
                );
            }
        }

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

    /**
     * @return bool|null
     */
    private function readLiteral(): ?bool
    {
        $literal = \ord($this->binaryDataReader->read(BinaryDataReader::UNSIGNED_SHORT_LENGTH));
        if (0 === $literal) {
            return null;
        }
        if (1 === $literal) {
            return true;
        }
        if (2 === $literal) {
            return false;
        }

        return null;
    }

    /**
     * @param JsonBinaryDecoderValue $jsonBinaryDecoderValue
     * @throws \LengthException
     * @throws JsonBinaryDecoderException
     * @throws BinaryDataReaderException
     */
    private function assignValues(JsonBinaryDecoderValue $jsonBinaryDecoderValue): void
    {
        if (false === $jsonBinaryDecoderValue->isIsResolved()) {
            $this->parseJson($jsonBinaryDecoderValue->getType());
        } else {
            if (null === $jsonBinaryDecoderValue->getValue()) {
                $this->jsonBinaryDecoderFormatter->formatValueNull();
            } else if (\is_bool($jsonBinaryDecoderValue->getValue())) {
                $this->jsonBinaryDecoderFormatter->formatValueBool($jsonBinaryDecoderValue->getValue());
            } else if (is_numeric($jsonBinaryDecoderValue->getValue())) {
                $this->jsonBinaryDecoderFormatter->formatValueNumeric($jsonBinaryDecoderValue->getValue());
            }
        }
    }

    /**
     * @param int $size
     * @throws \LengthException
     * @throws BinaryDataReaderException
     * @throws JsonBinaryDecoderException
     */
    private function parseArray(int $size): void
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

    /**
     * @param int $type
     * @throws JsonBinaryDecoderException
     */
    private function parseScalar(int $type): void
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
            $this->jsonBinaryDecoderFormatter->formatValue($this->binaryDataReader->read($this->readVariableInt()));
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

    private function parseBoolean(): void
    {
        $r = $this->readLiteral();
        if (null === $r) {
            $this->jsonBinaryDecoderFormatter->formatValue('null');
        } else {
            $this->jsonBinaryDecoderFormatter->formatValueBool($r);
        }
    }

    /**
     * @return int
     */
    private function readVariableInt(): int
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
}