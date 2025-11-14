<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Constant\PgsqlColumnType;
use Yiisoft\Db\Pgsql\Expression\DateRangeValue;
use Yiisoft\Db\Pgsql\Expression\Int4RangeValue;
use Yiisoft\Db\Pgsql\Expression\Int8RangeValue;
use Yiisoft\Db\Pgsql\Expression\NumRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsRangeValue;
use Yiisoft\Db\Pgsql\Expression\TsTzRangeValue;
use Yiisoft\Db\Schema\Column\AbstractColumn;

use function gettype;
use function is_array;
use function is_string;
use function sprintf;

final class RangeColumn extends AbstractColumn
{
    protected const DEFAULT_TYPE = PgsqlColumnType::RANGE;

    public function dbTypecast(mixed $value): mixed
    {
        if ($value === null || $value instanceof ExpressionInterface) {
            return $value;
        }

        if (!is_array($value)) {
            $this->throwWrongTypeException(gettype($value));
        }

        if (array_keys($value) !== [0, 1]) {
            throw new InvalidArgumentException(
                'Value of range should be an array with two elements: lower and upper bounds.',
            );
        }

        $dbType = $this->getDbType();
        [$lower, $upper] = $value;

        $column = match ($dbType) {
            'int4range' => ColumnBuilder::integer(),
            'int8range' => ColumnBuilder::bigint(),
            'numrange' => ColumnBuilder::decimal(),
            'tsrange' => ColumnBuilder::datetime(),
            'tstzrange' => ColumnBuilder::datetimeWithTimezone(),
            'daterange' => ColumnBuilder::date(),
            default => $this->throwNotSupportedDbType($dbType),
        };

        return '['
            . $this->dbTypecastBound($column->dbTypecast($lower))
            . ','
            . $this->dbTypecastBound($column->dbTypecast($upper))
            . ')';
    }

    public function phpTypecast(mixed $value): mixed
    {
        /**
         * @var string|null $value We expect `phpTypecast()` to only receive the value that the database returns, which
         * in this case is `null` or a `string`. To avoid extra checks.
         */

        if ($value === null || $value === 'empty') {
            return null;
        }

        if (!preg_match('/^(?P<open>\[|\()(?P<lower>[^,]*),(?P<upper>[^\)\]]*)(?P<close>\)|\])$/', $value, $matches)) {
            throw new NotSupportedException('Unsupported range format.');
        }

        $dbType = $this->getDbType();
        $lower = $matches['lower'] ? trim($matches['lower'], '"') : null;
        $upper = $matches['upper'] ? trim($matches['upper'], '"') : null;
        $includeLower = $matches['open'] === '[';
        $includeUpper = $matches['close'] === ']';

        switch ($dbType) {
            case 'int4range':
                $column = ColumnBuilder::integer();
                return new Int4RangeValue(
                    $column->phpTypecast($lower),
                    $column->phpTypecast($upper),
                    $includeLower,
                    $includeUpper,
                );
            case 'int8range':
                $column = ColumnBuilder::bigint();
                return new Int8RangeValue(
                    $column->phpTypecast($lower),
                    $column->phpTypecast($upper),
                    $includeLower,
                    $includeUpper,
                );
            case 'numrange':
                $column = ColumnBuilder::decimal();
                return new NumRangeValue(
                    $column->phpTypecast($lower),
                    $column->phpTypecast($upper),
                    $includeLower,
                    $includeUpper,
                );
            case 'tsrange':
                $column = ColumnBuilder::datetime();
                return new TsRangeValue(
                    $column->phpTypecast($lower),
                    $column->phpTypecast($upper),
                    $includeLower,
                    $includeUpper,
                );
            case 'tstzrange':
                $column = ColumnBuilder::datetimeWithTimezone();
                return new TsTzRangeValue(
                    $column->phpTypecast($lower),
                    $column->phpTypecast($upper),
                    $includeLower,
                    $includeUpper,
                );
            case 'daterange':
                $column = ColumnBuilder::date();
                return new DateRangeValue(
                    $column->phpTypecast($lower),
                    $column->phpTypecast($upper),
                    $includeLower,
                    $includeUpper,
                );
            default:
                $this->throwNotSupportedDbType($dbType);
        }
    }

    /**
     * @throws NotSupportedException
     */
    private function dbTypecastBound(ExpressionInterface|float|int|string|null $value): string
    {
        return match (gettype($value)) {
            'NULL' => 'NULL',
            'double',
            'integer' => (string) $value,
            'string' => $value,
            'object' => throw new NotSupportedException('Range bound as expression is not supported.'),
        };
    }

    /**
     * @throws NotSupportedException
     */
    private function throwNotSupportedDbType(?string $dbType): never
    {
        throw new NotSupportedException(
            sprintf(
                'Unsupported range type: %s',
                $dbType ?? 'unknown',
            ),
        );
    }
}
