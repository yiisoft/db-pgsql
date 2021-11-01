<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\RangeParser;
use Yiisoft\Db\Pgsql\Schema;
use DateInterval;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Expression\Expression;

final class RangeParserTest extends TestCase
{
    private const RANGES = [
        1 => [
            'int_range' => [1, 10],
            'bigint_range' => ['2147483647', '2147483650'],
            'num_range' => [10.5, 20.7],
            'ts_range' => ['2017-10-20 10:10:00', '2018-10-20 15:10:00'],
            'ts_tz_range' => ['2018-10-20 10:10:00+00:00', '2019-10-20 15:10:00+00:00'],
            'date_range' => ['2020-12-01', '2021-01-01'],
        ],
        2 => [
            'int_range' => [100, null],
            'bigint_range' => ['4147483647', null],
            'num_range' => [30.7, null],
            'ts_range' => ['2017-10-20 10:10:00', null],
            'ts_tz_range' => ['2018-10-20 10:10:00+00:00', null],
            'date_range' => ['2020-12-01', null],
        ],
        3 => [
            'int_range' => [null, 10],
            'bigint_range' => [null, '2147483650'],
            'num_range' => [null, 20.7],
            'ts_range' => [null, '2018-10-20 15:10:00'],
            'ts_tz_range' => [null, '2019-10-20 15:10:00+00:00'],
            'date_range' => [null, '2021-01-01'],
        ],
        4 => [
            'int_range' => [-100, 10],
            'bigint_range' => ['-2147483650', '2147483650'],
            'num_range' => [-13.2, 20.7],
            'ts_range' => [null, '2018-10-20 15:10:00'],
            'ts_tz_range' => [null, '2019-10-20 15:10:00+00:00'],
            'date_range' => [null, '2021-01-01'],
        ]
    ];

    private function getData(int $id, bool $exludeLower = false, bool $excludeUpper = false, string ...$columns): array
    {
        $data = ['id' => $id];

        foreach ($columns as $column) {
            $range = self::RANGES[$id][$column];

            switch ($column) {
                case 'int_range':
                    $expression = Schema::TYPE_INT_4_RANGE;
                    break;
                case 'bigint_range':
                    $expression = Schema::TYPE_INT_8_RANGE;
                    break;
                case 'num_range':
                    $expression = Schema::TYPE_NUM_RANGE;
                    break;
                case 'date_range':
                    $expression = Schema::TYPE_DATE_RANGE;
                    break;
                case 'ts_range':
                    $expression = Schema::TYPE_TS_RANGE;
                    break;
                case 'ts_tz_range':
                    $expression = Schema::TYPE_TS_TZ_RANGE;
                    break;
            }

            $expression .= '(';
            $expression .= ':min_' . $column . ',';
            $expression .= ':max_' . $column . ',';
            $expression .= "'";
            $expression .= $exludeLower ? '(' : '[';
            $expression .= $excludeUpper ? ')' : ']';
            $expression .= "'";
            $expression .= ')';

            $data[$column] = new Expression($expression, [':min_' . $column => $range[0], ':max_' . $column => $range[1]]);
        }

        return $data;
    }

    public function testIntRanges(): void
    {
        $ids = array_keys(self::RANGES);
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach ($ids as $id) {
            $data = $this->getData($id, false, false, 'int_range', 'bigint_range');
            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->select(['id', 'int_range', 'bigint_range'])->from('ranges')->all();
        $intParser = new RangeParser(Schema::TYPE_INT_4_RANGE);
        $bigIntParser = new RangeParser(Schema::TYPE_INT_8_RANGE);

        foreach ($rows as $row) {
            $range = self::RANGES[$row['id']];
            $this->assertIsString($row['int_range']);
            $this->assertIsString($row['bigint_range']);
            $this->assertSame($intParser->parse($row['int_range']), $range['int_range']);

            if (PHP_INT_SIZE === 8) {
                $map = array_map(fn ($value) => $value ? (int) $value : null, $range['bigint_range']);
            } else {
                $map = array_map(fn ($value) => $value ? (float) $value : null, $range['bigint_range']);
            }

            $this->assertSame($intParser->parse($row['bigint_range']), $map);
        }
    }

    public function testIntRangesWoLower(): void
    {
        $ids = array_keys(self::RANGES);
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach ($ids as $id) {
            $data = $this->getData($id, true, false, 'int_range', 'bigint_range');
            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->select(['id', 'int_range', 'bigint_range'])->from('ranges')->all();
        $intParser = new RangeParser(Schema::TYPE_INT_4_RANGE);
        $bigIntParser = new RangeParser(Schema::TYPE_INT_8_RANGE);

        foreach ($rows as $row) {
            $range = self::RANGES[$row['id']];
            $this->assertIsString($row['int_range']);
            $this->assertIsString($row['bigint_range']);
            $min = $range['int_range'][0] === null ? null : $range['int_range'][0] + 1;
            $max = $range['int_range'][1] === null ? null : $range['int_range'][1];
            $this->assertSame($intParser->parse($row['int_range']), [$min, $max]);

            if (PHP_INT_SIZE === 8) {
                $map = array_map(fn ($value) => $value ? (int) $value : null, $range['bigint_range']);
            } else {
                $map = array_map(fn ($value) => $value ? (float) $value : null, $range['bigint_range']);
            }

            $min = $map[0] === null ? null : $map[0] + 1;
            $max = $map[1] === null ? null : $map[1];

            $this->assertSame($intParser->parse($row['bigint_range']), [$min, $max]);
        }
    }

    public function testIntRangesWoUpper(): void
    {
        $ids = array_keys(self::RANGES);
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach ($ids as $id) {
            $data = $this->getData($id, false, true, 'int_range', 'bigint_range');
            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->select(['id', 'int_range', 'bigint_range'])->from('ranges')->all();
        $intParser = new RangeParser(Schema::TYPE_INT_4_RANGE);
        $bigIntParser = new RangeParser(Schema::TYPE_INT_8_RANGE);

        foreach ($rows as $row) {
            $range = self::RANGES[$row['id']];
            $this->assertIsString($row['int_range']);
            $this->assertIsString($row['bigint_range']);
            $min = $range['int_range'][0] === null ? null : $range['int_range'][0];
            $max = $range['int_range'][1] === null ? null : $range['int_range'][1] - 1;
            $this->assertSame($intParser->parse($row['int_range']), [$min, $max]);

            if (PHP_INT_SIZE === 8) {
                $map = array_map(fn ($value) => $value ? (int) $value : null, $range['bigint_range']);
            } else {
                $map = array_map(fn ($value) => $value ? (float) $value : null, $range['bigint_range']);
            }

            $min = $map[0] === null ? null : $map[0];
            $max = $map[1] === null ? null : $map[1] - 1;

            $this->assertSame($intParser->parse($row['bigint_range']), [$min, $max]);
        }
    }

    public function testDateRange(): void
    {
        $ids = array_keys(self::RANGES);
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach ($ids as $id) {
            $data = $this->getData($id, false, false, 'date_range', );
            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->select(['id', 'date_range'])->from('ranges')->all();
        $parser = new RangeParser(Schema::TYPE_DATE_RANGE);

        foreach ($rows as $row) {
            $range = self::RANGES[$row['id']]['date_range'];
            $result = $parser->parse($row['date_range']);
            $min = $result[0] === null ? null : $result[0]->format('Y-m-d');
            $max = $result[1] === null ? null : $result[1]->format('Y-m-d');

            $this->assertIsString($row['date_range']);
            $this->assertSame($range, [$min, $max]);
        }
    }

    public function testDateRangeWoLower(): void
    {
        $ids = array_keys(self::RANGES);
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach ($ids as $id) {
            $data = $this->getData($id, true, false, 'date_range', );
            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->select(['id', 'date_range'])->from('ranges')->all();
        $parser = new RangeParser(Schema::TYPE_DATE_RANGE);
        $interval = new DateInterval('P1D');

        foreach ($rows as $row) {
            $range = self::RANGES[$row['id']]['date_range'];
            $result = $parser->parse($row['date_range']);
            $min = $result[0] === null ? null : $result[0]->sub($interval)->format('Y-m-d');
            $max = $result[1] === null ? null : $result[1]->format('Y-m-d');

            $this->assertIsString($row['date_range']);
            $this->assertSame($range, [$min, $max]);
        }
    }

    public function testDateRangeWoUpper(): void
    {
        $ids = array_keys(self::RANGES);
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach ($ids as $id) {
            $data = $this->getData($id, false, true, 'date_range', );
            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->select(['id', 'date_range'])->from('ranges')->all();
        $parser = new RangeParser(Schema::TYPE_DATE_RANGE);
        $interval = new DateInterval('P1D');

        foreach ($rows as $row) {
            $range = self::RANGES[$row['id']]['date_range'];
            $result = $parser->parse($row['date_range']);
            $min = $result[0] === null ? null : $result[0]->format('Y-m-d');
            $max = $result[1] === null ? null : $result[1]->add($interval)->format('Y-m-d');

            $this->assertIsString($row['date_range']);
            $this->assertSame($range, [$min, $max]);
        }
    }

    public function testNumRange(): void
    {
        $ids = array_keys(self::RANGES);
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach ($ids as $id) {
            $data = $this->getData($id, false, false, 'num_range');
            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->select(['id', 'num_range'])->from('ranges')->all();
        $parser = new RangeParser(Schema::TYPE_NUM_RANGE);

        foreach ($rows as $row) {
            $range = self::RANGES[$row['id']];
            $this->assertIsString($row['num_range']);
            $this->assertSame($parser->parse($row['num_range']), $range['num_range']);
        }
    }

    public function testTsRange(): void
    {
        $ids = array_keys(self::RANGES);
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach ($ids as $id) {
            $data = $this->getData($id, false, false, 'ts_range');
            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->select(['id', 'ts_range'])->from('ranges')->all();
        $parser = new RangeParser(Schema::TYPE_TS_RANGE);

        foreach ($rows as $row) {
            $range = self::RANGES[$row['id']];
            $result = $parser->parse($row['ts_range']);
            $min = $result[0] === null ? null : $result[0]->format('Y-m-d H:i:s');
            $max = $result[1] === null ? null : $result[1]->format('Y-m-d H:i:s');
            $this->assertIsString($row['ts_range']);
            $this->assertSame([$min, $max], $range['ts_range']);
        }
    }

    public function testTsTzRange(): void
    {
        $ids = array_keys(self::RANGES);
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        foreach ($ids as $id) {
            $data = $this->getData($id, false, false, 'ts_tz_range');
            $command->insert('ranges', $data)->execute();
        }

        $rows = (new Query($db))->select(['id', 'ts_tz_range'])->from('ranges')->all();
        $parser = new RangeParser(Schema::TYPE_TS_TZ_RANGE);

        foreach ($rows as $row) {
            $range = self::RANGES[$row['id']];
            $result = $parser->parse($row['ts_tz_range']);
            $min = $result[0] === null ? null : $result[0]->format('Y-m-d H:i:sP');
            $max = $result[1] === null ? null : $result[1]->format('Y-m-d H:i:sP');
            $this->assertIsString($row['ts_tz_range']);
            $this->assertSame([$min, $max], $range['ts_tz_range']);
        }
    }
}
