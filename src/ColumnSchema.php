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
use function is_string;
use function json_decode;
use function strtolower;

/**
 * The class ColumnSchema for PostgreSQL database.
 */
final class ColumnSchema extends AbstractColumnSchema
{
    /**
     * @var int the dimension of array. Defaults to 0, means this column is not an array.
     */
    private int $dimension = 0;

    /**
     * @var string|null name of associated sequence if column is auto-incremental.
     */
    private ?string $sequenceName = null;

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
        if ($this->dimension > 0) {
            if (!is_array($value) && (is_string($value) || $value === null)) {
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
     * Casts $value after retrieving from the DBMS to PHP representation.
     *
     *
     * @throws JsonException
     *
     */
    protected function phpTypecastValue(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        switch ($this->getType()) {
            case AbstractSchema::TYPE_BOOLEAN:
                /** @var mixed */
                $value = is_string($value) ? strtolower($value) : $value;

                return match ($value) {
                    't', 'true' => true,
                    'f', 'false' => false,
                    default => (bool)$value,
                };
            case AbstractSchema::TYPE_JSON:
                return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        }

        return parent::phpTypecast($value);
    }

    /**
     * Creates instance of ArrayParser.
     *
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
