<?php
class Currency extends Model {
    protected string $table = 'currencies';

    public function findByCode(string $code): ?array {
        $stmt = $this->db->prepare("SELECT * FROM currencies WHERE code = :code");
        $stmt->execute(['code' => $code]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findActive(): array {
        $stmt = $this->db->query("SELECT * FROM currencies WHERE is_active = 1 ORDER BY code ASC");
        return $stmt->fetchAll();
    }
}
