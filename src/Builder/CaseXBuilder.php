<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Expression\Statement\CaseX;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function is_string;

/**
 * Builds expressions for {@see CaseX}.
 */
final class CaseXBuilder extends \Yiisoft\Db\Expression\Statement\Builder\CaseXBuilder
{
    /**
     * @param CaseX $expression The `CASE` expression to build.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $sql = 'CASE';

        if ($expression->value !== null) {
            $caseTypeHint = $this->buildTypeHint($expression->valueType);
            $sql .= ' ' . $this->buildCaseValueWithTypeHint($expression->value, $caseTypeHint, $params);
        } else {
            $caseTypeHint = '';
        }

        foreach ($expression->whenThen as $whenThen) {
            $sql .= ' WHEN ' . $this->buildConditionWithTypeHint($whenThen->when, $caseTypeHint, $params);
            $sql .= ' THEN ' . $this->queryBuilder->buildValue($whenThen->then, $params);
        }

        if ($expression->hasElse()) {
            $sql .= ' ELSE ' . $this->queryBuilder->buildValue($expression->else, $params);
        }

        return $sql . ' END';
    }

    private function buildCaseValueWithTypeHint(mixed $value, string $typeHint, array &$params): string
    {
        $builtValue = $this->buildCaseValue($value, $params);

        return $typeHint !== '' ? "($builtValue)$typeHint" : $builtValue;
    }

    private function buildConditionWithTypeHint(mixed $condition, string $typeHint, array &$params): string
    {
        $builtCondition = $this->buildCondition($condition, $params);

        return $typeHint !== '' ? "($builtCondition)$typeHint" : $builtCondition;
    }

    private function buildTypeHint(string|ColumnInterface $type): string
    {
        if (is_string($type)) {
            return $type === '' ? '' : "::$type";
        }

        return '::' . $this->queryBuilder->getColumnDefinitionBuilder()->buildType($type);
    }
}
