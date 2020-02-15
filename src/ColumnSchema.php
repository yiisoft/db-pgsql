<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Expressions\ArrayExpression;
use Yiisoft\Db\Expressions\ExpressionInterface;
use Yiisoft\Db\Expressions\JsonExpression;

/**
 * Class ColumnSchema for Postgres SQL database.
 */
class ColumnSchema extends \Yiisoft\Db\Schemas\ColumnSchema
{
    /**
     * @var int the dimension of array. Defaults to 0, means this column is not an array.
     */
    public int $dimension = 0;

    /**
     * @var string name of associated sequence if column is auto-incremental.
     */
    public ?string $sequenceName = null;

    /**
     * {@inheritdoc}
     */
    public function dbTypecast($value)
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if ($this->dimension > 0) {
            return $this->disableArraySupport
                ? (string) $value
                : new ArrayExpression($value, $this->dbType, $this->dimension);
        }

        if (\in_array($this->dbType, [Schema::TYPE_JSON, Schema::TYPE_JSONB], true)) {
            return new JsonExpression($value, $this->dbType);
        }

        return $this->typecast($value);
    }

    /**
     * {@inheritdoc}
     */
    public function phpTypecast($value)
    {
        if ($this->dimension > 0) {
            if (!\is_array($value)) {
                $value = $this->getArrayParser()->parse($value);
            }
            if (\is_array($value)) {
                array_walk_recursive($value, function (&$val, $key) {
                    $val = $this->phpTypecastValue($val);
                });
            } elseif ($value === null) {
                return null;
            }

            return $value;
        }

        return $this->phpTypecastValue($value);
    }

    /**
     * Casts $value after retrieving from the DBMS to PHP representation.
     *
     * @param string|null $value
     *
     * @return bool|mixed|null
     */
    protected function phpTypecastValue($value)
    {
        if ($value === null) {
            return null;
        }

        switch ($this->type) {
            case Schema::TYPE_BOOLEAN:
                $value = \is_string($value) ? strtolower($value) : $value;

                switch ($value) {
                    case 't':
                    case 'true':
                        return true;
                    case 'f':
                    case 'false':
                        return false;
                }

                return (bool) $value;
            case Schema::TYPE_JSON:
                return \json_decode($value, true);
        }

        return parent::phpTypecast($value);
    }

    /**
     * Creates instance of ArrayParser
     *
     * @return ArrayParser
     */
    protected function getArrayParser(): ArrayParser
    {
        static $parser = null;

        if ($parser === null) {
            $parser = new ArrayParser();
        }

        return $parser;
    }
}
