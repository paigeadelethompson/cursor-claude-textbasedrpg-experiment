<?php

namespace Tests\Training;

use PHPUnit\Framework\TestCase;
use Game\Training\WorshipSatan;
use Game\Player;

class WorshipSatanTest extends TestCase
{
    private $worshipSatan;
    private $player;

    protected function setUp(): void
    {
        $this->player = $this->createMock(Player::class);
        $this->worshipSatan = new WorshipSatan($this->player);
    }

    /** @test */
    public function calculate_gains_with_formula(): void
    {
        $modifiers = 1.5;
        $satanPoints = 10;
        $energyPerTrain = 5;
        $happy = 1000;
        $statTotal = 1000;

        // Constants from the formula
        $a = 3.480061091e-7;
        $b = 250;
        $c = 3.091619094e-6;
        $d = 6.82775184551527e-5;
        $e = -0.0301431777;

        // Expected result using the formula:
        // (Modifiers)*(Satan Points)*(Energy Per Train)*[ (a*ln(Happy+b)+c) * (Stat Total) + d*(Happy+b) + e ]
        $expected = $modifiers * $satanPoints * $energyPerTrain * (
            ($a * log($happy + $b) + $c) * $statTotal + 
            $d * ($happy + $b) + 
            $e
        );

        $result = $this->worshipSatan->calculateGains(
            $modifiers,
            $satanPoints,
            $energyPerTrain,
            $happy,
            $statTotal
        );

        $this->assertEquals($expected, $result, '', 0.00001);
    }

    /** @test */
    public function worship_reduces_happiness(): void
    {
        $energy = 100;
        $initialHappiness = 1000;

        $this->player->method('getGymModifiers')->willReturn(1.0);
        $this->player->method('getSatanPoints')->willReturn(10);
        $this->player->method('getHappiness')->willReturn($initialHappiness);
        $this->player->method('getStatTotal')->willReturn(1000);

        $this->player->expects($this->once())
            ->method('subtractHappiness')
            ->with($this->callback(function($happinessLoss) use ($energy) {
                return $happinessLoss >= $energy * 0.4 && 
                       $happinessLoss <= $energy * 0.6;
            }));

        $result = $this->worshipSatan->worship('strength', $energy);
        
        $this->assertArrayHasKey('happiness_lost', $result);
        $this->assertGreaterThanOrEqual($energy * 0.4, $result['happiness_lost']);
        $this->assertLessThanOrEqual($energy * 0.6, $result['happiness_lost']);
    }

    /** @test */
    public function worship_updates_player_state(): void
    {
        $energy = 50;
        $stat = 'strength';

        $this->player->method('getGymModifiers')->willReturn(1.0);
        $this->player->method('getSatanPoints')->willReturn(10);
        $this->player->method('getHappiness')->willReturn(1000);
        $this->player->method('getStatTotal')->willReturn(1000);

        $this->player->expects($this->once())
            ->method('subtractEnergy')
            ->with($energy);

        $this->player->expects($this->once())
            ->method('subtractHappiness')
            ->with($this->callback(function($loss) use ($energy) {
                return $loss >= $energy * 0.4 && $loss <= $energy * 0.6;
            }));

        $this->player->expects($this->once())
            ->method('addToStat')
            ->with(
                $this->equalTo($stat),
                $this->isType('float')
            );

        $result = $this->worshipSatan->worship($stat, $energy);

        $this->assertTrue($result['success']);
        $this->assertEquals($stat, $result['stat']);
        $this->assertEquals($energy, $result['energy_used']);
        $this->assertArrayHasKey('happiness_lost', $result);
        $this->assertArrayHasKey('gains', $result);
    }
} 