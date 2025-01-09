<?php

namespace Game;

/**
 * Class Faction
 * Handles faction management, wars, and member operations
 * 
 * @package Game
 */
class Faction {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var array Faction data */
    private $data;

    /** @var int Minimum members needed for war declaration */
    private const MIN_MEMBERS_FOR_WAR = 5;

    /** @var int Maximum active wars allowed */
    private const MAX_ACTIVE_WARS = 3;

    /** @var array Valid member ranks */
    private const RANKS = ['leader', 'officer', 'member', 'recruit'];

    /**
     * Faction constructor
     *
     * @param array $factionData Faction data from database
     * @throws \Exception If faction data is invalid
     */
    public function __construct(array $factionData) {
        if (!isset($factionData['id'])) {
            throw new \Exception("Invalid faction data");
        }
        $this->data = $factionData;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new faction
     *
     * @param Player $leader Faction leader
     * @param string $name Faction name
     * @param string $description Faction description
     * @return array Creation result
     * @throws \Exception If validation fails
     */
    public static function create(Player $leader, string $name, string $description): array {
        if (strlen($name) < 3 || strlen($name) > 30) {
            throw new \Exception("Faction name must be between 3 and 30 characters");
        }

        $db = Database::getInstance()->getConnection();
        
        // Check if name is taken
        $stmt = $db->prepare("SELECT 1 FROM factions WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetch()) {
            throw new \Exception("Faction name already taken");
        }

        // Create faction
        $stmt = $db->prepare("
            INSERT INTO factions (name, description, leader_id)
            VALUES (?, ?, ?)
            RETURNING id
        ");
        $stmt->execute([$name, $description, $leader->getId()]);
        $factionId = $stmt->fetchColumn();

        // Add leader as member
        $stmt = $db->prepare("
            INSERT INTO faction_members (faction_id, player_id, rank)
            VALUES (?, ?, 'leader')
        ");
        $stmt->execute([$factionId, $leader->getId()]);

        return [
            'success' => true,
            'faction_id' => $factionId
        ];
    }

    /**
     * Declare war on another faction
     *
     * @param Faction $targetFaction Target faction
     * @param string $reason War declaration reason
     * @return array War declaration result
     * @throws \Exception If war declaration conditions are not met
     */
    public function declareWar(Faction $targetFaction, string $reason): array {
        if ($this->getActiveWarsCount() >= self::MAX_ACTIVE_WARS) {
            throw new \Exception("Maximum active wars reached");
        }

        if ($this->getMemberCount() < self::MIN_MEMBERS_FOR_WAR) {
            throw new \Exception("Not enough members to declare war");
        }

        $stmt = $this->db->prepare("
            INSERT INTO faction_wars 
            (attacker_id, defender_id, reason, started_at)
            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
            RETURNING id
        ");
        
        $stmt->execute([
            $this->getId(),
            $targetFaction->getId(),
            $reason
        ]);

        return [
            'success' => true,
            'war_id' => $stmt->fetchColumn()
        ];
    }

    /**
     * Add a new member to the faction
     *
     * @param Player $player Player to add
     * @param string $rank Member rank
     * @return array Addition result
     * @throws \Exception If player is already in a faction
     */
    public function addMember(Player $player, string $rank = 'recruit'): array {
        if (!in_array($rank, self::RANKS)) {
            throw new \Exception("Invalid rank");
        }

        if ($this->getPlayerFaction($player->getId())) {
            throw new \Exception("Player is already in a faction");
        }

        $stmt = $this->db->prepare("
            INSERT INTO faction_members (faction_id, player_id, rank)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([$this->getId(), $player->getId(), $rank]);

        return [
            'success' => true,
            'rank' => $rank
        ];
    }

    // ... continuing with more faction methods ...
} 