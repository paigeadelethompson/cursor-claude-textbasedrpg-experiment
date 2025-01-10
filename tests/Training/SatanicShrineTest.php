<?php

namespace Tests\Training;

use PHPUnit\Framework\TestCase;
use Game\Training\SatanicShrine;
use Game\Player;

class SatanicShrineTest extends TestCase
{
    private $shrine;
    private $player;

    protected function setUp(): void
    {
        $this->player = $this->createMock(Player::class);
        $this->shrine = new SatanicShrine($this->player);
    }

    /** @test */
    public function calculate_gains_with_formula(): void
    {
        $modifiers = 1.5;
        $satanPoints = 10;
        $sacrificedEnergy = 5;
        $happy = 1000;
        $statTotal = 1000;

        // Constants from the formula
        $a = 3.480061091e-7;
        $b = 250;
        $c = 3.091619094e-6;
        $d = 6.82775184551527e-5;
        $e = -0.0301431777;

        // Expected result using the formula:
        // (Modifiers)*(Satan Points)*(Sacrificed Energy)*[ (a*ln(Happy+b)+c) * (Stat Total) + d*(Happy+b) + e ]
        $expected = $modifiers * $satanPoints * $sacrificedEnergy * (
            ($a * log($happy + $b) + $c) * $statTotal + 
            $d * ($happy + $b) + 
            $e
        );

        $result = $this->shrine->calculateGains(
            $modifiers,
            $satanPoints,
            $sacrificedEnergy,
            $happy,
            $statTotal
        );

        $this->assertEquals($expected, $result, '', 0.00001);
    }

    /** @test */
    public function sacrifice_reduces_happiness(): void
    {
        $sacrificedEnergy = 100;
        $initialHappiness = 1000;

        $this->player->method('getShrineModifiers')->willReturn(1.0);
        $this->player->method('getSatanPoints')->willReturn(10);
        $this->player->method('getHappiness')->willReturn($initialHappiness);
        $this->player->method('getStatTotal')->willReturn(1000);

        $this->player->expects($this->once())
            ->method('subtractHappiness')
            ->with($this->callback(function($happinessLoss) use ($sacrificedEnergy) {
                return $happinessLoss >= $sacrificedEnergy * 0.4 && 
                       $happinessLoss <= $sacrificedEnergy * 0.6;
            }));

        $result = $this->shrine->sacrifice('strength', $sacrificedEnergy);
        
        $this->assertArrayHasKey('happiness_lost', $result);
        $this->assertGreaterThanOrEqual($sacrificedEnergy * 0.4, $result['happiness_lost']);
        $this->assertLessThanOrEqual($sacrificedEnergy * 0.6, $result['happiness_lost']);
    }

    /** @test */
    public function sacrifice_updates_player_state(): void
    {
        $sacrificedEnergy = 50;
        $stat = 'strength';

        $this->player->method('getShrineModifiers')->willReturn(1.0);
        $this->player->method('getSatanPoints')->willReturn(10);
        $this->player->method('getHappiness')->willReturn(1000);
        $this->player->method('getStatTotal')->willReturn(1000);

        $this->player->expects($this->once())
            ->method('subtractEnergy')
            ->with($sacrificedEnergy);

        $this->player->expects($this->once())
            ->method('subtractHappiness')
            ->with($this->callback(function($loss) use ($sacrificedEnergy) {
                return $loss >= $sacrificedEnergy * 0.4 && $loss <= $sacrificedEnergy * 0.6;
            }));

        $this->player->expects($this->once())
            ->method('addToStat')
            ->with(
                $this->equalTo($stat),
                $this->isType('float')
            );

        $result = $this->shrine->sacrifice($stat, $sacrificedEnergy);

        $this->assertTrue($result['success']);
        $this->assertEquals($stat, $result['stat']);
        $this->assertEquals($sacrificedEnergy, $result['energy_sacrificed']);
        $this->assertArrayHasKey('happiness_lost', $result);
        $this->assertArrayHasKey('gains', $result);
    }

    /** @test */
    public function cannot_sacrifice_for_invalid_stat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid stat for sacrifice. Must be one of: strength, defense, speed, dexterity");

        $this->shrine->sacrifice('invalid_stat', 50);
    }

    /** @test */
    public function can_sacrifice_for_all_valid_stats(): void
    {
        $this->player->method('getShrineModifiers')->willReturn(1.0);
        $this->player->method('getSatanPoints')->willReturn(10);
        $this->player->method('getHappiness')->willReturn(1000);
        $this->player->method('getStatTotal')->willReturn(1000);

        foreach (SatanicShrine::STATS as $stat) {
            $result = $this->shrine->sacrifice($stat, 50);
            $this->assertTrue($result['success']);
            $this->assertEquals($stat, $result['stat']);
        }
    }
} 