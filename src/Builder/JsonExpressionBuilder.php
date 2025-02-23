<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use JsonException;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Builds expressions for {@see `Yiisoft\Db\Expression\JsonExpression`} for PostgreSQL Server.
 */
final class JsonExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The Method builds the raw SQL from the $expression that won't be additionally escaped or quoted.
     *
     * @param JsonExpression $expression The expression to build.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws JsonException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        /** @psalm-var mixed $value */
        $value = $expression->getValue();

        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof ExpressionInterface) {
            $statement = $this->queryBuilder->buildExpression($value, $params);

            if ($value instanceof ArrayExpression) {
                $statement = 'array_to_json(' . $statement . ')';
            }
        } else {
            $param = new Param(json_encode($value, JSON_THROW_ON_ERROR), DataType::STRING);
            $statement = $this->queryBuilder->bindParam($param, $params);
        }

        return $statement . $this->getTypeHint($expression);
    }

    /**
     * @return string The typecast expression based on {@see JsonExpression::getType()}.
     */
    private function getTypeHint(JsonExpression $expression): string
    {
        $type = $expression->getType();

        if ($type === null) {
            return '';
        }

        return '::' . $type;
    }
}
