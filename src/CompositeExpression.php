<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\ColumnSchemaInterface;

use function count;

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
 *
 * @template-implements ArrayAccess<string, mixed>
 * @template-implements IteratorAggregate<string>
 */
class CompositeExpression implements ExpressionInterface, ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @param ColumnSchemaInterface[]|null $columns
     * @psalm-param array<string, ColumnSchemaInterface>|null $columns
     */
    public function __construct(
        private mixed $value = [],
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
     * Sort values according to `$columns` order and fill skipped items with default values
     */
    public function getNormalizedValue(): mixed
    {
        if ($this->columns === null || !is_array($this->value)) {
            return $this->value;
        }

        if (!is_string(array_key_first($this->value))) {
            $value = $this->value;
            $columns = array_values($this->columns);

            for ($i = count($value); $i < count($columns); ++$i) {
                /** @psalm-suppress MixedAssignment */
                $value[$i] = $columns[$i]->getDefaultValue();
            }

            return $value;
        }

        $value = [];

        foreach ($this->columns as $name => $column) {
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

    /**
     * Whether an offset exists.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param int|string $offset An offset to check for.
     *
     * @throws InvalidConfigException If value is not an array.
     * @return bool Its `true` on success or `false` on failure.
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->value = $this->validateValue($this->value);
        return array_key_exists($offset, $this->value);
    }

    /**
     * Offset to retrieve.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param int|string $offset The offset to retrieve.
     *
     * @throws InvalidConfigException If value is not an array.
     * @return mixed Can return all value types.
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->value = $this->validateValue($this->value);
        return $this->value[$offset];
    }

    /**
     * Offset to set.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param int|string $offset The offset to assign the value to.
     * @param mixed $value The value to set.
     *
     * @throws InvalidConfigException If content value is not an array.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->value = $this->validateValue($this->value);
        $this->value[$offset] = $value;
    }

    /**
     * Offset to unset.
     *
     * @param int|string $offset The offset to unset.
     *
     * @throws InvalidConfigException If value is not an array.
     *
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->value = $this->validateValue($this->value);
        unset($this->value[$offset]);
    }

    /**
     * Count elements of the composite type's content.
     *
     * @link https://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     */
    public function count(): int
    {
        return count((array) $this->value);
    }

    /**
     * Retrieve an external iterator.
     *
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @throws InvalidConfigException If value is not an array.
     *
     * @return ArrayIterator An instance of an object implementing `Iterator` or `Traversable`.
     */
    public function getIterator(): ArrayIterator
    {
        $this->value = $this->validateValue($this->value);
        return new ArrayIterator($this->value);
    }

    /**
     * Validates the value of the composite expression is an array.
     *
     * @throws InvalidConfigException If value is not an array.
     */
    private function validateValue(mixed $value): array
    {
        if (!is_array($value)) {
            throw new InvalidConfigException('The CompositeExpression value must be an array.');
        }

        return $value;
    }
}
