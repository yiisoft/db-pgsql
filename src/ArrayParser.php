<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use function in_array;
use function strlen;

/**
 * Array representation to PHP array parser for PostgreSQL Server.
 */
final class ArrayParser
{
    /**
     * @var string Character used in an array.
     */
    private string $delimiter = ',';

    /**
     * Convert an array from PostgresSQL to PHP.
     *
     * @param string|null $value String to be converted.
     */
    public function parse(string|null $value): array|null
    {
        if ($value === null) {
            return null;
        }

        if ($value === '{}') {
            return [];
        }

        return $this->parseArray($value);
    }

    /**
     * Parse PostgreSQL array encoded in string.
     *
     * @param string $value String to be parsed.
     * @param int $i parse starting position.
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
                    $result[] = $this->parseString($value, $i);
            }
        }

        return $result;
    }

    /**
     * Parses PostgreSQL encoded string.
     *
     * @param string $value String to be parsed.
     * @param int $i Parse starting position.
     */
    private function parseString(string $value, int &$i): string|null
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
            $result = null;
        }

        return $result;
    }
}
