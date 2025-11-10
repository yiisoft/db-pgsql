<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

/**
 * Defines the available index methods for {@see DDLQueryBuilder::createIndex()} method.
 */
final class IndexMethod
{
    /**
     * Define the type of the index as `BRIN`.
     */
    public const BRIN = 'BRIN';
    /**
     * Define the type of the index as `BTREE`.
     */
    public const BTREE = 'BTREE';
    /**
     * Define the type of the index as `GIN`.
     */
    public const GIN = 'GIN';
    /**
     * Define the type of the index as `GIST`.
     */
    public const GIST = 'GIST';
    /**
     * Define the type of the index as `HASH`.
     */
    public const HASH = 'HASH';
    /**
     * Define the type of the index as `SPGIST`.
     */
    public const SPGIST = 'SPGIST';
}
