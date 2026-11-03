<?php
/**
 * Lists database table handler
 *
 * @package Aaj\Component\Aaj
 */

namespace Aaj\Component\Aaj\Administrator\Database;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Manages the #__aaj_lists table (bestseller / new releases caches)
 */
class ListsTable
{
    /** @var DatabaseInterface */
    private DatabaseInterface $db;

    /** @var string */
    private string $tableName;

    public function __construct()
    {
        $this->db        = Factory::getContainer()->get(DatabaseInterface::class);
        $this->tableName = '#__aaj_lists';
    }

    /* -------------------------------------------------------------------------
     * Read
     * ---------------------------------------------------------------------- */

    /**
     * Get a list by its unique key
     */
    public function getByKey(string $listKey): ?array
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName($this->tableName))
            ->where($this->db->quoteName('list_key') . ' = :key')
            ->bind(':key', $listKey)
            ->setLimit(1);

        $this->db->setQuery($query);
        $row = $this->db->loadAssoc();
        return $row ?: null;
    }

    /**
     * Get a list by ID
     */
    public function getById(int $id): ?array
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName($this->tableName))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER)
            ->setLimit(1);

        $this->db->setQuery($query);
        $row = $this->db->loadAssoc();
        return $row ?: null;
    }

    /**
     * Get lists
     */
    public function getLists(array $args = []): array
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName($this->tableName));

        if (!empty($args['type'])) {
            $type = $args['type'];
            $query->where($this->db->quoteName('type') . ' = :type')
                  ->bind(':type', $type);
        }

        if (!empty($args['outdated'])) {
            $hours  = (int) ($args['cache_duration'] ?? 720);
            $cutoff = Factory::getDate('-' . $hours . ' hours')->toSql();
            $query->where($this->db->quoteName('date_updated') . ' < :cutoff')
                  ->bind(':cutoff', $cutoff);
        }

        $orderby = $args['orderby'] ?? 'date_updated';
        $order   = strtoupper($args['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $query->order($this->db->quoteName($orderby) . ' ' . $order);

        $limit = (int) ($args['limit'] ?? 0);
        if ($limit > 0) {
            $query->setLimit($limit);
        }

        $this->db->setQuery($query);
        return $this->db->loadAssocList() ?: [];
    }

    /**
     * Count lists
     */
    public function count(): int
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName($this->tableName));
        $this->db->setQuery($query);
        return (int) $this->db->loadResult();
    }

    /* -------------------------------------------------------------------------
     * Write
     * ---------------------------------------------------------------------- */

    /**
     * Insert a new list
     */
    public function insert(array $data): int
    {
        $data = $this->sanitize($data);
        $query = $this->db->getQuery(true)->insert($this->db->quoteName($this->tableName));

        foreach ($data as $col => $val) {
            $query->columns($this->db->quoteName($col));
            $query->values($this->db->quote($val));
        }

        $this->db->setQuery($query);
        $this->db->execute();
        return (int) $this->db->insertid();
    }

    /**
     * Update a list by ID
     */
    public function update(int $id, array $data): bool
    {
        $data = $this->sanitize($data);
        $data['date_updated'] = Factory::getDate()->toSql();

        $query = $this->db->getQuery(true)
            ->update($this->db->quoteName($this->tableName));

        foreach ($data as $col => $val) {
            $query->set($this->db->quoteName($col) . ' = ' . $this->db->quote($val));
        }

        $query->where($this->db->quoteName('id') . ' = :id')
              ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $this->db->setQuery($query);
        return $this->db->execute();
    }

    /**
     * Insert or update a list by key
     */
    public function upsert(array $data): int
    {
        $existing = $this->getByKey($data['list_key'] ?? '');

        if ($existing) {
            $this->update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        if (empty($data['date_created'])) {
            $data['date_created'] = Factory::getDate()->toSql();
        }
        $data['date_updated'] = Factory::getDate()->toSql();

        return $this->insert($data);
    }

    /**
     * Delete a list by ID
     */
    public function delete(int $id): bool
    {
        $query = $this->db->getQuery(true)
            ->delete($this->db->quoteName($this->tableName))
            ->where($this->db->quoteName('id') . ' = :id')
            ->bind(':id', $id, \Joomla\Database\ParameterType::INTEGER);

        $this->db->setQuery($query);
        return $this->db->execute();
    }

    /* -------------------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------------- */

    /**
     * Generate a unique list key from type + params
     */
    public static function buildKey(string $type, array $params): string
    {
        ksort($params);
        return $type . '_' . md5(json_encode($params));
    }

    /**
     * Decode stored products JSON
     */
    public static function decodeProducts(string $json): array
    {
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Sanitize data to allowed columns
     */
    private function sanitize(array $data): array
    {
        $allowed = ['status', 'type', 'list_key', 'country', 'category', 'keywords', 'products', 'date_created', 'date_updated'];
        return array_intersect_key($data, array_flip($allowed));
    }
}
