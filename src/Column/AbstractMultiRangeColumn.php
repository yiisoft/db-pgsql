<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Column;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\Expression\MultiRangeValue;
use Yiisoft\Db\Schema\Column\AbstractColumn;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function gettype;
use function is_array;
use function is_string;

/**
 * @template T of ExpressionInterface
 */
abstract class AbstractMultiRangeColumn extends AbstractColumn
{
    /**
     * @inheritDoc
     *
     * @return string|ExpressionInterface|MultiRangeValue|null
     */
    public function dbTypecast(mixed $value): mixed
    {
        if ($value === null
            || is_string($value)
            || $value instanceof ExpressionInterface) {
            return $value;
        }

        if (!is_array($value)) {
            $this->throwWrongTypeException(gettype($value));
        }

        $rangeColumn = $this->getRangeColumn();
        $ranges = [];
        foreach ($value as $rawRange) {
            $range = $rangeColumn->dbTypecast($rawRange);
            if (!is_string($range)
                && !$range instanceof ExpressionInterface) {
                $this->throwWrongTypeException(gettype($rawRange));
            }
            $ranges[] = $range;
        }

        return new MultiRangeValue(...$ranges);
    }

    /**
     * @inheritDoc
     *
     * @return ?ExpressionInterface[]
     *
     * @psalm-return ?T[]
     */
    public function phpTypecast(mixed $value): mixed
    {
        /**
         * @var string|null $value We expect `phpTypecast()` to only receive the value that the database returns, which
         * in this case is `null` or a `string`. To avoid extra checks.
         */

        if ($value === null) {
            return null;
        }

        if ($value === '{}') {
            return [];
        }

        if (preg_match('/^{([\[\(][^,]*,[^\)\]]*[\)\]])(?:,(?1))*}$/', $value) !== 1) {
            throw new NotSupportedException('Unsupported multirange format');
        }

        preg_match_all('/[\[\(][^,]*,[^\)\]]*[\)\]]/', $value, $matches);

        $rangeColumn = $this->getRangeColumn();

        /** @psalm-var T[] */
        return array_map(
            $rangeColumn->phpTypecast(...),
            $matches[0],
        );
    }

    /**
     * @psalm-return AbstractRangeColumn<T>
     */
    abstract protected function getRangeColumn(): ColumnInterface;
}
