<?php
class Client extends Model {
    protected string $table = 'clients';

    public function create(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO clients (name, email, phone, address, company) VALUES (:name, :email, :phone, :address, :company)");
        $stmt->execute([
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'company' => $data['company'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("UPDATE clients SET name=:name, email=:email, phone=:phone, address=:address, company=:company WHERE id=:id");
        return $stmt->execute([
            'id'      => $id,
            'name'    => $data['name'],
            'email'   => $data['email'],
            'phone'   => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'company' => $data['company'] ?? null,
        ]);
    }

    public function search(string $keyword): array {
        $stmt = $this->db->prepare("SELECT * FROM clients WHERE name LIKE :kw OR email LIKE :kw2 OR company LIKE :kw3 ORDER BY name ASC");
        $like = "%$keyword%";
        $stmt->execute(['kw' => $like, 'kw2' => $like, 'kw3' => $like]);
        return $stmt->fetchAll();
    }
}
