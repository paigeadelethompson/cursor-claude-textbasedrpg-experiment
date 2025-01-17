<?php

namespace Tests\Travel;

use PHPUnit\Framework\TestCase;
use Game\Travel;
use Game\Player;

class TravelTest extends TestCase {
    private $travel;
    private $player;

    protected function setUp(): void {
        $this->player = $this->createMock(Player::class);
        $this->travel = new Travel($this->player);
    }

    /** @test */
    public function travel_to_valid_location(): void {
        $locationId = 'city-1';
        $this->player->method('getEnergy')->willReturn(100);
        $this->player->expects($this->once())
            ->method('subtractEnergy')
            ->with(Travel::ENERGY_COST);

        $result = $this->travel->travelTo($locationId);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($locationId, $result['location']);
    }

    /** @test */
    public function cannot_travel_with_insufficient_energy(): void {
        $this->player->method('getEnergy')->willReturn(5);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient energy');

        $this->travel->travelTo('city-2');
    }

    /** @test */
    public function cannot_travel_to_invalid_location(): void {
        $this->player->method('getEnergy')->willReturn(100);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid location');

        $this->travel->travelTo('nonexistent-city');
    }
} 