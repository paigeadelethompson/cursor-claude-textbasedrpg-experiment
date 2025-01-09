<?php

namespace Tests\Market;

use PHPUnit\Framework\TestCase;
use Game\Marketplace;
use Game\Player;

class MarketplaceTest extends TestCase {
    private $marketplace;
    private $player;

    protected function setUp(): void {
        $this->player = $this->createMock(Player::class);
        $this->marketplace = new Marketplace($this->player);
    }

    /** @test */
    public function create_listing(): void {
        $itemId = 'test-item-id';
        $quantity = 1;
        $price = 100.00;

        $result = $this->marketplace->createListing($itemId, $quantity, $price);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($quantity, $result['quantity']);
        $this->assertEquals($price, $result['price']);
    }

    /** @test */
    public function cannot_exceed_max_listings(): void {
        for ($i = 0; $i < Marketplace::MAX_LISTINGS_PER_PLAYER; $i++) {
            $this->marketplace->createListing('item-' . $i, 1, 100.00);
        }

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Maximum listings reached');

        $this->marketplace->createListing('one-too-many', 1, 100.00);
    }
} 