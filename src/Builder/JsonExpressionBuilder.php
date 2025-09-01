<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use JsonException;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Value\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Value\JsonExpression;
use Yiisoft\Db\Expression\Value\Builder\JsonExpressionBuilder as BaseJsonExpressionBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see `Yiisoft\Db\Expression\Value\JsonExpression`} for PostgreSQL Server.
 *
 * @implements ExpressionBuilderInterface<JsonExpression>
 */
final class JsonExpressionBuilder implements ExpressionBuilderInterface
{
    private BaseJsonExpressionBuilder $baseExpressionBuilder;

    public function __construct(QueryBuilderInterface $queryBuilder)
    {
        $this->baseExpressionBuilder = new BaseJsonExpressionBuilder($queryBuilder);
    }

    /**
     * The Method builds the raw SQL from the $expression that won't be additionally escaped or quoted.
     *
     * @param JsonExpression $expression The expression to build.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $statement = $this->baseExpressionBuilder->build($expression, $params);

        if ($expression->getValue() instanceof ArrayExpression) {
            $statement = 'array_to_json(' . $statement . ')';
        }

        return $statement . $this->getTypeHint($expression);
    }

    /**
     * @return string The typecast expression based on {@see JsonExpression::getType()}.
     */
    private function getTypeHint(JsonExpression $expression): string
    {
        $type = $expression->getType();

        if ($type === null) {
            return '';
        }

        return '::' . $type;
    }
}
