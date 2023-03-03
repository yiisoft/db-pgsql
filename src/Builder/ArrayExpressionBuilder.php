<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Traversable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\SchemaInterface;

use function implode;
use function in_array;
use function is_array;
use function str_repeat;

/**
 * ArrayExpressionBuilder builds {@see `Yiisoft\Db\Expression\ArrayExpression`} for PostgresSQL Server.
 */
final class ArrayExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Method builds the raw SQL from the expression that will not be additionally escaped or quoted.
     *
     * @param ExpressionInterface $expression The expression to be built.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that will not be additionally escaped or quoted.
     *
     * @psalm-param ArrayExpression $expression
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        /** @psalm-var array|mixed|QueryInterface $value */
        $value = $expression->getValue();

        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof QueryInterface) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);
            return $this->buildSubqueryArray($sql, $expression);
        }

        /** @psalm-var string[] $placeholders */
        $placeholders = $this->buildPlaceholders($expression, $params);

        return 'ARRAY[' . implode(', ', $placeholders) . ']' . $this->getTypehint($expression);
    }

    /**
     * Builds placeholders array out of $expression values.
     *
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-param ArrayExpression $expression
     */
    protected function buildPlaceholders(ExpressionInterface $expression, array &$params): array
    {
        $placeholders = [];

        /** @psalm-var mixed $value */
        $value = $expression->getValue();

        if (!is_array($value) && !$value instanceof Traversable) {
            return $placeholders;
        }

        if ($expression->getDimension() > 1) {
            /** @psalm-var ExpressionInterface|int $item */
            foreach ($value as $item) {
                $placeholders[] = $this->build($this->unnestArrayExpression($expression, $item), $params);
            }
            return $placeholders;
        }

        /** @psalm-var ExpressionInterface|int $item */
        foreach ($value as $item) {
            if ($item instanceof QueryInterface) {
                [$sql, $params] = $this->queryBuilder->build($item, $params);
                $placeholders[] = $this->buildSubqueryArray($sql, $expression);
                continue;
            }

            $item = $this->typecastValue($expression, $item);

            if ($item instanceof ExpressionInterface) {
                $placeholders[] = $this->queryBuilder->buildExpression($item, $params);
                continue;
            }

            $placeholders[] = $this->queryBuilder->bindParam($item, $params);
        }

        return $placeholders;
    }

    private function unnestArrayExpression(ArrayExpression $expression, mixed $value): ArrayExpression
    {
        return new ArrayExpression($value, $expression->getType(), $expression->getDimension() - 1);
    }

    /**
     * @return string The typecast expression based on {@see type}.
     */
    protected function getTypeHint(ArrayExpression $expression): string
    {
        $type = $expression->getType();

        if ($type === null) {
            return '';
        }

        $dimension = $expression->getDimension();
        $result = '::' . $type;
        $result .= str_repeat('[]', $dimension);

        return $result;
    }

    /**
     * Build an array expression from a sub-query SQL.
     *
     * @param string $sql The sub-query SQL.
     * @param ArrayExpression $expression The array expression.
     *
     * @return string The sub-query array expression.
     */
    protected function buildSubqueryArray(string $sql, ArrayExpression $expression): string
    {
        return 'ARRAY(' . $sql . ')' . $this->getTypeHint($expression);
    }

    /**
     * @return array|bool|ExpressionInterface|int|JsonExpression|string|null The cast value or expression.
     */
    protected function typecastValue(
        ArrayExpression $expression,
        array|bool|int|string|ExpressionInterface|null $value
    ): array|bool|int|string|JsonExpression|ExpressionInterface|null {
        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if (in_array($expression->getType(), [SchemaInterface::TYPE_JSON, SchemaInterface::TYPE_JSONB], true)) {
            return new JsonExpression($value);
        }

        return $value;
    }
}
