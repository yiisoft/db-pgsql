<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

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

final class ArrayColumnSchema extends AbstractColumnSchema
{
    private ColumnSchemaInterface|null $column = null;

    /**
     * @var int The dimension of array, must be greater than 0.
     */
    private int $dimension = 1;

    /**
     * Set dimension of an array, must be greater than 0.
     */
    public function dimension(int $dimension): void
    {
        $this->dimension = $dimension;
    }

    private function getColumn(): ColumnSchemaInterface
    {
        if ($this->column === null) {
            if ($this->getType() === Schema::TYPE_BIT) {
                $this->column = new BitColumnSchema($this->getName());
                $this->column->size($this->getSize());
            } else {
                $this->column = match ($this->getPhpType()) {
                    SchemaInterface::PHP_TYPE_INTEGER => new IntegerColumnSchema($this->getName()),
                    SchemaInterface::PHP_TYPE_DOUBLE => new DoubleColumnSchema($this->getName()),
                    SchemaInterface::PHP_TYPE_BOOLEAN => new BooleanColumnSchema($this->getName()),
                    SchemaInterface::PHP_TYPE_RESOURCE => new BinaryColumnSchema($this->getName()),
                    SchemaInterface::PHP_TYPE_ARRAY => new JsonColumnSchema($this->getName()),
                    default => new StringColumnSchema($this->getName()),
                };
            }
        }

        return $this->column;
    }

    /**
     * @return int Get the dimension of the array.
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

    /**
     * Recursively converts array values for use in a db query.
     *
     * @param mixed $value The array or iterable object.
     * @param int $dimension The array dimension. Should be more than 0.
     *
     * @return array Converted values.
     */
    private function dbTypecastArray(mixed $value, int $dimension): array
    {
        if (!is_iterable($value)) {
            return [];
        }

        $items = [];
        $column = $this->getColumn();

        /** @psalm-var mixed $val */
        foreach ($value as $val) {
            if ($dimension > 1) {
                $items[] = $this->dbTypecastArray($val, $dimension - 1);
            } else {
                /** @psalm-var mixed */
                $items[] = $column->dbTypecast($val);
            }
        }

        return $items;
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

        if ($this->dimension === 1 && $this->getType() !== SchemaInterface::TYPE_JSON) {
            return array_map([$column, 'phpTypecast'], $value);
        }

        array_walk_recursive($value, function (string|null &$val) use ($column): void {
            /** @psalm-var mixed $val */
            $val = $column->phpTypecast($val);
        });

        return $value;
    }
}
