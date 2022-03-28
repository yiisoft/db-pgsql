<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\PDO;

use PDOException;
use Yiisoft\Db\Cache\QueryCache;
use Yiisoft\Db\Command\CommandPDO;
use Yiisoft\Db\Connection\ConnectionPDOInterface;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Query\QueryBuilderInterface;

final class CommandPDOPgsql extends CommandPDO
{
    public function __construct(private ConnectionPDOInterface $db, QueryCache $queryCache)
    {
        parent::__construct($queryCache);
    }

    /**
     * @inheritDoc
     */
    public function insertEx(string $table, array $columns): bool|array
    {
        $params = [];
        $sql = $this->queryBuilder()->insertEx($table, $columns, $params);

        $this->setSql($sql)->bindValues($params);
        $this->prepare(false);

        /** @var mixed */
        $queryOne = $this->queryOne();

        return is_array($queryOne) ? $queryOne : false;
    }

    public function queryBuilder(): QueryBuilderInterface
    {
        return $this->db->getQueryBuilder();
    }

    public function prepare(?bool $forRead = null): void
    {
        if (isset($this->pdoStatement)) {
            $this->bindPendingParams();

            return;
        }

        $sql = $this->getSql();

        if ($this->db->getTransaction()) {
            /** master is in a transaction. use the same connection. */
            $forRead = false;
        }

        if ($forRead || ($forRead === null && $this->db->getSchema()->isReadQuery($sql))) {
            $pdo = $this->db->getSlavePdo();
        } else {
            $pdo = $this->db->getMasterPdo();
        }

        try {
            $this->pdoStatement = $pdo?->prepare($sql);
            $this->bindPendingParams();
        } catch (PDOException $e) {
            $message = $e->getMessage() . "\nFailed to prepare SQL: $sql";
            /** @var array|null */
            $errorInfo = $e->errorInfo ?? null;

            throw new Exception($message, $errorInfo, $e);
        }
    }

    protected function getCacheKey(int $queryMode, string $rawSql): array
    {
        return [
            __CLASS__,
            $queryMode,
            $this->db->getDriver()->getDsn(),
            $this->db->getDriver()->getUsername(),
            $rawSql,
        ];
    }

    protected function internalExecute(?string $rawSql): void
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
                        fn (?string $rawSql): ?string => $this->internalExecute($rawSql),
                        $this->isolationLevel
                    );
                } else {
                    $this->pdoStatement?->execute();
                }
                break;
            } catch (\Exception $e) {
                $rawSql = $rawSql ?: $this->getRawSql();
                $e = (new ConvertException($e, $rawSql))->run();

                if ($this->retryHandler === null || !($this->retryHandler)($e, $attempt)) {
                    throw $e;
                }
            }
        }
    }
}
