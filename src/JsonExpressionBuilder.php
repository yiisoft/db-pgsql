<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Exceptions\InvalidArgumentException;
use Yiisoft\Db\Expressions\ArrayExpression;
use Yiisoft\Db\Expressions\ExpressionBuilderInterface;
use Yiisoft\Db\Expressions\ExpressionBuilderTrait;
use Yiisoft\Db\Expressions\ExpressionInterface;
use Yiisoft\Db\Expressions\JsonExpression;
use Yiisoft\Db\Querys\Query;
use Yiisoft\Json\Json;

/**
 * Class JsonExpressionBuilder builds {@see JsonExpression} for PostgreSQL DBMS.
 */
class JsonExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * {@inheritdoc}
     *
     * @param JsonExpression|ExpressionInterface $expression the expression to be built
     * @param array $params
     *
     * @return string
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $value = $expression->getValue();

        if ($value instanceof Query) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);
            return "($sql)" . $this->getTypecast($expression);
        }
        if ($value instanceof ArrayExpression) {
            $placeholder = 'array_to_json(' . $this->queryBuilder->buildExpression($value, $params) . ')';
        } else {
            $placeholder = $this->queryBuilder->bindParam(Json::encode($value), $params);
        }

        return $placeholder . $this->getTypecast($expression);
    }

    /**
     * @param JsonExpression $expression
     *
     * @return string the typecast expression based on [[type]].
     */
    protected function getTypecast(JsonExpression $expression): string
    {
        if ($expression->getType() === null) {
            return '';
        }

        return '::' . $expression->getType();
    }
}
