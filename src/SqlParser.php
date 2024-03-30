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
                    : null,
                '$' => $this->skipQuotedWithDollar(),
                '-' => $this->sql[$this->position] === '-'
                    ? ++$this->position && $this->skipToAfterChar("\n")
                    : null,
                '/' => $this->sql[$this->position] === '*'
                    ? ++$this->position && $this->skipToAfterString('*/')
                    : null,
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
     * Skips dollar-quoted string.
     */
    private function skipQuotedWithDollar(): void
    {
        $pos = $this->position;
        $identifier = $this->parseIdentifier();

        if ($this->sql[$this->position] !== '$') {
            $this->position = $pos;
            return;
        }

        ++$this->position;

        $this->skipToAfterString('$' . $identifier . '$');
    }
}
