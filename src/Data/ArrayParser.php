<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Data;

use function in_array;

/**
 * Array representation to PHP array parser for PostgreSQL Server.
 */
final class ArrayParser
{
    /**
     * Convert an array from PostgresSQL to PHP.
     *
     * @param string $value String to parse.
     *
     * @return (array|string|null)[]|null Parsed value.
     *
     * @psalm-return list<array|string|null>|null
     */
    public function parse(string $value): array|null
    {
        return $value[0] === '{'
            ? $this->parseArray($value)
            : null;
    }

    /**
     * Parse PostgreSQL array encoded in string.
     *
     * @param string $value String to parse.
     * @param int $i parse starting position.
     *
     * @return (array|string|null)[] Parsed value.
     *
     * @psalm-return list<array|string|null>
     */
    private function parseArray(string $value, int &$i = 0): array
    {
        if ($value[++$i] === '}') {
            ++$i;
            return [];
        }

        for ($result = [];; ++$i) {
            $result[] = match ($value[$i]) {
                '{' => $this->parseArray($value, $i),
                ',', '}' => null,
                '"' => $this->parseQuotedString($value, $i),
                default => $this->parseUnquotedString($value, $i),
            };

            if ($value[$i] === '}') {
                ++$i;
                return $result;
            }
        }
    }

    /**
     * Parses quoted string.
     */
    private function parseQuotedString(string $value, int &$i): string
    {
        for ($result = '', ++$i;; ++$i) {
            if ($value[$i] === '\\') {
                ++$i;
            } elseif ($value[$i] === '"') {
                ++$i;
                return $result;
            }

            $result .= $value[$i];
        }
    }

    /**
     * Parses unquoted string.
     */
    private function parseUnquotedString(string $value, int &$i): string|null
    {
        for ($result = '';; ++$i) {
            if (in_array($value[$i], [',', '}'], true)) {
                return $result !== 'NULL'
                    ? $result
                    : null;
            }

            $result .= $value[$i];
        }
    }
}
