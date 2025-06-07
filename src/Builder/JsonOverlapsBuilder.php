<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\Builder\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlaps;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function preg_match;

/**
 * Builds expressions for {@see JsonOverlaps} for PostgreSQL Server.
 *
 * @implements ExpressionBuilderInterface<JsonOverlaps>
 */
final class JsonOverlapsBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    /**
     * Build SQL for {@see JsonOverlaps}.
     *
     * @param JsonOverlaps $expression The {@see JsonOverlaps} to be built.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $column = $expression->column instanceof ExpressionInterface
            ? $this->queryBuilder->buildExpression($expression->column)
            : $this->queryBuilder->getQuoter()->quoteColumnName($expression->column);
        $values = $expression->values;

        if ($values instanceof JsonExpression) {
            /** @psalm-suppress MixedArgument */
            $values = new ArrayExpression($values->getValue());
        } elseif (!$values instanceof ExpressionInterface) {
            $values = new ArrayExpression($values);
        }

        $values = $this->queryBuilder->buildExpression($values, $params);

        if (preg_match('/::\w+\[]$/', $values, $matches) === 1) {
            $typeHint = $matches[0];

            return "ARRAY(SELECT jsonb_array_elements_text($column::jsonb))$typeHint && $values";
        }

        return "ARRAY(SELECT jsonb_array_elements_text($column::jsonb)) && $values::text[]";
    }
}
