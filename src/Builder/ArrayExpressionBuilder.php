<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\AbstractArrayExpressionBuilder;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Data\LazyArray;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\AbstractArrayColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;

use function array_map;
use function implode;
use function is_array;
use function iterator_to_array;
use function str_repeat;

/**
 * Builds expressions for {@see ArrayExpression} for PostgreSQL Server.
 */
final class ArrayExpressionBuilder extends AbstractArrayExpressionBuilder
{
    protected function buildStringValue(string $value, ArrayExpression $expression, array &$params): string
    {
        $param = new Param($value, DataType::STRING);

        $column = $this->getColumn($expression);
        $dbType = $this->getColumnDbType($column);

        $typeHint = $this->getTypeHint($dbType, $column?->getDimension() ?? 1);

        return $this->queryBuilder->bindParam($param, $params) . $typeHint;
    }

    protected function buildSubquery(QueryInterface $query, ArrayExpression $expression, array &$params): string
    {
        $column = $this->getColumn($expression);
        $dbType = $this->getColumnDbType($column);

        return $this->buildNestedSubquery($query, $dbType, $column?->getDimension() ?? 1, $params);
    }

    protected function buildValue(iterable $value, ArrayExpression $expression, array &$params): string
    {
        $column = $this->getColumn($expression);
        $dbType = $this->getColumnDbType($column);

        return $this->buildNestedValue($value, $dbType, $column?->getColumn(), $column?->getDimension() ?? 1, $params);
    }

    protected function getLazyArrayValue(LazyArrayInterface $value): array|string
    {
        if ($value instanceof LazyArray) {
            return $value->getRawValue();
        }

        return $value->getValue();
    }

    /**
     * @param string[] $placeholders
     */
    private function buildNestedArray(array $placeholders, string $dbType, int $dimension): string
    {
        $typeHint = $this->getTypeHint($dbType, $dimension);

        return 'ARRAY[' . implode(',', $placeholders) . ']' . $typeHint;
    }

    private function buildNestedSubquery(QueryInterface $query, string $dbType, int $dimension, array &$params): string
    {
        [$sql, $params] = $this->queryBuilder->build($query, $params);

        return "ARRAY($sql)" . $this->getTypeHint($dbType, $dimension);
    }

    private function buildNestedValue(iterable $value, string $dbType, ColumnInterface|null $column, int $dimension, array &$params): string
    {
        $placeholders = [];
        $queryBuilder = $this->queryBuilder;
        $isTypecastingEnabled = $column !== null && $queryBuilder->isTypecastingEnabled();

        if ($dimension > 1) {
            /** @var iterable|null $item */
            foreach ($value as $item) {
                if ($item === null) {
                    $placeholders[] = 'NULL';
                } elseif ($item instanceof ExpressionInterface) {
                    $placeholders[] = $item instanceof QueryInterface
                        ? $this->buildNestedSubquery($item, $dbType, $dimension - 1, $params)
                        : $queryBuilder->buildExpression($item, $params);
                } else {
                    $placeholders[] = $this->buildNestedValue($item, $dbType, $column, $dimension - 1, $params);
                }
            }
        } else {
            if ($isTypecastingEnabled) {
                $value = $this->dbTypecast($value, $column);
            }

            foreach ($value as $item) {
                $placeholders[] = $queryBuilder->buildValue($item, $params);
            }
        }

        return $this->buildNestedArray($placeholders, $dbType, $dimension);
    }

    private function getColumn(ArrayExpression $expression): AbstractArrayColumn|null
    {
        $type = $expression->getType();

        if ($type === null || $type instanceof AbstractArrayColumn) {
            return $type;
        }

        $info = [];

        if ($type instanceof ColumnInterface) {
            $info['column'] = $type;
        } elseif ($type !== ColumnType::ARRAY) {
            $column = $this
                ->queryBuilder
                ->getColumnFactory()
                ->fromDefinition($type);

            if ($column instanceof AbstractArrayColumn) {
                return $column;
            }

            $info['column'] = $column;
        }

        /** @var AbstractArrayColumn */
        return $this
            ->queryBuilder
            ->getColumnFactory()
            ->fromType(ColumnType::ARRAY, $info);
    }

    private function getColumnDbType(AbstractArrayColumn|null $column): string
    {
        if ($column === null) {
            return '';
        }

        return rtrim($this->queryBuilder->getColumnDefinitionBuilder()->buildType($column), '[]');
    }

    /**
     * Return the type hint expression based on type and dimension.
     */
    private function getTypeHint(string $dbType, int $dimension): string
    {
        if (empty($dbType)) {
            return '';
        }

        return '::' . $dbType . str_repeat('[]', $dimension);
    }

    /**
     * Converts array values for use in a db query.
     *
     * @param iterable $value The array or iterable object.
     * @param ColumnInterface $column The column instance to typecast values.
     *
     * @return iterable Converted values.
     */
    private function dbTypecast(iterable $value, ColumnInterface $column): iterable
    {
        if (!is_array($value)) {
            $value = iterator_to_array($value, false);
        }

        return array_map($column->dbTypecast(...), $value);
    }
}
