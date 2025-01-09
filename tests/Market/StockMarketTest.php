<?php

namespace Tests\Market;

use PHPUnit\Framework\TestCase;
use Game\StockMarket;
use Game\Player;

class StockMarketTest extends TestCase {
    private $stockMarket;
    private $player;

    protected function setUp(): void {
        $this->player = $this->createMock(Player::class);
        $this->stockMarket = new StockMarket($this->player);
    }

    /** @test */
    public function buy_stock_with_sufficient_funds(): void {
        $stockId = 'test-stock-id';
        $quantity = 10;
        $price = 100.00;

        $this->player->method('getMoney')->willReturn(2000.00);
        $this->player->expects($this->once())
            ->method('subtractMoney')
            ->with($quantity * $price);

        $result = $this->stockMarket->buyStock($stockId, $quantity, $price);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($quantity, $result['quantity']);
        $this->assertEquals($price, $result['price']);
    }

    /** @test */
    public function cannot_buy_stock_with_insufficient_funds(): void {
        $this->player->method('getMoney')->willReturn(50.00);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient funds');

        $this->stockMarket->buyStock('test-stock-id', 10, 100.00);
    }
} 