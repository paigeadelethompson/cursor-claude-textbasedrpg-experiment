<?php

namespace Game;

/**
 * Class Experience
 * Handles player experience, leveling, and skill progression
 * 
 * @package Game
 */
class Experience {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var Player The player instance */
    private $player;

    /** @var int Base experience required for level 2 */
    private const BASE_LEVEL_EXP = 100;

    /** @var float Experience multiplier per level */
    private const LEVEL_MULTIPLIER = 1.5;

    /** @var int Maximum player level */
    private const MAX_LEVEL = 100;

    /**
     * Experience constructor
     *
     * @param Player $player The player instance
     */
    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Add experience points to player
     *
     * @param int $amount Amount of experience to add
     * @return array Result containing level up information
     */
    public function addExperience(int $amount): array {
        $currentExp = $this->player->getExperience();
        $currentLevel = $this->player->getLevel();
        
        $newExp = $currentExp + $amount;
        $nextLevelExp = $this->getRequiredExperience($currentLevel + 1);
        
        $leveledUp = false;
        $levelsGained = 0;
        
        while ($newExp >= $nextLevelExp && $currentLevel < self::MAX_LEVEL) {
            $leveledUp = true;
            $levelsGained++;
            $currentLevel++;
            $nextLevelExp = $this->getRequiredExperience($currentLevel + 1);
        }

        $this->updateExperience($newExp, $currentLevel);

        return [
            'success' => true,
            'experience_gained' => $amount,
            'leveled_up' => $leveledUp,
            'levels_gained' => $levelsGained,
            'new_level' => $currentLevel,
            'new_experience' => $newExp
        ];
    }

    /**
     * Calculate required experience for a level
     *
     * @param int $level Target level
     * @return int Required experience points
     */
    public function getRequiredExperience(int $level): int {
        if ($level <= 1) return 0;
        return (int) (self::BASE_LEVEL_EXP * pow(self::LEVEL_MULTIPLIER, $level - 2));
    }

    /**
     * Get experience progress towards next level
     *
     * @return array Progress information
     */
    public function getLevelProgress(): array {
        $currentLevel = $this->player->getLevel();
        $currentExp = $this->player->getExperience();
        $nextLevelExp = $this->getRequiredExperience($currentLevel + 1);
        $currentLevelExp = $this->getRequiredExperience($currentLevel);

        $progress = ($currentExp - $currentLevelExp) / ($nextLevelExp - $currentLevelExp) * 100;

        return [
            'current_exp' => $currentExp,
            'next_level_exp' => $nextLevelExp,
            'progress_percentage' => round($progress, 2),
            'exp_remaining' => $nextLevelExp - $currentExp
        ];
    }

    /**
     * Update player's experience and level
     *
     * @param int $experience New experience total
     * @param int $level New level
     * @return void
     */
    private function updateExperience(int $experience, int $level): void {
        $stmt = $this->db->prepare("
            UPDATE players 
            SET experience = ?, level = ?
            WHERE id = ?
        ");
        $stmt->execute([$experience, $level, $this->player->getId()]);
    }
} 