<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Common\CommonPdoCommandTest;
use Yiisoft\Db\Tests\Support\DbHelper;

/**
 * @group pgsql
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class PdoCommandTest extends CommonPdoCommandTest
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Pgsql\Tests\Provider\CommandPDOProvider::bindParam
     */
    public function testBindParam(
        string $field,
        string $name,
        mixed $value,
        int $dataType,
        int|null $length,
        mixed $driverOptions,
        array $expected,
    ): void {
        parent::testBindParam($field, $name, $value, $dataType, $length, $driverOptions, $expected);
    }

    /**
     * {@link https://github.com/yiisoft/db-pgsql/issues/1}
     */
    public function testInsertAndReadToArrayColumn(): void
    {
        $db = $this->getConnection(true);

        $arrValue = [1, 2, 3, 4];
        $insertedData = $db->createCommand()->insertWithReturningPks('{{%table_with_array_col}}', ['array_col' => $arrValue]);

        $this->assertGreaterThan(0, $insertedData['id']);

        $selectData = $db->createCommand('select * from {{%table_with_array_col}} where id=:id', $insertedData)->queryOne();

        $this->assertEquals('{1,2,3,4}', $selectData['array_col']);

        $columnSchema = $db->getTableSchema('{{%table_with_array_col}}')->getColumn('array_col');

        $this->assertSame($arrValue, $columnSchema->phpTypecast($selectData['array_col']));
    }

    public function testCommandLogging(): void
    {
        parent::testCommandLogging();
        $db = $this->getConnection(true);

        /** @var AbstractPdoCommand $command */
        $command = $db->createCommand();

        $sql = DbHelper::replaceQuotes(
            <<<SQL
            INSERT INTO [[customer]] ([[name]], [[email]]) VALUES ('test', 'email@email') RETURNING [[id]]
            SQL,
            $db->getDriverName(),
        );
        $command->setLogger($this->createQueryLogger($sql, ['Yiisoft\Db\Driver\Pdo\AbstractPdoCommand::insertWithReturningPks']));
        $command->insertWithReturningPks('{{%customer}}', ['name' => 'test', 'email' => 'email@email']);

        $db->close();;
    }
}
