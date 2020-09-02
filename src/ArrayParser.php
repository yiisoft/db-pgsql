<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use function in_array;
use function strlen;

final class ArrayParser
{
    /**
     * @var string Character used in array
     */
    private string $delimiter = ',';

    /**
     * Convert array from PostgreSQL to PHP
     *
     * @param string $value string to be converted
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

    /**
     * Pares PgSQL array encoded in string.
     *
     * @param string $value
     * @param int $i parse starting position.
     *
     * @return array
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
                    /* `{}` case */
                    if (empty($result)) {
                        $result[] = null;
                    }

                    /* `{,}` case */
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
     * Parses PgSQL encoded string.
     *
     * @param string $value
     * @param int $i parse starting position.
     *
     * @return string|null
     */
    private function parseString(string $value, int &$i): ?string
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
