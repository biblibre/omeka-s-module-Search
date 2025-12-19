<?php

namespace Search;

use DateTime;
use PDO;
use Doctrine\DBAL\Connection;

class IndexationService
{
    const DEFAULT_LIMIT = 100;
    const RESOURCE_TYPE_MAP = [
        'items' => 'Omeka\Entity\Item',
        'item_sets' => 'Omeka\Entity\ItemSet',
        'media' => 'Omeka\Entity\Media',
    ];

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

    public function refreshIndexResources(int $index_id, DateTime $touched = null)
    {
        $settings_json = $this->connection->fetchOne(
            'SELECT settings FROM search_index WHERE id = ?',
            [$index_id],
            [PDO::PARAM_INT]
        );
        $settings = json_decode($settings_json ?? '{}', true);
        $resources = $settings['resources'] ?? [];
        $resource_types = $this->resourcesToResourceTypes($resources);

        if ($resource_types) {
            $this->connection->executeStatement(
                <<<'SQL'
                    DELETE search_resource
                    FROM search_resource JOIN resource ON (search_resource.resource_id = resource.id)
                    WHERE search_resource.index_id = ? AND resource.resource_type NOT IN (?)
                SQL,
                [$index_id, $resource_types],
                [PDO::PARAM_INT, Connection::PARAM_STR_ARRAY]
            );

            if ($touched) {
                $this->connection->executeStatement(
                    <<<'SQL'
                        INSERT INTO search_resource (index_id, resource_id, touched)
                        SELECT ?, resource.id, ?
                        FROM resource WHERE resource.resource_type IN (?)
                        ON DUPLICATE KEY UPDATE touched = VALUES(touched)
                    SQL,
                    [$index_id, $touched->format('Y-m-d H:i:s'), $resource_types],
                    [PDO::PARAM_INT, PDO::PARAM_STR, Connection::PARAM_STR_ARRAY]
                );
            } else {
                $this->connection->executeStatement(
                    <<<'SQL'
                        INSERT INTO search_resource (index_id, resource_id, touched)
                        SELECT ?, resource.id, COALESCE(resource.modified, resource.created)
                        FROM resource WHERE resource.resource_type IN (?)
                        ON DUPLICATE KEY UPDATE touched = VALUES(touched)
                    SQL,
                    [$index_id, $resource_types],
                    [PDO::PARAM_INT, Connection::PARAM_STR_ARRAY]
                );
            }
        } else {
            $this->connection->executeStatement(
                'DELETE FROM search_resource WHERE index_id = ?',
                [$index_id],
                [\PDO::PARAM_INT]
            );
        }
    }

    public function touchResource(int $index_id, int $resource_id, DateTime $touched = null)
    {
        $touched ??= new DateTime();

        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO search_resource (index_id, resource_id, touched)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE touched = VALUES(touched)
            SQL,
            [$index_id, $resource_id, $touched->format('Y-m-d H:i:s')],
            [PDO::PARAM_INT, PDO::PARAM_INT, PDO::PARAM_STR]
        );
    }

    public function resourcesToResourceTypes(array $resources): array
    {
        $resource_types = array_map(fn ($resource) => self::RESOURCE_TYPE_MAP[$resource] ?? null, $resources);
        $resource_types = array_values(array_filter($resource_types));

        return $resource_types;
    }

    public function getIndexedResourcesCount(int $index_id): int
    {
        return $this->connection->fetchOne(
            'SELECT COUNT(*) FROM search_resource WHERE index_id = ? AND indexed >= touched',
            [$index_id],
            [PDO::PARAM_INT]
        );
    }

    public function getTotalResourcesCount(int $index_id): int
    {
        return $this->connection->fetchOne(
            'SELECT COUNT(*) FROM search_resource WHERE index_id = ?',
            [$index_id],
            [PDO::PARAM_INT]
        );
    }
}
