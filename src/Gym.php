<?php

namespace Game;

/**
 * Class Gym
 * Handles player training, stat improvements, and workout sessions
 * 
 * @package Game
 */
class Gym {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var Player The player instance */
    private $player;

    /** @var int Energy cost per training session */
    private const TRAINING_ENERGY_COST = 10;

    /** @var int Base experience per training session */
    private const BASE_TRAINING_EXP = 5;

    /** @var array Valid training stats */
    private const VALID_STATS = ['strength', 'defense', 'speed', 'dexterity'];

    /**
     * Gym constructor
     *
     * @param Player $player The player instance
     */
    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Train a specific combat stat
     *
     * @param string $stat The stat to train
     * @param int $intensity Training intensity (1-3)
     * @return array Training result
     * @throws \Exception If validation fails or insufficient energy
     */
    public function train(string $stat, int $intensity = 1): array {
        if (!in_array($stat, self::VALID_STATS)) {
            throw new \Exception("Invalid training stat");
        }

        $energyCost = self::TRAINING_ENERGY_COST * $intensity;
        if ($this->player->getEnergy() < $energyCost) {
            throw new \Exception("Insufficient energy");
        }

        $improvement = $this->calculateImprovement($stat, $intensity);
        $experience = self::BASE_TRAINING_EXP * $intensity;

        $this->updateStat($stat, $improvement);
        $this->player->useEnergy($energyCost);
        $this->player->addExperience($experience);

        return [
            'success' => true,
            'stat_improved' => $stat,
            'improvement' => $improvement,
            'energy_used' => $energyCost,
            'experience_gained' => $experience
        ];
    }

    /**
     * Calculate stat improvement based on current level and intensity
     *
     * @param string $stat Stat being trained
     * @param int $intensity Training intensity
     * @return int Improvement amount
     */
    private function calculateImprovement(string $stat, int $intensity): int {
        $currentStat = $this->player->getCombatStats()[$stat];
        $baseImprovement = max(1, floor(10 / sqrt($currentStat)));
        return $baseImprovement * $intensity;
    }

    /**
     * Update a combat stat
     *
     * @param string $stat Stat to update
     * @param int $amount Improvement amount
     * @return void
     */
    private function updateStat(string $stat, int $amount): void {
        $stmt = $this->db->prepare("
            UPDATE combat_stats 
            SET $stat = $stat + ?
            WHERE player_id = ?
        ");
        $stmt->execute([$amount, $this->player->getId()]);
    }
} 