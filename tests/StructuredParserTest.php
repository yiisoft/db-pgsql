<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Data\StructuredParser;

/**
 * @group pgsql
 */
final class StructuredParserTest extends TestCase
{
    public static function parserProvider(): iterable
    {
        yield [[null], '()'];
        yield [[''], '("")'];
        yield [[null, null], '(,)'];
        yield [
            ["a\nb"],
            "(\"a\nb\")",
        ];
        yield [
            ['10.0', 'USD'],
            '(10.0,USD)',
        ];
        yield [
            ['1', '-2', null, '42'],
            '(1,-2,,42)',
        ];
        yield [
            [',', ')', '"', '\\', '"\\,)', 'NULL', 't', 'f'],
            '(",",")","\\"","\\\\","\\"\\\\,)",NULL,t,f)',
        ];
        yield [
            ['[",","null",true,"false","f"]'],
            '("[\",\",\"null\",true,\"false\",\"f\"]")',
        ];
        // Multibyte strings
        yield [
            ['我', '👍🏻', 'multibyte строка我👍🏻', 'נטשופ צרכנות'],
            '(我,👍🏻,"multibyte строка我👍🏻","נטשופ צרכנות")',
        ];
        // Default values can have any expressions
        yield [null, "'(10.0,USD)::structured_type'"];
    }

    #[DataProvider('parserProvider')]
    public function testParser(?array $expected, string $value): void
    {
        $parser = new StructuredParser();
        $this->assertSame($expected, $parser->parse($value));
    }
}
