<?php

namespace Game;

/**
 * Class FactionWar
 * Handles faction warfare mechanics, scoring, and rewards
 * 
 * @package Game
 */
class FactionWar {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var array War data */
    private $data;

    /** @var int Base points awarded per successful attack */
    private const BASE_POINTS_PER_ATTACK = 10;

    /** @var int War duration in days */
    private const WAR_DURATION_DAYS = 3;

    /** @var int Minimum attacks required for war participation */
    private const MIN_ATTACKS_FOR_REWARDS = 5;

    /**
     * FactionWar constructor
     *
     * @param array $warData War data from database
     * @throws \Exception If war data is invalid
     */
    public function __construct(array $warData) {
        if (!isset($warData['id'])) {
            throw new \Exception("Invalid war data");
        }
        $this->data = $warData;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Record an attack in the war
     *
     * @param Player $attacker Attacking player
     * @param Player $defender Defending player
     * @param int $damage Damage dealt
     * @return array Attack record result
     */
    public function recordAttack(Player $attacker, Player $defender, int $damage): array {
        $points = $this->calculatePoints($damage);
        
        $stmt = $this->db->prepare("
            INSERT INTO faction_war_attacks 
            (war_id, attacker_id, defender_id, damage, points)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $this->data['id'],
            $attacker->getId(),
            $defender->getId(),
            $damage,
            $points
        ]);

        $this->updateFactionScore($attacker->getFactionId(), $points);

        return [
            'success' => true,
            'points_earned' => $points
        ];
    }

    /**
     * End the war and distribute rewards
     *
     * @return array War conclusion results
     */
    public function conclude(): array {
        $scores = $this->getFactionScores();
        $winnerId = $scores[0]['faction_id'];
        $rewards = $this->calculateRewards($winnerId);

        $this->distributeRewards($rewards);
        $this->updateWarStatus('concluded', $winnerId);

        return [
            'winner_id' => $winnerId,
            'rewards' => $rewards
        ];
    }

    /**
     * Calculate points for an attack
     *
     * @param int $damage Damage dealt in attack
     * @return int Points awarded
     */
    private function calculatePoints(int $damage): int {
        return self::BASE_POINTS_PER_ATTACK + floor($damage / 10);
    }

    /**
     * Update faction's war score
     *
     * @param string $factionId Faction ID
     * @param int $points Points to add
     * @return void
     */
    private function updateFactionScore(string $factionId, int $points): void {
        $stmt = $this->db->prepare("
            UPDATE faction_wars_scores
            SET score = score + ?
            WHERE war_id = ? AND faction_id = ?
        ");
        $stmt->execute([$points, $this->data['id'], $factionId]);
    }

    /**
     * Get current war scores
     *
     * @return array Array of faction scores
     */
    private function getFactionScores(): array {
        $stmt = $this->db->prepare("
            SELECT faction_id, score
            FROM faction_wars_scores
            WHERE war_id = ?
            ORDER BY score DESC
        ");
        $stmt->execute([$this->data['id']]);
        return $stmt->fetchAll();
    }

    /**
     * Calculate rewards for war participants
     *
     * @param string $winningFactionId ID of winning faction
     * @return array Reward distribution details
     */
    private function calculateRewards(string $winningFactionId): array {
        $stmt = $this->db->prepare("
            SELECT 
                player_id,
                COUNT(*) as attacks,
                SUM(points) as total_points
            FROM faction_war_attacks
            WHERE war_id = ?
            GROUP BY player_id
            HAVING COUNT(*) >= ?
        ");
        $stmt->execute([$this->data['id'], self::MIN_ATTACKS_FOR_REWARDS]);
        
        $participants = $stmt->fetchAll();
        $rewards = [];

        foreach ($participants as $participant) {
            $multiplier = $participant['faction_id'] === $winningFactionId ? 1.5 : 1.0;
            $rewards[$participant['player_id']] = [
                'money' => floor($participant['total_points'] * 100 * $multiplier),
                'experience' => floor($participant['total_points'] * 10 * $multiplier)
            ];
        }

        return $rewards;
    }

    /**
     * Distribute rewards to participants
     *
     * @param array $rewards Reward distribution details
     * @return void
     */
    private function distributeRewards(array $rewards): void {
        foreach ($rewards as $playerId => $reward) {
            $player = new Player($this->getPlayerById($playerId));
            $player->addMoney($reward['money']);
            $player->addExperience($reward['experience']);
        }
    }

    /**
     * Update war status
     *
     * @param string $status New war status
     * @param string $winnerId ID of winning faction
     * @return void
     */
    private function updateWarStatus(string $status, string $winnerId): void {
        $stmt = $this->db->prepare("
            UPDATE faction_wars
            SET status = ?, winner_id = ?, ended_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$status, $winnerId, $this->data['id']]);
    }

    // ... continuing with more FactionWar methods ...
} 