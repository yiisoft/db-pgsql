<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Pgsql\Condition\ILike;
use Yiisoft\Db\Pgsql\Condition\NotILike;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\NotLike;

/**
 * Build an object of {@see Like} into SQL expressions for PostgreSQL Server.
 */
final class LikeBuilder extends \Yiisoft\Db\QueryBuilder\Condition\Builder\LikeBuilder
{
    protected const OPERATOR_DATA = [
        Like::class => [false, 'LIKE'],
        NotLike::class => [true, 'NOT LIKE'],
        ILike::class => [false, 'ILIKE'],
        NotILike::class => [true, 'NOT ILIKE'],
    ];
}
