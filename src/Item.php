<?php

namespace Game;

/**
 * Class Item
 * Handles game items, their effects, and usage
 * 
 * @package Game
 */
class Item {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var array Item data */
    private $data;

    /** @var array Valid item types */
    private const VALID_TYPES = ['weapon', 'armor', 'consumable', 'medical', 'special'];

    /** @var array Valid effect types */
    private const VALID_EFFECTS = ['health', 'energy', 'strength', 'defense', 'speed', 'dexterity'];

    /**
     * Item constructor
     *
     * @param array $itemData Item data from database
     * @throws \Exception If item data is invalid
     */
    public function __construct(array $itemData) {
        if (!isset($itemData['id'])) {
            throw new \Exception("Invalid item data");
        }
        $this->data = $itemData;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new item type
     *
     * @param string $name Item name
     * @param string $type Item type
     * @param float $msrp Manufacturer's suggested retail price
     * @param array $effects Item effects
     * @return array Creation result
     * @throws \Exception If validation fails
     */
    public static function create(string $name, string $type, float $msrp, array $effects = []): array {
        if (!in_array($type, self::VALID_TYPES)) {
            throw new \Exception("Invalid item type");
        }

        self::validateEffects($effects);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO items (name, type, msrp, effects)
            VALUES (?, ?, ?, ?)
            RETURNING id
        ");

        $stmt->execute([
            $name,
            $type,
            $msrp,
            json_encode($effects)
        ]);

        return [
            'success' => true,
            'item_id' => $stmt->fetchColumn()
        ];
    }

    /**
     * Use an item on a player
     *
     * @param Player $player Target player
     * @return array Usage result
     * @throws \Exception If item cannot be used
     */
    public function use(Player $player): array {
        if ($this->data['type'] !== 'consumable' && $this->data['type'] !== 'medical') {
            throw new \Exception("This item cannot be used");
        }

        $effects = $this->applyEffects($player);

        return [
            'success' => true,
            'effects_applied' => $effects
        ];
    }

    /**
     * Get item's base damage (for weapons)
     *
     * @return int Base damage value
     * @throws \Exception If item is not a weapon
     */
    public function getBaseDamage(): int {
        if ($this->data['type'] !== 'weapon') {
            throw new \Exception("Not a weapon");
        }

        return $this->data['effects']['damage'] ?? 0;
    }

    /**
     * Apply item effects to a player
     *
     * @param Player $player Target player
     * @return array Applied effects
     */
    private function applyEffects(Player $player): array {
        $appliedEffects = [];

        foreach ($this->data['effects'] as $stat => $value) {
            switch ($stat) {
                case 'health':
                    $player->heal($value);
                    break;
                case 'energy':
                    $player->restoreEnergy($value);
                    break;
                default:
                    if (in_array($stat, self::VALID_EFFECTS)) {
                        $player->updateCombatStat($stat, $value);
                    }
            }
            $appliedEffects[$stat] = $value;
        }

        return $appliedEffects;
    }

    /**
     * Validate item effects
     *
     * @param array $effects Effects to validate
     * @throws \Exception If effects are invalid
     */
    private static function validateEffects(array $effects): void {
        foreach ($effects as $stat => $value) {
            if (!in_array($stat, self::VALID_EFFECTS)) {
                throw new \Exception("Invalid effect type: $stat");
            }
            if (!is_numeric($value)) {
                throw new \Exception("Effect value must be numeric");
            }
        }
    }
} 