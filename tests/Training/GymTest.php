<?php

namespace Tests\Training;

use PHPUnit\Framework\TestCase;
use Game\Training\Gym;
use Game\Player;

class GymTest extends TestCase {
    private $gym;
    private $player;

    protected function setUp(): void {
        $this->player = $this->createMock(Player::class);
        $this->gym = new Gym($this->player);
    }

    /** @test */
    public function calculate_gains_with_formula(): void {
        $modifiers = 1.5;
        $gymDots = 10;
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
        // (Modifiers)*(Gym Dots)*(Energy Per Train)*[ (a*ln(Happy+b)+c) * (Stat Total) + d*(Happy+b) + e ]
        $expected = $modifiers * $gymDots * $energyPerTrain * (
            ($a * log($happy + $b) + $c) * $statTotal + 
            $d * ($happy + $b) + 
            $e
        );

        $result = $this->gym->calculateGains(
            $modifiers,
            $gymDots,
            $energyPerTrain,
            $happy,
            $statTotal
        );

        $this->assertEquals($expected, $result, '', 0.00001);
    }

    /** @test */
    public function calculate_gains_with_zero_happiness(): void {
        $modifiers = 1.0;
        $gymDots = 5;
        $energyPerTrain = 3;
        $happy = 0;
        $statTotal = 500;

        $result = $this->gym->calculateGains(
            $modifiers,
            $gymDots,
            $energyPerTrain,
            $happy,
            $statTotal
        );

        // Should still give some gains even with 0 happiness
        $this->assertGreaterThan(0, $result);
    }

    /** @test */
    public function calculate_gains_with_max_values(): void {
        $modifiers = 2.0;
        $gymDots = 100;
        $energyPerTrain = 10;
        $happy = 5000;
        $statTotal = 5000;

        $result = $this->gym->calculateGains(
            $modifiers,
            $gymDots,
            $energyPerTrain,
            $happy,
            $statTotal
        );

        // Result should be positive but not unreasonably large
        $this->assertGreaterThan(0, $result);
        $this->assertLessThan($statTotal, $result);
    }

    /** @test */
    public function gains_increase_with_higher_happiness(): void {
        $baseGains = $this->gym->calculateGains(1.0, 10, 5, 1000, 1000);
        $higherGains = $this->gym->calculateGains(1.0, 10, 5, 2000, 1000);

        $this->assertGreaterThan($baseGains, $higherGains);
    }

    /** @test */
    public function gains_scale_linearly_with_modifiers(): void {
        $baseGains = $this->gym->calculateGains(1.0, 10, 5, 1000, 1000);
        $doubledGains = $this->gym->calculateGains(2.0, 10, 5, 1000, 1000);

        $this->assertEquals($baseGains * 2, $doubledGains, '', 0.00001);
    }

    /** @test */
    public function training_reduces_happiness(): void {
        $energy = 100;
        $initialHappiness = 1000;

        $this->player->method('getGymModifiers')->willReturn(1.0);
        $this->player->method('getGymDots')->willReturn(10);
        $this->player->method('getHappiness')->willReturn($initialHappiness);
        $this->player->method('getStatTotal')->willReturn(1000);

        $this->player->expects($this->once())
            ->method('subtractHappiness')
            ->with($this->callback(function($happinessLoss) use ($energy) {
                // Happiness loss should be 40-60% of energy used
                return $happinessLoss >= $energy * 0.4 && 
                       $happinessLoss <= $energy * 0.6;
            }));

        $result = $this->gym->train('strength', $energy);
        
        $this->assertArrayHasKey('happiness_lost', $result);
        $this->assertGreaterThanOrEqual($energy * 0.4, $result['happiness_lost']);
        $this->assertLessThanOrEqual($energy * 0.6, $result['happiness_lost']);
    }

    /** @test */
    public function training_updates_player_state(): void {
        $energy = 50;
        $gains = 100.0;
        $happinessLoss = 25;
        $stat = 'strength';

        $this->player->method('getGymModifiers')->willReturn(1.0);
        $this->player->method('getGymDots')->willReturn(10);
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
            ->with($stat, $this->isType('float'));

        $result = $this->gym->train($stat, $energy);

        $this->assertTrue($result['success']);
        $this->assertEquals($stat, $result['stat']);
        $this->assertEquals($energy, $result['energy_used']);
    }
} 