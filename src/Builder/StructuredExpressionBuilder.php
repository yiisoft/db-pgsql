<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\StructuredExpression;
use Yiisoft\Db\Pgsql\Data\LazyArrayStructured;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\AbstractStructuredColumn;

use function implode;

/**
 * Builds expressions for {@see StructuredExpression} for PostgreSQL Server.
 */
final class StructuredExpressionBuilder extends \Yiisoft\Db\Expression\StructuredExpressionBuilder
{
    protected const LAZY_ARRAY_CLASS = LazyArrayStructured::class;

    protected function buildStringValue(string $value, StructuredExpression $expression, array &$params): string
    {
        $param = new Param($value, DataType::STRING);

        return $this->queryBuilder->bindParam($param, $params) . $this->getTypeHint($expression);
    }

    protected function buildSubquery(QueryInterface $query, StructuredExpression $expression, array &$params): string
    {
        [$sql, $params] = $this->queryBuilder->build($query, $params);

        return "($sql)" . $this->getTypeHint($expression);
    }

    protected function buildValue(array|object $value, StructuredExpression $expression, array &$params): string
    {
        $value = $this->prepareValues($value, $expression);
        /** @psalm-var string[] $placeholders */
        $placeholders = $this->buildPlaceholders($value, $expression, $params);

        return 'ROW(' . implode(',', $placeholders) . ')' . $this->getTypeHint($expression);
    }

    /**
     * Builds a placeholder array out of $expression value.
     *
     * @param array $value The expression value.
     * @param StructuredExpression $expression The structured expression.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    private function buildPlaceholders(array $value, StructuredExpression $expression, array &$params): array
    {
        $type = $expression->getType();
        $columns = $type instanceof AbstractStructuredColumn ? $type->getColumns() : [];

        $placeholders = [];

        /** @psalm-var int|string $columnName */
        foreach ($value as $columnName => $item) {
            if (isset($columns[$columnName])) {
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
     * Returns the type hint expression based on type.
     */
    private function getTypeHint(StructuredExpression $expression): string
    {
        $type = $expression->getType();

        if ($type instanceof AbstractStructuredColumn) {
            $type = $type->getDbType();
        }

        if (empty($type)) {
            return '';
        }

        return '::' . $type;
    }
}
