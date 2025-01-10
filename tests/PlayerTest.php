<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Game\Player;

class PlayerTest extends TestCase {
    private $player;

    protected function setUp(): void {
        $this->player = new Player('test-player-id');
    }

    /** @test */
    public function starts_with_default_values(): void {
        $this->assertEquals(100, $this->player->getEnergy());
        $this->assertEquals(100, $this->player->getHappiness());
        $this->assertEquals(0, $this->player->getSatanPoints());
        $this->assertEquals(1.0, $this->player->getShrineModifiers());
        $this->assertEquals(40, $this->player->getStatTotal()); // 10 * 4 stats
    }

    /** @test */
    public function can_subtract_energy(): void {
        $this->player->subtractEnergy(50);
        $this->assertEquals(50, $this->player->getEnergy());
    }

    /** @test */
    public function cannot_subtract_more_energy_than_available(): void {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient energy for sacrifice');
        
        $this->player->subtractEnergy(150);
    }

    /** @test */
    public function can_subtract_happiness(): void {
        $this->player->subtractHappiness(30);
        $this->assertEquals(70, $this->player->getHappiness());
    }

    /** @test */
    public function happiness_cannot_go_below_zero(): void {
        $this->player->subtractHappiness(150);
        $this->assertEquals(0, $this->player->getHappiness());
    }

    /** @test */
    public function can_add_to_valid_stat(): void {
        $this->player->addToStat('strength', 5.5);
        $this->assertEquals(15, $this->player->getStat('strength'));
    }

    /** @test */
    public function cannot_add_to_invalid_stat(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->player->addToStat('invalid_stat', 5);
    }

    /** @test */
    public function can_add_satan_points(): void {
        $this->player->addSatanPoints(10);
        $this->assertEquals(10, $this->player->getSatanPoints());
    }

    /** @test */
    public function can_update_shrine_modifiers(): void {
        $this->player->updateShrineModifiers(1.5);
        $this->assertEquals(1.5, $this->player->getShrineModifiers());
    }

    /** @test */
    public function energy_regenerates_over_time(): void {
        $player = new Player('test-player-id');
        $player->subtractEnergy(50);
        
        // Simulate time passing
        $reflection = new \ReflectionClass($player);
        $lastUpdate = $reflection->getProperty('lastEnergyUpdate');
        $lastUpdate->setAccessible(true);
        $lastUpdate->setValue($player, time() - 600); // 10 minutes ago

        // Should have regenerated 2 energy points (5 minutes per point)
        $this->assertEquals(52, $player->getEnergy());
    }

    /** @test */
    public function happiness_regenerates_over_time(): void {
        $player = new Player('test-player-id');
        $player->subtractHappiness(50);
        
        // Simulate time passing
        $reflection = new \ReflectionClass($player);
        $lastUpdate = $reflection->getProperty('lastHappinessUpdate');
        $lastUpdate->setAccessible(true);
        $lastUpdate->setValue($player, time() - 1200); // 20 minutes ago

        // Should have regenerated 2 happiness points (10 minutes per point)
        $this->assertEquals(52, $player->getHappiness());
    }

    /** @test */
    public function saves_state_to_database(): void {
        $db = $this->createMock(\PDO::class);
        $stmt = $this->createMock(\PDOStatement::class);
        
        $db->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($stmt);

        $db->expects($this->once())
            ->method('beginTransaction');

        $db->expects($this->once())
            ->method('commit');

        $player = new Player('test-player-id', $db);
        $player->save();
    }
} 