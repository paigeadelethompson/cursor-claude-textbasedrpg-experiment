<?php

namespace Game\GraphQL;

use Game\Database;
use PDO;

abstract class Resolver {
    protected PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    protected function getPlayerById(string $id): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM players WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    protected function getFactionById(string $id): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM factions WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    protected function validatePlayer(string $playerId): void {
        $player = $this->getPlayerById($playerId);
        if (!$player) {
            throw new \Exception("Player not found");
        }
    }

    protected function validateFaction(string $factionId): void {
        $faction = $this->getFactionById($factionId);
        if (!$faction) {
            throw new \Exception("Faction not found");
        }
    }
} 