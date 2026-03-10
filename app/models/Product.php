<?php
class Product extends Model {
    protected string $table = 'products';

    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO products (name, description, unit_price, currency, unit, is_active) VALUES (:name, :description, :unit_price, :currency, :unit, :is_active)");
        $stmt->execute([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'unit_price'  => $data['unit_price'],
            'currency'    => $data['currency'] ?? BASE_CURRENCY,
            'unit'        => $data['unit'] ?? 'pcs',
            'is_active'   => $data['is_active'] ?? 1,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("UPDATE products SET name=:name, description=:description, unit_price=:unit_price, currency=:currency, unit=:unit, is_active=:is_active WHERE id=:id");
        return $stmt->execute([
            'id'          => $id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'unit_price'  => $data['unit_price'],
            'currency'    => $data['currency'] ?? BASE_CURRENCY,
            'unit'        => $data['unit'] ?? 'pcs',
            'is_active'   => $data['is_active'] ?? 1,
        ]);
    }

    public function findActive(): array {
        $stmt = $this->db->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function findFiltered(?string $search = null, ?string $currency = null, ?string $status = null, string $sort = 'name_asc'): array {
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR description LIKE :search)";
            $params['search'] = '%' . trim($search) . '%';
        }

        if (!empty($currency)) {
            $sql .= " AND currency = :currency";
            $params['currency'] = $currency;
        }

        if ($status === 'active') {
            $sql .= " AND is_active = 1";
        } elseif ($status === 'inactive') {
            $sql .= " AND is_active = 0";
        }

        $orderBy = match($sort) {
            'name_desc' => 'name DESC',
            'price_asc' => 'unit_price ASC',
            'price_desc' => 'unit_price DESC',
            default => 'name ASC',
        };

        $sql .= " ORDER BY {$orderBy}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
