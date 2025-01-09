<?php

namespace Game\GraphQL;

use Game\Database;
use PDO;

/**
 * Class Resolver
 * Base class for all GraphQL resolvers providing common functionality
 * 
 * @package Game\GraphQL
 */
abstract class Resolver {
    /** @var \PDO Database connection instance */
    protected $db;

    /** @var array Request context containing authentication info */
    protected $context;

    /**
     * Resolver constructor
     *
     * @param array $context GraphQL request context
     */
    public function __construct(array $context) {
        $this->db = Database::getInstance()->getConnection();
        $this->context = $context;
    }

    /**
     * Get player information by ID
     *
     * @param string $id Player ID
     * @return array|null Player data or null if not found
     */
    protected function getPlayerById(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM players WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get faction information by ID
     *
     * @param string $id Faction ID
     * @return array|null Faction data or null if not found
     */
    protected function getFactionById(string $id): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM factions WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Validate player existence
     *
     * @param string $playerId Player ID to validate
     * @throws \Exception If player not found
     */
    protected function validatePlayer(string $playerId): void {
        $player = $this->getPlayerById($playerId);
        if (!$player) {
            throw new \Exception("Player not found");
        }
    }

    /**
     * Validate faction existence
     *
     * @param string $factionId Faction ID to validate
     * @throws \Exception If faction not found
     */
    protected function validateFaction(string $factionId): void {
        $faction = $this->getFactionById($factionId);
        if (!$faction) {
            throw new \Exception("Faction not found");
        }
    }
} 