<?php

namespace Game;

/**
 * Class Player
 * Core player class handling player data, stats, and actions
 * 
 * @package Game
 */
class Player {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var array Player data */
    private $data;

    /** @var int Maximum energy level */
    private const MAX_ENERGY = 100;

    /** @var int Maximum happiness level */
    private const MAX_HAPPINESS = 100;

    /** @var int Minutes for one energy point regeneration */
    private const ENERGY_REGEN_MINUTES = 5;

    /** @var int Minutes for one happiness point regeneration */
    private const HAPPINESS_REGEN_MINUTES = 10;

    /**
     * Player constructor
     *
     * @param array $playerData Player data from database
     * @throws \Exception If player data is invalid
     */
    public function __construct(array $playerData) {
        if (!isset($playerData['id'])) {
            throw new \Exception("Invalid player data");
        }
        $this->data = $playerData;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get player's unique identifier
     *
     * @return string Player ID
     */
    public function getId(): string {
        return $this->data['id'];
    }

    /**
     * Get player's username
     *
     * @return string Username
     */
    public function getUsername(): string {
        return $this->data['username'];
    }

    /**
     * Get player's current money balance
     *
     * @return float Current balance
     */
    public function getMoney(): float {
        return $this->data['money'];
    }

    /**
     * Add money to player's balance
     *
     * @param float $amount Amount to add
     * @throws \Exception If amount is negative
     */
    public function addMoney(float $amount): void {
        if ($amount < 0) {
            throw new \Exception("Cannot add negative amount");
        }

        $stmt = $this->db->prepare("
            UPDATE players 
            SET money = money + ? 
            WHERE id = ?
        ");
        $stmt->execute([$amount, $this->getId()]);
        $this->data['money'] += $amount;
    }

    /**
     * Deduct money from player's balance
     *
     * @param float $amount Amount to deduct
     * @throws \Exception If insufficient funds or negative amount
     */
    public function deductMoney(float $amount): void {
        if ($amount < 0) {
            throw new \Exception("Cannot deduct negative amount");
        }

        if ($this->getMoney() < $amount) {
            throw new \Exception("Insufficient funds");
        }

        $stmt = $this->db->prepare("
            UPDATE players 
            SET money = money - ? 
            WHERE id = ?
        ");
        $stmt->execute([$amount, $this->getId()]);
        $this->data['money'] -= $amount;
    }

    /**
     * Get player's current energy level
     *
     * @return int Current energy level
     */
    public function getEnergy(): int {
        $this->updateEnergy();
        return $this->data['energy'];
    }

    /**
     * Get player's current happiness level
     *
     * @return int Current happiness level
     */
    public function getHappiness(): int {
        $this->updateHappiness();
        return $this->data['happiness'];
    }

    /**
     * Update player's energy based on regeneration time
     *
     * @return void
     */
    private function updateEnergy(): void {
        $lastUpdate = strtotime($this->data['last_energy_update']);
        $now = time();
        $minutesPassed = ($now - $lastUpdate) / 60;
        
        if ($minutesPassed >= self::ENERGY_REGEN_MINUTES) {
            $energyGained = floor($minutesPassed / self::ENERGY_REGEN_MINUTES);
            $newEnergy = min(self::MAX_ENERGY, $this->data['energy'] + $energyGained);

            if ($newEnergy != $this->data['energy']) {
                $stmt = $this->db->prepare("
                    UPDATE players 
                    SET energy = ?, last_energy_update = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$newEnergy, $this->getId()]);
                $this->data['energy'] = $newEnergy;
                $this->data['last_energy_update'] = date('Y-m-d H:i:s');
            }
        }
    }

    /**
     * Update player's happiness based on regeneration time
     *
     * @return void
     */
    private function updateHappiness(): void {
        // Similar to updateEnergy but for happiness
        // ... existing code ...
    }

    // ... continue with other methods ...
} 