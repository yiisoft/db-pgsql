<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Pgsql\Builder\ArrayExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\CompositeExpressionBuilder;
use Yiisoft\Db\Pgsql\Builder\JsonExpressionBuilder;
use Yiisoft\Db\Pgsql\Expression\CompositeExpression;
use Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;

use function array_merge;

/**
 * Implements a DQL (Data Query Language) SQL statements for PostgreSQL Server.
 */
final class DQLQueryBuilder extends AbstractDQLQueryBuilder
{
    /**
     * Has an array of default condition classes.
     *
     * Extend this method if you want to change default condition classes for the query builder.
     *
     * {@see conditionClasses} docs for details.
     */
    protected function defaultConditionClasses(): array
    {
        return array_merge(parent::defaultConditionClasses(), [
            'ILIKE' => LikeCondition::class,
            'NOT ILIKE' => LikeCondition::class,
            'OR ILIKE' => LikeCondition::class,
            'OR NOT ILIKE' => LikeCondition::class,
        ]);
    }

    /**
     * Has an array of default expression builders.
     *
     * Extend this method and override it if you want to change default expression builders for this query builder.
     *
     * {@see ExpressionBuilder} docs for details.
     *
     * @psalm-return array<string, class-string<ExpressionBuilderInterface>>
     */
    protected function defaultExpressionBuilders(): array
    {
        return array_merge(parent::defaultExpressionBuilders(), [
            ArrayExpression::class => ArrayExpressionBuilder::class,
            JsonExpression::class => JsonExpressionBuilder::class,
            CompositeExpression::class => CompositeExpressionBuilder::class,
        ]);
    }

    public function getExpressionBuilder(ExpressionInterface $expression): object
    {
        $className = $expression::class;

        if (!isset($this->expressionBuilders[$className])) {
            foreach ($this->expressionBuilders as $expressionClassName => $builderClassName) {
                if ($className instanceof $expressionClassName) {
                    return new $builderClassName($this->queryBuilder);
                }
            }

            throw new InvalidArgumentException(
                'Expression of class ' . $className . ' can not be built in ' . static::class
            );
        }

        return new $this->expressionBuilders[$className]($this->queryBuilder);
    }
}
