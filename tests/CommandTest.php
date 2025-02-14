<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Pgsql\Connection;
use Yiisoft\Db\Pgsql\Dsn;
use Yiisoft\Db\Pgsql\Driver;
use Yiisoft\Db\Pgsql\Tests\Provider\CommandProvider;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonCommandTest;

use function serialize;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandTest extends CommonCommandTest
{
    use TestTrait;

    protected string $upsertTestCharCast = 'CAST([[address]] AS VARCHAR(255))';

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\DDLQueryBuilder::addDefaultValue is not supported by PostgreSQL.'
        );

        $command->addDefaultValue('{{table}}', '{{name}}', 'column', 'value');

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\CommandProvider::batchInsert
     *
     * @throws Throwable
     */
    public function testBatchInsert(
        string $table,
        iterable $values,
        array $columns,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1
    ): void {
        parent::testBatchInsert($table, $values, $columns, $expected, $expectedParams, $insertedRow);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBooleanValuesInsert(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insert('{{bool_values}}', ['bool_col' => true]);

        $this->assertSame(1, $command->execute());

        $command = $db->createCommand();
        $command->insert('{{bool_values}}', ['bool_col' => false]);

        $this->assertSame(1, $command->execute());

        $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM [[bool_values]] WHERE [[bool_col]] = TRUE;
            SQL,
        );

        $this->assertSame(1, $command->queryScalar());

        $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM [[bool_values]] WHERE [[bool_col]] = FALSE;
            SQL,
        );

        $this->assertSame(1, $command->queryScalar());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testBooleanValuesBatchInsert(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->insertBatch('{{bool_values}}', [[true], [false]], ['bool_col']);

        $this->assertSame(2, $command->execute());

        $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM "bool_values" WHERE bool_col = TRUE;
            SQL,
        );

        $this->assertSame(1, $command->queryScalar());

        $command->setSql(
            <<<SQL
            SELECT COUNT(*) FROM "bool_values" WHERE bool_col = FALSE;
            SQL,
        );

        $this->assertSame(1, $command->queryScalar());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testDelete(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $command->delete('{{customer}}', ['id' => 2])->execute();
        $chekSql = <<<SQL
        SELECT COUNT([[id]]) FROM [[customer]]
        SQL;
        $command->setSql($chekSql);

        $this->assertSame(2, $command->queryScalar());

        $command->delete('{{customer}}', ['id' => 3])->execute();
        $command->setSql($chekSql);

        $this->assertSame(1, $command->queryScalar());

        $db->close();
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\DDLQueryBuilder::dropDefaultValue is not supported by PostgreSQL.'
        );

        $command->dropDefaultValue('{{table}}', '{{name}}');

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\CommandProvider::rawSql
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        parent::testGetRawSql($sql, $params, $expectedRawSql);
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     *
     * {@link https://github.com/yiisoft/yii2/issues/11498}
     */
    public function testSaveSerializedObject(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();
        $command = $command->insert(
            '{{type}}',
            [
                'int_col' => 1,
                'char_col' => 'serialize',
                'float_col' => 5.6,
                'bool_col' => true,
                'blob_col' => serialize($db),
            ],
        );

        $this->assertSame(1, $command->execute());

        $command->update('{{type}}', ['blob_col' => serialize($db)], ['char_col' => 'serialize']);

        $this->assertSame(1, $command->execute());

        $db->close();
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\CommandProvider::update
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $conditions,
        array $params,
        array $expectedValues,
        int $expectedCount,
    ): void {
        parent::testUpdate($table, $columns, $conditions, $params, $expectedValues, $expectedCount);
    }

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\CommandProvider::upsert
     *
     * @throws Exception
     * @throws Throwable
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        parent::testUpsert($firstData, $secondData);
    }

    public function testinsertWithReturningPksUuid(): void
    {
        $db = $this->getConnection(true);

        $command = $db->createCommand();
        $result = $command->insertWithReturningPks(
            '{{%table_uuid}}',
            [
                'col' => 'test',
            ],
        );

        $this->assertIsString($result['uuid']);

        // for example ['uuid' => 738146be-87b1-49f2-9913-36142fb6fcbe]
        $this->assertStringMatchesFormat('%s-%s-%s-%s-%s', $result['uuid']);

        $this->assertEquals(36, strlen($result['uuid']));

        $db->close();
    }

    public function testShowDatabases(): void
    {
        $this->assertSame([self::getDatabaseName()], self::getDb()->createCommand()->showDatabases());
    }

    #[DataProviderExternal(CommandProvider::class, 'createIndex')]
    public function testCreateIndex(array $columns, array $indexColumns, string|null $indexType, string|null $indexMethod): void
    {
        parent::testCreateIndex($columns, $indexColumns, $indexType, $indexMethod);
    }
}
