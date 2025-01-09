<?php

namespace Game;

/**
 * Class Inventory
 * Manages player inventory, items, and equipment slots
 * 
 * @package Game
 */
class Inventory {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var Player The player instance */
    private $player;

    /** @var array Valid equipment slots */
    private const VALID_SLOTS = ['primary', 'secondary', 'armor', 'temporary'];

    /** @var int Maximum items per stack */
    private const MAX_STACK_SIZE = 99;

    /**
     * Inventory constructor
     *
     * @param Player $player The player instance
     */
    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Add items to player's inventory
     *
     * @param string $itemId Item identifier
     * @param int $quantity Quantity to add
     * @return array Addition result
     * @throws \Exception If item doesn't exist
     */
    public function addItem(string $itemId, int $quantity): array {
        $item = $this->getItemInfo($itemId);
        if (!$item) {
            throw new \Exception("Item not found");
        }

        $existingItem = $this->getPlayerItem($itemId);
        if ($existingItem) {
            $stmt = $this->db->prepare("
                UPDATE inventory 
                SET quantity = quantity + ?
                WHERE player_id = ? AND item_id = ?
            ");
            $stmt->execute([$quantity, $this->player->getId(), $itemId]);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO inventory (player_id, item_id, quantity)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$this->player->getId(), $itemId, $quantity]);
        }

        return [
            'success' => true,
            'item' => $item,
            'quantity' => $quantity
        ];
    }

    /**
     * Remove items from player's inventory
     *
     * @param string $itemId Item identifier
     * @param int $quantity Quantity to remove
     * @return array Removal result
     * @throws \Exception If insufficient quantity
     */
    public function removeItem(string $itemId, int $quantity): array {
        $existingItem = $this->getPlayerItem($itemId);
        if (!$existingItem || $existingItem['quantity'] < $quantity) {
            throw new \Exception("Insufficient items");
        }

        if ($existingItem['quantity'] == $quantity) {
            $stmt = $this->db->prepare("
                DELETE FROM inventory 
                WHERE player_id = ? AND item_id = ?
            ");
            $stmt->execute([$this->player->getId(), $itemId]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE inventory 
                SET quantity = quantity - ?
                WHERE player_id = ? AND item_id = ?
            ");
            $stmt->execute([$quantity, $this->player->getId(), $itemId]);
        }

        return [
            'success' => true,
            'item_id' => $itemId,
            'quantity_removed' => $quantity
        ];
    }

    /**
     * Equip an item to a slot
     *
     * @param string $itemId Item identifier
     * @param string $slot Equipment slot
     * @return array Equipment result
     * @throws \Exception If slot is invalid or item cannot be equipped
     */
    public function equipItem(string $itemId, string $slot): array {
        if (!in_array($slot, self::VALID_SLOTS)) {
            throw new \Exception("Invalid equipment slot");
        }

        $item = $this->getPlayerItem($itemId);
        if (!$item) {
            throw new \Exception("Item not found in inventory");
        }

        // Unequip current item in slot
        $this->unequipSlot($slot);

        $stmt = $this->db->prepare("
            UPDATE inventory 
            SET equipped_slot = ?
            WHERE player_id = ? AND item_id = ?
        ");
        $stmt->execute([$slot, $this->player->getId(), $itemId]);

        return [
            'success' => true,
            'item_id' => $itemId,
            'slot' => $slot
        ];
    }

    /**
     * Get all items in player's inventory
     *
     * @return array Array of inventory items
     */
    public function getItems(): array {
        $stmt = $this->db->prepare("
            SELECT i.*, it.name, it.type, it.effects
            FROM inventory i
            JOIN items it ON it.id = i.item_id
            WHERE i.player_id = ?
        ");
        $stmt->execute([$this->player->getId()]);
        return $stmt->fetchAll();
    }

    /**
     * Get equipped items
     *
     * @return array Array of equipped items by slot
     */
    public function getEquippedItems(): array {
        $stmt = $this->db->prepare("
            SELECT i.*, it.name, it.type, it.effects, i.equipped_slot
            FROM inventory i
            JOIN items it ON it.id = i.item_id
            WHERE i.player_id = ? AND i.equipped_slot IS NOT NULL
        ");
        $stmt->execute([$this->player->getId()]);
        
        $equipped = [];
        foreach ($stmt->fetchAll() as $item) {
            $equipped[$item['equipped_slot']] = $item;
        }
        return $equipped;
    }

    // ... continuing with more inventory methods ...
} 