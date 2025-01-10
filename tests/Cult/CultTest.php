<?php

namespace Tests\Cult;

use PHPUnit\Framework\TestCase;
use Game\Cult;
use Game\Player;

class CultTest extends TestCase {
    private $cult;
    private $player;

    protected function setUp(): void {
        $this->player = $this->createMock(Player::class);
        $this->cult = new Cult($this->player);
    }

    /** @test */
    public function create_new_cult(): void {
        $name = "Test Cult";
        $description = "A test cult description";

        $result = $this->cult->create($name, $description);

        $this->assertTrue($result['success']);
        $this->assertEquals($name, $result['name']);
        $this->assertEquals($description, $result['description']);
    }

    /** @test */
    public function declare_war_on_another_cult(): void {
        $targetCultId = 'cult-2';
        
        $this->player->method('getCultRank')->willReturn('leader');

        $result = $this->cult->declareWar($targetCultId);

        $this->assertTrue($result['success']);
        $this->assertEquals($targetCultId, $result['defending_cult_id']);
        $this->assertEquals('active', $result['status']);
    }

    /** @test */
    public function form_alliance_with_another_cult(): void {
        $allyCultId = 'cult-2';
        
        $this->player->method('getCultRank')->willReturn('leader');

        $result = $this->cult->formAlliance($allyCultId);

        $this->assertTrue($result['success']);
        $this->assertEquals($allyCultId, $result['ally_cult_id']);
    }
} 