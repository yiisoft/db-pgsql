<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Pgsql\Column\ColumnBuilder;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;

use function array_replace;
use function version_compare;

final class QueryBuilderProvider extends \Yiisoft\Db\Tests\Provider\QueryBuilderProvider
{
    use TestTrait;

    protected static string $driverName = 'pgsql';

    public static function alterColumn(): array
    {
        return [
            ['SET NOT null', 'ALTER TABLE "foo1" ALTER COLUMN "bar" SET NOT null'],
            ['drop default', 'ALTER TABLE "foo1" ALTER COLUMN "bar" drop default'],
            ['reset xyz', 'ALTER TABLE "foo1" ALTER COLUMN "bar" reset xyz'],
            ['string', 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255)'],
            ['varchar(255)', 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255)'],
            ['string NOT NULL', 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET NOT NULL'],
            ['string NULL', 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" DROP NOT NULL'],
            ['string DEFAULT NULL', 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT NULL'],
            ["string DEFAULT ''", 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT \'\''],
            ['timestamp(0) DEFAULT now()', 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE timestamp(0), ALTER COLUMN "bar" SET DEFAULT now()'],
            ['string CHECK (char_length(bar) > 5)', 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ADD CONSTRAINT foo1_bar_check CHECK (char_length(bar) > 5)'],
            ['string(30) UNIQUE', 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(30), ADD UNIQUE ("bar")'],
            ['varchar(255) USING bar::varchar', 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255) USING bar::varchar'],
            ['varchar(255) using cast("bar" as varchar)', 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255) using cast("bar" as varchar)'],
            [ColumnBuilder::string(), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255)'],
            [ColumnBuilder::string()->notNull(), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET NOT NULL'],
            [ColumnBuilder::string()->null(), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" DROP NOT NULL'],
            [ColumnBuilder::string()->defaultValue(null), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT NULL'],
            [ColumnBuilder::string()->defaultValue(''), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT \'\''],
            [ColumnBuilder::string()->null()->defaultValue('xxx'), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ALTER COLUMN "bar" SET DEFAULT \'xxx\', ALTER COLUMN "bar" DROP NOT NULL'],
            [ColumnBuilder::timestamp()->defaultValue(new Expression('now()')), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE timestamp(0), ALTER COLUMN "bar" SET DEFAULT now()'],
            [ColumnBuilder::string()->check('char_length(bar) > 5'), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255), ADD CONSTRAINT foo1_bar_check CHECK (char_length(bar) > 5)'],
            [ColumnBuilder::string(30)->unique(), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(30), ADD UNIQUE ("bar")'],
            [ColumnBuilder::string()->extra('USING bar::varchar'), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255) USING bar::varchar'],
            [ColumnBuilder::string()->extra('using cast("bar" as varchar)'), 'ALTER TABLE "foo1" ALTER COLUMN "bar" TYPE varchar(255) using cast("bar" as varchar)'],
        ];
    }

    public static function buildCondition(): array
    {
        return [
            ...parent::buildCondition(),
            /**
            * adding conditions for ILIKE i.e. case insensitive LIKE.
            *
            * {@see https://www.postgresql.org/docs/8.3/static/functions-matching.html#FUNCTIONS-LIKE}
            */
            /* empty values */
            [['ilike', 'name', []], '0=1', []],
            [['not ilike', 'name', []], '', []],
            [['or ilike', 'name', []], '0=1', []],
            [['or not ilike', 'name', []], '', []],

            /* simple ilike */
            [['ilike', 'name', 'heyho'], '"name" ILIKE :qp0', [':qp0' => new Param('%heyho%', DataType::STRING)]],
            [['not ilike', 'name', 'heyho'], '"name" NOT ILIKE :qp0', [':qp0' => new Param('%heyho%', DataType::STRING)]],
            [['or ilike', 'name', 'heyho'], '"name" ILIKE :qp0', [':qp0' => new Param('%heyho%', DataType::STRING)]],
            [['or not ilike', 'name', 'heyho'], '"name" NOT ILIKE :qp0', [':qp0' => new Param('%heyho%', DataType::STRING)]],

            /* ilike for many values */
            [
                ['ilike', 'name', ['heyho', 'abc']],
                '"name" ILIKE :qp0 AND "name" ILIKE :qp1',
                [':qp0' => new Param('%heyho%', DataType::STRING), ':qp1' => new Param('%abc%', DataType::STRING)],
            ],
            [
                ['not ilike', 'name', ['heyho', 'abc']],
                '"name" NOT ILIKE :qp0 AND "name" NOT ILIKE :qp1',
                [':qp0' => new Param('%heyho%', DataType::STRING), ':qp1' => new Param('%abc%', DataType::STRING)],
            ],
            [
                ['or ilike', 'name', ['heyho', 'abc']],
                '"name" ILIKE :qp0 OR "name" ILIKE :qp1', [':qp0' => new Param('%heyho%', DataType::STRING), ':qp1' => new Param('%abc%', DataType::STRING)],
            ],
            [
                ['or not ilike', 'name', ['heyho', 'abc']],
                '"name" NOT ILIKE :qp0 OR "name" NOT ILIKE :qp1',
                [':qp0' => new Param('%heyho%', DataType::STRING), ':qp1' => new Param('%abc%', DataType::STRING)],
            ],

            /* Checks to verity that operators work correctly */
            [['@>', 'id', new ArrayExpression([1])], '"id" @> ARRAY[:qp0]', [':qp0' => 1]],
            [['<@', 'id', new ArrayExpression([1])], '"id" <@ ARRAY[:qp0]', [':qp0' => 1]],
            [['=', 'id',  new ArrayExpression([1])], '"id" = ARRAY[:qp0]', [':qp0' => 1]],
            [['<>', 'id', new ArrayExpression([1])], '"id" <> ARRAY[:qp0]', [':qp0' => 1]],
            [['>', 'id',  new ArrayExpression([1])], '"id" > ARRAY[:qp0]', [':qp0' => 1]],
            [['<', 'id',  new ArrayExpression([1])], '"id" < ARRAY[:qp0]', [':qp0' => 1]],
            [['>=', 'id', new ArrayExpression([1])], '"id" >= ARRAY[:qp0]', [':qp0' => 1]],
            [['<=', 'id', new ArrayExpression([1])], '"id" <= ARRAY[:qp0]', [':qp0' => 1]],
            [['&&', 'id', new ArrayExpression([1])], '"id" && ARRAY[:qp0]', [':qp0' => 1]],
        ];
    }

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO "customer" DEFAULT VALUES
        SQL;

        return $insert;
    }

    public static function insertWithReturningPks(): array
    {
        return [
            'regular-values' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'silverfire',
                    'address' => 'Kyiv {{city}}, Ukraine',
                    'is_active' => false,
                    'related_id' => null,
                ],
                [],
                <<<SQL
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4) RETURNING "id"
                SQL,
                [
                    ':qp0' => 'test@example.com',
                    ':qp1' => 'silverfire',
                    ':qp2' => 'Kyiv {{city}}, Ukraine',
                    ':qp3' => false,
                    ':qp4' => null,
                ],
            ],
            'params-and-expressions' => [
                '{{%type}}',
                ['{{%type}}.[[related_id]]' => null, '[[time]]' => new Expression('now()')],
                [],
                <<<SQL
                INSERT INTO {{%type}} ("related_id", "time") VALUES (:qp0, now())
                SQL,
                [':qp0' => null],
            ],
            'carry passed params' => [
                'customer',
                [
                    'email' => 'test@example.com',
                    'name' => 'sergeymakinen',
                    'address' => '{{city}}',
                    'is_active' => false,
                    'related_id' => null,
                    'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                ],
                [':phBar' => 'bar'],
                <<<SQL
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id", "col") VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar)) RETURNING "id"
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':qp5' => null,
                    ':phFoo' => 'foo',
                ],
            ],
            'carry passed params (query)' => [
                'customer',
                (new Query(self::getDb()))
                    ->select(['email', 'name', 'address', 'is_active', 'related_id'])
                    ->from('customer')
                    ->where(
                        [
                            'email' => 'test@example.com',
                            'name' => 'sergeymakinen',
                            'address' => '{{city}}',
                            'is_active' => false,
                            'related_id' => null,
                            'col' => new Expression('CONCAT(:phFoo, :phBar)', [':phFoo' => 'foo']),
                        ],
                    ),
                [':phBar' => 'bar'],
                <<<SQL
                INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id") SELECT "email", "name", "address", "is_active", "related_id" FROM "customer" WHERE ("email"=:qp1) AND ("name"=:qp2) AND ("address"=:qp3) AND ("is_active"=:qp4) AND ("related_id" IS NULL) AND ("col"=CONCAT(:phFoo, :phBar)) RETURNING "id"
                SQL,
                [
                    ':phBar' => 'bar',
                    ':qp1' => 'test@example.com',
                    ':qp2' => 'sergeymakinen',
                    ':qp3' => '{{city}}',
                    ':qp4' => false,
                    ':phFoo' => 'foo',
                ],
            ],
            [
                '{{%order_item}}',
                ['order_id' => 1, 'item_id' => 1, 'quantity' => 1, 'subtotal' => 1.0],
                [],
                <<<SQL
                INSERT INTO {{%order_item}} ("order_id", "item_id", "quantity", "subtotal") VALUES (:qp0, :qp1, :qp2, :qp3) RETURNING "order_id", "item_id"
                SQL,
                [':qp0' => 1, ':qp1' => 1, ':qp2' => 1, ':qp3' => 1.0,],
            ],
        ];
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => 'INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") ' .
                    'VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") ' .
                    'DO UPDATE SET "address"=EXCLUDED."address", "status"=EXCLUDED."status", "profile_id"=EXCLUDED."profile_id"',
            ],
            'regular values with unique at not the first position' => [
                3 => 'INSERT INTO "T_upsert" ("address", "email", "status", "profile_id") ' .
                    'VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") ' .
                    'DO UPDATE SET "address"=EXCLUDED."address", "status"=EXCLUDED."status", "profile_id"=EXCLUDED."profile_id"',
            ],
            'regular values with update part' => [
                2 => ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('"T_upsert"."orders" + 1')],
                3 => 'INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") ' .
                    'VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") ' .
                    'DO UPDATE SET "address"=:qp4, "status"=:qp5, "orders"="T_upsert"."orders" + 1',
            ],
            'regular values without update part' => [
                3 => 'INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") ' .
                    'VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT DO NOTHING',
            ],
            'query' => [
                3 => 'INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" ' .
                    'WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "status"=EXCLUDED."status"',
            ],
            'query with update part' => [
                2 => ['address' => 'foo {{city}}', 'status' => 2, 'orders' => new Expression('"T_upsert"."orders" + 1')],
                3 => 'INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" ' .
                    'WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "address"=:qp1, "status"=:qp2, "orders"="T_upsert"."orders" + 1',
            ],
            'query without update part' => [
                3 => 'INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" ' .
                    'WHERE "name"=:qp0 LIMIT 1 ON CONFLICT DO NOTHING',
            ],
            'values and expressions' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('extract(epoch from now()) * 1000')],
                3 => 'INSERT INTO {{%T_upsert}} ("email", "ts") VALUES (:qp0, extract(epoch from now()) * 1000) ' .
                    'ON CONFLICT ("email") DO UPDATE SET "ts"=EXCLUDED."ts"',
            ],
            'values and expressions with update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('extract(epoch from now()) * 1000')],
                2 => ['[[orders]]' => new Expression('EXCLUDED.orders + 1')],
                3 => 'INSERT INTO {{%T_upsert}} ("email", "ts") VALUES (:qp0, extract(epoch from now()) * 1000) ' .
                    'ON CONFLICT ("email") DO UPDATE SET "orders"=EXCLUDED.orders + 1',
            ],
            'values and expressions without update part' => [
                1 => ['{{%T_upsert}}.[[email]]' => 'dynamic@example.com', '[[ts]]' => new Expression('extract(epoch from now()) * 1000')],
                3 => 'INSERT INTO {{%T_upsert}} ("email", "ts") VALUES (:qp0, extract(epoch from now()) * 1000) ON CONFLICT DO NOTHING',
            ],
            'query, values and expressions with update part' => [
                1 => (new Query(self::getDb()))
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('extract(epoch from now()) * 1000'),
                        ],
                    ),
                2 => ['ts' => 0, '[[orders]]' => new Expression('EXCLUDED.orders + 1')],
                3 => 'INSERT INTO {{%T_upsert}} ("email", [[ts]]) SELECT :phEmail AS "email", extract(epoch from now()) * 1000 AS [[ts]] ' .
                    'ON CONFLICT ("email") DO UPDATE SET "ts"=:qp1, "orders"=EXCLUDED.orders + 1',
            ],
            'query, values and expressions without update part' => [
                1 => (new Query(self::getDb()))
                    ->select(
                        [
                            'email' => new Expression(':phEmail', [':phEmail' => 'dynamic@example.com']),
                            '[[ts]]' => new Expression('extract(epoch from now()) * 1000'),
                        ],
                    ),
                3 => 'INSERT INTO {{%T_upsert}} ("email", [[ts]]) SELECT :phEmail AS "email", extract(epoch from now()) * 1000 AS [[ts]] ON CONFLICT DO NOTHING',
            ],
            'no columns to update' => [
                3 => 'INSERT INTO "T_upsert_1" ("a") VALUES (:qp0) ON CONFLICT DO NOTHING',
            ],
            'no columns to update with unique' => [
                3 => 'INSERT INTO {{%T_upsert}} ("email") VALUES (:qp0) ON CONFLICT DO NOTHING',
            ],
            'no unique columns in table - simple insert' => [
                3 => 'INSERT INTO {{%animal}} ("type") VALUES (:qp0)',
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        $upsert['table view'] = [
            'animal_view',
            ['id' => 3, 'type' => 'yiiunit\data\ar\Mouse'],
            true,
            'INSERT INTO "animal_view" ("id", "type") VALUES (:qp0, :qp1) ON CONFLICT ("id") DO UPDATE SET "type"=EXCLUDED."type"',
            [':qp0' => 3, ':qp1' => 'yiiunit\data\ar\Mouse'],
        ];

        return $upsert;
    }

    public static function overlapsCondition(): array
    {
        $data = parent::overlapsCondition();

        $data['null'][1] = 0;
        $data['expression'][0] = new Expression("'{0,1,2,7}'");
        $data['query expression'][0] = (new Query(self::getDb()))->select(new ArrayExpression([0,1,2,7]));
        $data[] = [new Expression('ARRAY[0,1,2,7]'), 1];
        $data[] = [new ArrayExpression([0,1,2,7]), 1];

        return $data;
    }

    public static function buildColumnDefinition(): array
    {
        $values = parent::buildColumnDefinition();

        $values[PseudoType::PK][0] = 'serial PRIMARY KEY';
        $values[PseudoType::UPK][0] = 'serial PRIMARY KEY';
        $values[PseudoType::BIGPK][0] = 'bigserial PRIMARY KEY';
        $values[PseudoType::UBIGPK][0] = 'bigserial PRIMARY KEY';
        $values[PseudoType::UUID_PK][0] = 'uuid PRIMARY KEY DEFAULT gen_random_uuid()';
        $values[PseudoType::UUID_PK_SEQ][0] = 'uuid PRIMARY KEY DEFAULT gen_random_uuid()';
        $values['primaryKey()'][0] = 'serial PRIMARY KEY';
        $values['smallPrimaryKey()'][0] = 'smallserial PRIMARY KEY';
        $values['bigPrimaryKey()'][0] = 'bigserial PRIMARY KEY';
        $values['uuidPrimaryKey()'][0] = 'uuid PRIMARY KEY DEFAULT gen_random_uuid()';
        $values['bit()'][0] = 'varbit';
        $values['bit(1)'][0] = 'varbit(1)';
        $values['bit(8)'][0] = 'varbit(8)';
        $values['bit(64)'][0] = 'varbit(64)';
        $values['tinyint()'][0] = 'smallint';
        $values['tinyint(2)'][0] = 'smallint';
        $values['smallint(4)'][0] = 'smallint';
        $values['integer(8)'][0] = 'integer';
        $values['bigint(15)'][0] = 'bigint';
        $values['float()'][0] = 'real';
        $values['float(10)'][0] = 'real';
        $values['float(10,2)'][0] = 'real';
        $values['double()'][0] = 'double precision';
        $values['double(10)'][0] = 'double precision';
        $values['double(10,2)'][0] = 'double precision';
        $values['decimal()'][0] = 'numeric(10,0)';
        $values['decimal(5)'][0] = 'numeric(5,0)';
        $values['decimal(5,2)'][0] = 'numeric(5,2)';
        $values['decimal(null)'][0] = 'numeric';
        $values['money()'][0] = 'money';
        $values['money(10)'][0] = 'money';
        $values['money(10,2)'][0] = 'money';
        $values['money(null)'][0] = 'money';
        $values['text(1000)'][0] = 'text';
        $values['binary()'][0] = 'bytea';
        $values['binary(1000)'][0] = 'bytea';
        $values['uuid()'][0] = 'uuid';
        $values['datetime()'][0] = 'timestamp(0)';
        $values['datetime(6)'][0] = 'timestamp(6)';
        $values['datetime(null)'][0] = 'timestamp';
        $values['array()'][0] = 'varchar[]';
        $values['structured()'][0] = 'jsonb';
        $values['json()'][0] = 'jsonb';
        $values['json(100)'][0] = 'jsonb';
        $values['unsigned()'][0] = 'integer';
        $values['scale(2)'][0] = 'numeric(10,2)';
        $values['integer(8)->scale(2)'][0] = 'integer';

        $db = self::getDb();
        $serverVersion = self::getDb()->getServerInfo()->getVersion();
        $db->close();

        if (version_compare($serverVersion, '13', '<')) {
            $uuidExpression = "uuid_in(overlay(overlay(md5(now()::text || random()::text) placing '4' from 13) placing"
                . ' to_hex(floor(4 * random() + 8)::int)::text from 17)::cstring)';

            $values[PseudoType::UUID_PK][0] = "uuid PRIMARY KEY DEFAULT $uuidExpression";
            $values[PseudoType::UUID_PK_SEQ][0] = "uuid PRIMARY KEY DEFAULT $uuidExpression";
            $values['uuidPrimaryKey()'][0] = "uuid PRIMARY KEY DEFAULT $uuidExpression";
        }

        return [
            ...$values,
            ['text[]', ColumnBuilder::array()->dbType('text[]')],
            ['int[]', 'int[]'],
            ['character varying(255)', 'character varying(255)'],
            ['character varying(255)[][]', 'character varying(255)[][]'],
            ['timestamp(5)', 'timestamp (5) without time zone'],
            ['timestamptz', 'timestamp with time zone'],
            ['time(3)', 'time(3) without time zone'],
            ['timetz(0)', 'time(0) with time zone'],
        ];
    }

    public static function prepareParam(): array
    {
        $values = parent::prepareParam();

        $values['binary'][0] = "'\\x737472696e67'::bytea";

        return $values;
    }

    public static function prepareValue(): array
    {
        $values = parent::prepareValue();

        $values['binary'][0] = "'\\x737472696e67'::bytea";
        $values['paramBinary'][0] = "'\\x737472696e67'::bytea";

        return $values;
    }
}
