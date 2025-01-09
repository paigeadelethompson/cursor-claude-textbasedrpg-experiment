<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\Faction;
use Game\Player;
use Game\FactionWar;

/**
 * Class FactionResolver
 * Handles GraphQL operations for factions and faction warfare
 * 
 * @package Game\GraphQL\Resolvers
 */
class FactionResolver extends Resolver {
    /**
     * Get faction information
     *
     * @param array $args GraphQL arguments containing faction ID
     * @return array Faction data
     */
    public function faction(array $args): array {
        return $this->getFactionById($args['id']);
    }

    /**
     * Create a new faction
     *
     * @param array $args GraphQL arguments containing faction details
     * @return array Creation result
     */
    public function createFaction(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        return Faction::create(
            $player,
            $args['input']['name'],
            $args['input']['description']
        );
    }

    /**
     * Get faction members
     *
     * @param array $args GraphQL arguments containing faction ID
     * @return array Array of faction members
     */
    public function factionMembers(array $args): array {
        $stmt = $this->db->prepare("
            SELECT 
                fm.*,
                p.username,
                p.level
            FROM faction_members fm
            JOIN players p ON p.id = fm.player_id
            WHERE fm.faction_id = ?
            ORDER BY fm.rank ASC, p.level DESC
        ");
        $stmt->execute([$args['factionId']]);
        return $stmt->fetchAll();
    }

    /**
     * Get faction wars
     *
     * @param array $args GraphQL arguments containing faction ID
     * @return array Array of faction wars
     */
    public function factionWars(array $args): array {
        $stmt = $this->db->prepare("
            SELECT * FROM faction_wars
            WHERE attacker_id = ? OR defender_id = ?
            ORDER BY started_at DESC
        ");
        $stmt->execute([$args['factionId'], $args['factionId']]);
        return $stmt->fetchAll();
    }

    /**
     * Promote faction member
     *
     * @param array $args GraphQL arguments containing promotion details
     * @return bool Success status
     */
    public function promoteMember(array $args): bool {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $faction = new Faction($this->getFactionById($args['input']['factionId']));
        
        return $faction->promoteMember(
            $args['input']['memberId'],
            $args['input']['newRank']
        );
    }

    /**
     * Declare war on another faction
     *
     * @param array $args GraphQL arguments containing war declaration details
     * @return array War declaration result
     */
    public function declareWar(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $faction = new Faction($this->getFactionById($args['input']['factionId']));
        $targetFaction = new Faction($this->getFactionById($args['input']['targetId']));
        
        return $faction->declareWar($targetFaction, $args['input']['reason']);
    }

    // ... continuing with more resolver methods ...
} 