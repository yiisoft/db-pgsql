<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\QueryBuilder\Condition\Builder\AbstractOverlapsConditionBuilder;
use Yiisoft\Db\QueryBuilder\Condition\JsonOverlapsCondition;

/**
 * Builds expressions for {@see JsonOverlapsCondition} for PostgreSQL Server.
 */
final class JsonOverlapsConditionBuilder extends AbstractOverlapsConditionBuilder
{
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
        $column = $this->prepareColumn($expression->getColumn());
        $values = $expression->getValues();

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
