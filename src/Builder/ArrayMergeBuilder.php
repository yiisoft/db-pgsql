<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Expression\Function\ArrayMerge;
use Yiisoft\Db\Expression\Function\Builder\MultiOperandFunctionBuilder;
use Yiisoft\Db\Expression\Function\MultiOperandFunction;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function implode;
use function is_string;

/**
 * Builds SQL expressions which merge arrays for {@see ArrayMerge} objects.
 *
 * ```sql
 * ARRAY(SELECT DISTINCT UNNEST(operand1::int[] || operand2::int[]))::int[]
 * sql
 */
final class ArrayMergeBuilder extends MultiOperandFunctionBuilder
{
    /**
     * Builds a SQL expression which merges arrays from the given {@see ArrayMerge} object.
     *
     * @param ArrayMerge $expression The expression to build.
     * @param array $params The parameters to bind.
     *
     * @return string The SQL expression.
     */
    protected function buildFromExpression(MultiOperandFunction $expression, array &$params): string
    {
        $typeHint = $this->buildTypeHint($expression->getType());
        $builtOperands = [];

        foreach ($expression->getOperands() as $operand) {
            $builtOperands[] = $this->buildOperand($operand, $params) . $typeHint;
        }

        return 'ARRAY(SELECT DISTINCT UNNEST(' . implode(' || ', $builtOperands) . "))$typeHint";
    }

    protected function buildTypeHint(string|ColumnInterface $type): string
    {
        if (is_string($type)) {
            return $type === '' ? '' : "::$type";
        }

        return '::' . $this->queryBuilder->getColumnDefinitionBuilder()->buildType($type);
    }
}
