<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use JsonException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Schema\ColumnSchema as AbstractColumnSchema;
use Yiisoft\Db\Schema\Schema as AbstractSchema;
use function array_walk_recursive;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_string;
use function json_decode;
use function str_ends_with;
use function strtolower;
use const JSON_THROW_ON_ERROR;

/**
 * The class ColumnSchema for PostgreSQL database.
 */
final class ColumnSchema extends AbstractColumnSchema
{
    /**
     * @var string|null real DB type with [*] on end for array
     */
    private ?string $rawDbType = null;

    /**
     * @var string|null php type from pgsql array values
     */
    private ?string $phpArrayType = null;

    /**
     * @var int the dimension of array. Defaults to 0, means this column is not an array.
     */
    private int $dimension = 0;

    /**
     * @var string|null name of associated sequence if column is auto-incremental.
     */
    private ?string $sequenceName = null;

    public function rawDbType(?string $value): void
    {
        $this->rawDbType = $value;
    }

    public function phpArrayType(string $type): void
    {
        $this->phpArrayType = $type;
    }

    /**
     * Return type of PgSql array values
     *
     * @return string|null
     */
    public function getPhpArrayType(): ?string
    {
        return $this->phpArrayType;
    }

    /**
     * Check this column is PgSql array type or not
     *
     * @return bool
     */
    public function isPgSqlArray(): bool
    {
        if ($this->rawDbType) {
            return str_ends_with($this->rawDbType, ']');
        }

        return $this->dimension > 0;
    }

    /**
     * Converts the input value according to {@see type} and {@see dbType} for use in a db query.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     *
     * @param mixed $value input value
     *
     * @return mixed converted value. This may also be an array containing the value as the first element and the PDO
     * type as the second element.
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if ($this->dimension > 0) {
            return new ArrayExpression($value, $this->getDbType(), $this->dimension);
        }

        if (in_array($this->getDbType(), [AbstractSchema::TYPE_JSON, Schema::TYPE_JSONB], true)) {
            return new JsonExpression($value, $this->getDbType());
        }

        return $this->typecast($value);
    }

    /**
     * Converts the input value according to {@see phpType} after retrieval from the database.
     *
     * If the value is null or an {@see Expression}, it will not be converted.
     *
     * @param mixed $value input value
     *
     *@throws JsonException
     *
     * @return mixed converted value
     */
    public function phpTypecast(mixed $value): mixed
    {
        if ($this->isPgSqlArray()) {
            if (is_string($value) || $value === null) {
                $value = $this->getArrayParser()->parse($value);
            }

            if (is_array($value)) {
                array_walk_recursive($value, function (?string &$val) {
                    /** @var mixed */
                    $val = $this->phpTypecastValue($val);
                });
            } else {
                return null;
            }

            return $value;
        }

        return $this->phpTypecastValue($value);
    }

    /**
     * Cast mixed value to PHP boolean type
     *
     * @param mixed $value
     * @return bool|null
     */
    private static function castBooleanValue(mixed $value): ?bool
    {
        if (is_bool($value) || $value === null) {
            return $value;
        }
        /** @var mixed $value */
        $value = is_string($value) ? strtolower($value) : $value;

        return match ($value) {
            't', 'true' => true,
            'f', 'false' => false,
            default => (bool) $value,
        };
    }

    /**
     * Casts $value after retrieving from the DBMS to PHP representation.
     *
     * @param mixed $value
     *
     * @throws JsonException
     *
     * @return mixed
     */
    protected function phpTypecastValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($this->isPgSqlArray()) {
            return match ($this->phpArrayType) {
                AbstractSchema::PHP_TYPE_INTEGER => is_int($value) ? $value : (int) $value,
                AbstractSchema::PHP_TYPE_DOUBLE => is_float($value) ? $value : (float) $value,
                AbstractSchema::PHP_TYPE_BOOLEAN => self::castBooleanValue($value),
                AbstractSchema::PHP_TYPE_ARRAY => json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR),
                default => $value,
            };
        }

        return match ($this->getType()) {
            AbstractSchema::TYPE_BOOLEAN => self::castBooleanValue($value),
            AbstractSchema::TYPE_JSON => json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR),
            default => parent::phpTypecast($value),
        };
    }

    /**
     * Creates instance of ArrayParser.
     *
     * @return ArrayParser
     */
    protected function getArrayParser(): ArrayParser
    {
        return new ArrayParser();
    }

    /**
     * @return int Get the dimension of array. Defaults to 0, means this column is not an array.
     */
    public function getDimension(): int
    {
        return $this->dimension;
    }

    /**
     * @return string|null name of associated sequence if column is auto-incremental.
     */
    public function getSequenceName(): ?string
    {
        return $this->sequenceName;
    }

    /**
     * Set dimension of array. Defaults to 0, means this column is not an array.
     */
    public function dimension(int $dimension): void
    {
        $this->dimension = $dimension;
    }

    /**
     * Set name of associated sequence if column is auto-incremental.
     */
    public function sequenceName(?string $sequenceName): void
    {
        $this->sequenceName = $sequenceName;
    }
}
