<?php

namespace Game;

/**
 * Class Item
 * Handles game items, their effects, and usage
 * 
 * @package Game
 */
class Item {
    private $id;
    private $name;
    private $type;
    private $msrp;
    private $description;
    private $effects;
    private $modelData;
    private $db;

    /** @var array Valid item types */
    private const VALID_TYPES = ['weapon', 'armor', 'consumable', 'medical', 'special'];

    /** @var array Valid effect types */
    private const VALID_EFFECTS = ['health', 'energy', 'strength', 'defense', 'speed', 'dexterity'];

    /**
     * Item constructor
     *
     * @param string $id Item ID
     * @param \PDO|null $db Database connection instance
     */
    public function __construct(string $id, ?\PDO $db = null) {
        $this->id = $id;
        $this->db = $db;
        
        if ($db) {
            $this->loadFromDatabase();
        }
    }

    /**
     * Load item data from the database
     */
    private function loadFromDatabase(): void {
        $stmt = $this->db->prepare("
            SELECT * FROM items WHERE id = ?
        ");
        $stmt->execute([$this->id]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($data) {
            $this->name = $data['name'];
            $this->type = $data['type'];
            $this->msrp = $data['msrp'];
            $this->description = $data['description'];
            $this->effects = json_decode($data['effects'], true);
            $this->modelData = json_decode($data['model_data'], true);
        }
    }

    /**
     * Get item ID
     *
     * @return string Item ID
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * Get item name
     *
     * @return string Item name
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get item type
     *
     * @return string Item type
     */
    public function getType(): string {
        return $this->type;
    }

    /**
     * Get item MSRP
     *
     * @return float Item MSRP
     */
    public function getMsrp(): float {
        return $this->msrp;
    }

    /**
     * Get item description
     *
     * @return string Item description
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * Get item effects
     *
     * @return array Item effects
     */
    public function getEffects(): array {
        return $this->effects;
    }

    /**
     * Get item model data
     *
     * @return array Item model data
     */
    public function getModelData(): array {
        return $this->modelData;
    }

    /**
     * Check if item is usable
     *
     * @return bool True if item is usable, false otherwise
     */
    public function isUsable(): bool {
        return !($this->type === 'UNUSABLE' || 
                ($this->effects['usable'] ?? true) === false);
    }

    /**
     * Check if item is collectible
     *
     * @return bool True if item is collectible, false otherwise
     */
    public function isCollectible(): bool {
        return $this->effects['collectible'] ?? false;
    }

    /**
     * Create a new item
     *
     * @param \PDO $db Database connection instance
     * @param string $name Item name
     * @param string $type Item type
     * @param float $msrp Item MSRP
     * @param string $description Item description
     * @param array $effects Item effects
     * @param array $modelData Item model data
     * @return Item Created item instance
     */
    public static function create(
        \PDO $db,
        string $name,
        string $type,
        float $msrp,
        string $description,
        array $effects,
        array $modelData
    ): self {
        $stmt = $db->prepare("
            INSERT INTO items (
                name, type, msrp, description, effects, model_data
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $type,
            $msrp,
            $description,
            json_encode($effects),
            json_encode($modelData)
        ]);

        return new self($db->lastInsertId(), $db);
    }

    /**
     * Use an item on a player
     *
     * @param Player $player Target player
     * @return array Usage result
     * @throws \Exception If item cannot be used
     */
    public function use(Player $player): array {
        if ($this->type !== 'consumable' && $this->type !== 'medical') {
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
        if ($this->type !== 'weapon') {
            throw new \Exception("Not a weapon");
        }

        return $this->effects['damage'] ?? 0;
    }

    /**
     * Apply item effects to a player
     *
     * @param Player $player Target player
     * @return array Applied effects
     */
    private function applyEffects(Player $player): array {
        $appliedEffects = [];

        foreach ($this->effects as $stat => $value) {
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