<?php

namespace Game;

class Marketplace {
    private $db;
    private $player;

    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    public function listItem(string $itemId, int $quantity, float $price): array {
        // Check if player owns enough of the item
        $inventory = $this->getPlayerInventoryItem($itemId);
        if (!$inventory || $inventory['quantity'] < $quantity) {
            throw new \Exception("Insufficient items to list");
        }

        // Create market listing
        $stmt = $this->db->prepare("
            INSERT INTO market_listings 
            (seller_id, item_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $this->player->getId(),
            $itemId,
            $quantity,
            $price
        ]);

        // Remove items from player's inventory
        $this->updateInventoryQuantity($itemId, -$quantity);

        return [
            'success' => true,
            'listing_id' => $this->db->lastInsertId()
        ];
    }

    public function buyListing(string $listingId): array {
        $listing = $this->getListingInfo($listingId);
        
        if ($this->player->getMoney() < ($listing['price'] * $listing['quantity'])) {
            throw new \Exception("Insufficient funds");
        }

        // Transfer money
        $this->player->deductMoney($listing['price'] * $listing['quantity']);
        $seller = new Player($this->getPlayerById($listing['seller_id']));
        $seller->addMoney($listing['price'] * $listing['quantity']);

        // Transfer items
        $this->addToInventory($listing['item_id'], $listing['quantity']);

        // Remove listing
        $stmt = $this->db->prepare("
            DELETE FROM market_listings WHERE id = ?
        ");
        $stmt->execute([$listingId]);

        return [
            'success' => true,
            'cost' => $listing['price'] * $listing['quantity'],
            'quantity' => $listing['quantity']
        ];
    }

    public function searchListings(array $filters = []): array {
        $sql = "
            SELECT ml.*, i.name as item_name, i.type as item_type, 
                   i.msrp as item_msrp, p.username as seller_name
            FROM market_listings ml
            JOIN items i ON i.id = ml.item_id
            JOIN players p ON p.id = ml.seller_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['type'])) {
            $sql .= " AND i.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['max_price'])) {
            $sql .= " AND ml.price <= ?";
            $params[] = $filters['max_price'];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function getListingInfo(string $listingId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM market_listings WHERE id = ?
        ");
        $stmt->execute([$listingId]);
        $listing = $stmt->fetch();

        if (!$listing) {
            throw new \Exception("Listing not found");
        }

        return $listing;
    }

    private function getPlayerInventoryItem(string $itemId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM inventory 
            WHERE player_id = ? AND item_id = ?
        ");
        $stmt->execute([$this->player->getId(), $itemId]);
        return $stmt->fetch();
    }

    private function updateInventoryQuantity(string $itemId, int $change): void {
        $inventory = $this->getPlayerInventoryItem($itemId);
        
        if ($inventory) {
            $newQuantity = $inventory['quantity'] + $change;
            if ($newQuantity <= 0) {
                $stmt = $this->db->prepare("
                    DELETE FROM inventory 
                    WHERE player_id = ? AND item_id = ?
                ");
                $stmt->execute([$this->player->getId(), $itemId]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE inventory 
                    SET quantity = quantity + ?
                    WHERE player_id = ? AND item_id = ?
                ");
                $stmt->execute([$change, $this->player->getId(), $itemId]);
            }
        } else if ($change > 0) {
            $stmt = $this->db->prepare("
                INSERT INTO inventory (player_id, item_id, quantity)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$this->player->getId(), $itemId, $change]);
        }
    }
} 