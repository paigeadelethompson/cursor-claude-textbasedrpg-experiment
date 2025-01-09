<?php

namespace Game;

class Gym {
    private $player;
    private $db;

    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    public function train(string $statType): array {
        if ($this->player->getEnergy() < 5) {
            throw new \Exception("Not enough energy to train");
        }

        // Calculate stat gain based on current level and random factor
        $baseGain = 1;
        $randomFactor = rand(80, 120) / 100;
        $statGain = ceil($baseGain * $randomFactor);

        // Record training session
        $stmt = $this->db->prepare("
            INSERT INTO gym_training_logs 
            (player_id, training_type, energy_spent, stat_gain) 
            VALUES (?, ?, 5, ?)
        ");
        
        $stmt->execute([
            $this->player->getId(),
            $statType,
            $statGain
        ]);

        // Update player stats
        $this->player->updateStat($statType, $statGain);
        $this->player->reduceEnergy(5);

        return [
            'success' => true,
            'stat_gain' => $statGain,
            'energy_spent' => 5
        ];
    }
} 