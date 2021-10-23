<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\RangeParser;
use Yiisoft\Db\Pgsql\Schema;
use DateTime;
use DateInterval;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Expression\Expression;

final class RangeParserTest extends TestCase
{
    private const RANGES = [
        1 => [
            "int_range" => [1, 10],
            "bigint_range" => ["2147483647", "2147483650"],
            "num_range" => [10.5, 20.7],
            "ts_range" => ["2017-10-20 10:10:00", "2018-10-20 15:10:00"],
            "ts_tz_range" => ["2018-10-20 10:10:00+00:00", "2019-10-20 15:10:00+00:00"],
            "date_range" => ["2020-12-01", "2021-01-01"]
        ],
        2 => [
            "int_range" => [100, null],
            "bigint_range" => ["4147483647", null],
            "num_range" => [30.7, null],
            "ts_range" => ["2017-10-20 10:10:00", null],
            "ts_tz_range" => ["2018-10-20 10:10:00+00:00", null],
            "date_range" => ["2020-12-01", null]
        ],
        3 => [
            "int_range" => [null, 10],
            "bigint_range" => [null, "2147483650"],
            "num_range" => [null, 20.7],
            "ts_range" => [null, "2018-10-20 15:10:00"],
            "ts_tz_range" => [null, "2019-10-20 15:10:00+00:00"],
            "date_range" => [null, "2021-01-01"]
        ],
    ];

    public function testSimpleRanges(): void
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach (self::RANGES as $id => $values) {
            $data = ['id' => $id];

            foreach ($values as $column => $val) {
                switch ($column) {
                    case 'int_range':
                        $data[$column] = new Expression(Schema::TYPE_INT_4_RANGE . "(:min_int, :max_int, '[]')", [':min_int' => $val[0], ':max_int' => $val[1]]);
                        break;
                    case 'bigint_range':
                        $data[$column] = new Expression(Schema::TYPE_INT_8_RANGE . "(:min_bigint, :max_bigint, '[]')", [':min_bigint' => $val[0], ':max_bigint' => $val[1]]);
                        break;
                    case 'num_range':
                        $data[$column] = new Expression(Schema::TYPE_NUM_RANGE . "(:min_num, :max_num, '[]')", [':min_num' => $val[0], ':max_num' => $val[1]]);
                        break;
                    case 'ts_range':
                        $data[$column] = new Expression(Schema::TYPE_TS_RANGE . "(:min_ts, :max_ts, '[]')", [':min_ts' => $val[0], ':max_ts' => $val[1]]);
                        break;
                    case 'ts_tz_range':
                        $data[$column] = new Expression(Schema::TYPE_TS_TZ_RANGE . "(:min_tstz, :max_tstz, '[]')", [':min_tstz' => $val[0], ':max_tstz' => $val[1]]);
                        break;
                    case 'date_range':
                        $data[$column] = new Expression(Schema::TYPE_DATE_RANGE . "(:min_date, :max_date, '[]')", [':min_date' => $val[0], ':max_date' => $val[1]]);
                        break;
                }
            }

            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->from('ranges')->all();

        foreach ($rows as $row) {
            $id = $row['id'];


            foreach ($row as $column => $value) {
                $range =  self::RANGES[$id][$column] ?? null;

                switch ($column) {
                    case 'int_range':
                        $parser = new RangeParser(Schema::TYPE_INT_4_RANGE);
                        $result = $parser->parse($value);
                        $this->assertTrue(is_string($value));
                        $this->assertSame($result, $range);
                        break;
                    case 'bigint_range':
                        $parser = new RangeParser(Schema::TYPE_INT_8_RANGE);
                        $result = $parser->parse($value);


                        if (PHP_INT_SIZE === 4) {
                            $min = $range[0] === null ? null : (float) $range[0];
                            $max = $range[1] === null ? null : (float) $range[1];
                        } else {
                            $min = $range[0] === null ? null : (int) $range[0];
                            $max = $range[1] === null ? null : (int) $range[1];
                        }

                        $this->assertTrue(is_string($value));
                        $this->assertSame($result, [$min, $max]);

                        break;
                    case 'num_range':
                        $parser = new RangeParser(Schema::TYPE_NUM_RANGE);
                        $result = $parser->parse($value);
                        $this->assertTrue(is_string($value));
                        $this->assertSame($result, $range);

                        break;
                    case 'ts_range':
                        $parser = new RangeParser(Schema::TYPE_TS_RANGE);
                        $result = $parser->parse($value);
                        $min = $result[0] === null ? null : $result[0]->format('Y-m-d H:i:s');
                        $max = $result[1] === null ? null : $result[1]->format('Y-m-d H:i:s');

                        $this->assertTrue(is_string($value));
                        $this->assertSame($range, [$min, $max]);

                        break;
                    case 'ts_tz_range':
                        $parser = new RangeParser(Schema::TYPE_TS_TZ_RANGE);
                        $result = $parser->parse($value);
                        $min = $result[0] === null ? null : $result[0]->format('Y-m-d H:i:sP');
                        $max = $result[1] === null ? null : $result[1]->format('Y-m-d H:i:sP');

                        $this->assertTrue(is_string($value));
                        $this->assertSame($range, [$min, $max]);

                        break;
                    case 'date_range':
                        $parser = new RangeParser(Schema::TYPE_DATE_RANGE);
                        $result = $parser->parse($value);
                        $min = $result[0] === null ? null : $result[0]->format('Y-m-d');
                        $max = $result[1] === null ? null : $result[1]->format('Y-m-d');

                        $this->assertTrue(is_string($value));
                        $this->assertSame($range, [$min, $max]);

                        break;
                }
            }
        }
    }

    public function testLowerRanges()
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach (self::RANGES as $id => $values) {
            $data = ['id' => $id];

            foreach ($values as $column => $val) {
                switch ($column) {
                    case 'int_range':
                        $data[$column] = new Expression(Schema::TYPE_INT_4_RANGE . "(:min_int, :max_int, '(]')", [':min_int' => $val[0], ':max_int' => $val[1]]);
                        break;
                    case 'bigint_range':
                        $data[$column] = new Expression(Schema::TYPE_INT_8_RANGE . "(:min_bigint, :max_bigint, '(]')", [':min_bigint' => $val[0], ':max_bigint' => $val[1]]);
                        break;
                    case 'num_range':
                        $data[$column] = new Expression(Schema::TYPE_NUM_RANGE . "(:min_num, :max_num, '(]')", [':min_num' => $val[0], ':max_num' => $val[1]]);
                        break;
                    case 'ts_range':
                        $data[$column] = new Expression(Schema::TYPE_TS_RANGE . "(:min_ts, :max_ts, '(]')", [':min_ts' => $val[0], ':max_ts' => $val[1]]);
                        break;
                    case 'ts_tz_range':
                        $data[$column] = new Expression(Schema::TYPE_TS_TZ_RANGE . "(:min_tstz, :max_tstz, '(]')", [':min_tstz' => $val[0], ':max_tstz' => $val[1]]);
                        break;
                    case 'date_range':
                        $data[$column] = new Expression(Schema::TYPE_DATE_RANGE . "(:min_date, :max_date, '(]')", [':min_date' => $val[0], ':max_date' => $val[1]]);
                        break;
                }
            }

            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->from('ranges')->all();

        foreach ($rows as $row) {
            $id = $row['id'];


            foreach ($row as $column => $value) {
                $range =  self::RANGES[$id][$column] ?? null;

                switch ($column) {
                    case 'int_range':
                        $parser = new RangeParser(Schema::TYPE_INT_4_RANGE);
                        $result = $parser->parse($value);
                        $min = $range[0] === null ? null : $range[0] + 1;
                        $max = $range[1] === null ? null : $range[1];

                        $this->assertSame($result, [$min, $max]);
                        break;
                    case 'bigint_range':
                        $parser = new RangeParser(Schema::TYPE_INT_8_RANGE);
                        $result = $parser->parse($value);

                        if (PHP_INT_SIZE === 4) {
                            $min = $range[0] === null ? null : (float) $range[0] + 1;
                            $max = $range[1] === null ? null : (float) $range[1];
                        } else {
                            $min = $range[0] === null ? null : (int) $range[0] + 1;
                            $max = $range[1] === null ? null : (int) $range[1];
                        }

                        $this->assertSame($result, [$min, $max]);

                        break;
                    case 'num_range':
                        $parser = new RangeParser(Schema::TYPE_NUM_RANGE);
                        $result = $parser->parse($value);
                        $this->assertTrue(is_string($value));
                        $this->assertSame($result, $range);

                        break;
                    case 'ts_range':
                        $parser = new RangeParser(Schema::TYPE_TS_RANGE);
                        $result = $parser->parse($value);
                        $min = $result[0] === null ? null : $result[0]->format('Y-m-d H:i:s');
                        $max = $result[1] === null ? null : $result[1]->format('Y-m-d H:i:s');

                        $this->assertSame($range, [$min, $max]);

                        break;
                    case 'ts_tz_range':
                        $parser = new RangeParser(Schema::TYPE_TS_TZ_RANGE);
                        $result = $parser->parse($value);
                        $min = $result[0] === null ? null : $result[0]->format('Y-m-d H:i:sP');
                        $max = $result[1] === null ? null : $result[1]->format('Y-m-d H:i:sP');

                        $this->assertSame($range, [$min, $max]);

                        break;
                    case 'date_range':
                        $interval = new DateInterval('P1D');
                        $parser = new RangeParser(Schema::TYPE_DATE_RANGE);
                        $result = $parser->parse($value);
                        $min = $result[0] === null ? null : $result[0]->sub($interval)->format('Y-m-d');
                        $max = $result[1] === null ? null : $result[1]->format('Y-m-d');

                        $this->assertSame($range, [$min, $max]);

                        break;
                }
            }
        }
    }

    public function testUpperRanges()
    {
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach (self::RANGES as $id => $values) {
            $data = ['id' => $id];

            foreach ($values as $column => $val) {
                switch ($column) {
                    case 'int_range':
                        $data[$column] = new Expression(Schema::TYPE_INT_4_RANGE . "(:min_int, :max_int, '[)')", [':min_int' => $val[0], ':max_int' => $val[1]]);
                        break;
                    case 'bigint_range':
                        $data[$column] = new Expression(Schema::TYPE_INT_8_RANGE . "(:min_bigint, :max_bigint, '[)')", [':min_bigint' => $val[0], ':max_bigint' => $val[1]]);
                        break;
                    case 'num_range':
                        $data[$column] = new Expression(Schema::TYPE_NUM_RANGE . "(:min_num, :max_num, '[)')", [':min_num' => $val[0], ':max_num' => $val[1]]);
                        break;
                    case 'ts_range':
                        $data[$column] = new Expression(Schema::TYPE_TS_RANGE . "(:min_ts, :max_ts, '[)')", [':min_ts' => $val[0], ':max_ts' => $val[1]]);
                        break;
                    case 'ts_tz_range':
                        $data[$column] = new Expression(Schema::TYPE_TS_TZ_RANGE . "(:min_tstz, :max_tstz, '[)')", [':min_tstz' => $val[0], ':max_tstz' => $val[1]]);
                        break;
                    case 'date_range':
                        $data[$column] = new Expression(Schema::TYPE_DATE_RANGE . "(:min_date, :max_date, '[)')", [':min_date' => $val[0], ':max_date' => $val[1]]);
                        break;
                }
            }

            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->from('ranges')->all();

        foreach ($rows as $row) {
            $id = $row['id'];


            foreach ($row as $column => $value) {
                $range =  self::RANGES[$id][$column] ?? null;

                switch ($column) {
                    case 'int_range':
                        $parser = new RangeParser(Schema::TYPE_INT_4_RANGE);
                        $result = $parser->parse($value);
                        $min = $range[0] === null ? null : $range[0];
                        $max = $range[1] === null ? null : $range[1] - 1;

                        $this->assertSame($result, [$min, $max]);
                        break;
                    case 'bigint_range':
                        $parser = new RangeParser(Schema::TYPE_INT_8_RANGE);
                        $result = $parser->parse($value);

                        if (PHP_INT_SIZE === 4) {
                            $min = $range[0] === null ? null : (float) $range[0];
                            $max = $range[1] === null ? null : (float) $range[1] - 1;
                        } else {
                            $min = $range[0] === null ? null : (int) $range[0];
                            $max = $range[1] === null ? null : (int) $range[1] - 1;
                        }

                        $this->assertSame($result, [$min, $max]);

                        break;
                    case 'num_range':
                        $parser = new RangeParser(Schema::TYPE_NUM_RANGE);
                        $result = $parser->parse($value);
                        $this->assertTrue(is_string($value));
                        $this->assertSame($result, $range);

                        break;
                    case 'ts_range':
                        $parser = new RangeParser(Schema::TYPE_TS_RANGE);
                        $result = $parser->parse($value);
                        $min = $result[0] === null ? null : $result[0]->format('Y-m-d H:i:s');
                        $max = $result[1] === null ? null : $result[1]->format('Y-m-d H:i:s');

                        $this->assertSame($range, [$min, $max]);

                        break;
                    case 'ts_tz_range':
                        $parser = new RangeParser(Schema::TYPE_TS_TZ_RANGE);
                        $result = $parser->parse($value);
                        $min = $result[0] === null ? null : $result[0]->format('Y-m-d H:i:sP');
                        $max = $result[1] === null ? null : $result[1]->format('Y-m-d H:i:sP');

                        $this->assertSame($range, [$min, $max]);

                        break;
                    case 'date_range':
                        $interval = new DateInterval('P1D');
                        $parser = new RangeParser(Schema::TYPE_DATE_RANGE);
                        $result = $parser->parse($value);
                        $min = $result[0] === null ? null : $result[0]->format('Y-m-d');
                        $max = $result[1] === null ? null : $result[1]->add($interval)->format('Y-m-d');

                        $this->assertSame($range, [$min, $max]);

                        break;
                }
            }
        }
    }
}
