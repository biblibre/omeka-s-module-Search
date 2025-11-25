<?php

namespace Search;

use DateTime;
use PDO;
use Doctrine\DBAL\Connection;

class IndexationService
{
    const DEFAULT_LIMIT = 100;

    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function lockAndFetchResources(array $args): array
    {
        $pid = getmypid();

        $limit = $args['limit'] ?? self::DEFAULT_LIMIT;

        $this->connection->executeStatement(
            <<<'SQL'
                UPDATE search_resource
                SET locked_by_pid = ?
                WHERE locked_by_pid IS NULL AND (indexed IS NULL OR indexed < touched)
                ORDER BY touched, index_id, resource_id LIMIT ?
            SQL,
            [$pid, $limit],
            [PDO::PARAM_INT, PDO::PARAM_INT]
        );
        $search_resources = $this->connection->fetchAll(
            <<<'SQL'
                SELECT id, index_id, resource_id
                FROM search_resource
                WHERE locked_by_pid = ?
            SQL,
            [$pid],
            [PDO::PARAM_INT]
        );

        return $search_resources;
    }

    public function unlockAndMarkAsIndexedResources(array $ids, DateTime $indexed)
    {
        $this->connection->executeStatement(
            <<<'SQL'
                UPDATE search_resource
                SET indexed = ?, locked_by_pid = NULL
                WHERE id IN (?)
            SQL,
            [$indexed->format('Y-m-d H:i:s'), $ids],
            [PDO::PARAM_STR, Connection::PARAM_INT_ARRAY]
        );
    }
}
