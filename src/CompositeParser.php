<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

/**
 * Composite type representation to PHP array parser for PostgreSQL Server.
 */
class CompositeParser
{
    /**
     * Convert composite type from PostgreSQL to PHP array
     *
     * @param string $value String to convert.
     */
    public function parse(string $value): array|null
    {
        if ($value[0] !== '(') {
            return null;
        }

        return $this->parseComposite($value);
    }

    /**
     * Parse PostgreSQL composite type encoded in string.
     *
     * @param string $value String to parse.
     */
    private function parseComposite(string $value): array
    {
        for ($result = [], $i = 1;; ++$i) {
            $result[] = match ($value[$i]) {
                ',', ')' => null,
                '"' => $this->parseQuotedString($value, $i),
                default => $this->parseUnquotedString($value, $i),
            };

            if ($value[$i] === ')') {
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
    private function parseUnquotedString(string $value, int &$i): string
    {
        for ($result = '';; ++$i) {
            if (in_array($value[$i], [',', ')'], true)) {
                return $result;
            }

            $result .= $value[$i];
        }
    }
}
