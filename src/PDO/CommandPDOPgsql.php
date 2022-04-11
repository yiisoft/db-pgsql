<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\PDO;

use Yiisoft\Db\Command\CommandPDO;
use Yiisoft\Db\Exception\ConvertException;
use Yiisoft\Db\Query\QueryBuilderInterface;

final class CommandPDOPgsql extends CommandPDO
{
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
