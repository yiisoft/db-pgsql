<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Value\JsonValue;
use Yiisoft\Db\Expression\Value\Builder\JsonValueBuilder as BaseJsonValueBuilder;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Builds expressions for {@see `Yiisoft\Db\Expression\Value\JsonValue`} for PostgreSQL Server.
 *
 * @implements ExpressionBuilderInterface<JsonValue>
 */
final class JsonValueBuilder implements ExpressionBuilderInterface
{
    private BaseJsonValueBuilder $baseValueBuilder;

    public function __construct(QueryBuilderInterface $queryBuilder)
    {
        $this->baseValueBuilder = new BaseJsonValueBuilder($queryBuilder);
    }

    /**
     * The Method builds the raw SQL from the $expression that won't be additionally escaped or quoted.
     *
     * @param JsonValue $expression The expression to build.
     * @param array $params The binding parameters.
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $statement = $this->baseValueBuilder->build($expression, $params);

        if ($expression->value instanceof ArrayValue) {
            $statement = 'array_to_json(' . $statement . ')';
        }

        return $statement . $this->getTypeHint($expression);
    }

    /**
     * @return string The typecast expression based on {@see JsonValue::type}.
     */
    private function getTypeHint(JsonValue $expression): string
    {
        $type = $expression->type;

        if ($type === null) {
            return '';
        }

        return '::' . $type;
    }
}
