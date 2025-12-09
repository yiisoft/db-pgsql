<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Data\ArrayParser;

/**
 * @group pgsql
 */
final class ArrayParserTest extends TestCase
{
    public static function parserProvider(): iterable
    {
        yield [[], '{}'];
        yield [[''], '{""}'];
        yield [[null, null], '{NULL,NULL}'];
        yield [[null, null], '{,}'];
        yield [
            ["a\nb"],
            "{\"a\nb\"}",
        ];
        yield [
            ['1', '2', '3'],
            '{1,2,3}',
        ];
        yield [
            ['1', '-2', null, '42'],
            '{1,-2,NULL,42}',
        ];
        yield [
            [['text'], [null], ['1']],
            '{{text},{NULL},{1}}',
        ];
        yield [
            [',', '}', '"', '\\', '"\\,}', 'NULL', 't', 'f'],
            '{",","}","\\"","\\\\","\\"\\\\,}","NULL",t,f}',
        ];
        yield [
            ['[",","null",true,"false","f"]'],
            '{"[\",\",\"null\",true,\"false\",\"f\"]"}',
        ];
        // Multibyte strings
        yield [
            ['我', '👍🏻', 'multibyte строка我👍🏻', 'נטשופ צרכנות'],
            '{我,👍🏻,"multibyte строка我👍🏻","נטשופ צרכנות"}',
        ];
        // Similar cases can be in default values
        yield [null, "'{one,two}'::text[]"];
    }

    #[DataProvider('parserProvider')]
    public function testParser(?array $expected, string $value): void
    {
        $arrayParse = new ArrayParser();
        $this->assertSame($expected, $arrayParse->parse($value));
    }
}
