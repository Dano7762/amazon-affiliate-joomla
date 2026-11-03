<?php
/**
 * Products database table handler
 *
 * @package Aaj\Component\Aaj
 */

namespace Aaj\Component\Aaj\Administrator\Database;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Manages the #__aaj_products table
 */
class ProductsTable
{
    /** @var DatabaseInterface */
    private DatabaseInterface $db;

    /** @var string */
    private string $tableName;

    public function __construct()
    {
        $this->db        = Factory::getContainer()->get(DatabaseInterface::class);
        $this->tableName = '#__aaj_products';
    }

    /* -------------------------------------------------------------------------
     * Read
     * ---------------------------------------------------------------------- */

    /**
     * Get a single product by field value
     */
    public function getBy(string $field, mixed $value): ?array
    {
        $allowedFields = ['id', 'asin', 'ean', 'isbn'];
        if (!in_array($field, $allowedFields, true)) {
            return null;
        }

        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName($this->tableName))
            ->where($this->db->quoteName($field) . ' = :value')
            ->bind(':value', $value)
            ->setLimit(1);

        $this->db->setQuery($query);
        $row = $this->db->loadAssoc();

        return $row ?: null;
    }

    /**
     * Get product by ID
     */
    public function getById(int $id): ?array
    {
        return $this->getBy('id', $id);
    }

    /**
     * Get product by ASIN
     */
    public function getByAsin(string $asin): ?array
    {
        return $this->getBy('asin', $asin);
    }

    /**
     * Get multiple products
     *
     * @param  array $args  Supports: asins[], status, orderby, order, limit, offset, outdated (bool), cache_duration (hours)
     * @return array
     */
    public function getProducts(array $args = []): array
    {
        $query = $this->db->getQuery(true)
            ->select('*')
            ->from($this->db->quoteName($this->tableName));

        // Filter by status
        if (!empty($args['status'])) {
            $status = $args['status'];
            $query->where($this->db->quoteName('status') . ' = :status')
                  ->bind(':status', $status);
        }

        // Filter by ASINs
        if (!empty($args['asins']) && is_array($args['asins'])) {
            $quotedAsins = array_map([$this->db, 'quote'], $args['asins']);
            $query->where($this->db->quoteName('asin') . ' IN (' . implode(',', $quotedAsins) . ')');
        }

        // Filter outdated (older than cache_duration hours)
        if (!empty($args['outdated'])) {
            $hours    = (int) ($args['cache_duration'] ?? 720);
            $cutoff   = Factory::getDate('-' . $hours . ' hours')->toSql();
            $query->where($this->db->quoteName('date_updated') . ' < :cutoff')
                  ->bind(':cutoff', $cutoff);
        }

        // Order
        $orderby = $args['orderby'] ?? 'date_updated';
        $order   = strtoupper($args['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $allowedOrderby = ['id', 'asin', 'title', 'price', 'rating', 'reviews', 'salesrank', 'date_created', 'date_updated'];
        if (!in_array($orderby, $allowedOrderby, true)) {
            $orderby = 'date_updated';
        }
        $query->order($this->db->quoteName($orderby) . ' ' . $order);

        // Limit / Offset
        $limit  = (int) ($args['limit'] ?? 0);
        $offset = (int) ($args['offset'] ?? 0);
        if ($limit > 0) {
            $query->setLimit($limit, $offset);
        }

        $this->db->setQuery($query);
        return $this->db->loadAssocList() ?: [];
    }

    /**
     * Count products
     */
    public function count(array $args = []): int
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName($this->tableName));

        if (!empty($args['status'])) {
            $status = $args['status'];
            $query->where($this->db->quoteName('status') . ' = :status')
                  ->bind(':status', $status);
        }

        $this->db->setQuery($query);
        return (int) $this->db->loadResult();
    }

    /* -------------------------------------------------------------------------
     * Write
     * ---------------------------------------------------------------------- */

    /**
     * Insert a new product row
     *
     * @return int  New product ID
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
     * Update an existing product row by ID
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
     * Insert or update a product by ASIN
     *
     * @return int  Product ID
     */
    public function upsert(array $data): int
    {
        $existing = $this->getByAsin($data['asin'] ?? '');

        if ($existing) {
            $this->update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        return $this->insert($data);
    }

    /**
     * Delete a product by ID
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

    /**
     * Update only the reviews/rating fields
     */
    public function updateReviews(int $id, float $rating, int $reviews): bool
    {
        return $this->update($id, [
            'rating'          => (string) $rating,
            'reviews'         => (string) $reviews,
            'reviews_updated' => Factory::getDate()->toSql(),
        ]);
    }

    /* -------------------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------------- */

    /**
     * Sanitize data to only keep known columns
     */
    private function sanitize(array $data): array
    {
        $allowed = [
            'status', 'asin', 'ean', 'isbn', 'binding', 'product_group',
            'title', 'url', 'image_ids', 'features', 'attributes',
            'availability', 'currency', 'price', 'savings', 'savings_percentage',
            'savings_basis', 'salesrank', 'is_prime', 'is_amazon_fulfilled',
            'shipping_charges', 'rating', 'reviews', 'reviews_updated',
            'date_created', 'date_updated',
        ];

        return array_intersect_key($data, array_flip($allowed));
    }
}
