<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionBuilderTrait;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\Query;
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
