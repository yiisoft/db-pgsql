<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\TestWith;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Pgsql\Column\ArrayColumn;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Tests\AbstractMultiOperandFunctionBuilderTest;

final class MultiOperandFunctionBuilderTest extends AbstractMultiOperandFunctionBuilderTest
{
    use TestTrait;

    public static function dataClasses(): array
    {
        return [
            ...parent::dataClasses(),
            ArrayMerge::class => [ArrayMerge::class],
        ];
    }

    public static function dataBuild(): array
    {
        $stringParam = new Param('{1,2,3}', DataType::STRING);
        $query = self::getDb()->select('column')->from('table')->limit(1);
        $queryString = '(SELECT "column" FROM "table" LIMIT 1)';

        return [
            ...parent::dataBuild(),

            'ArrayMerge with 1 operand' => [
                ArrayMerge::class,
                ['expression'],
                '(expression)',
            ],
            'ArrayMerge with 2 operands' => [
                ArrayMerge::class,
                ['expression', $stringParam],
                'ARRAY(SELECT DISTINCT UNNEST(expression || :qp0))',
                [':qp0' => $stringParam],
            ],
            'ArrayMerge with 4 operands' => [
                ArrayMerge::class,
                ['expression', [2, 3, 4], $stringParam, $query],
                "ARRAY(SELECT DISTINCT UNNEST(expression || ARRAY[2,3,4] || :qp0 || $queryString))",
                [
                    ':qp0' => $stringParam,
                ],
            ],
        ];
    }

    #[TestWith(['int[]', '::int[]'])]
    #[TestWith([new IntegerColumn(), '::integer[]'])]
    #[TestWith([new ArrayColumn(), '::varchar[]'])]
    #[TestWith([new ArrayColumn(column: new IntegerColumn()), '::integer[]'])]
    public function testBuiltWithTypeHint(string|ColumnInterface $type, string $typeHint): void
    {
        $db = $this->getConnection();
        $qb = $db->getQueryBuilder();

        $stringParam = new Param('{1,2,3}', DataType::STRING);
        $query = self::getDb()->select('column')->from('table')->limit(1);
        $queryString = '(SELECT "column" FROM "table" LIMIT 1)';

        $arrayMerge = (new ArrayMerge(
            'expression',
            [2, 3, 4],
            $stringParam,
            $query
        ))->type($type);
        $params = [];

        $this->assertSame(
            "ARRAY(SELECT DISTINCT UNNEST(expression$typeHint || ARRAY[2,3,4]$typeHint || :qp0$typeHint || $queryString$typeHint))$typeHint",
            $qb->buildExpression($arrayMerge, $params)
        );
    }
}
