<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\Composite\CompositeParser;

/**
 * @group pgsql
 */
final class CompositeParserTest extends TestCase
{
    public function testParser(): void
    {
        $compositeParser = new CompositeParser();

        $this->assertSame([null], $compositeParser->parse('()'));
        $this->assertSame([0 => null, 1 => null], $compositeParser->parse('(,)'));
        $this->assertSame([0 => '10.0', 1 => 'USD'], $compositeParser->parse('(10.0,USD)'));
        $this->assertSame([0 => '1', 1 => '-2', 2 => null, 3 => '42'], $compositeParser->parse('(1,-2,,42)'));
        $this->assertSame([0 => ''], $compositeParser->parse('("")'));
        $this->assertSame(
            [0 => '[",","null",true,"false","f"]'],
            $compositeParser->parse('("[\",\",\"null\",true,\"false\",\"f\"]")')
        );

        // Default values can have any expressions
        $this->assertSame(null, $compositeParser->parse("'(10.0,USD)::composite_type'"));
    }
}
