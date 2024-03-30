<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Syntax\SqlParser as BaseSqlParser;

final class SqlParser extends BaseSqlParser
{
    public function getNextPlaceholder(int|null &$position = null): string|null
    {
        $result = null;
        $length = $this->length - 1;

        while ($this->position < $length) {
            $pos = $this->position++;

            match ($this->sql[$pos]) {
                ':' => ($word = $this->parseWord()) === ''
                    ? $this->skipChars(':')
                    : $result = ':' . $word,
                '"', "'" => $this->skipQuotedWithoutEscape($this->sql[$pos]),
                'e', 'E' => $this->sql[$this->position] === "'"
                    ? ++$this->position && $this->skipQuotedWithEscape("'")
                    : $this->skipIdentifier(),
                '$' => $this->skipQuotedWithDollar(),
                '-' => $this->sql[$this->position] === '-'
                    ? ++$this->position && $this->skipToAfterChar("\n")
                    : null,
                '/' => $this->sql[$this->position] === '*'
                    ? ++$this->position && $this->skipToAfterString('*/')
                    : null,
                // Identifiers can contain dollar sign which can be used for quoting. Skip them.
                '_','a', 'b', 'c', 'd', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u',
                'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q',
                'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' => $this->skipIdentifier(),
                default => null,
            };

            if ($result !== null) {
                $position = $pos;

                return $result;
            }
        }

        return null;
    }

    /**
     * Parses and returns identifier. Equals to `[_a-zA-Z][$\w]+` in regular expressions.
     *
     * @return string Parsed identifier.
     */
    protected function parseIdentifier(): string
    {
        return match ($this->sql[$this->position]) {
            '_',
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u',
            'v', 'w', 'x', 'y', 'z',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U',
            'V', 'W', 'X', 'Y', 'Z' => $this->sql[$this->position++] . $this->parseWordWithDollar(),
            default => '',
        };
    }

    /**
     * Parses and returns identifier without dollar sign. Equals to `[_a-zA-Z]\w+` in regular expressions.
     *
     * @return string Parsed identifier.
     */
    private function parseIdentifierWithoutDollar(): string
    {
        return parent::parseIdentifier();
    }

    /**
     * Parses and returns word symbols include dollar sign. Equals to `[$\w]+` in regular expressions.
     *
     * @return string Parsed word symbols.
     */
    private function parseWordWithDollar(): string
    {
        $word = '';
        $continue = true;

        while ($continue && $this->position < $this->length) {
            match ($this->sql[$this->position]) {
                '$', '_', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
                'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u',
                'v', 'w', 'x', 'y', 'z',
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U',
                'V', 'W', 'X', 'Y', 'Z' => $word .= $this->sql[$this->position++],
                default => $continue = false,
            };
        }

        return $word;
    }

    /**
     * Skips dollar-quoted string.
     */
    private function skipQuotedWithDollar(): void
    {
        $pos = $this->position;
        $identifier = $this->parseIdentifierWithoutDollar();

        if ($this->sql[$this->position] !== '$') {
            $this->position = $pos;
            return;
        }

        ++$this->position;

        $this->skipToAfterString('$' . $identifier . '$');
    }

    /**
     * Skips an identifier. Equals to `[$\w]+` in regular expressions.
     */
    private function skipIdentifier(): void
    {
        $continue = true;

        while ($continue && $this->position < $this->length) {
            match ($this->sql[$this->position]) {
                '$', '_', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
                'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u',
                'v', 'w', 'x', 'y', 'z',
                'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U',
                'V', 'W', 'X', 'Y', 'Z' => ++$this->position,
                default => $continue = false,
            };
        }
    }
}
