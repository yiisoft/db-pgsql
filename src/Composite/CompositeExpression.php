<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Composite;

use Traversable;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\ColumnSchemaInterface;

/**
 * Represents a composite SQL expression.
 *
 * For example:
 *
 * ```php
 * new CompositeExpression(['price' => 10, 'currency_code' => 'USD']);
 * ```
 *
 * Will be encoded to `ROW(10, USD)`
 */
class CompositeExpression implements ExpressionInterface
{
    /**
     * @param ColumnSchemaInterface[]|null $columns
     * @psalm-param array<string, ColumnSchemaInterface>|null $columns
     */
    public function __construct(
        private mixed $value,
        private string|null $type = null,
        private array|null $columns = null,
    ) {
    }

    /**
     * The composite type name.
     *
     * Defaults to `null` which means the type isn't explicitly specified.
     *
     * Note that in the case where a type isn't specified explicitly and DBMS can't guess it from the context, SQL error
     * will be raised.
     */
    public function getType(): string|null
    {
        return $this->type;
    }

    /**
     * @return ColumnSchemaInterface[]|null
     */
    public function getColumns(): array|null
    {
        return $this->columns;
    }

    /**
     * The composite type's content. It can be represented as an associative array of the composite type's column names
     * and values.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Sorted values according to order of the composite type columns, indexed keys replaced with column names,
     * skipped items filled with default values, extra items removed.
     */
    public function getNormalizedValue(): mixed
    {
        if (empty($this->columns) || !is_iterable($this->value)) {
            return $this->value;
        }

        $normalized = [];
        $value = $this->value;
        $columnsNames = array_keys($this->columns);

        if ($value instanceof Traversable) {
            $value = iterator_to_array($value);
        }

        foreach ($columnsNames as $i => $columnsName) {
            if (array_key_exists($columnsName, $value)) {
                /** @psalm-suppress MixedAssignment */
                $normalized[$columnsName] = $value[$columnsName];
            } elseif (array_key_exists($i, $value)) {
                /** @psalm-suppress MixedAssignment */
                $normalized[$columnsName] = $value[$i];
            } else {
                /** @psalm-suppress MixedAssignment */
                $normalized[$columnsName] = $this->columns[$columnsName]->getDefaultValue();
            }
        }

        return $normalized;
    }
}
