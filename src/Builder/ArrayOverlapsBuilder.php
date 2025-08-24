<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Builder\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\QueryBuilder\Condition\ArrayOverlaps;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function preg_match;

/**
 * Builds expressions for {@see ArrayOverlaps} for PostgreSQL Server.
 *
 * @implements ExpressionBuilderInterface<ArrayOverlaps>
 */
final class ArrayOverlapsBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    /**
     * Build SQL for {@see ArrayOverlaps}.
     *
     * @param ArrayOverlaps $expression The {@see ArrayOverlaps} to be built.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $expression->column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($expression->column)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);
        $values = $expression->values;

        if (!$values instanceof ExpressionInterface) {
            $values = new ArrayExpression($values);
        } elseif ($values instanceof JsonExpression) {
            /** @psalm-suppress MixedArgument */
            $values = new ArrayExpression($values->getValue());
        }

        $values = $this->queryBuilder->buildExpression($values, $params);

        if (preg_match('/::\w+\[]$/', $values, $matches) === 1) {
            $typeHint = $matches[0];

            return "$column$typeHint && $values";
        }

        return "$column::text[] && $values::text[]";
    }
}
