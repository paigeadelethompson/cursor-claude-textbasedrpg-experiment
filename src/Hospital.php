<?php

namespace Game;

class Hospital {
    private $db;
    private $player;
    private const BASE_HOSPITAL_TIME = 15; // minutes
    private const HEALTH_REGEN_RATE = 2; // health points per minute

    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    public function admit(Player $attacker, int $damage, string $reason = null): array {
        if ($this->isHospitalized()) {
            throw new \Exception("Player is already in hospital");
        }

        $hospitalTime = $this->calculateHospitalTime($attacker->getCombatStats()['hospitalization_effectiveness']);
        $releaseTime = date('Y-m-d H:i:s', strtotime("+{$hospitalTime} minutes"));
        
        $stmt = $this->db->prepare("
            INSERT INTO hospital_stays 
            (player_id, attacker_id, release_time, initial_health, current_health, reason)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $currentHealth = $this->player->getCombatStats()['health'];
        
        $stmt->execute([
            $this->player->getId(),
            $attacker->getId(),
            $releaseTime,
            $currentHealth,
            $currentHealth,
            $reason
        ]);

        return [
            'success' => true,
            'release_time' => $releaseTime,
            'hospital_time' => $hospitalTime
        ];
    }

    public function selfMedicate(string $itemId): array {
        $stay = $this->getCurrentStay();
        if (!$stay) {
            throw new \Exception("Player is not in hospital");
        }

        $inventory = new Inventory($this->player);
        $item = $inventory->getItem($itemId);

        if (!$item || !isset($item['effects']['health']) || $item['effects']['health'] <= 0) {
            throw new \Exception("Invalid healing item");
        }

        $timeReduction = $this->calculateTimeReduction($item['effects']['health']);
        $newReleaseTime = date('Y-m-d H:i:s', 
            strtotime($stay['release_time']) - ($timeReduction * 60)
        );

        if (strtotime($newReleaseTime) <= time()) {
            // Player can leave immediately
            $this->release('self_discharged');
            $inventory->removeItem($itemId, 1);
            
            return [
                'success' => true,
                'released' => true,
                'time_reduced' => $timeReduction
            ];
        }

        // Update release time
        $stmt = $this->db->prepare("
            UPDATE hospital_stays 
            SET release_time = ?
            WHERE id = ? AND status = 'admitted'
        ");
        $stmt->execute([$newReleaseTime, $stay['id']]);
        
        $inventory->removeItem($itemId, 1);

        return [
            'success' => true,
            'released' => false,
            'time_reduced' => $timeReduction,
            'new_release_time' => $newReleaseTime
        ];
    }

    public function getHospitalizedPlayers(): array {
        $stmt = $this->db->prepare("
            SELECT 
                hs.*,
                p.username as player_name,
                a.username as attacker_name
            FROM hospital_stays hs
            JOIN players p ON p.id = hs.player_id
            LEFT JOIN players a ON a.id = hs.attacker_id
            WHERE status = 'admitted'
            AND release_time > CURRENT_TIMESTAMP
            ORDER BY admitted_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function isHospitalized(): bool {
        return (bool) $this->getCurrentStay();
    }

    public function getCurrentStay(): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM hospital_stays
            WHERE player_id = ?
            AND status = 'admitted'
            AND release_time > CURRENT_TIMESTAMP
            LIMIT 1
        ");
        $stmt->execute([$this->player->getId()]);
        return $stmt->fetch() ?: null;
    }

    public function updateHealth(): void {
        $stay = $this->getCurrentStay();
        if (!$stay) return;

        $minutesElapsed = (time() - strtotime($stay['admitted_at'])) / 60;
        $healthGained = floor($minutesElapsed * self::HEALTH_REGEN_RATE);
        $newHealth = min(100, $stay['current_health'] + $healthGained);

        $stmt = $this->db->prepare("
            UPDATE hospital_stays
            SET current_health = ?
            WHERE id = ?
        ");
        $stmt->execute([$newHealth, $stay['id']]);
    }

    private function release(string $status = 'released'): void {
        $stmt = $this->db->prepare("
            UPDATE hospital_stays
            SET status = ?, current_health = 100
            WHERE player_id = ? AND status = 'admitted'
        ");
        $stmt->execute([$status, $this->player->getId()]);

        // Update player's health
        $this->player->updateCombatStat('health', 100);
    }

    private function calculateHospitalTime(int $hospitalizationEffectiveness): int {
        return self::BASE_HOSPITAL_TIME + floor($hospitalizationEffectiveness / 2);
    }

    private function calculateTimeReduction(int $healingPower): int {
        // Each point of healing reduces hospital time by 30 seconds
        return floor($healingPower * 0.5);
    }
} 