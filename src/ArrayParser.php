<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use function in_array;

/**
 * Array representation to PHP array parser for PostgreSQL Server.
 */
final class ArrayParser
{
    /**
     * @var int Current parsing position
     */
    private int $pos = 0;

    /**
     * @param string $value The parse string from PostgresSQL
     */
    public function __construct(
        private string $value,
    ) {
    }

    /**
     * Parses PostgreSQL encoded array.
     */
    public function parse(): array
    {
        if ($this->value[++$this->pos] === '}') {
            ++$this->pos;
            return [];
        }

        for ($result = [];; ++$this->pos) {
            $result[] = match ($this->value[$this->pos]) {
                '{' => $this->parse(),
                ',', '}' => null,
                default => $this->parseString(),
            };

            if ($this->value[$this->pos] === '}') {
                ++$this->pos;
                return $result;
            }
        }
    }

    /**
     * Parses PostgreSQL encoded string.
     */
    private function parseString(): string|null
    {
        return $this->value[$this->pos] === '"'
            ? $this->parseQuotedString()
            : $this->parseUnquotedString();
    }

    /**
     * Parses quoted string.
     *
     * @psalm-suppress LoopInvalidation
     */
    private function parseQuotedString(): string
    {
        for ($result = '', ++$this->pos;; ++$this->pos) {
            if ($this->value[$this->pos] === '\\') {
                ++$this->pos;
            } elseif ($this->value[$this->pos] === '"') {
                ++$this->pos;
                return $result;
            }

            $result .= $this->value[$this->pos];
        }
    }

    /**
     * Parses unquoted string.
     */
    private function parseUnquotedString(): string|null
    {
        for ($result = '';; ++$this->pos) {
            if (in_array($this->value[$this->pos], [',', '}'], true)) {
                return $result !== 'NULL'
                    ? $result
                    : null;
            }

            $result .= $this->value[$this->pos];
        }
    }
}
