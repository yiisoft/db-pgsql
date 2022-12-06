<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests\Provider;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Provider\AbstractQueryBuilderProvider;
use Yiisoft\Db\Tests\Support\TraversableObject;

use function array_replace;

final class QueryBuilderProvider extends AbstractQueryBuilderProvider
{
    use TestTrait;

    protected string $likeEscapeCharSql = '';
    protected array $likeParameterReplacements = [];

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function buildCondition(): array
    {
        $db = $this->getConnection();

        $buildCondition = parent::buildCondition();

        return array_merge(
            $buildCondition,
            [
                /**
                * adding conditions for ILIKE i.e. case insensitive LIKE.
                *
                * {@see http://www.postgresql.org/docs/8.3/static/functions-matching.html#FUNCTIONS-LIKE}
                */
                /* empty values */
                [['ilike', 'name', []], '0=1', []],
                [['not ilike', 'name', []], '', []],
                [['or ilike', 'name', []], '0=1', []],
                [['or not ilike', 'name', []], '', []],

                /* simple ilike */
                [['ilike', 'name', 'heyho'], '"name" ILIKE :qp0', [':qp0' => '%heyho%']],
                [['not ilike', 'name', 'heyho'], '"name" NOT ILIKE :qp0', [':qp0' => '%heyho%']],
                [['or ilike', 'name', 'heyho'], '"name" ILIKE :qp0', [':qp0' => '%heyho%']],
                [['or not ilike', 'name', 'heyho'], '"name" NOT ILIKE :qp0', [':qp0' => '%heyho%']],

                /* ilike for many values */
                [
                    ['ilike', 'name', ['heyho', 'abc']],
                    '"name" ILIKE :qp0 AND "name" ILIKE :qp1',
                    [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],
                [
                    ['not ilike', 'name', ['heyho', 'abc']],
                    '"name" NOT ILIKE :qp0 AND "name" NOT ILIKE :qp1',
                    [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],
                [
                    ['or ilike', 'name', ['heyho', 'abc']],
                    '"name" ILIKE :qp0 OR "name" ILIKE :qp1', [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],
                [
                    ['or not ilike', 'name', ['heyho', 'abc']],
                    '"name" NOT ILIKE :qp0 OR "name" NOT ILIKE :qp1',
                    [':qp0' => '%heyho%', ':qp1' => '%abc%'],
                ],

                /* array condition corner cases */
                [['@>', 'id', new ArrayExpression([1])], '"id" @> ARRAY[:qp0]', [':qp0' => 1]],
                'scalar can not be converted to array #1' => [['@>', 'id', new ArrayExpression(1)], '"id" @> ARRAY[]', []],
                [
                    'scalar can not be converted to array #2' => [
                        '@>', 'id', new ArrayExpression(false),
                    ],
                    '"id" @> ARRAY[]',
                    [],
                ],
                [
                    ['&&', 'price', new ArrayExpression([12, 14], 'float')],
                    '"price" && ARRAY[:qp0, :qp1]::float[]',
                    [':qp0' => 12, ':qp1' => 14],
                ],
                [
                    ['@>', 'id', new ArrayExpression([2, 3])],
                    '"id" @> ARRAY[:qp0, :qp1]',
                    [':qp0' => 2, ':qp1' => 3],
                ],
                'array of arrays' => [
                    ['@>', 'id', new ArrayExpression([[1,2], [3,4]], 'float', 2)],
                    '"id" @> ARRAY[ARRAY[:qp0, :qp1]::float[], ARRAY[:qp2, :qp3]::float[]\\]::float[][]',
                    [':qp0' => 1, ':qp1' => 2, ':qp2' => 3, ':qp3' => 4],
                ],
                [['@>', 'id', new ArrayExpression([])], '"id" @> ARRAY[]', []],
                'array can contain nulls' => [
                    ['@>', 'id', new ArrayExpression([null])], '"id" @> ARRAY[:qp0]', [':qp0' => null],
                ],
                'traversable objects are supported' => [
                    ['@>', 'id', new ArrayExpression(new TraversableObject([1, 2, 3]))],
                    '[[id]] @> ARRAY[:qp0, :qp1, :qp2]',
                    [':qp0' => 1, ':qp1' => 2, ':qp2' => 3],
                ],
                [['@>', 'time', new ArrayExpression([new Expression('now()')])], '[[time]] @> ARRAY[now()]', []],
                [
                    [
                        '@>',
                        'id',
                        new ArrayExpression((new Query($db))->select('id')->from('users')->where(['active' => 1])),
                    ],
                    '[[id]] @> ARRAY(SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)',
                    [':qp0' => 1],
                ],
                [
                    [
                        '@>',
                        'id',
                        new ArrayExpression(
                            [
                                (new Query($db))->select('id')->from('users')->where(['active' => 1]),
                            ],
                            'integer'
                        ),
                    ],
                    '[[id]] @> ARRAY[ARRAY(SELECT [[id]] FROM [[users]] WHERE [[active]]=:qp0)::integer[]]::integer[]',
                    [':qp0' => 1],
                ],

                /* json conditions */
                [
                    ['=', 'jsoncol', new JsonExpression(['lang' => 'uk', 'country' => 'UA'])],
                    '[[jsoncol]] = :qp0',
                    [':qp0' => '{"lang":"uk","country":"UA"}'],
                ],
                [
                    ['=', 'jsoncol', new JsonExpression([false])],
                    '[[jsoncol]] = :qp0', [':qp0' => '[false]'],
                ],
                [
                    ['=', 'prices', new JsonExpression(['seeds' => 15, 'apples' => 25], 'jsonb')],
                    '[[prices]] = :qp0::jsonb', [':qp0' => '{"seeds":15,"apples":25}'],
                ],
                'nested json' => [
                    [
                        '=',
                        'data',
                        new JsonExpression(
                            [
                                'user' => ['login' => 'silverfire', 'password' => 'c4ny0ur34d17?'],
                                'props' => ['mood' => 'good'],
                            ]
                        ),
                    ],
                    '"data" = :qp0',
                    [':qp0' => '{"user":{"login":"silverfire","password":"c4ny0ur34d17?"},"props":{"mood":"good"}}'],
                ],
                'null value' => [['=', 'jsoncol', new JsonExpression(null)], '"jsoncol" = :qp0', [':qp0' => 'null']],
                'null as array value' => [
                    ['=', 'jsoncol', new JsonExpression([null])], '"jsoncol" = :qp0', [':qp0' => '[null]'],
                ],
                'null as object value' => [
                    ['=', 'jsoncol', new JsonExpression(['nil' => null])], '"jsoncol" = :qp0', [':qp0' => '{"nil":null}'],
                ],
                'query' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression((new Query($db))->select('params')->from('user')->where(['id' => 1])),
                    ],
                    '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)',
                    [':qp0' => 1],
                ],
                'query with type' => [
                    [
                        '=',
                        'jsoncol',
                        new JsonExpression(
                            (new Query($db))->select('params')->from('user')->where(['id' => 1]),
                            'jsonb'
                        ),
                    ],
                    '[[jsoncol]] = (SELECT [[params]] FROM [[user]] WHERE [[id]]=:qp0)::jsonb',
                    [':qp0' => 1],
                ],
                'array of json expressions' => [
                    [
                        '=',
                        'colname',
                        new ArrayExpression(
                            [new JsonExpression(['a' => null, 'b' => 123, 'c' => [4, 5]]), new JsonExpression([true])]
                        ),
                    ],
                    '"colname" = ARRAY[:qp0, :qp1]',
                    [':qp0' => '{"a":null,"b":123,"c":[4,5]}', ':qp1' => '[true]'],
                ],
                'Items in ArrayExpression of type json should be casted to Json' => [
                    ['=', 'colname', new ArrayExpression([['a' => null, 'b' => 123, 'c' => [4, 5]], [true]], 'json')],
                    '"colname" = ARRAY[:qp0, :qp1]::json[]',
                    [':qp0' => '{"a":null,"b":123,"c":[4,5]}', ':qp1' => '[true]'],
                ],
                'Two dimension array of text' => [
                    [
                        '=',
                        'colname',
                        new ArrayExpression([['text1', 'text2'], ['text3', 'text4'], [null, 'text5']], 'text', 2),
                    ],
                    '"colname" = ARRAY[ARRAY[:qp0, :qp1]::text[], ARRAY[:qp2, :qp3]::text[], ARRAY[:qp4, :qp5]::text[]]::text[][]',
                    [
                        ':qp0' => 'text1',
                        ':qp1' => 'text2',
                        ':qp2' => 'text3',
                        ':qp3' => 'text4',
                        ':qp4' => null,
                        ':qp5' => 'text5',
                    ],
                ],
                'Three dimension array of booleans' => [
                    [
                        '=',
                        'colname',
                        new ArrayExpression([[[true], [false, null]], [[false], [true], [false]], [['t', 'f']]], 'bool', 3),
                    ],
                    '"colname" = ARRAY[ARRAY[ARRAY[:qp0]::bool[], ARRAY[:qp1, :qp2]::bool[]]::bool[][], ARRAY[ARRAY[:qp3]::bool[], ARRAY[:qp4]::bool[], ARRAY[:qp5]::bool[]]::bool[][], ARRAY[ARRAY[:qp6, :qp7]::bool[]]::bool[][]]::bool[][][]',
                    [
                        ':qp0' => true,
                        ':qp1' => false,
                        ':qp2' => null,
                        ':qp3' => false,
                        ':qp4' => true,
                        ':qp5' => false,
                        ':qp6' => 't',
                        ':qp7' => 'f',
                    ],
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
            ]
        );
    }

    public function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO "customer" DEFAULT VALUES
        SQL;

        return $insert;
    }

    public function insertEx(): array
    {
        $insertEx = parent::insertEx();

        $insertEx['regular-values'][3] = <<<SQL
        INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id") VALUES (:qp0, :qp1, :qp2, :qp3, :qp4) RETURNING "id"
        SQL;
        $insertEx['carry passed params'][3] = <<<SQL
        INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id", "col") VALUES (:qp1, :qp2, :qp3, :qp4, :qp5, CONCAT(:phFoo, :phBar)) RETURNING "id"
        SQL;
        $insertEx['carry passed params (query)'][3] = <<<SQL
        INSERT INTO "customer" ("email", "name", "address", "is_active", "related_id") SELECT "email", "name", "address", "is_active", "related_id" FROM "customer" WHERE ("email"=:qp1) AND ("name"=:qp2) AND ("address"=:qp3) AND ("is_active"=:qp4) AND ("related_id" IS NULL) AND ("col"=CONCAT(:phFoo, :phBar)) RETURNING "id"
        SQL;

        return $insertEx;
    }

    public function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => [
                    <<<SQL
                    WITH "EXCLUDED" ("email", "address", "status", "profile_id") AS (VALUES (CAST(:qp0 AS varchar), CAST(:qp1 AS text), CAST(:qp2 AS int2), CAST(:qp3 AS int4))), "upsert" AS (UPDATE "T_upsert" SET "address"="EXCLUDED"."address", "status"="EXCLUDED"."status", "profile_id"="EXCLUDED"."profile_id" FROM "EXCLUDED" WHERE (("T_upsert"."email"="EXCLUDED"."email")) RETURNING "T_upsert".*) INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") SELECT "email", "address", "status", "profile_id" FROM "EXCLUDED" WHERE NOT EXISTS (SELECT 1 FROM "upsert" WHERE (("upsert"."email"="EXCLUDED"."email")))
                    SQL,
                    <<<SQL
                    INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") DO UPDATE SET "address"=EXCLUDED."address", "status"=EXCLUDED."status", "profile_id"=EXCLUDED."profile_id"
                    SQL,
                ],
                4 => [[':qp0' => 'test@example.com', ':qp1' => 'bar {{city}}', ':qp2' => 1, ':qp3' => null]],
            ],
            'regular values with update part' => [
                3 => [
                    <<<SQL
                    WITH "EXCLUDED" ("email", "address", "status", "profile_id") AS (VALUES (CAST(:qp0 AS varchar), CAST(:qp1 AS text), CAST(:qp2 AS int2), CAST(:qp3 AS int4))), "upsert" AS (UPDATE "T_upsert" SET "address"=:qp4, "status"=:qp5, "orders"=T_upsert.orders + 1 FROM "EXCLUDED" WHERE (("T_upsert"."email"="EXCLUDED"."email")) RETURNING "T_upsert".*) INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") SELECT "email", "address", "status", "profile_id" FROM "EXCLUDED" WHERE NOT EXISTS (SELECT 1 FROM "upsert" WHERE (("upsert"."email"="EXCLUDED"."email")))
                    SQL,
                    <<<SQL
                    INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT ("email") DO UPDATE SET "address"=:qp4, "status"=:qp5, "orders"=T_upsert.orders + 1
                    SQL,
                ],
                4 => [
                    [
                        ':qp0' => 'test@example.com',
                        ':qp1' => 'bar {{city}}',
                        ':qp2' => 1,
                        ':qp3' => null,
                        ':qp4' => 'foo {{city}}',
                        ':qp5' => 2,
                    ],
                ],
            ],
            'regular values without update part' => [
                3 => [
                    <<<SQL
                    WITH "EXCLUDED" ("email", "address", "status", "profile_id") AS (VALUES (CAST(:qp0 AS varchar), CAST(:qp1 AS text), CAST(:qp2 AS int2), CAST(:qp3 AS int4))) INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") SELECT "email", "address", "status", "profile_id" FROM "EXCLUDED" WHERE NOT EXISTS (SELECT 1 FROM "T_upsert" WHERE (("T_upsert"."email"="EXCLUDED"."email")))
                    SQL,
                    <<<SQL
                    INSERT INTO "T_upsert" ("email", "address", "status", "profile_id") VALUES (:qp0, :qp1, :qp2, :qp3) ON CONFLICT DO NOTHING
                    SQL,
                ],
                4 => [[':qp0' => 'test@example.com', ':qp1' => 'bar {{city}}', ':qp2' => 1, ':qp3' => null]],
            ],
            'query' => [
                3 => [
                    <<<SQL
                    WITH "EXCLUDED" ("email", "status") AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1), "upsert" AS (UPDATE "T_upsert" SET "status"="EXCLUDED"."status" FROM "EXCLUDED" WHERE (("T_upsert"."email"="EXCLUDED"."email")) RETURNING "T_upsert".*) INSERT INTO "T_upsert" ("email", "status") SELECT "email", "status" FROM "EXCLUDED" WHERE NOT EXISTS (SELECT 1 FROM "upsert" WHERE (("upsert"."email"="EXCLUDED"."email")))
                    SQL,
                    <<<SQL
                    INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "status"=EXCLUDED."status"
                    SQL,
                ],
            ],
            'query with update part' => [
                3 => [
                    <<<SQL
                    WITH "EXCLUDED" ("email", "status") AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1), "upsert" AS (UPDATE "T_upsert" SET "address"=:qp1, "status"=:qp2, "orders"=T_upsert.orders + 1 FROM "EXCLUDED" WHERE (("T_upsert"."email"="EXCLUDED"."email")) RETURNING "T_upsert".*) INSERT INTO "T_upsert" ("email", "status") SELECT "email", "status" FROM "EXCLUDED" WHERE NOT EXISTS (SELECT 1 FROM "upsert" WHERE (("upsert"."email"="EXCLUDED"."email")))
                    SQL,
                    <<<SQL
                    INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT ("email") DO UPDATE SET "address"=:qp1, "status"=:qp2, "orders"=T_upsert.orders + 1
                    SQL,
                ],
            ],
            'query without update part' => [
                3 => [
                    <<<SQL
                    WITH "EXCLUDED" ("email", "status") AS (SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1) INSERT INTO "T_upsert" ("email", "status") SELECT "email", "status" FROM "EXCLUDED" WHERE NOT EXISTS (SELECT 1 FROM "T_upsert" WHERE (("T_upsert"."email"="EXCLUDED"."email")))
                    SQL,
                    <<<SQL
                    INSERT INTO "T_upsert" ("email", "status") SELECT "email", 2 AS "status" FROM "customer" WHERE "name"=:qp0 LIMIT 1 ON CONFLICT DO NOTHING
                    SQL,
                ],
            ],
            'values and expressions' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'values and expressions with update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'values and expressions without update part' => [
                3 => <<<SQL
                INSERT INTO {{%T_upsert}} ({{%T_upsert}}.[[email]], [[ts]]) VALUES (:qp0, now())
                SQL,
            ],
            'query, values and expressions with update part' => [
                3 => [
                    <<<SQL
                    WITH "EXCLUDED" ("email", [[time]]) AS (SELECT :phEmail AS "email", now() AS [[time]]), "upsert" AS (UPDATE {{%T_upsert}} SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1 FROM "EXCLUDED" WHERE (({{%T_upsert}}."email"="EXCLUDED"."email")) RETURNING {{%T_upsert}}.*) INSERT INTO {{%T_upsert}} ("email", [[time]]) SELECT "email", [[time]] FROM "EXCLUDED" WHERE NOT EXISTS (SELECT 1 FROM "upsert" WHERE (("upsert"."email"="EXCLUDED"."email")))
                    SQL,
                    <<<SQL
                    INSERT INTO {{%T_upsert}} ("email", [[time]]) SELECT :phEmail AS "email", now() AS [[time]] ON CONFLICT ("email") DO UPDATE SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1
                    SQL,
                ],
            ],
            'query, values and expressions without update part' => [
                3 => [
                    <<<SQL
                    WITH "EXCLUDED" ("email", [[time]]) AS (SELECT :phEmail AS "email", now() AS [[time]]), "upsert" AS (UPDATE {{%T_upsert}} SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1 FROM "EXCLUDED" WHERE (({{%T_upsert}}."email"="EXCLUDED"."email")) RETURNING {{%T_upsert}}.*) INSERT INTO {{%T_upsert}} ("email", [[time]]) SELECT "email", [[time]] FROM "EXCLUDED" WHERE NOT EXISTS (SELECT 1 FROM "upsert" WHERE (("upsert"."email"="EXCLUDED"."email")))
                    SQL,
                    <<<SQL
                    INSERT INTO {{%T_upsert}} ("email", [[time]]) SELECT :phEmail AS "email", now() AS [[time]] ON CONFLICT ("email") DO UPDATE SET "ts"=:qp1, [[orders]]=T_upsert.orders + 1
                    SQL,
                ],
            ],
            'no columns to update' => [
                3 => [
                    <<<SQL
                    WITH "EXCLUDED" ("a") AS (VALUES (CAST(:qp0 AS int2))) INSERT INTO "T_upsert_1" ("a") SELECT "a" FROM "EXCLUDED" WHERE NOT EXISTS (SELECT 1 FROM "T_upsert_1" WHERE (("T_upsert_1"."a"="EXCLUDED"."a")))
                    SQL,
                    <<<SQL
                    INSERT INTO "T_upsert_1" ("a") VALUES (:qp0) ON CONFLICT DO NOTHING
                    SQL,
                ],
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }
}
