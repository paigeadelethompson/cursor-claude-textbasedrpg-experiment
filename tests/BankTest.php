<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Game\Bank;
use Game\Player;

class BankTest extends TestCase {
    private $bank;
    private $player;

    protected function setUp(): void {
        $this->player = $this->createMock(Player::class);
        $this->bank = new Bank($this->player);
    }

    public function testCreateCD(): void {
        $amount = 1000.00;
        $termMonths = 3;

        $this->player->method('getMoney')->willReturn(2000.00);
        $this->player->expects($this->once())
            ->method('subtractMoney')
            ->with($amount);

        $result = $this->bank->createCD($amount, $termMonths);
        
        $this->assertTrue($result['success']);
        $this->assertEquals($amount, $result['amount']);
        $this->assertEquals($termMonths, $result['term_months']);
    }

    public function testCalculateInterest(): void {
        $cd = [
            'amount' => 1000.00,
            'term_months' => 3,
            'start_date' => date('Y-m-d H:i:s', strtotime('-3 months')),
            'interest_rate' => 2.5
        ];

        $interest = $this->bank->calculateCDInterest($cd);
        $expectedInterest = 1000 * (0.025 / 4); // 2.5% APR for 3 months

        $this->assertEquals($expectedInterest, $interest);
    }
} 