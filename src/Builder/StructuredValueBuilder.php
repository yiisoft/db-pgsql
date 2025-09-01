<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Value\Builder\AbstractStructuredValueBuilder;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Expression\Value\StructuredValue;
use Yiisoft\Db\Pgsql\Data\StructuredLazyArray;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\AbstractStructuredColumn;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;

use function implode;

/**
 * Builds expressions for {@see StructuredValue} for PostgreSQL Server.
 */
final class StructuredValueBuilder extends AbstractStructuredValueBuilder
{
    protected function buildStringValue(string $value, StructuredValue $expression, array &$params): string
    {
        $param = new Param($value, DataType::STRING);

        return $this->queryBuilder->bindParam($param, $params) . $this->getTypeHint($expression);
    }

    protected function buildSubquery(QueryInterface $query, StructuredValue $expression, array &$params): string
    {
        [$sql, $params] = $this->queryBuilder->build($query, $params);

        return "($sql)" . $this->getTypeHint($expression);
    }

    protected function buildValue(array|object $value, StructuredValue $expression, array &$params): string
    {
        $value = $this->prepareValues($value, $expression);
        /** @psalm-var string[] $placeholders */
        $placeholders = $this->buildPlaceholders($value, $expression, $params);

        return 'ROW(' . implode(',', $placeholders) . ')' . $this->getTypeHint($expression);
    }

    protected function getLazyArrayValue(LazyArrayInterface $value): array|string
    {
        if ($value instanceof StructuredLazyArray) {
            return $value->getRawValue();
        }

        return $value->getValue();
    }

    /**
     * Builds a placeholder array out of $expression value.
     *
     * @param array $value The expression value.
     * @param StructuredValue $expression The structured expression.
     * @param array $params The binding parameters.
     */
    private function buildPlaceholders(array $value, StructuredValue $expression, array &$params): array
    {
        $type = $expression->type;
        $queryBuilder = $this->queryBuilder;
        $columns = $type instanceof AbstractStructuredColumn && $queryBuilder->isTypecastingEnabled()
            ? $type->getColumns()
            : [];

        $placeholders = [];

        /** @psalm-var int|string $columnName */
        foreach ($value as $columnName => $item) {
            if (isset($columns[$columnName])) {
                $item = $columns[$columnName]->dbTypecast($item);
            }

            $placeholders[] = $queryBuilder->buildValue($item, $params);
        }

        return $placeholders;
    }

    /**
     * Returns the type hint expression based on type.
     */
    private function getTypeHint(StructuredValue $expression): string
    {
        $type = $expression->type;

        if ($type instanceof AbstractStructuredColumn) {
            $type = $type->getDbType();
        }

        if (empty($type)) {
            return '';
        }

        return '::' . $type;
    }
}
