<?php

namespace Game;

use Game\Training\SatanicShrine;

class Player {
    private $id;
    private $stats;
    private $energy;
    private $happiness;
    private $satanPoints;
    private $shrineModifiers;
    private $lastEnergyUpdate;
    private $lastHappinessUpdate;
    private $db;

    private const MAX_ENERGY = 100;
    private const MAX_HAPPINESS = 100;
    private const ENERGY_REGEN_MINUTES = 5;
    private const HAPPINESS_REGEN_MINUTES = 10;

    public function __construct(string $id, ?\PDO $db = null) {
        $this->id = $id;
        $this->db = $db;
        $this->stats = [
            'strength' => 10,
            'defense' => 10,
            'speed' => 10,
            'dexterity' => 10
        ];
        $this->energy = 100;
        $this->happiness = 100;
        $this->satanPoints = 0;
        $this->shrineModifiers = 1.0;
        $this->lastEnergyUpdate = time();
        $this->lastHappinessUpdate = time();

        if ($db) {
            $this->loadFromDatabase();
        }
    }

    private function loadFromDatabase(): void {
        $stmt = $this->db->prepare("
            SELECT * FROM players 
            LEFT JOIN combat_stats ON players.id = combat_stats.player_id
            WHERE players.id = ?
        ");
        $stmt->execute([$this->id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($data) {
            $this->stats = [
                'strength' => $data['strength'],
                'defense' => $data['defense'],
                'speed' => $data['speed'],
                'dexterity' => $data['dexterity']
            ];
            $this->energy = $data['energy'];
            $this->happiness = $data['happiness'];
            $this->satanPoints = $data['satan_points'] ?? 0;
            $this->shrineModifiers = $data['shrine_modifiers'] ?? 1.0;
            $this->lastEnergyUpdate = strtotime($data['last_energy_update']);
            $this->lastHappinessUpdate = strtotime($data['last_happiness_update']);
        }
    }

    private function updateEnergy(): void {
        $now = time();
        $minutesPassed = ($now - $this->lastEnergyUpdate) / 60;
        
        if ($minutesPassed >= self::ENERGY_REGEN_MINUTES) {
            $energyGained = floor($minutesPassed / self::ENERGY_REGEN_MINUTES);
            $this->energy = min(self::MAX_ENERGY, $this->energy + $energyGained);
            $this->lastEnergyUpdate = $now;

            if ($this->db) {
                $stmt = $this->db->prepare("
                    UPDATE players 
                    SET energy = ?, last_energy_update = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$this->energy, $this->id]);
            }
        }
    }

    private function updateHappiness(): void {
        $now = time();
        $minutesPassed = ($now - $this->lastHappinessUpdate) / 60;
        
        if ($minutesPassed >= self::HAPPINESS_REGEN_MINUTES) {
            $happinessGained = floor($minutesPassed / self::HAPPINESS_REGEN_MINUTES);
            $this->happiness = min(self::MAX_HAPPINESS, $this->happiness + $happinessGained);
            $this->lastHappinessUpdate = $now;

            if ($this->db) {
                $stmt = $this->db->prepare("
                    UPDATE players 
                    SET happiness = ?, last_happiness_update = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$this->happiness, $this->id]);
            }
        }
    }

    public function getShrineModifiers(): float {
        return $this->shrineModifiers;
    }

    public function getSatanPoints(): int {
        return $this->satanPoints;
    }

    public function getHappiness(): int {
        $this->updateHappiness();
        return $this->happiness;
    }

    public function getStatTotal(): int {
        return array_sum($this->stats);
    }

    public function subtractEnergy(int $amount): void {
        if ($amount > $this->energy) {
            throw new \Exception('Insufficient energy for sacrifice');
        }
        $this->energy -= $amount;
    }

    public function subtractHappiness(int $amount): void {
        $this->happiness = max(0, $this->happiness - $amount);
    }

    public function addToStat(string $stat, float $amount): void {
        if (!array_key_exists($stat, $this->stats)) {
            throw new \InvalidArgumentException("Invalid stat: $stat");
        }
        $this->stats[$stat] += $amount;
    }

    public function getStat(string $stat): int {
        if (!array_key_exists($stat, $this->stats)) {
            throw new \InvalidArgumentException("Invalid stat: $stat");
        }
        return $this->stats[$stat];
    }

    public function getEnergy(): int {
        $this->updateEnergy();
        return $this->energy;
    }

    public function addSatanPoints(int $points): void {
        $this->satanPoints += $points;
    }

    public function updateShrineModifiers(float $modifier): void {
        $this->shrineModifiers = $modifier;
    }

    public function save(): void {
        if (!$this->db) {
            return;
        }

        $this->db->beginTransaction();
        try {
            // Update player stats
            $stmt = $this->db->prepare("
                UPDATE combat_stats 
                SET strength = ?, defense = ?, speed = ?, dexterity = ?
                WHERE player_id = ?
            ");
            $stmt->execute([
                $this->stats['strength'],
                $this->stats['defense'],
                $this->stats['speed'],
                $this->stats['dexterity'],
                $this->id
            ]);

            // Update player state
            $stmt = $this->db->prepare("
                UPDATE players 
                SET energy = ?, 
                    happiness = ?,
                    satan_points = ?,
                    shrine_modifiers = ?,
                    last_energy_update = FROM_UNIXTIME(?),
                    last_happiness_update = FROM_UNIXTIME(?)
                WHERE id = ?
            ");
            $stmt->execute([
                $this->energy,
                $this->happiness,
                $this->satanPoints,
                $this->shrineModifiers,
                $this->lastEnergyUpdate,
                $this->lastHappinessUpdate,
                $this->id
            ]);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
} 