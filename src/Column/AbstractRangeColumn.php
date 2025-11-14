<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\AbstractColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function gettype;
use function is_array;
use function sprintf;

abstract class AbstractRangeColumn extends AbstractColumn
{
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

        [$lower, $upper] = $value;
        $boundColumn = $this->getBoundColumn();

        return '['
            . $this->prepareBoundValue($boundColumn->dbTypecast($lower))
            . ','
            . $this->prepareBoundValue($boundColumn->dbTypecast($upper))
            . ']';
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

        return $this->createRangeValue(
            $matches['lower'] === '' ? null : trim($matches['lower'], '"'),
            $matches['upper'] === '' ? null : trim($matches['upper'], '"'),
            $matches['open'] === '[',
            $matches['close'] === ']',
        );
    }

    abstract protected function getBoundColumn(): ColumnInterface;

    /**
     * @throws NotSupportedException
     */
    abstract protected function createRangeValue(
        ?string $lower,
        ?string $upper,
        bool $includeLower,
        bool $includeUpper,
    ): ExpressionInterface;

    /**
     * @throws NotSupportedException
     */
    private function prepareBoundValue(mixed $value): string
    {
        return match (gettype($value)) {
            'NULL' => '',
            'double',
            'integer' => (string) $value,
            'string' => $value,
            default => throw new NotSupportedException(
                sprintf(
                    'Range bound supports only string, int, float and null values. Got %s.',
                    get_debug_type($value),
                ),
            ),
        };
    }
}
