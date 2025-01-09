<?php

namespace Tests\Bank;

use PHPUnit\Framework\TestCase;
use Game\Bank\CD;
use Game\Player;

class CDTest extends TestCase {
    private $cd;
    private $player;

    protected function setUp(): void {
        $this->player = $this->createMock(Player::class);
        $this->cd = new CD($this->player);
    }

    public function testCalculateInterestRate(): void {
        $amount = 1000.00;
        $termMonths = 12;

        $rate = $this->cd->calculateInterestRate($amount, $termMonths);
        
        $this->assertIsFloat($rate);
        $this->assertGreaterThan(0, $rate);
    }

    public function testCalculateMaturityDate(): void {
        $termMonths = 6;
        $startDate = new \DateTime();
        
        $maturityDate = $this->cd->calculateMaturityDate($termMonths, $startDate);
        
        $this->assertEquals(
            $startDate->modify("+{$termMonths} months")->format('Y-m-d'),
            $maturityDate->format('Y-m-d')
        );
    }
} 