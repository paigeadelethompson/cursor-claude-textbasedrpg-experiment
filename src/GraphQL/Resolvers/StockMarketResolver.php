<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\StockMarket;
use Game\Player;

/**
 * Class StockMarketResolver
 * Handles GraphQL operations for the stock market
 * 
 * @package Game\GraphQL\Resolvers
 */
class StockMarketResolver extends Resolver {
    /**
     * Get all available stocks and their current prices
     *
     * @return array Array of stock information
     */
    public function stocks(): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $stockMarket = new StockMarket($player);
        return $stockMarket->getStocks();
    }

    /**
     * Buy shares of a stock
     *
     * @param array $args GraphQL arguments containing stock transaction details
     * @return array Transaction result
     */
    public function buyStock(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $stockMarket = new StockMarket($player);
        
        return $stockMarket->buyStock(
            $args['input']['stockId'],
            $args['input']['quantity']
        );
    }

    /**
     * Sell shares of a stock
     *
     * @param array $args GraphQL arguments containing stock transaction details
     * @return array Transaction result
     */
    public function sellStock(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $stockMarket = new StockMarket($player);
        
        return $stockMarket->sellStock(
            $args['input']['stockId'],
            $args['input']['quantity']
        );
    }

    /**
     * Get player's stock portfolio
     *
     * @param array $player Player data
     * @return array Array of owned stocks
     */
    public function stocks(array $player): array {
        $stockMarket = new StockMarket(new Player($player));
        return $stockMarket->getPortfolio();
    }

    /**
     * Get stock price history
     *
     * @param array $args GraphQL arguments containing stock ID and time range
     * @return array Array of historical prices
     */
    public function stockPriceHistory(array $args): array {
        $stmt = $this->db->prepare("
            SELECT price, created_at
            FROM stock_price_history
            WHERE stock_id = ?
            AND created_at >= ?
            ORDER BY created_at ASC
        ");
        
        $timeRange = $args['timeRange'] ?? '24h';
        $startTime = date('Y-m-d H:i:s', strtotime("-1 {$timeRange}"));
        
        $stmt->execute([$args['stockId'], $startTime]);
        return $stmt->fetchAll();
    }

    /**
     * Get current market statistics
     *
     * @return array Market statistics
     */
    public function marketStats(): array {
        // Get total market volume
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_transactions,
                SUM(quantity * price) as total_volume
            FROM stock_transactions
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        $stats = $stmt->fetch();

        // Get biggest gainers and losers
        $stmt = $this->db->prepare("
            SELECT 
                s.*,
                ((s.current_price - h.price) / h.price * 100) as price_change
            FROM stocks s
            JOIN stock_price_history h ON h.stock_id = s.id
            WHERE h.created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY price_change DESC
            LIMIT 5
        ");
        $stmt->execute();
        $gainers = $stmt->fetchAll();

        return [
            'volume_24h' => $stats['total_volume'] ?? 0,
            'transactions_24h' => $stats['total_transactions'] ?? 0,
            'top_gainers' => $gainers
        ];
    }
} 