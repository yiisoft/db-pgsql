<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Pgsql\RangeParser;
use Yiisoft\Db\Pgsql\Schema;
use DateTime;

final class RangeParserTest extends TestCase
{
    public function testAutoRange(): void
    {
        $rangeParser = new RangeParser();

        $this->assertNull($rangeParser->parse(null));
        $this->assertSame([null, null], $rangeParser->parse('(,)'));
        $this->assertSame([10, 12], $rangeParser->parse('[10,12]'));
        $this->assertSame([10, 12], $rangeParser->parse('(9,13)'));
        $this->assertSame([9.5, (float) 13], $rangeParser->parse('[9.5,13]'));
    }

    public function testIntRange(): void
    {
        $rangeParser = new RangeParser(Schema::TYPE_INT_4_RANGE);

        $this->assertSame([2, 6], $rangeParser->parse('[2,6]'));
        $this->assertSame([2, 6], $rangeParser->parse('(1,6]'));
        $this->assertSame([2, 6], $rangeParser->parse('[2,7)'));
    }

    public function testNumRange(): void
    {
        $rangeParser = new RangeParser(Schema::TYPE_NUM_RANGE);

        $this->assertSame([(float) 2, (float) 6], $rangeParser->parse('[2,6]'));
    }

    public function testDateRange(): void
    {
        $rangeParser = new RangeParser(Schema::TYPE_DATE_RANGE);
        $result = $rangeParser->parse('["2017-10-09",]');
        $includeResult = $rangeParser->parse('("2017-10-09",]');

        $this->assertTrue($result[0] instanceof DateTime);
        $this->assertNull($result[1]);
        $this->assertSame($includeResult[0]->format('Y-m-d'), '2017-10-10');
    }
}
