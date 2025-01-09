<?php

namespace Game;

class Inventory {
    private $db;
    private $player;

    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    public function getItems(): array {
        $stmt = $this->db->prepare("
            SELECT i.*, inv.quantity, inv.equipped_slot
            FROM inventory inv
            JOIN items i ON i.id = inv.item_id
            WHERE inv.player_id = ?
            ORDER BY i.type, i.name
        ");
        $stmt->execute([$this->player->getId()]);
        return $stmt->fetchAll();
    }

    public function equipItem(string $itemId, string $slot): array {
        // Validate slot type
        if (!in_array($slot, ['primary', 'secondary', 'temporary'])) {
            throw new \Exception("Invalid equipment slot");
        }

        // Check if player owns the item
        $inventory = $this->getPlayerInventoryItem($itemId);
        if (!$inventory) {
            throw new \Exception("Item not found in inventory");
        }

        // Unequip current item in slot if exists
        $stmt = $this->db->prepare("
            UPDATE inventory 
            SET equipped_slot = NULL
            WHERE player_id = ? AND equipped_slot = ?
        ");
        $stmt->execute([$this->player->getId(), $slot]);

        // Equip new item
        $stmt = $this->db->prepare("
            UPDATE inventory 
            SET equipped_slot = ?
            WHERE player_id = ? AND item_id = ?
        ");
        $stmt->execute([$slot, $this->player->getId(), $itemId]);

        return [
            'success' => true,
            'slot' => $slot,
            'item_id' => $itemId
        ];
    }

    public function useItem(string $itemId): array {
        $item = $this->getItemInfo($itemId);
        $inventory = $this->getPlayerInventoryItem($itemId);

        if (!$inventory) {
            throw new \Exception("Item not found in inventory");
        }

        // Apply item effects
        $effects = json_decode($item['effects'], true);
        foreach ($effects as $effect => $value) {
            switch ($effect) {
                case 'health':
                    $this->player->modifyHealth($value);
                    break;
                case 'energy':
                    $this->player->modifyEnergy($value);
                    break;
                case 'happiness':
                    $this->player->modifyHappiness($value);
                    break;
                // Add other effect types as needed
            }
        }

        // Remove one item from inventory
        $this->updateInventoryQuantity($itemId, -1);

        return [
            'success' => true,
            'effects_applied' => $effects
        ];
    }

    public function getEquippedItems(): array {
        $stmt = $this->db->prepare("
            SELECT i.*, inv.equipped_slot
            FROM inventory inv
            JOIN items i ON i.id = inv.item_id
            WHERE inv.player_id = ? AND inv.equipped_slot IS NOT NULL
        ");
        $stmt->execute([$this->player->getId()]);
        return $stmt->fetchAll();
    }

    private function getItemInfo(string $itemId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM items WHERE id = ?
        ");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            throw new \Exception("Item not found");
        }

        return $item;
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
        }
    }
} 