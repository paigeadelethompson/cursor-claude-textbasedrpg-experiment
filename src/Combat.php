<?php

namespace Game;

class Combat {
    private $attacker;
    private $defender;
    private $db;

    public function __construct(Player $attacker, Player $defender) {
        $this->attacker = $attacker;
        $this->defender = $defender;
        $this->db = Database::getInstance()->getConnection();
    }

    public function initiateCombat(): array {
        $rounds = [];
        $totalDamage = 0;
        $maxRounds = 5;

        for ($round = 1; $round <= $maxRounds; $round++) {
            $attackPower = $this->calculateAttackPower();
            $defenseValue = $this->calculateDefenseValue();
            
            $damage = max(0, $attackPower - $defenseValue);
            $totalDamage += $damage;

            $rounds[] = [
                'round' => $round,
                'attack_power' => $attackPower,
                'defense_value' => $defenseValue,
                'damage' => $damage
            ];

            if ($totalDamage >= $this->defender->getHealth()) {
                break;
            }
        }

        $result = [
            'success' => $totalDamage > 0,
            'rounds' => $rounds,
            'total_damage' => $totalDamage,
            'experience_gained' => $this->calculateExperience($totalDamage)
        ];

        // Log combat result
        $this->logCombat($result);

        return $result;
    }

    private function calculateAttackPower(): float {
        $weaponBonus = $this->attacker->getEquippedWeaponBonus();
        $baseAttack = $this->attacker->calculateAttackPower();
        $randomFactor = rand(80, 120) / 100;

        return ($baseAttack + $weaponBonus) * $randomFactor;
    }

    private function calculateDefenseValue(): float {
        $armorBonus = $this->defender->getEquippedArmorBonus();
        $baseDefense = $this->defender->calculateDefenseValue();
        $randomFactor = rand(90, 110) / 100;

        return ($baseDefense + $armorBonus) * $randomFactor;
    }

    private function calculateExperience(float $damageDealt): int {
        $levelDifference = $this->defender->getLevel() - $this->attacker->getLevel();
        $baseExp = 10;
        $expMultiplier = 1 + (max(0, $levelDifference) * 0.1);
        
        return ceil(($baseExp + ($damageDealt * 0.5)) * $expMultiplier);
    }

    private function logCombat(array $result): void {
        $stmt = $this->db->prepare("
            INSERT INTO combat_logs 
            (attacker_id, defender_id, result, energy_cost)
            VALUES (?, ?, ?, 25)
        ");

        $stmt->execute([
            $this->attacker->getId(),
            $this->defender->getId(),
            json_encode($result)
        ]);
    }
} 