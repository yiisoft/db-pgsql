<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use JsonException;
use PDO;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Schema\AbstractColumnSchema;
use Yiisoft\Db\Schema\SchemaInterface;
use function array_walk_recursive;
use function bindec;
use function decbin;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function str_pad;

/**
 * Represents the metadata of a column in a database table for PostgreSQL Server.
 *
 * It provides information about the column's name, type, size, precision, and other details.
 *
 * It's used to store and retrieve metadata about a column in a database table and is typically used in conjunction with
 * the {@see TableSchema}, which represents the metadata of a database table as a whole.
 *
 * The following code shows how to use:
 *
 * ```php
 * use Yiisoft\Db\Pgsql\ColumnSchema;
 *
 * $column = new ColumnSchema();
 * $column->name('id');
 * $column->allowNull(false);
 * $column->dbType('integer');
 * $column->phpType('integer');
 * $column->type('integer');
 * $column->defaultValue(0);
 * $column->autoIncrement(true);
 * $column->primaryKey(true);
 * ```
 */
final class ColumnSchema extends AbstractColumnSchema
{
    /**
     * @var int The dimension of array. Defaults to 0, means this column isn't an array.
     */
    private int $dimension = 0;

    /**
     * @var string|null Name of an associated sequence if column is auto incremental.
     */
    private string|null $sequenceName = null;

    /**
     * Converts the input value according to {@see type} and {@see dbType} for use in a db query.
     *
     * If the value is null or an {@see Expression}, it won't be converted.
     *
     * @param mixed $value input value
     *
     * @return mixed Converted value.
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        if ($this->dimension > 0) {
            return new ArrayExpression($value, $this->getDbType(), $this->dimension);
        }

        return match ($this->getType()) {
            SchemaInterface::TYPE_JSON => new JsonExpression($value, $this->getDbType()),

            SchemaInterface::TYPE_BINARY => is_string($value)
                ? new Param($value, PDO::PARAM_LOB) // explicitly setup PDO param type for binary column
                : $this->typecast($value),

            Schema::TYPE_BIT => is_int($value)
                ? str_pad(decbin($value), (int) $this->getSize(), '0', STR_PAD_LEFT)
                : (string) $value,

            default => $this->typecast($value),
        };
    }

    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is null or an {@see Expression}, it won't be converted.
     *
     * @param mixed $value The input value
     *
     * @throws JsonException
     *
     * @return mixed The converted value
     */
    public function phpTypecast(mixed $value): mixed
    {
        if (is_string($value) && $rangeParser = $this->getRangeParser()) {
            return $rangeParser->parse($value);
        }

        if (is_string($value) && $multiRangeParser = $this->getMultiRangeParser()) {
            return $multiRangeParser->parse($value);
        }

        if ($this->dimension > 0) {
            if (is_string($value)) {
                $value = $this->getArrayParser()->parse($value);
            }

            if (!is_array($value)) {
                return null;
            }

            array_walk_recursive($value, function (mixed &$val) {
                /** @psalm-var mixed $val */
                $val = $this->phpTypecastValue($val);
            });

            return $value;
        }

        return $this->phpTypecastValue($value);
    }

    /**
     * Casts $value after retrieving from the DBMS to PHP representation.
     *
     * @throws JsonException
     */
    private function phpTypecastValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this->getType()) {
            Schema::TYPE_BIT => is_string($value) ? bindec($value) : $value,

            SchemaInterface::TYPE_BOOLEAN => $value && $value !== 'f',

            SchemaInterface::TYPE_JSON
                => json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR),

            default => parent::phpTypecast($value),
        };
    }

    /**
     * Creates instance of ArrayParser.
     */
    private function getArrayParser(): ArrayParser
    {
        return new ArrayParser();
    }

    /**
     * @psalm-suppress PossiblyNullArgument
     */
    private function getRangeParser(): ?RangeParser
    {
        if ($this->getDbType() !== null && RangeParser::isAllowedType($this->getDbType())) {
            return new RangeParser($this->getDbType());
        }

        return null;
    }

    /**
     * @psalm-suppress PossiblyNullArgument
     */
    private function getMultiRangeParser(): ?MultiRangeParser
    {
        if ($this->getDbType() !== null && MultiRangeParser::isAllowedType($this->getDbType())) {
            return new MultiRangeParser($this->getDbType());
        }

        return null;
    }

    /**
     * @return int Get the dimension of the array.
     *
     * Defaults to 0, means this column isn't an array.
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    /**
     * @return string|null name of an associated sequence if column is auto incremental.
     */
    public function getSequenceName(): string|null
    {
        return $this->sequenceName;
    }

    /**
     * Set dimension of an array.
     *
     * Defaults to 0, means this column isn't an array.
     */
    public function dimension(int $dimension): void
    {
        $this->dimension = $dimension;
    }

    /**
     * Set the name of an associated sequence if a column is auto incremental.
     */
    public function sequenceName(string|null $sequenceName): void
    {
        $this->sequenceName = $sequenceName;
    }
}
