<?php

namespace Tests\Combat;

use PHPUnit\Framework\TestCase;
use Game\Combat;
use Game\Player;

class CombatTest extends TestCase {
    private $combat;
    private $player;

    protected function setUp(): void {
        $this->player = $this->createMock(Player::class);
        $this->combat = new Combat($this->player);
    }

    public function testAttackPlayer(): void {
        $targetId = 'target-1';
        $this->player->method('getEnergy')->willReturn(100);
        $this->player->expects($this->once())
            ->method('subtractEnergy')
            ->with(Combat::ATTACK_ENERGY_COST);

        $result = $this->combat->attackPlayer($targetId);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('damage', $result);
    }

    public function testCannotAttackWithInsufficientEnergy(): void {
        $this->player->method('getEnergy')->willReturn(5);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient energy');

        $this->combat->attackPlayer('target-1');
    }
} 