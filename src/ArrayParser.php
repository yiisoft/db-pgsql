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
     * @var int Length of the parse string
     */
    private int $length;

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
        $this->length = strlen($value);
    }

    /**
     * Parses PostgreSQL encoded array.
     *
     * @throws ArrayParserException
     */
    public function parse(): array
    {
        if ($this->value[++$this->pos] === '}') {
            ++$this->pos;
            return [];
        }

        for ($result = []; $this->pos < $this->length; ++$this->pos) {
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

        throw new ArrayParserException('Expected closing brace <}>');
    }

    /**
     * Parses PostgreSQL encoded string.
     *
     * @throws ArrayParserException
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
     * @throws ArrayParserException
     * @psalm-suppress LoopInvalidation
     */
    private function parseQuotedString(): string
    {
        for ($result = '', ++$this->pos; $this->pos < $this->length; ++$this->pos) {
            if ($this->value[$this->pos] === '\\') {
                ++$this->pos;
            } elseif ($this->value[$this->pos] === '"') {
                ++$this->pos;
                return $result;
            }

            $result .= $this->value[$this->pos];
        }

        throw new ArrayParserException('Expected double quote <">');
    }

    /**
     * Parses unquoted string.
     *
     * @throws ArrayParserException
     * @psalm-suppress PossiblyNullArrayAccess
     */
    private function parseUnquotedString(): string|null
    {
        for ($result = ''; $this->pos < $this->length; ++$this->pos) {
            if (in_array($this->value[$this->pos], [',', '}'], true)) {
                return $result !== 'NULL'
                    ? $result
                    : null;
            }

            $result .= $this->value[$this->pos];
        }

        throw new ArrayParserException('Expected comma <,> or closing brace <}>');
    }
}
