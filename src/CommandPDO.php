<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql;

use Exception;
use Throwable;
use Yiisoft\Db\Driver\PDO\CommandPDO as AbstractCommandPDO;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function is_array;

final class CommandPDO extends AbstractCommandPDO
{
    /**
     * @inheritDoc
     */
    public function insertWithReturningPks(string $table, array $columns): bool|array
    {
        $params = [];
        $sql = $this->queryBuilder()->insertEx($table, $columns, $params);

        $this->setSql($sql)->bindValues($params);
        $this->prepare(false);

        /** @var mixed $queryOne */
        $queryOne = $this->queryOne();

        return is_array($queryOne) ? $queryOne : false;
    }

    public function queryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }

    /**
     * @psalm-suppress UnusedClosureParam
     *
     * @throws \Yiisoft\Db\Exception\Exception
     * @throws Throwable
     */
    protected function internalExecute(string|null $rawSql): void
    {
        $attempt = 0;

        while (true) {
            try {
                if (
                    ++$attempt === 1
                    && $this->isolationLevel !== null
                    && $this->db->getTransaction() === null
                ) {
                    $this->db->transaction(
                        fn (ConnectionPDOInterface $db) => $this->internalExecute($rawSql),
                        $this->isolationLevel
                    );
                } else {
                    $this->pdoStatement?->execute();
                }
                break;
            } catch (Exception $e) {
                $rawSql = $rawSql ?: $this->getRawSql();
                $e = (new ConvertException($e, $rawSql))->run();

                if ($this->retryHandler === null || !($this->retryHandler)($e, $attempt)) {
                    throw $e;
                }
            }
        }
    }
}
