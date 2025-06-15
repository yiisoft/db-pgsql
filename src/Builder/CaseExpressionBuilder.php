<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use InvalidArgumentException;
use Yiisoft\Db\Expression\CaseExpression;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function is_string;

/**
 * Builds expressions for {@see CaseExpression}.
 */
final class CaseExpressionBuilder extends \Yiisoft\Db\Expression\CaseExpressionBuilder
{
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $whenClauses = $expression->getWhen();

        if (empty($whenClauses)) {
            throw new InvalidArgumentException('The CASE expression must have at least one WHEN clause.');
        }

        $sql = 'CASE';
        $queryBuilder = $this->queryBuilder;

        $case = $expression->getCase();

        if ($case !== null) {
            $caseTypeHint = $this->buildTypeHint($expression->getCaseType());
            $sql .= ' ' . $this->buildCondition($case, $params) . $caseTypeHint;
        } else {
            $caseTypeHint = '';
        }

        foreach ($whenClauses as $when) {
            $sql .= ' WHEN ' . $this->buildCondition($when->condition, $params) . $caseTypeHint;
            $sql .= ' THEN ' . $queryBuilder->buildValue($when->result, $params);
        }

        if ($expression->hasElse()) {
            $sql .= ' ELSE ' . $queryBuilder->buildValue($expression->getElse(), $params);
        }

        return $sql . ' END';
    }

    protected function buildTypeHint(string|ColumnInterface $type): string
    {
        if (is_string($type)) {
            return $type === '' ? '' : "::$type";
        }

        return '::' . $this->queryBuilder->getColumnDefinitionBuilder()->buildType($type);
    }
}
