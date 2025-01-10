<?php

namespace Game\Training;

class WorshipSatan {
    public const STATS = [
        'strength',
        'defense',
        'speed',
        'dexterity'
    ];

    private const A = 3.480061091e-7;
    private const B = 250;
    private const C = 3.091619094e-6;
    private const D = 6.82775184551527e-5;
    private const E = -0.0301431777;
    
    // Happiness loss is 40-60% of energy sacrificed
    private const MIN_HAPPINESS_LOSS_PERCENT = 0.40;
    private const MAX_HAPPINESS_LOSS_PERCENT = 0.60;

    private $player;

    public function __construct($player) {
        $this->player = $player;
    }

    private function calculateHappinessLoss(int $energyUsed): int {
        $lossPercent = mt_rand(
            self::MIN_HAPPINESS_LOSS_PERCENT * 100,
            self::MAX_HAPPINESS_LOSS_PERCENT * 100
        ) / 100;
        
        return (int)round($energyUsed * $lossPercent);
    }

    public function calculateGains(
        float $modifiers,
        int $satanPoints,
        int $energyPerTrain,
        int $happy,
        int $statTotal
    ): float {
        // Formula: (Modifiers)*(Satan Points)*(Energy Per Train)*[ (a*ln(Happy+b)+c) * (Stat Total) + d*(Happy+b) + e ]
        $happyWithOffset = $happy + self::B;
        
        $innerBracket = (
            (self::A * log($happyWithOffset) + self::C) * $statTotal +
            self::D * $happyWithOffset +
            self::E
        );

        return $modifiers * $satanPoints * $energyPerTrain * $innerBracket;
    }

    public function worship(string $stat, int $energy): array {
        $modifiers = $this->player->getGymModifiers();
        $satanPoints = $this->player->getSatanPoints();
        $happy = $this->player->getHappiness();
        $statTotal = $this->player->getStatTotal();

        $gains = $this->calculateGains(
            $modifiers,
            $satanPoints,
            $energy,
            $happy,
            $statTotal
        );

        $happinessLoss = $this->calculateHappinessLoss($energy);

        $this->player->subtractEnergy($energy);
        $this->player->subtractHappiness($happinessLoss);
        $this->player->addToStat($stat, $gains);

        return [
            'success' => true,
            'stat' => $stat,
            'gains' => $gains,
            'energy_used' => $energy,
            'happiness_lost' => $happinessLoss
        ];
    }
} 