<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Traversable;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\ArrayParser;
use Yiisoft\Db\Pgsql\Schema;
use Yiisoft\Db\Schema\Column\AbstractColumnSchema;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\Column\DoubleColumnSchema;
use Yiisoft\Db\Schema\Column\JsonColumnSchema;
use Yiisoft\Db\Schema\Column\StringColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;

use function array_map;
use function array_walk_recursive;
use function is_array;
use function is_iterable;
use function is_string;
use function iterator_to_array;

final class ArrayColumnSchema extends AbstractColumnSchema
{
    /**
     * @var ColumnSchemaInterface|null The column of an array item.
     */
    private ColumnSchemaInterface|null $column = null;

    /**
     * @var int The dimension of array, must be greater than 0.
     */
    private int $dimension = 1;

    public function __construct(
        string $type = Schema::TYPE_ARRAY,
        string|null $phpType = SchemaInterface::PHP_TYPE_ARRAY,
    ) {
        parent::__construct($type, $phpType);
    }

    /**
     * Set column of an array item.
     */
    public function column(ColumnSchemaInterface|null $column): static
    {
        $this->column = $column;
        return $this;
    }

    /**
     * @return ColumnSchemaInterface the column of an array item.
     */
    public function getColumn(): ColumnSchemaInterface
    {
        if ($this->column === null) {
            $type = $this->getType();
            $phpType = $this->getPhpType();

            $this->column = match ($type) {
                Schema::TYPE_BIT => new BitColumnSchema($type, $phpType),
                Schema::TYPE_STRUCTURED => new StructuredColumnSchema($type, $phpType),
                SchemaInterface::TYPE_BIGINT => PHP_INT_SIZE !== 8
                    ? new BigIntColumnSchema($type, $phpType)
                    : new IntegerColumnSchema($type, $phpType),
                default => match ($phpType) {
                    SchemaInterface::PHP_TYPE_INTEGER => new IntegerColumnSchema($type, $phpType),
                    SchemaInterface::PHP_TYPE_DOUBLE => new DoubleColumnSchema($type, $phpType),
                    SchemaInterface::PHP_TYPE_BOOLEAN => new BooleanColumnSchema($type, $phpType),
                    SchemaInterface::PHP_TYPE_RESOURCE => new BinaryColumnSchema($type, $phpType),
                    SchemaInterface::PHP_TYPE_ARRAY => new JsonColumnSchema($type, $phpType),
                    default => new StringColumnSchema($type, $phpType),
                },
            };

            $this->column->dbType($this->getDbType());
            $this->column->enumValues($this->getEnumValues());
            $this->column->precision($this->getPrecision());
            $this->column->scale($this->getScale());
            $this->column->size($this->getSize());
        }

        return $this->column;
    }

    /**
     * Set dimension of an array, must be greater than 0.
     */
    public function dimension(int $dimension): static
    {
        $this->dimension = $dimension;
        return $this;
    }

    /**
     * @return int the dimension of the array.
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    public function dbTypecast(mixed $value): ExpressionInterface|null
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        if ($this->dimension === 1 && is_array($value)) {
            $value = array_map([$this->getColumn(), 'dbTypecast'], $value);
        } else {
            $value = $this->dbTypecastArray($value, $this->dimension);
        }

        return new ArrayExpression($value, $this->getDbType(), $this->dimension);
    }

    public function phpTypecast(mixed $value): array|null
    {
        if (is_string($value)) {
            $value = (new ArrayParser())->parse($value);
        }

        if (!is_array($value)) {
            return null;
        }

        if ($this->getType() === SchemaInterface::TYPE_STRING) {
            return $value;
        }

        $column = $this->getColumn();

        if ($this->dimension === 1 && $column->getType() !== SchemaInterface::TYPE_JSON) {
            return array_map([$column, 'phpTypecast'], $value);
        }

        array_walk_recursive($value, function (string|null &$val) use ($column): void {
            $val = $column->phpTypecast($val);
        });

        return $value;
    }

    /**
     * Recursively converts array values for use in a db query.
     *
     * @param mixed $value The array or iterable object.
     * @param int $dimension The array dimension. Should be more than 0.
     *
     * @return array|null Converted values.
     */
    private function dbTypecastArray(mixed $value, int $dimension): array|null
    {
        if ($value === null) {
            return null;
        }

        if (!is_iterable($value)) {
            return [];
        }

        if ($dimension <= 1) {
            return array_map(
                [$this->getColumn(), 'dbTypecast'],
                $value instanceof Traversable
                    ? iterator_to_array($value, false)
                    : $value
            );
        }

        $items = [];

        foreach ($value as $val) {
            $items[] = $this->dbTypecastArray($val, $dimension - 1);
        }

        return $items;
    }
}
