<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use JsonException;
use function in_array;
use function json_decode;
use function strlen;
use const JSON_THROW_ON_ERROR;

/**
 * The class converts PostgresSQL array representation to PHP array.
 */
final class ArrayParser
{
    /**
     * @var string Character used in array
     */
    private string $delimiter = ',';

    /**
     * @var string|null cast array values to php type
     */
    private ?string $typeCast = null;

    /**
     * Convert array from PostgresSQL to PHP
     *
     * @param string|null $value string to be converted
     *
     * @return array|null
     */
    public function parse(?string $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if ($value === '{}') {
            return [];
        }

        return $this->parseArray($value);
    }

    public function withTypeCast(?string $typeCast): self
    {
        $new = clone $this;
        $new->typeCast = $typeCast;

        return $new;
    }

    /**
     * Pares PgSQL array encoded in string.
     *
     * @param string $value
     * @param int $i parse starting position.
     *
     * @return array
     * @throws JsonException
     */
    private function parseArray(string $value, int &$i = 0): array
    {
        $result = [];
        $len = strlen($value);

        for (++$i; $i < $len; ++$i) {
            switch ($value[$i]) {
                case '{':
                    $result[] = $this->parseArray($value, $i);
                    break;
                case '}':
                    break 2;
                case $this->delimiter:
                    /** `{}` case */
                    if (empty($result)) {
                        $result[] = null;
                    }

                    /** `{,}` case */
                    if (in_array($value[$i + 1], [$this->delimiter, '}'], true)) {
                        $result[] = null;
                    }
                    break;
                default:
                    /** @var mixed */
                    $result[] = $this->parseString($value, $i);
            }
        }

        return $result;
    }

    /**
     * Parses PgSQL encoded string.
     *
     * @param string $value
     * @param int $i parse starting position.
     *
     * @return mixed
     * @throws JsonException
     */
    private function parseString(string $value, int &$i): mixed
    {
        $isQuoted = $value[$i] === '"';
        $stringEndChars = $isQuoted ? ['"'] : [$this->delimiter, '}'];
        $result = '';
        $len = strlen($value);

        for ($i += $isQuoted ? 1 : 0; $i < $len; ++$i) {
            if (in_array($value[$i], ['\\', '"'], true) && in_array($value[$i + 1], [$value[$i], '"'], true)) {
                ++$i;
            } elseif (in_array($value[$i], $stringEndChars, true)) {
                break;
            }

            $result .= $value[$i];
        }

        $i -= $isQuoted ? 0 : 1;

        if (!$isQuoted && $result === 'NULL') {
            return null;
        }

        return match ($this->typeCast) {
            Schema::PHP_TYPE_INTEGER => (int) $result,
            Schema::PHP_TYPE_DOUBLE => (float) $result,
            Schema::PHP_TYPE_ARRAY => json_decode($result, true, 512, JSON_THROW_ON_ERROR),
            Schema::PHP_TYPE_BOOLEAN => ColumnSchema::castBooleanValue($result),
            default => $result,
        };
    }
}
