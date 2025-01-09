<?php

namespace Game;

/**
 * Class Combat
 * Handles player combat mechanics, damage calculations, and combat rewards
 * 
 * @package Game
 */
class Combat {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var Player The attacking player */
    private $attacker;

    /** @var int Base energy cost for attacking */
    private const ATTACK_ENERGY_COST = 25;

    /** @var int Base experience gained for successful attacks */
    private const BASE_EXPERIENCE = 50;

    /** @var float Maximum damage multiplier from critical hits */
    private const MAX_CRIT_MULTIPLIER = 1.5;

    /**
     * Combat constructor
     *
     * @param Player $attacker The attacking player
     */
    public function __construct(Player $attacker) {
        $this->attacker = $attacker;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Execute an attack on another player
     *
     * @param string $defenderId Target player's ID
     * @return array Attack result containing damage and rewards
     * @throws \Exception If attack conditions are not met
     */
    public function attack(string $defenderId): array {
        $defender = new Player($this->getPlayerById($defenderId));
        
        if ($this->attacker->getId() === $defenderId) {
            throw new \Exception("Cannot attack yourself");
        }

        if ($this->attacker->getEnergy() < self::ATTACK_ENERGY_COST) {
            throw new \Exception("Insufficient energy");
        }

        $attackerStats = $this->getCombatStats($this->attacker->getId());
        $defenderStats = $this->getCombatStats($defenderId);

        $damage = $this->calculateDamage($attackerStats, $defenderStats);
        $this->applyDamage($defenderId, $damage);

        $experience = $this->calculateExperience($damage, $defenderStats['level']);
        $this->awardExperience($experience);

        return [
            'success' => true,
            'damage' => $damage,
            'experience_gained' => $experience,
            'critical_hit' => $damage > $attackerStats['strength'] * 1.2
        ];
    }

    /**
     * Calculate damage based on attacker and defender stats
     *
     * @param array $attackerStats Attacker's combat stats
     * @param array $defenderStats Defender's combat stats
     * @return int Calculated damage amount
     */
    private function calculateDamage(array $attackerStats, array $defenderStats): int {
        $baseDamage = ($attackerStats['strength'] * 0.6 + 
                      $attackerStats['dexterity'] * 0.4);
        
        $defense = ($defenderStats['defense'] * 0.7 + 
                   $defenderStats['speed'] * 0.3);

        $damage = max(1, $baseDamage - ($defense * 0.5));

        // Critical hit calculation
        if (rand(1, 100) <= $attackerStats['dexterity']) {
            $damage *= (1 + (rand(10, 50) / 100));
        }

        return (int) min($damage, $defenderStats['health']);
    }

    /**
     * Apply damage to defender and handle hospitalization
     *
     * @param string $defenderId Defender's ID
     * @param int $damage Amount of damage to apply
     * @return void
     */
    private function applyDamage(string $defenderId, int $damage): void {
        $stmt = $this->db->prepare("
            UPDATE combat_stats 
            SET health = health - ?
            WHERE player_id = ?
        ");
        $stmt->execute([$damage, $defenderId]);

        // Check for hospitalization
        $defenderHealth = $this->getPlayerHealth($defenderId);
        if ($defenderHealth <= 0) {
            $this->hospitalizePlayer($defenderId);
        }
    }

    /**
     * Calculate experience gained from combat
     *
     * @param int $damage Damage dealt
     * @param int $defenderLevel Defender's level
     * @return int Experience points gained
     */
    private function calculateExperience(int $damage, int $defenderLevel): int {
        return (int) (self::BASE_EXPERIENCE * ($damage / 100) * 
               (1 + max(0, $defenderLevel - $this->attacker->getLevel()) * 0.1));
    }

    // ... continue with other combat-related methods ...
} 