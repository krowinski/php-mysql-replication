<?php

declare(strict_types=1);

namespace MySQLReplication\JsonBinaryDecoder;

use InvalidArgumentException;
use LengthException;
use MySQLReplication\BinaryDataReader\BinaryDataReader;

/**
 * @see https://github.com/mysql/mysql-server/blob/5.7/sql/json_binary.cc
 * @see https://github.com/mysql/mysql-server/blob/8.0/sql/json_binary.cc
 * @see https://github.com/shyiko/mysql-binlog-connector-java/blob/master/src/main/java/com/github/shyiko/mysql/binlog/event/deserialization/json/JsonBinary.java
 */
readonly class JsonBinaryDecoderService
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
    //public const OPAQUE = 15;

    public const LITERAL_NULL = 0;

    public const LITERAL_TRUE = 1;

    public const LITERAL_FALSE = 2;

    public const SMALL_OFFSET_SIZE = 2;

    public const LARGE_OFFSET_SIZE = 4;

    public const KEY_ENTRY_SIZE_SMALL = 2 + self::SMALL_OFFSET_SIZE;

    public const KEY_ENTRY_SIZE_LARGE = 2 + self::LARGE_OFFSET_SIZE;

    public const VALUE_ENTRY_SIZE_SMALL = 1 + self::SMALL_OFFSET_SIZE;

    public const VALUE_ENTRY_SIZE_LARGE = 1 + self::LARGE_OFFSET_SIZE;

    public const OBJECT = 1;

    public const ARRAY = 2;

    public const SCALAR = 3;

    private int $dataLength;

    public function __construct(
        private BinaryDataReader $binaryDataReader,
        private JsonBinaryDecoderFormatter $jsonBinaryDecoderFormatter
    ) {
        $this->dataLength = $this->binaryDataReader->getBinaryDataLength();
    }

    public static function makeJsonBinaryDecoder(string $data): self
    {
        return new self(new BinaryDataReader($data), new JsonBinaryDecoderFormatter());
    }

    public function parseToString(): string
    {
        // Sometimes, we can insert a NULL JSON even we set the JSON field as NOT NULL.
        // If we meet this case, we can return a 'null' value.
        if ($this->binaryDataReader->getBinaryDataLength() === 0) {
            return 'null';
        }
        $this->parseJson($this->binaryDataReader->readUInt8());

        return $this->jsonBinaryDecoderFormatter->getJsonString();
    }

    private function parseJson(int $type): void
    {
        $results = [];
        if ($type === self::SMALL_OBJECT) {
            $results[self::OBJECT] = $this->parseArrayOrObject(self::OBJECT, self::SMALL_OFFSET_SIZE);
        } elseif ($type === self::LARGE_OBJECT) {
            $results[self::OBJECT] = $this->parseArrayOrObject(self::OBJECT, self::LARGE_OFFSET_SIZE);
        } elseif ($type === self::SMALL_ARRAY) {
            $results[self::ARRAY] = $this->parseArrayOrObject(self::ARRAY, self::SMALL_OFFSET_SIZE);
        } elseif ($type === self::LARGE_ARRAY) {
            $results[self::ARRAY] = $this->parseArrayOrObject(self::ARRAY, self::LARGE_OFFSET_SIZE);
        } else {
            $results[self::SCALAR][] = [
                'name' => null,
                'value' => $this->parseScalar($type),
            ];
        }

        $this->parseToJson($results);
    }

    private function parseToJson(array $results): void
    {
        foreach ($results as $dataType => $entities) {
            if ($dataType === self::OBJECT) {
                $this->jsonBinaryDecoderFormatter->formatBeginObject();
            } elseif ($dataType === self::ARRAY) {
                $this->jsonBinaryDecoderFormatter->formatBeginArray();
            }

            foreach ($entities as $i => $entity) {
                if ($dataType === self::SCALAR) {
                    if ($entity['value']->value === null) {
                        $this->jsonBinaryDecoderFormatter->formatValue('null');
                    } elseif (is_bool($entity['value']->value)) {
                        $this->jsonBinaryDecoderFormatter->formatValueBool($entity['value']->value);
                    } else {
                        $this->jsonBinaryDecoderFormatter->formatValue($entity['value']->value);
                    }
                    continue;
                }

                if ($i !== 0) {
                    $this->jsonBinaryDecoderFormatter->formatNextEntry();
                }

                if ($entity['name'] !== null) {
                    $this->jsonBinaryDecoderFormatter->formatName($entity['name']);
                }
                $this->assignValues($entity['value']);
            }

            if ($dataType === self::OBJECT) {
                $this->jsonBinaryDecoderFormatter->formatEndObject();
            } elseif ($dataType === self::ARRAY) {
                $this->jsonBinaryDecoderFormatter->formatEndArray();
            }
        }
    }

    private function parseArrayOrObject(int $type, int $intSize): array
    {
        $large = $intSize === self::LARGE_OFFSET_SIZE;
        $offsetSize = self::offsetSize($large);
        if ($this->dataLength < 2 * $offsetSize) {
            throw new InvalidArgumentException('Document is not long enough to contain the two length fields');
        }

        $elementCount = $this->binaryDataReader->readUIntBySize($intSize);
        $bytes = $this->binaryDataReader->readUIntBySize($intSize);

        if ($bytes > $this->dataLength) {
            throw new InvalidArgumentException(
                'The value can\'t have more bytes than what\'s available in the data buffer.'
            );
        }

        $keyEntrySize = self::keyEntrySize($large);
        $valueEntrySize = self::valueEntrySize($large);

        $headerSize = 2 * $offsetSize;

        if ($type === self::OBJECT) {
            $headerSize += $elementCount * $keyEntrySize;
        }
        $headerSize += $elementCount * $valueEntrySize;

        if ($headerSize > $bytes) {
            throw new InvalidArgumentException('Header is larger than the full size of the value.');
        }

        $keyLengths = [];
        if ($type === self::OBJECT) {
            // Read each key-entry, consisting of the offset and length of each key ...
            for ($i = 0; $i !== $elementCount; ++$i) {
                $keyOffset = $this->binaryDataReader->readUIntBySize($intSize);
                $keyLengths[$i] = $this->binaryDataReader->readUInt16();
                if ($keyOffset < $headerSize) {
                    throw new InvalidArgumentException('Invalid key offset');
                }
            }
        }

        $entries = [];
        for ($i = 0; $i !== $elementCount; ++$i) {
            $entries[$i] = $this->getOffsetOrInLinedValue($bytes, $intSize, $valueEntrySize);
        }

        $keys = [];
        if ($type === self::OBJECT) {
            for ($i = 0; $i !== $elementCount; ++$i) {
                $keys[$i] = $this->binaryDataReader->read($keyLengths[$i]);
            }
        }

        $results = [];
        for ($i = 0; $i !== $elementCount; ++$i) {
            $results[] = [
                'name' => $keys[$i] ?? null,
                'value' => $entries[$i],
            ];
        }

        return $results;
    }

    private static function offsetSize(bool $large): int
    {
        return $large ? self::LARGE_OFFSET_SIZE : self::SMALL_OFFSET_SIZE;
    }

    private static function keyEntrySize(bool $large): int
    {
        return $large ? self::KEY_ENTRY_SIZE_LARGE : self::KEY_ENTRY_SIZE_SMALL;
    }

    private static function valueEntrySize(bool $large): int
    {
        return $large ? self::VALUE_ENTRY_SIZE_LARGE : self::VALUE_ENTRY_SIZE_SMALL;
    }

    private function getOffsetOrInLinedValue(int $bytes, int $intSize, int $valueEntrySize): JsonBinaryDecoderValue
    {
        $type = $this->binaryDataReader->readUInt8();

        if (self::isInLinedType($type, $intSize)) {
            $scalar = $this->parseScalar($type);

            // In binlog format, JSON arrays are fixed width elements, even though type value can be smaller.
            // In order to properly process this case, we need to move cursor to the next element, which is on position 1 + $valueEntrySize (1 is length of type)
            if ($type === self::UINT16 || $type === self::INT16) {
                $readNextBytes = $valueEntrySize - 2 - 1;
                $this->binaryDataReader->read($readNextBytes);
            }

            return $scalar;
        }

        $offset = $this->binaryDataReader->readUIntBySize($intSize);
        if ($offset > $bytes) {
            throw new LengthException(
                'The offset for the value in the JSON binary document is ' . $offset . ', which is larger than the binary form of the JSON document (' . $bytes . ' bytes)'
            );
        }

        return new JsonBinaryDecoderValue(false, null, $type, $offset);
    }

    private static function isInLinedType(int $type, int $intSize): bool
    {
        return match ($type) {
            self::LITERAL, self::INT16, self::UINT16 => true,
            self::INT32, self::UINT32 => $intSize === self::LARGE_OFFSET_SIZE,
            default => false,
        };
    }

    private function parseScalar(int $type): JsonBinaryDecoderValue
    {
        if ($type === self::LITERAL) {
            $data = $this->readLiteral();
        } elseif ($type === self::INT16) {
            $data = $this->binaryDataReader->readInt16();
        } elseif ($type === self::INT32) {
            $data = ($this->binaryDataReader->readInt32());
        } elseif ($type === self::INT64) {
            $data = $this->binaryDataReader->readInt64();
        } elseif ($type === self::UINT16) {
            $data = ($this->binaryDataReader->readUInt16());
        } elseif ($type === self::UINT64) {
            $data = ($this->binaryDataReader->readUInt64());
        } elseif ($type === self::DOUBLE) {
            $data = ($this->binaryDataReader->readDouble());
        } elseif ($type === self::STRING) {
            $data = ($this->binaryDataReader->read($this->readVariableInt()));
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

        return new JsonBinaryDecoderValue(true, $data, $type);
    }

    private function readLiteral(): ?bool
    {
        $literal = ord($this->binaryDataReader->read(BinaryDataReader::UNSIGNED_SHORT_LENGTH));
        if ($literal === self::LITERAL_NULL) {
            return null;
        }
        if ($literal === self::LITERAL_TRUE) {
            return true;
        }
        if ($literal === self::LITERAL_FALSE) {
            return false;
        }

        return null;
    }

    private function readVariableInt(): int
    {
        $maxBytes = min($this->binaryDataReader->getBinaryDataLength(), 5);
        $len = 0;
        for ($i = 0; $i < $maxBytes; ++$i) {
            $size = $this->binaryDataReader->readUInt8();
            // Get the next 7 bits of the length.
            $len |= ($size & 0x7f) << (7 * $i);
            if (($size & 0x80) === 0) {
                // This was the last byte. Return successfully.
                return $len;
            }
        }

        return $len;
    }

    private function assignValues(JsonBinaryDecoderValue $jsonBinaryDecoderValue): void
    {
        if ($jsonBinaryDecoderValue->isResolved === false) {
            $this->ensureOffset($jsonBinaryDecoderValue->offset);
            $this->parseJson($jsonBinaryDecoderValue->type);
        } elseif ($jsonBinaryDecoderValue->value === null) {
            $this->jsonBinaryDecoderFormatter->formatValueNull();
        } elseif (is_bool($jsonBinaryDecoderValue->value)) {
            $this->jsonBinaryDecoderFormatter->formatValueBool($jsonBinaryDecoderValue->value);
        } elseif (is_numeric($jsonBinaryDecoderValue->value)) {
            $this->jsonBinaryDecoderFormatter->formatValueNumeric($jsonBinaryDecoderValue->value);
        }
    }

    private function ensureOffset(?int $ensureOffset): void
    {
        if ($ensureOffset === null) {
            return;
        }
        $pos = $this->binaryDataReader->getReadBytes();
        if ($pos !== $ensureOffset) {
            if ($ensureOffset < $pos) {
                return;
            }
            $this->binaryDataReader->advance($ensureOffset + 1 - $pos);
        }
    }
}
