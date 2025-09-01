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
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $sql = 'CASE';

        if ($expression->value !== null) {
            $caseTypeHint = $this->buildTypeHint($expression->valueType);
            $sql .= ' ' . $this->buildConditionWithTypeHint($expression->value, $caseTypeHint, $params);
        } else {
            $caseTypeHint = '';
        }

        foreach ($expression->when as $when) {
            $sql .= ' WHEN ' . $this->buildConditionWithTypeHint($when->condition, $caseTypeHint, $params);
            $sql .= ' THEN ' . $this->buildResult($when->result, $params);
        }

        if ($expression->hasElse()) {
            $sql .= ' ELSE ' . $this->buildResult($expression->else, $params);
        }

        return $sql . ' END';
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
