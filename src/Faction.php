<?php

namespace Game;

class Faction {
    private $db;
    private $id;
    private $data;

    public function __construct(string $factionId) {
        $this->db = Database::getInstance()->getConnection();
        $this->id = $factionId;
        $this->loadFactionData();
    }

    private function loadFactionData(): void {
        $stmt = $this->db->prepare("
            SELECT * FROM factions WHERE id = ?
        ");
        $stmt->execute([$this->id]);
        $this->data = $stmt->fetch();

        if (!$this->data) {
            throw new \Exception("Faction not found");
        }
    }

    public static function create(string $name, string $description, Player $leader): self {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO factions (name, description, leader_id)
            VALUES (?, ?, ?)
            RETURNING id
        ");
        
        $stmt->execute([$name, $description, $leader->getId()]);
        $factionId = $stmt->fetchColumn();

        // Add leader as first member
        $stmt = $db->prepare("
            INSERT INTO faction_members (faction_id, player_id, role)
            VALUES (?, ?, 'leader')
        ");
        $stmt->execute([$factionId, $leader->getId()]);

        // Initialize faction ranking
        $stmt = $db->prepare("
            INSERT INTO faction_rankings (faction_id)
            VALUES (?)
        ");
        $stmt->execute([$factionId]);

        return new self($factionId);
    }

    public function addMember(Player $player, string $role = 'member'): void {
        if ($this->isMember($player->getId())) {
            throw new \Exception("Player is already a member");
        }

        $stmt = $this->db->prepare("
            INSERT INTO faction_members (faction_id, player_id, role)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$this->id, $player->getId(), $role]);

        // Update member count
        $stmt = $this->db->prepare("
            UPDATE factions 
            SET member_count = member_count + 1
            WHERE id = ?
        ");
        $stmt->execute([$this->id]);
    }

    public function declareWar(Faction $defender, int $pointsAtStake): array {
        if ($this->hasActiveWar($defender->getId())) {
            throw new \Exception("Already at war with this faction");
        }

        $stmt = $this->db->prepare("
            INSERT INTO faction_wars 
            (attacker_faction_id, defender_faction_id, points_at_stake)
            VALUES (?, ?, ?)
            RETURNING id
        ");
        
        $stmt->execute([
            $this->id,
            $defender->getId(),
            $pointsAtStake
        ]);

        return [
            'success' => true,
            'war_id' => $stmt->fetchColumn()
        ];
    }

    public function recordWarParticipation(string $warId, Player $player, array $stats): void {
        $stmt = $this->db->prepare("
            INSERT INTO faction_war_participation 
            (war_id, player_id, faction_id, attacks_made, damage_dealt, points_contributed)
            VALUES (?, ?, ?, ?, ?, ?)
            ON CONFLICT (war_id, player_id) DO UPDATE
            SET attacks_made = attacks_made + ?,
                damage_dealt = damage_dealt + ?,
                points_contributed = points_contributed + ?
        ");

        $stmt->execute([
            $warId,
            $player->getId(),
            $this->id,
            $stats['attacks'] ?? 0,
            $stats['damage'] ?? 0,
            $stats['points'] ?? 0,
            $stats['attacks'] ?? 0,
            $stats['damage'] ?? 0,
            $stats['points'] ?? 0
        ]);
    }

    public function endWar(string $warId, string $winnerFactionId): void {
        $stmt = $this->db->prepare("
            UPDATE faction_wars
            SET status = 'completed',
                winner_faction_id = ?,
                end_time = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$winnerFactionId, $warId]);

        // Update faction rankings
        $war = $this->getWarInfo($warId);
        $pointsAtStake = $war['points_at_stake'];

        if ($winnerFactionId === $this->id) {
            $this->updateRanking($pointsAtStake, true);
            (new self($war['defender_faction_id']))->updateRanking(-$pointsAtStake, false);
        } else {
            $this->updateRanking(-$pointsAtStake, false);
            (new self($war['attacker_faction_id']))->updateRanking($pointsAtStake, true);
        }
    }

    private function updateRanking(int $pointsChange, bool $won): void {
        $stmt = $this->db->prepare("
            UPDATE faction_rankings
            SET rank_points = rank_points + ?,
                wars_won = wars_won + ?,
                wars_lost = wars_lost + ?,
                last_updated = CURRENT_TIMESTAMP
            WHERE faction_id = ?
        ");

        $stmt->execute([
            $pointsChange,
            $won ? 1 : 0,
            $won ? 0 : 1,
            $this->id
        ]);
    }

    private function hasActiveWar(string $defenderFactionId): bool {
        $stmt = $this->db->prepare("
            SELECT 1 FROM faction_wars
            WHERE (attacker_faction_id = ? OR defender_faction_id = ?)
            AND (attacker_faction_id = ? OR defender_faction_id = ?)
            AND status = 'active'
        ");
        
        $stmt->execute([
            $this->id, $this->id,
            $defenderFactionId, $defenderFactionId
        ]);
        
        return (bool) $stmt->fetch();
    }

    private function getWarInfo(string $warId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM faction_wars WHERE id = ?
        ");
        $stmt->execute([$warId]);
        return $stmt->fetch();
    }

    private function isMember(string $playerId): bool {
        $stmt = $this->db->prepare("
            SELECT 1 FROM faction_members
            WHERE faction_id = ? AND player_id = ?
        ");
        $stmt->execute([$this->id, $playerId]);
        return (bool) $stmt->fetch();
    }
} 