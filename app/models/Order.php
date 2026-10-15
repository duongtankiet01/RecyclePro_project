<?php
declare(strict_types=1);

class Order
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Fetch orders available for collaborators.
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableOrders(): array
    {
        try {
            $sql = "
                SELECT 
                    op.participant_id,
                    op.order_id,
                    op.quantity,
                    op.accepted_at,
                    bo.waste_type,
                    bo.points_per_kg,
                    u.full_name AS household_name,
                    ua.address_line,
                    ua.province_code,
                    ua.ward_code
                FROM order_participants op
                JOIN business_orders bo ON op.order_id = bo.order_id
                JOIN users u ON op.household_id = u.user_id
                LEFT JOIN user_addresses ua ON u.user_id = ua.user_id AND ua.is_primary = 1
                WHERE op.delivery_method = 'ctv'
                  AND op.status = 'pending'
                  AND op.collector_id IS NULL
                ORDER BY op.accepted_at DESC
                LIMIT 50
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            return array_map(function (array $row): array {
                $totalPoints = (float)($row['quantity'] ?? 0) * (float)($row['points_per_kg'] ?? 0);
                $advancePoints = $totalPoints;
                $collectorReward = $totalPoints * 0.35;
                
                return [
                    'id' => (int)$row['participant_id'],
                    'code' => '#CTV-' . (int)$row['participant_id'],
                    'material' => $row['waste_type'] ?? '',
                    'customer_name' => $row['household_name'] ?? '',
                    'address' => $row['address_line'] ?? 'Chưa có địa chỉ',
                    'weight_label' => isset($row['quantity']) ? ('~' . (float)$row['quantity'] . ' kg') : '',
                    'reward_label' => number_format((int)$collectorReward * 100, 0, ',', '.') . 'đ',
                ];
            }, $rows);
        } catch (PDOException $e) {
            if ($this->isMissingTableError($e)) {
                return [];
            }
            throw $e;
        }
    }

    /**
     * Fetch accepted orders for a given collaborator.
     */
    public function getOrdersByCollaborator(int $collaboratorId): array
    {
        try {
            $sql = "
                SELECT 
                    op.participant_id,
                    op.status,
                    op.quantity,
                    op.accepted_at,
                    op.collected_at,
                    op.delivered_at,
                    bo.waste_type,
                    u.full_name AS household_name,
                    ua.address_line,
                    ua.province_code,
                    ua.ward_code
                FROM order_participants op
                JOIN business_orders bo ON op.order_id = bo.order_id
                JOIN users u ON op.household_id = u.user_id
                LEFT JOIN user_addresses ua ON u.user_id = ua.user_id AND ua.is_primary = 1
                WHERE op.collector_id = :collector_id
                ORDER BY op.accepted_at DESC
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['collector_id' => $collaboratorId]);
            $rows = $stmt->fetchAll();

            return array_map(function (array $row): array {
                $status = (string)($row['status'] ?? 'pending');
                return [
                    'id' => (int)$row['participant_id'],
                    'code' => '#CTV-' . (int)$row['participant_id'],
                    'customer_name' => $row['household_name'] ?? '',
                    'address' => $row['address_line'] ?? 'Chưa có địa chỉ',
                    'status_label' => $this->mapStatusLabel($status),
                    'status_class' => $this->mapStatusClass($status),
                    'can_confirm_pickup' => false,
                    'can_confirm_delivery' => false,
                ];
            }, $rows);
        } catch (PDOException $e) {
            if ($this->isMissingTableError($e)) {
                return [];
            }
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        try {
            $sql = "
                SELECT 
                    op.participant_id,
                    op.order_id,
                    op.quantity,
                    op.status,
                    bo.waste_type,
                    bo.points_per_kg,
                    u.full_name AS household_name,
                    u.phone_number AS household_phone,
                    ua.address_line,
                    ua.province_code,
                    ua.ward_code
                FROM order_participants op
                JOIN business_orders bo ON op.order_id = bo.order_id
                JOIN users u ON op.household_id = u.user_id
                LEFT JOIN user_addresses ua ON u.user_id = ua.user_id AND ua.is_primary = 1
                WHERE op.participant_id = :pid
                LIMIT 1
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['pid' => $id]);
            $row = $stmt->fetch();
            if ($row === false) {
                return null;
            }

            $totalPoints = (float)($row['quantity'] ?? 0) * (float)($row['points_per_kg'] ?? 0);
            $advancePoints = $totalPoints;
            $collectorReward = $totalPoints * 0.35;

            return [
                'id' => (int)$row['participant_id'],
                'code' => '#CTV-' . (int)$row['participant_id'],
                'title' => 'Thu gom ' . ($row['waste_type'] ?? ''),
                'material' => $row['waste_type'] ?? '',
                'customer_name' => $row['household_name'] ?? '',
                'address' => $row['address_line'] ?? 'Chưa có địa chỉ',
                'customer_phone' => $row['household_phone'] ?? '',
                'weight_label' => isset($row['quantity']) ? ('~' . (float)$row['quantity'] . ' kg') : '',
                'advance_payment_label' => number_format((int)$advancePoints * 100, 0, ',', '.') . 'đ',
                'reward_label' => number_format((int)$collectorReward * 100, 0, ',', '.') . 'đ',
            ];
        } catch (PDOException $e) {
            if ($this->isMissingTableError($e)) {
                return null;
            }
            throw $e;
        }
    }

    private function isMissingTableError(PDOException $exception): bool
    {
        return $exception->getCode() === '42S02' || (int)$exception->getCode() === 1146;
    }

    private function buildAddress(string $address, string $ward, string $district, string $province): string
    {
        $parts = array_filter([$address, $ward, $district, $province]);
        return implode(', ', $parts);
    }

    private function mapStatusLabel(string $status): string
    {
        switch ($status) {
            case 'pending':
                return 'Đang thu gom';
            case 'confirmed':
                return 'Hoàn tất';
            case 'cancelled':
                return 'Đã hủy';
            default:
                return 'Chờ xác nhận';
        }
    }

    private function mapStatusClass(string $status): string
    {
        switch ($status) {
            case 'pending':
                return 'status-in-progress';
            case 'confirmed':
                return 'status-completed';
            case 'cancelled':
                return 'status-cancelled';
            default:
                return 'status-info';
        }
    }
}
