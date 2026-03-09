<?php
class AuditLog extends Model {
    protected string $table = 'audit_logs';

    public static function log(string $entityType, int $entityId, string $action, $oldValue = null, $newValue = null): void {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO audit_logs (entity_type, entity_id, action, old_value, new_value, ip_address, user_agent)
            VALUES (:entity_type, :entity_id, :action, :old_value, :new_value, :ip, :ua)
        ");
        $stmt->execute([
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'action'      => $action,
            'old_value'   => $oldValue !== null ? json_encode($oldValue) : null,
            'new_value'   => $newValue !== null ? json_encode($newValue) : null,
            'ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua'          => isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
        ]);
    }

    public function findByEntity(string $type, int $id): array {
        $stmt = $this->db->prepare("SELECT * FROM audit_logs WHERE entity_type = :t AND entity_id = :id ORDER BY created_at DESC");
        $stmt->execute(['t' => $type, 'id' => $id]);
        return $stmt->fetchAll();
    }
}
