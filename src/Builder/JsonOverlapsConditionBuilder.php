<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlapsCondition;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see JsonOverlapsCondition} for PostgreSQL Server.
 *
 * @implements ExpressionBuilderInterface<JsonOverlapsCondition>
 */
final class JsonOverlapsConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    /**
     * Build SQL for {@see JsonOverlapsCondition}.
     *
     * @param JsonOverlapsCondition $expression The {@see JsonOverlapsCondition} to be built.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
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

        return "ARRAY(SELECT jsonb_array_elements_text($column::jsonb)) && $values::text[]";
    }
}
