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
use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Query\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Schema as AbstractSchema;

use function get_class;
use function implode;
use function in_array;
use function is_array;
use function str_repeat;

/**
 * The class ArrayExpressionBuilder builds {@see ArrayExpression} for PostgresSQL DBMS.
 */
final class ArrayExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Method builds the raw SQL from the $expression that will not be additionally escaped or quoted.
     *
     * @param ExpressionInterface $expression the expression to be built.
     * @param array $params the binding parameters.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        /**
         *  @var ArrayExpression $expression
         *  @var array|mixed|QueryInterface $value
         */
        $value = $expression->getValue();

        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof QueryInterface) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);
            return $this->buildSubqueryArray($sql, $expression);
        }

        /** @psalm-var string[] */
        $placeholders = $this->buildPlaceholders($expression, $params);

        return 'ARRAY[' . implode(', ', $placeholders) . ']' . $this->getTypehint($expression);
    }

    /**
     * Builds placeholders array out of $expression values.
     *
     * @param ExpressionInterface $expression
     * @param array $params the binding parameters.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return array
     */
    protected function buildPlaceholders(ExpressionInterface $expression, array &$params): array
    {
        $placeholders = [];

        /**
         *  @var ArrayExpression $expression
         *  @var mixed $value
         */
        $value = $expression->getValue();

        if (!is_array($value) && !$value instanceof Traversable) {
            return $placeholders;
        }

        if ($expression->getDimension() > 1) {
            /** @var ExpressionInterface|int $item */
            foreach ($value as $item) {
                $placeholders[] = $this->build($this->unnestArrayExpression($expression, $item), $params);
            }
            return $placeholders;
        }

        /** @var ExpressionInterface|int $item */
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

    /**
     * @param ArrayExpression $expression
     * @param array|mixed|QueryInterface $value
     *
     * @return ArrayExpression
     */
    private function unnestArrayExpression(ArrayExpression $expression, mixed $value): ArrayExpression
    {
        $expressionClass = get_class($expression);

        return new $expressionClass($value, $expression->getType(), $expression->getDimension() - 1);
    }

    /**
     * @param ArrayExpression $expression
     *
     * @return string the typecast expression based on {@see type}.
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
     * Build an array expression from a subquery SQL.
     *
     * @param string $sql the subquery SQL.
     * @param ArrayExpression $expression
     *
     * @return string the subquery array expression.
     */
    protected function buildSubqueryArray(string $sql, ArrayExpression $expression): string
    {
        return 'ARRAY(' . $sql . ')' . $this->getTypeHint($expression);
    }

    /**
     * Casts $value to use in $expression.
     *
     * @param ArrayExpression $expression
     * @param array|bool|ExpressionInterface|int|string|null $value
     *
     * @return array|bool|ExpressionInterface|int|JsonExpression|string|null
     */
    protected function typecastValue(
        ArrayExpression $expression,
        array|bool|int|string|ExpressionInterface|null $value
    ): array|bool|int|string|JsonExpression|ExpressionInterface|null {
        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if (in_array($expression->getType(), [AbstractSchema::TYPE_JSON, Schema::TYPE_JSONB], true)) {
            return new JsonExpression($value);
        }

        return $value;
    }
}
