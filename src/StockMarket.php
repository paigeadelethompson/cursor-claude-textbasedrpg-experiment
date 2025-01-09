<?php

namespace Game;

class StockMarket {
    private $db;
    private $player;

    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    public function buyStock(string $symbol, int $quantity): array {
        $stock = $this->getStockInfo($symbol);
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

    public function sellStock(string $symbol, int $quantity): array {
        $stock = $this->getStockInfo($symbol);
        $playerStock = $this->getPlayerStockHolding($symbol);

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

    public function updateStockPrices(): void {
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
} 