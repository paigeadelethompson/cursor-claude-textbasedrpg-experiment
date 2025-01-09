<?php

namespace Game;

/**
 * Class Marketplace
 * Handles buying, selling, and listing of items in the game marketplace
 * 
 * @package Game
 */
class Marketplace {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var Player The player instance */
    private $player;

    /** @var float Maximum markup percentage allowed for listings */
    private const MAX_MARKUP_PERCENTAGE = 500; // 500%

    /** @var int Maximum number of active listings per player */
    private const MAX_LISTINGS_PER_PLAYER = 10;

    /** @var array Active changefeeds */
    private static $changefeeds = [];

    /**
     * Marketplace constructor
     *
     * @param Player $player The player instance
     */
    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new market listing
     *
     * @param string $itemId Item identifier
     * @param int $quantity Quantity to list
     * @param float $price Price per unit
     * @return array Listing creation result
     * @throws \Exception If validation fails or insufficient items
     */
    public function createListing(string $itemId, int $quantity, float $price): array {
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

    /**
     * Buy an item from a market listing
     *
     * @param string $listingId Listing identifier
     * @param int $quantity Quantity to buy
     * @return array Purchase result
     * @throws \Exception If validation fails or insufficient funds
     */
    public function buyListing(string $listingId, int $quantity): array {
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

    /**
     * Cancel an active market listing
     *
     * @param string $listingId Listing identifier
     * @return array Cancellation result
     * @throws \Exception If listing not found or not owned by player
     */
    public function cancelListing(string $listingId): array {
        // ... existing code ...
    }

    /**
     * Get all active market listings
     *
     * @param array $filter Optional filter parameters
     * @return array Array of active listings
     */
    public function getListings(array $filter = []): array {
        // ... existing code ...
    }

    /**
     * Get player's active market listings
     *
     * @return array Array of player's listings
     */
    public function getPlayerListings(): array {
        // ... existing code ...
    }

    /**
     * Validate listing price against item MSRP
     *
     * @param float $price Listing price
     * @param float $msrp Item manufacturer's suggested retail price
     * @return bool True if price is valid
     */
    private function validatePrice(float $price, float $msrp): bool {
        // ... existing code ...
    }

    /**
     * Get listing information by ID
     *
     * @param string $listingId Listing identifier
     * @return array|null Listing information or null if not found
     */
    private function getListingById(string $listingId): ?array {
        // ... existing code ...
    }

    /**
     * Check if player has reached maximum listings limit
     *
     * @return bool True if player has reached limit
     */
    private function hasReachedListingLimit(): bool {
        // ... existing code ...
    }

    /**
     * Get information about a specific market listing
     *
     * @param string $listingId The listing identifier
     * @return array Listing information
     * @throws \Exception If listing not found
     */
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

    /**
     * Get a player's inventory item
     *
     * @param string $itemId Item identifier
     * @return array|null Inventory item information or null if not found
     */
    private function getPlayerInventoryItem(string $itemId): ?array {
        $stmt = $this->db->prepare("
            SELECT * FROM inventory 
            WHERE player_id = ? AND item_id = ?
        ");
        $stmt->execute([$this->player->getId(), $itemId]);
        return $stmt->fetch();
    }

    /**
     * Update the quantity of an item in player's inventory
     *
     * @param string $itemId Item identifier
     * @param int $change Amount to change (positive for add, negative for remove)
     * @throws \Exception If insufficient quantity for removal
     */
    private function updateInventoryQuantity(string $itemId, int $change): void {
        if ($change < 0) {
            // Check if player has enough items
            $inventory = $this->getPlayerInventoryItem($itemId);
            if (!$inventory || $inventory['quantity'] < abs($change)) {
                throw new \Exception("Insufficient items");
            }
        }

        $stmt = $this->db->prepare("
            UPDATE inventory 
            SET quantity = quantity + ?
            WHERE player_id = ? AND item_id = ?
        ");
        $stmt->execute([$change, $this->player->getId(), $itemId]);
    }

    /**
     * Add items to player's inventory
     *
     * @param string $itemId Item identifier
     * @param int $quantity Quantity to add
     */
    private function addToInventory(string $itemId, int $quantity): void {
        // Check if player already has this item
        $inventory = $this->getPlayerInventoryItem($itemId);
        
        if ($inventory) {
            $this->updateInventoryQuantity($itemId, $quantity);
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO inventory (player_id, item_id, quantity)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$this->player->getId(), $itemId, $quantity]);
        }
    }

    /**
     * Initialize marketplace changefeed
     * 
     * @return void
     */
    public function initializeChangefeed(): void {
        $stmt = $this->db->prepare("
            CREATE CHANGEFEED FOR TABLE marketplace_listings 
            INTO 'kafka://kafka:9092'
            WITH updated, resolved='10s'
        ");
        $stmt->execute();
    }
} 