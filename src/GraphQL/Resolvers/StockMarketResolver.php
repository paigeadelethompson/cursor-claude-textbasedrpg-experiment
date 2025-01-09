<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\StockMarket;
use Game\Player;

class StockMarketResolver extends Resolver {
    public function stocks(): array {
        $stmt = $this->db->prepare("
            SELECT * FROM stocks
            ORDER BY symbol ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function stockPriceHistory(array $stock): array {
        $stmt = $this->db->prepare("
            SELECT price, timestamp 
            FROM stock_price_history
            WHERE stock_id = ?
            ORDER BY timestamp DESC
            LIMIT 100
        ");
        $stmt->execute([$stock['id']]);
        return $stmt->fetchAll();
    }

    public function buyStock(array $args): array {
        $this->validatePlayer($args['input']['playerId']);
        
        $player = new Player($this->getPlayerById($args['input']['playerId']));
        $stockMarket = new StockMarket($player);
        
        return $stockMarket->buyStock(
            $args['input']['stockId'],
            $args['input']['quantity']
        );
    }

    public function sellStock(array $args): array {
        $this->validatePlayer($args['input']['playerId']);
        
        $player = new Player($this->getPlayerById($args['input']['playerId']));
        $stockMarket = new StockMarket($player);
        
        return $stockMarket->sellStock(
            $args['input']['stockId'],
            $args['input']['quantity']
        );
    }
} 