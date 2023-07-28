<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Expression\CompositeExpression;
use Yiisoft\Db\Pgsql\Expression\CompositeExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function implode;

/**
 * Builds expressions for {@see CompositeExpression} for PostgreSQL Server.
 */
final class CompositeExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The Method builds the raw SQL from the expression that won't be additionally escaped or quoted.
     *
     * @param ExpressionInterface $expression The expression build.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     *
     * @psalm-param CompositeExpressionInterface $expression
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        if (!$expression instanceof CompositeExpressionInterface) {
            throw new \InvalidArgumentException(
                'TypeError: ' . self::class. '::build(): Argument #1 ($expression) must be instance of '
                . CompositeExpressionInterface::class . ', instance of ' . $expression::class . ' given.'
            );
        }

        /** @psalm-var mixed $value */
        $value = $expression->getValue();

        if (empty($value)) {
            return 'NULL';
        }

        if ($value instanceof QueryInterface) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);
            return "($sql)" . $this->getTypeHint($expression);
        }

        /** @psalm-var string[] $placeholders */
        $placeholders = $this->buildPlaceholders($expression, $params);

        if (empty($placeholders)) {
            return 'NULL';
        }

        return 'ROW(' . implode(', ', $placeholders) . ')' . $this->getTypeHint($expression);
    }

    /**
     * Builds a placeholder array out of $expression values.
     *
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    private function buildPlaceholders(CompositeExpressionInterface $expression, array &$params): array
    {
        $placeholders = [];

        /** @psalm-var mixed $value */
        $value = $expression->getNormalizedValue();

        if (!is_iterable($value)) {
            return $placeholders;
        }

        $columns = (array) $expression->getColumns();

        // TODO retrieve columns from schema
        // if (empty($columns) && $expression->getType() !== null) {
        //     $columns = $schema->findColumns((string) $expression->getType());
        // }

        $columnNames = array_keys($columns);

        /**
         * @psalm-var int|string $columnName
         * @psalm-var mixed $item
         */
        foreach ($value as $columnName => $item) {
            if (is_int($columnName)) {
                $columnName = $columnNames[$columnName] ?? null;
            }

            if ($columnName !== null && isset($columns[$columnName])) {
                /** @psalm-var mixed $item */
                $item = $columns[$columnName]->dbTypecast($item);
            }

            if ($item instanceof ExpressionInterface) {
                $placeholders[] = $this->queryBuilder->buildExpression($item, $params);
            } else {
                $placeholders[] = $this->queryBuilder->bindParam($item, $params);
            }
        }

        return $placeholders;
    }

    /**
     * @return string The typecast expression based on {@see type}.
     */
    private function getTypeHint(CompositeExpressionInterface $expression): string
    {
        $type = $expression->getType();

        if ($type === null) {
            return '';
        }

        return '::' . $type;
    }
}
