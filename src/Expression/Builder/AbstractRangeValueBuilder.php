<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Expression\Builder;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

/**
 * @template T as ExpressionInterface
 * @implements ExpressionBuilderInterface<T>
 */
abstract class AbstractRangeValueBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    abstract protected function getBoundColumn(): ColumnInterface;

    /**
     * @throws NotSupportedException
     */
    final protected function buildRange(
        mixed $lower,
        mixed $upper,
        bool $includeLower,
        bool $includeUpper,
        array &$params,
    ): string {
        $column = $this->getBoundColumn();
        return ($includeLower ? '[' : '(')
            . $this->prepareBoundValue($lower, $column, $params)
            . ', '
            . $this->prepareBoundValue($upper, $column, $params)
            . ($includeUpper ? ']' : ')');
    }

    /**
     * @throws NotSupportedException
     */
    private function prepareBoundValue(mixed $value, ColumnInterface $boundColumn, array &$params): string
    {
        $value = $boundColumn->dbTypecast($value);
        if ($value === null) {
            return '';
        }

        return $this->queryBuilder->buildValue($value, $params);
    }
}
