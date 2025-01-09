<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\Marketplace;
use Game\Player;

class MarketplaceResolver extends Resolver {
    public function marketListings(array $args): array {
        $marketplace = new Marketplace(null);
        return $marketplace->searchListings($args['filter'] ?? []);
    }

    public function createMarketListing(array $args): array {
        $this->validatePlayer($args['input']['sellerId']);
        
        $player = new Player($this->getPlayerById($args['input']['sellerId']));
        $marketplace = new Marketplace($player);
        
        return $marketplace->listItem(
            $args['input']['itemId'],
            $args['input']['quantity'],
            $args['input']['price']
        );
    }

    public function buyMarketListing(array $args): array {
        $this->validatePlayer($args['playerId']);
        
        $player = new Player($this->getPlayerById($args['playerId']));
        $marketplace = new Marketplace($player);
        
        return $marketplace->buyListing($args['id']);
    }

    public function item(array $listing): array {
        $stmt = $this->db->prepare("
            SELECT * FROM items WHERE id = ?
        ");
        $stmt->execute([$listing['item_id']]);
        return $stmt->fetch();
    }

    public function seller(array $listing): array {
        return $this->getPlayerById($listing['seller_id']);
    }
} 