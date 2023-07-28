<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression;

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
     * Sorted values according to order of the composite type columns and filled with default values skipped items.
     */
    public function getNormalizedValue(): mixed
    {
        if ($this->columns === null || !is_array($this->value)) {
            return $this->value;
        }

        $value = [];
        $columns = $this->columns;

        if (is_int(array_key_first($this->value))) {
            $columns = array_values($this->columns);
        }

        foreach ($columns as $name => $column) {
            if (array_key_exists($name, $this->value)) {
                /** @psalm-suppress MixedAssignment */
                $value[$name] = $this->value[$name];
            } else {
                /** @psalm-suppress MixedAssignment */
                $value[$name] = $column->getDefaultValue();
            }
        }

        return $value;
    }
}
