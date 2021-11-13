<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Pgsql\RangeParser;
use Yiisoft\Db\Pgsql\Schema;
use DateInterval;
use DateTime;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Expression\Expression;

/**
 * @group rangeParser
 */
final class RangeParserTest extends TestCase
{
    private const TABLE = '{{ranges}}';

    private const FIELDS = [
        Schema::TYPE_INT_4_RANGE => 'int_range',
        Schema::TYPE_INT_8_RANGE => 'bigint_range',
        Schema::TYPE_NUM_RANGE => 'num_range',
        Schema::TYPE_DATE_RANGE => 'date_range',
        Schema::TYPE_TS_RANGE => 'ts_range',
        Schema::TYPE_TS_TZ_RANGE => 'ts_tz_range',
    ];

    /**
     * @param string $type
     * @param string $field
     * @param string $brackets
     * @param array $inserted
     * @return mixed[]
     */
    public function insertAndGetResult(string $field, string $type, string $brackets, array $inserted): array
    {
        $db = $this->getConnection();
        $db->createCommand()->delete(self::TABLE)->execute();

        $db->createCommand()->insert(
            self::TABLE,
            [
                $field => new Expression(
                    $type . "(:min, :max, '" . $brackets . "')",
                    [':min' => $inserted[0], ':max' => $inserted[1]]
                )
            ]
        )->execute();

        return (new Query($db))->select([$field])->from(self::TABLE)->one();
    }

    /**
     * @return array
     */
    public function rangeNumberProvider(): array
    {
        return [
            // ::int4range
            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '[]', 'inserted' => [1, 10], 'expected' => [1, 10], ],
            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '[]', 'inserted' => [null, 10], 'expected' => [null, 10], ],
            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '[]', 'inserted' => [10, null], 'expected' => [10, null], ],
            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '[]', 'inserted' => [-100, 10], 'expected' => [-100, 10], ],

            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '[)', 'inserted' => [1, 10], 'expected' => [1, 9], ],
            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '[)', 'inserted' => [null, 10], 'expected' => [null, 9], ],
            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '[)', 'inserted' => [10, null], 'expected' => [10, null], ],
            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '[)', 'inserted' => [-100, 10], 'expected' => [-100, 9], ],

            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '(]', 'inserted' => [1, 10], 'expected' => [2, 10], ],
            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '(]', 'inserted' => [null, 10], 'expected' => [null, 10], ],
            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '(]', 'inserted' => [10, null], 'expected' => [11, null], ],
            [ Schema::TYPE_INT_4_RANGE, 'brackets' => '(]', 'inserted' => [-100, 10], 'expected' => [-99, 10], ],

            // ::int8range
            [ Schema::TYPE_INT_8_RANGE, '[]', ['2147483647', '2147483650'], ['2147483647', '2147483650'], ],
            [ Schema::TYPE_INT_8_RANGE, '[]', ['4147483647', null], ['4147483647', null], ],
            [ Schema::TYPE_INT_8_RANGE, '[]', [null, '2147483650'], [null, '2147483650'], ],
            [ Schema::TYPE_INT_8_RANGE, '[]', ['-2147483650', '2147483650'], ['-2147483650', '2147483650'], ],

            [ Schema::TYPE_INT_8_RANGE, '[)', ['2147483647', '2147483650'], ['2147483647', '2147483649'], ],
            [ Schema::TYPE_INT_8_RANGE, '[)', ['4147483647', null], ['4147483647', null], ],
            [ Schema::TYPE_INT_8_RANGE, '[)', [null, '2147483650'], [null, '2147483649'], ],
            [ Schema::TYPE_INT_8_RANGE, '[)', ['-2147483650', '2147483650'], ['-2147483650', '2147483649'], ],

            [ Schema::TYPE_INT_8_RANGE, '(]', ['2147483647', '2147483650'], ['2147483648', '2147483650'], ],
            [ Schema::TYPE_INT_8_RANGE, '(]', ['4147483647', null], ['4147483648', null], ],
            [ Schema::TYPE_INT_8_RANGE, '(]', [null, '2147483650'], [null, '2147483650'], ],
            [ Schema::TYPE_INT_8_RANGE, '(]', ['-2147483650', '2147483650'], ['-2147483649', '2147483650'], ],

            // ::numrange
            [ Schema::TYPE_NUM_RANGE, '[]', [10.5, 20.7], [10.5, 20.7], ],
            [ Schema::TYPE_NUM_RANGE, '[]', [30.7, null], [30.7, null], ],
            [ Schema::TYPE_NUM_RANGE, '[]', [null, 20.7], [null, 20.7], ],
            [ Schema::TYPE_NUM_RANGE, '[]', [-13.2, 20.7], [-13.2, 20.7], ],

            [ Schema::TYPE_NUM_RANGE, '[)', [10.5, 20.7], [10.5, 20.7], ],
            [ Schema::TYPE_NUM_RANGE, '(]', [10.5, 20.7], [10.5, 20.7], ],
            [ Schema::TYPE_NUM_RANGE, '()', [10.5, 20.7], [10.5, 20.7], ],
        ];
    }

    /**
     * @dataProvider rangeNumberProvider
     */
    public function testNumberRanges(string $type, string $brackets, array $inserted, array $expected): void
    {
        $field = self::FIELDS[$type];
        $row = $this->insertAndGetResult($field, $type, $brackets, $inserted);

        $parser = new RangeParser($type);
        $result = $parser->parse($row[$field]);

        $this->assertEquals($result, $expected);
    }

    /**
     * @return array[]
     */
    public function rangeDateProvider(): array
    {
        return [
            // ::daterange
            [ Schema::TYPE_DATE_RANGE, '[]', ['2020-12-01', '2021-01-01'], ['2020-12-01', '2021-01-01'], 'Y-m-d', ],
            [ Schema::TYPE_DATE_RANGE, '[]', ['2020-12-01', null], ['2020-12-01', null], 'Y-m-d', ],
            [ Schema::TYPE_DATE_RANGE, '[]', [null, '2020-12-01'], [null, '2020-12-01'], 'Y-m-d', ],

            [ Schema::TYPE_DATE_RANGE, '[)', ['2020-12-01', '2021-01-01'], ['2020-12-01', '2020-12-31'], 'Y-m-d', ],
            [ Schema::TYPE_DATE_RANGE, '[)', ['2020-12-01', null], ['2020-12-01', null], 'Y-m-d', ],
            [ Schema::TYPE_DATE_RANGE, '[)', [null, '2020-12-01'], [null, '2020-11-30'], 'Y-m-d',],

            [ Schema::TYPE_DATE_RANGE, '(]', ['2020-12-01', '2021-01-01'], ['2020-12-02', '2021-01-01'], 'Y-m-d', ],
            [ Schema::TYPE_DATE_RANGE, '(]', ['2020-12-01', null], ['2020-12-02', null], 'Y-m-d', ],
            [ Schema::TYPE_DATE_RANGE, '(]', [null, '2020-12-01'], [null, '2020-12-01'], 'Y-m-d', ],

            // ::tsrange
            [ Schema::TYPE_TS_RANGE, '[]', ['2017-10-20 10:10:00', '2018-10-20 15:10:00'], ['2017-10-20 10:10:00', '2018-10-20 15:10:00'], 'Y-m-d H:i:s', ],
            [ Schema::TYPE_TS_RANGE, '[]', ['2017-10-20 10:10:00', null], ['2017-10-20 10:10:00', null], 'Y-m-d H:i:s', ],
            [ Schema::TYPE_TS_RANGE, '[]', [null, '2018-10-20 15:10:00'], [null, '2018-10-20 15:10:00'], 'Y-m-d H:i:s', ],

            [ Schema::TYPE_TS_RANGE, '[)', ['2017-10-20 10:10:00', '2018-10-20 15:10:00'], ['2017-10-20 10:10:00', '2018-10-20 15:10:00'], 'Y-m-d H:i:s', ],
            [ Schema::TYPE_TS_RANGE, '(]', ['2017-10-20 10:10:00', '2018-10-20 15:10:00'], ['2017-10-20 10:10:00', '2018-10-20 15:10:00'], 'Y-m-d H:i:s', ],
            [ Schema::TYPE_TS_RANGE, '()', ['2017-10-20 10:10:00', '2018-10-20 15:10:00'], ['2017-10-20 10:10:00', '2018-10-20 15:10:00'], 'Y-m-d H:i:s', ],
            [ Schema::TYPE_TS_RANGE, '()', ['2017-10-20 10:10:00', '2018-10-20 15:10:00'], ['2017-10-20 10:10:00', '2018-10-20 15:10:00'], 'Y-m-d H:i:s', ],

            // ::tstzrange
            [ Schema::TYPE_TS_TZ_RANGE, '[]', ['2018-10-20 10:10:00+00:00', '2019-10-20 15:10:00+00:00'], ['2018-10-20 10:10:00+00:00', '2019-10-20 15:10:00+00:00'], 'Y-m-d H:i:sP', ],
            [ Schema::TYPE_TS_TZ_RANGE, '[]', ['2018-10-20 10:10:00+00:00', null], ['2018-10-20 10:10:00+00:00', null], 'Y-m-d H:i:sP', ],
            [ Schema::TYPE_TS_TZ_RANGE, '[]', [null, '2019-10-20 15:10:00+00:00'], [null, '2019-10-20 15:10:00+00:00'], 'Y-m-d H:i:sP', ],

            [ Schema::TYPE_TS_TZ_RANGE, '(]', ['2018-10-20 10:10:00+00:00', '2019-10-20 15:10:00+00:00'], ['2018-10-20 10:10:00+00:00', '2019-10-20 15:10:00+00:00'], 'Y-m-d H:i:sP', ],
            [ Schema::TYPE_TS_TZ_RANGE, '[)', ['2018-10-20 10:10:00+00:00', '2019-10-20 15:10:00+00:00'], ['2018-10-20 10:10:00+00:00', '2019-10-20 15:10:00+00:00'], 'Y-m-d H:i:sP', ],
            [ Schema::TYPE_TS_TZ_RANGE, '()', ['2018-10-20 10:10:00+00:00', '2019-10-20 15:10:00+00:00'], ['2018-10-20 10:10:00+00:00', '2019-10-20 15:10:00+00:00'], 'Y-m-d H:i:sP', ],
        ];
    }

    /**
     * @dataProvider rangeDateProvider
     */
    public function testDateRanges(string $type, string $brackets, array $inserted, array $expected, string $format): void
    {
        $field = self::FIELDS[$type];
        $row = $this->insertAndGetResult($field, $type, $brackets, $inserted);

        $parser = new RangeParser($type);
        $result = $parser->parse($row[$field]);

        $this->assertIsString($row[$field]);
        $this->assertEquals($expected, [
            $result[0] instanceof DateTime ? $result[0]->format($format) : null,
            $result[1] instanceof DateTime ? $result[1]->format($format) : null,
        ]);
    }
}
