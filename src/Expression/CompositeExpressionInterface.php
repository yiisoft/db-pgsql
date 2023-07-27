<?php

namespace Yiisoft\Db\Pgsql\Expression;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\ColumnSchemaInterface;

interface CompositeExpressionInterface extends ExpressionInterface
{
    /**
     * The composite type name.
     *
     * Defaults to `null` which means the type isn't explicitly specified.
     *
     * Note that in the case where a type isn't specified explicitly and DBMS can't guess it from the context, SQL error
     * will be raised.
     */
    public function getType(): string|null;

    /**
     * @return ColumnSchemaInterface[]|null
     */
    public function getColumns(): array|null;

    /**
     * The composite type's content. It can be represented as an associative array of the composite type's column names
     * and values or as a list of the composite type column's values.
     */
    public function getValue(): mixed;

    /**
     * Sorted values according to order of the composite type columns and filled with default values skipped items.
     */
    public function getNormalizedValue(): mixed;
}
