<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Closure;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Throwable;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Tests\Provider\CommandProvider;
use Yiisoft\Db\Pgsql\Tests\Support\IntegrationTestTrait;
use Yiisoft\Db\Pgsql\Tests\Support\TestConnection;
use Yiisoft\Db\Tests\Common\CommonCommandTest;

use function serialize;
use function strlen;

/**
 * @group pgsql
 */
final class CommandTest extends CommonCommandTest
{
    use IntegrationTestTrait;

    public function testAddDefaultValue(): void
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        $exception = null;
        try {
            $command->addDefaultValue('{{table}}', '{{name}}', 'column', 'value');
        } catch (Throwable $exception) {
        }

        $this->assertInstanceOf(NotSupportedException::class, $exception);
        $this->assertSame(
            'Yiisoft\Db\Pgsql\DDLQueryBuilder::addDefaultValue is not supported by PostgreSQL.',
            $exception->getMessage(),
        );

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'batchInsert')]
    public function testBatchInsert(
        string $table,
        iterable $values,
        array $columns,
        string $expected,
        array $expectedParams = [],
        int $insertedRow = 1,
    ): void {
        parent::testBatchInsert($table, $values, $columns, $expected, $expectedParams, $insertedRow);
    }

    public function testBooleanValuesInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

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

    public function testBooleanValuesBatchInsert(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

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

    public function testDelete(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

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

    public function testDropDefaultValue(): void
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Pgsql\DDLQueryBuilder::dropDefaultValue is not supported by PostgreSQL.',
        );

        $command->dropDefaultValue('{{table}}', '{{name}}');

        $db->close();
    }

    #[DataProviderExternal(CommandProvider::class, 'rawSql')]
    public function testGetRawSql(string $sql, array $params, string $expectedRawSql): void
    {
        parent::testGetRawSql($sql, $params, $expectedRawSql);
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/11498
     */
    public function testSaveSerializedObject(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

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
    }

    #[DataProviderExternal(CommandProvider::class, 'update')]
    public function testUpdate(
        string $table,
        array $columns,
        array|ExpressionInterface|string $conditions,
        Closure|array|ExpressionInterface|string|null $from,
        array $params,
        array $expectedValues,
        int $expectedCount,
    ): void {
        parent::testUpdate($table, $columns, $conditions, $from, $params, $expectedValues, $expectedCount);
    }

    #[DataProviderExternal(CommandProvider::class, 'upsert')]
    public function testUpsert(Closure|array $firstData, Closure|array $secondData): void
    {
        parent::testUpsert($firstData, $secondData);
    }

    public function testInsertReturningPksUuid(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = $db->createCommand();
        $result = $command->insertReturningPks(
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
        $db = TestConnection::getShared();

        $databases = $db->createCommand()->showDatabases();

        $this->assertSame(
            [TestConnection::databaseName()],
            $databases,
        );
    }

    #[DataProviderExternal(CommandProvider::class, 'createIndex')]
    public function testCreateIndex(array $columns, array $indexColumns, ?string $indexType, ?string $indexMethod): void
    {
        parent::testCreateIndex($columns, $indexColumns, $indexType, $indexMethod);
    }

    protected function getUpsertTestCharCast(): string
    {
        return 'CAST([[address]] AS VARCHAR(255))';
    }
}
