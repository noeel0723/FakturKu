<?php
class Product extends Model {
    protected string $table = 'products';

    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO products (name, description, unit_price, currency, unit) VALUES (:name, :description, :unit_price, :currency, :unit)");
        $stmt->execute([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'unit_price'  => $data['unit_price'],
            'currency'    => $data['currency'] ?? BASE_CURRENCY,
            'unit'        => $data['unit'] ?? 'pcs',
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("UPDATE products SET name=:name, description=:description, unit_price=:unit_price, currency=:currency, unit=:unit WHERE id=:id");
        return $stmt->execute([
            'id'          => $id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'unit_price'  => $data['unit_price'],
            'currency'    => $data['currency'] ?? BASE_CURRENCY,
            'unit'        => $data['unit'] ?? 'pcs',
        ]);
    }

    public function findActive(): array {
        $stmt = $this->db->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll();
    }
}
