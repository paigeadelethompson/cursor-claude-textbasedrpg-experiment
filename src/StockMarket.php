<?php

namespace Game;

/**
 * Class StockMarket
 * Handles stock trading, price updates, and market operations
 * 
 * @package Game
 */
class StockMarket {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var Player The player instance */
    private $player;

    /** @var float Maximum percentage a stock can move in one update */
    private const MAX_PRICE_MOVEMENT = 0.15; // 15%

    /** @var int Minimum minutes between price updates */
    private const PRICE_UPDATE_INTERVAL = 5;

    /**
     * StockMarket constructor
     *
     * @param Player $player The player instance
     */
    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Buy shares of a stock
     *
     * @param string $stockId Stock identifier
     * @param int $quantity Number of shares to buy
     * @return array Transaction result
     * @throws \Exception If validation fails or insufficient funds
     */
    public function buyStock(string $stockId, int $quantity): array {
        $stock = $this->getStockInfo($stockId);
        $totalCost = $stock['current_price'] * $quantity;

        if ($this->player->getMoney() < $totalCost) {
            throw new \Exception("Insufficient funds");
        }

        $stmt = $this->db->prepare("
            INSERT INTO player_stocks 
            (player_id, stock_id, quantity, purchase_price)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $this->player->getId(),
            $stock['id'],
            $quantity,
            $stock['current_price']
        ]);

        $this->player->deductMoney($totalCost);

        return [
            'success' => true,
            'cost' => $totalCost,
            'quantity' => $quantity,
            'stock' => $stock
        ];
    }

    /**
     * Sell shares of a stock
     *
     * @param string $stockId Stock identifier
     * @param int $quantity Number of shares to sell
     * @return array Transaction result
     * @throws \Exception If validation fails or insufficient shares
     */
    public function sellStock(string $stockId, int $quantity): array {
        $stock = $this->getStockInfo($stockId);
        $playerStock = $this->getPlayerStockHolding($stockId);

        if ($playerStock['quantity'] < $quantity) {
            throw new \Exception("Insufficient shares");
        }

        $totalValue = $stock['current_price'] * $quantity;
        
        // Update player's stock quantity
        if ($playerStock['quantity'] == $quantity) {
            $stmt = $this->db->prepare("
                DELETE FROM player_stocks 
                WHERE player_id = ? AND stock_id = ?
            ");
            $stmt->execute([$this->player->getId(), $stock['id']]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE player_stocks 
                SET quantity = quantity - ?
                WHERE player_id = ? AND stock_id = ?
            ");
            $stmt->execute([$quantity, $this->player->getId(), $stock['id']]);
        }

        $this->player->addMoney($totalValue);

        return [
            'success' => true,
            'value' => $totalValue,
            'quantity' => $quantity,
            'stock' => $stock
        ];
    }

    /**
     * Get current stock prices
     *
     * @return array Array of current stock information
     */
    public function getStocks(): array {
        // Simulate market movements
        $stmt = $this->db->prepare("
            SELECT * FROM stocks
        ");
        $stmt->execute();
        $stocks = $stmt->fetchAll();

        foreach ($stocks as $stock) {
            $change = rand(-500, 500) / 100; // -5.00 to +5.00
            $newPrice = max(0.01, $stock['current_price'] + $change);

            // Update current price
            $stmt = $this->db->prepare("
                UPDATE stocks 
                SET current_price = ?, last_updated = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$newPrice, $stock['id']]);

            // Record price history
            $stmt = $this->db->prepare("
                INSERT INTO stock_price_history 
                (stock_id, price) VALUES (?, ?)
            ");
            $stmt->execute([$stock['id'], $newPrice]);
        }
    }

    /**
     * Get player's stock portfolio
     *
     * @return array Array of owned stocks and their details
     */
    public function getPortfolio(): array {
        $stmt = $this->db->prepare("
            SELECT ps.* FROM player_stocks ps
            JOIN stocks s ON s.id = ps.stock_id
            WHERE ps.player_id = ?
        ");
        $stmt->execute([$this->player->getId()]);
        return $stmt->fetchAll();
    }

    /**
     * Update stock prices based on market simulation
     *
     * @return void
     */
    private function updatePrices(): void {
        // Simulate market movements
        $stmt = $this->db->prepare("
            SELECT * FROM stocks
        ");
        $stmt->execute();
        $stocks = $stmt->fetchAll();

        foreach ($stocks as $stock) {
            $change = rand(-500, 500) / 100; // -5.00 to +5.00
            $newPrice = max(0.01, $stock['current_price'] + $change);

            // Update current price
            $stmt = $this->db->prepare("
                UPDATE stocks 
                SET current_price = ?, last_updated = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$newPrice, $stock['id']]);

            // Record price history
            $stmt = $this->db->prepare("
                INSERT INTO stock_price_history 
                (stock_id, price) VALUES (?, ?)
            ");
            $stmt->execute([$stock['id'], $newPrice]);
        }
    }

    /**
     * Calculate new stock price using random walk algorithm
     *
     * @param float $currentPrice Current stock price
     * @return float New calculated price
     */
    private function calculateNewPrice(float $currentPrice): float {
        $change = rand(-500, 500) / 100; // -5.00 to +5.00
        $newPrice = max(0.01, $currentPrice + $change);

        return $newPrice;
    }

    private function getStockInfo(string $symbol): array {
        $stmt = $this->db->prepare("
            SELECT * FROM stocks WHERE symbol = ?
        ");
        $stmt->execute([$symbol]);
        $stock = $stmt->fetch();

        if (!$stock) {
            throw new \Exception("Stock not found");
        }

        return $stock;
    }

    private function getPlayerStockHolding(string $symbol): array {
        $stmt = $this->db->prepare("
            SELECT ps.* FROM player_stocks ps
            JOIN stocks s ON s.id = ps.stock_id
            WHERE ps.player_id = ? AND s.symbol = ?
        ");
        $stmt->execute([$this->player->getId(), $symbol]);
        return $stmt->fetch();
    }

    /**
     * Initialize stock market changefeed
     * 
     * @return void
     */
    public function initializeChangefeed(): void {
        // Create changefeed for stock prices
        $stmt = $this->db->prepare("
            CREATE CHANGEFEED FOR TABLE stock_prices 
            INTO 'kafka://kafka:9092'
            WITH updated, resolved='5s'
        ");
        $stmt->execute();

        // Create changefeed for player transactions
        $stmt = $this->db->prepare("
            CREATE CHANGEFEED FOR TABLE stock_transactions 
            INTO 'kafka://kafka:9092'
            WITH updated, resolved='10s'
        ");
        $stmt->execute();
    }

    /**
     * Update stock prices with market simulation
     *
     * @return void
     */
    public function updatePrices(): void {
        $this->db->beginTransaction();

        try {
            $stocks = $this->getAllStocks();
            foreach ($stocks as $stock) {
                $priceChange = $this->calculatePriceChange($stock['current_price']);
                $newPrice = $stock['current_price'] + $priceChange;

                $stmt = $this->db->prepare("
                    INSERT INTO stock_prices (stock_id, price, timestamp)
                    VALUES (?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$stock['id'], $newPrice]);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
} 