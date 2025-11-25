<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Syntax\AbstractColumnDefinitionParser;

use function preg_match;
use function preg_replace;
use function strlen;
use function substr;

/**
 * Parses column definition string. For example, `string(255)` or `int unsigned`.
 */
final class ColumnDefinitionParser extends AbstractColumnDefinitionParser
{
    private const TYPE_PATTERN = '/^(?:('
        . 'time(?:stamp)?\s*(?:\((\d+)\))? with(?:out)? time zone'
        . ')|('
        . '(?:character|bit) varying'
        . '|double precision'
        . '|\w*'
        . ')(?:\(([^)]+)\))?)(\[[\d\[\]]*\])?\s*/i';

    protected function parseDefinition(string $definition): array
    {
        preg_match(self::TYPE_PATTERN, $definition, $matches);

        /** @var string $type */
        $type = $matches[3] ?? preg_replace('/\s*\(\d+\)/', '', $matches[1]);

        return [
            $type,
            $matches[4] ?? $matches[2] ?? null,
            $matches[5] ?? null,
            substr($definition, strlen($matches[0])),
        ];
    }

    protected function parseTypeParams(string $type, string $params): array
    {
        return match ($type) {
            'bit varying',
            'bit',
            'bpchar',
            'char',
            'character varying',
            'character',
            'decimal',
            'double precision',
            'float4',
            'float8',
            'int',
            'interval',
            'numeric',
            'real',
            'string',
            'time with time zone',
            'time without time zone',
            'time',
            'timestamp with time zone',
            'timestamp without time zone',
            'timestamp',
            'timestamptz',
            'timetz',
            'varbit',
            'varchar' => $this->parseSizeInfo($params),
            default => [],
        };
    }
}
