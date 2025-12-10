<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Data;

use function preg_match;
use function strcspn;
use function stripslashes;
use function strlen;
use function substr;

/**
 * Structured type representation to PHP array parser for PostgreSQL Server.
 */
final class StructuredParser
{
    /**
     * Converts structured (composite) type value from PostgreSQL to PHP array.
     *
     * @param string $value Value to parse.
     *
     * @return (string|null)[]|null Parsed value.
     *
     * @psalm-return non-empty-list<null|string>|null
     */
    public function parse(string $value): ?array
    {
        if ($value[0] !== '(') {
            return null;
        }

        return $this->parseComposite($value);
    }

    /**
     * Parses PostgreSQL composite type value encoded in string.
     *
     * @param string $value String to parse.
     *
     * @return (string|null)[] Parsed value.
     *
     * @psalm-return non-empty-list<null|string>
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
        preg_match('/(?>[^"\\\\]+|\\\\.)*/', $value, $matches, 0, $i + 1);
        $i += strlen($matches[0]) + 2;

        return stripslashes($matches[0]);
    }

    /**
     * Parses unquoted string.
     */
    private function parseUnquotedString(string $value, int &$i): string
    {
        $length = strcspn($value, ',)', $i);
        $result = substr($value, $i, $length);
        $i += $length;

        return $result;
    }
}
