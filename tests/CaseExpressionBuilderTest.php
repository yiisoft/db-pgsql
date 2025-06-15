<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Tests;

use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Pgsql\Column\IntegerColumn;
use Yiisoft\Db\Pgsql\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractCaseExpressionBuilderTest;

/**
 * @group pgsql
 */
final class CaseExpressionBuilderTest extends AbstractCaseExpressionBuilderTest
{
    use TestTrait;

    public static function buildProvider(): array
    {
        return [
            ...parent::buildProvider(),
            'without case and type hint' => [
                (new CaseExpression())->caseType('int')
                    ->addWhen(1, 'a'),
                'CASE WHEN 1 THEN :qp0 END',
                [
                    ':qp0' => new Param('a', DataType::STRING),
                ],
            ],
            'with case and type hint' => [
                (new CaseExpression('expression', 'int'))
                    ->addWhen(1, 'a')
                    ->else('b'),
                'CASE expression::int WHEN 1::int THEN :qp0 ELSE :qp1 END',
                [
                    ':qp0' => new Param('a', DataType::STRING),
                    ':qp1' => new Param('b', DataType::STRING),
                ],
            ],
            'with case and type hint with column' => [
                (new CaseExpression('expression', new IntegerColumn()))
                    ->addWhen(1, 'a')
                    ->else('b'),
                'CASE expression::integer WHEN 1::integer THEN :qp0 ELSE :qp1 END',
                [
                    ':qp0' => new Param('a', DataType::STRING),
                    ':qp1' => new Param('b', DataType::STRING),
                ],
            ],
        ];
    }
}
