<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\Marketplace;
use Game\Player;

/**
 * Class MarketplaceResolver
 * Handles GraphQL operations for the marketplace
 * 
 * @package Game\GraphQL\Resolvers
 */
class MarketplaceResolver extends Resolver {
    /**
     * Get market listings with optional filters
     *
     * @param array $args GraphQL arguments containing filters
     * @return array Array of market listings
     */
    public function marketListings(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $marketplace = new Marketplace($player);
        return $marketplace->getListings($args['filter'] ?? []);
    }

    /**
     * Create a new market listing
     *
     * @param array $args GraphQL arguments containing listing details
     * @return array Creation result
     */
    public function createMarketListing(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $marketplace = new Marketplace($player);
        return $marketplace->createListing(
            $args['input']['itemId'],
            $args['input']['quantity'],
            $args['input']['price']
        );
    }

    /**
     * Buy items from a market listing
     *
     * @param array $args GraphQL arguments containing listing ID
     * @return array Purchase result
     */
    public function buyMarketListing(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $marketplace = new Marketplace($player);
        return $marketplace->buyListing($args['id']);
    }

    /**
     * Get item information for a market listing
     *
     * @param array $listing The market listing data
     * @return array Item information
     */
    public function item(array $listing): array {
        $stmt = $this->db->prepare("
            SELECT * FROM items WHERE id = ?
        ");
        $stmt->execute([$listing['item_id']]);
        return $stmt->fetch();
    }

    /**
     * Get seller information for a market listing
     *
     * @param array $listing The market listing data
     * @return array Seller information
     */
    public function seller(array $listing): array {
        return $this->getPlayerById($listing['seller_id']);
    }
} 