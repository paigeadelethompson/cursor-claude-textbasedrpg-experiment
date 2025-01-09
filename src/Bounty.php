<?php

namespace Game;

class Bounty {
    private $db;
    private $player;
    private const MAX_BOUNTIES_PER_PLAYER = 10;
    private const MIN_BOUNTY_AMOUNT = 1000;

    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    public function placeBounty(string $targetId, float $amount): array {
        if ($targetId === $this->player->getId()) {
            throw new \Exception("Cannot place bounty on yourself");
        }

        if ($amount < self::MIN_BOUNTY_AMOUNT) {
            throw new \Exception("Minimum bounty amount is $" . self::MIN_BOUNTY_AMOUNT);
        }

        if ($this->player->getMoney() < $amount) {
            throw new \Exception("Insufficient funds");
        }

        // Check if target exists
        $target = $this->getPlayerById($targetId);
        if (!$target) {
            throw new \Exception("Target player not found");
        }

        // Check bounty limit
        $activeBounties = $this->getActiveBountiesCount();
        if ($activeBounties >= self::MAX_BOUNTIES_PER_PLAYER) {
            throw new \Exception("Maximum bounties limit reached");
        }

        // Check if already has active bounty on target
        if ($this->hasActiveBountyOn($targetId)) {
            throw new \Exception("Already have active bounty on this player");
        }

        $stmt = $this->db->prepare("
            INSERT INTO bounties (issuer_id, target_id, amount)
            VALUES (?, ?, ?)
            RETURNING id
        ");

        $stmt->execute([
            $this->player->getId(),
            $targetId,
            $amount
        ]);

        $this->player->deductMoney($amount);

        return [
            'success' => true,
            'bounty_id' => $stmt->fetchColumn(),
            'amount' => $amount
        ];
    }

    public function claimBounty(string $bountyId, string $hospitalStayId): array {
        $bounty = $this->getBountyById($bountyId);
        if (!$bounty || $bounty['status'] !== 'active') {
            throw new \Exception("Invalid or inactive bounty");
        }

        // Verify hospital stay
        $stmt = $this->db->prepare("
            SELECT * FROM hospital_stays 
            WHERE id = ? AND player_id = ? AND attacker_id = ?
            AND status = 'admitted'
            AND created_at > CURRENT_TIMESTAMP - INTERVAL '5 minutes'
        ");
        $stmt->execute([
            $hospitalStayId,
            $bounty['target_id'],
            $this->player->getId()
        ]);

        if (!$stmt->fetch()) {
            throw new \Exception("Invalid claim: Must be recent hospital admission by you");
        }

        // Update bounty status
        $stmt = $this->db->prepare("
            UPDATE bounties 
            SET status = 'claimed', 
                claimed_by = ?,
                claimed_at = CURRENT_TIMESTAMP
            WHERE id = ? AND status = 'active'
            RETURNING amount
        ");

        $stmt->execute([$this->player->getId(), $bountyId]);
        $amount = $stmt->fetchColumn();

        if (!$amount) {
            throw new \Exception("Bounty already claimed");
        }

        $this->player->addMoney($amount);

        return [
            'success' => true,
            'amount' => $amount
        ];
    }

    public function getActiveBounties(): array {
        $stmt = $this->db->prepare("
            SELECT 
                b.*,
                p1.username as issuer_name,
                p2.username as target_name
            FROM bounties b
            JOIN players p1 ON p1.id = b.issuer_id
            JOIN players p2 ON p2.id = b.target_id
            WHERE b.status = 'active'
            ORDER BY b.amount DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBountiesOnPlayer(string $playerId): array {
        $stmt = $this->db->prepare("
            SELECT 
                b.*,
                p.username as issuer_name
            FROM bounties b
            JOIN players p ON p.id = b.issuer_id
            WHERE b.target_id = ? AND b.status = 'active'
            ORDER BY b.amount DESC
        ");
        $stmt->execute([$playerId]);
        return $stmt->fetchAll();
    }

    private function getActiveBountiesCount(): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM bounties
            WHERE issuer_id = ? AND status = 'active'
        ");
        $stmt->execute([$this->player->getId()]);
        return $stmt->fetchColumn();
    }

    private function hasActiveBountyOn(string $targetId): bool {
        $stmt = $this->db->prepare("
            SELECT 1 FROM bounties
            WHERE issuer_id = ? AND target_id = ? AND status = 'active'
        ");
        $stmt->execute([$this->player->getId(), $targetId]);
        return (bool) $stmt->fetch();
    }

    private function getBountyById(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM bounties WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private function getPlayerById(string $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM players WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
} 