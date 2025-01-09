<?php

namespace Game;

class Player {
    private $id;
    private $username;
    private $stats;
    
    public function __construct(array $data) {
        $this->id = $data['id'];
        $this->username = $data['username'];
        $this->stats = [
            'combat' => [
                'strength' => $data['strength'] ?? 10,
                'defense' => $data['defense'] ?? 10,
                'speed' => $data['speed'] ?? 10,
                'dexterity' => $data['dexterity'] ?? 10,
                'health' => $data['health'] ?? 100
            ],
            'energy' => $data['energy'] ?? 100,
            'happiness' => $data['happiness'] ?? 100,
            'level' => $data['level'] ?? 1,
            'experience' => $data['experience'] ?? 0
        ];
    }

    public function updateStats(): void {
        $db = Database::getInstance()->getConnection();
        // Update player stats based on time passed
        $currentTime = time();
        
        // Calculate energy regeneration
        $timeSinceLastEnergy = $currentTime - strtotime($this->lastEnergyUpdate);
        $energyGain = floor($timeSinceLastEnergy / 300) * 5; // 5 points every 5 minutes
        if ($energyGain > 0) {
            $this->stats['energy'] = min(100, $this->stats['energy'] + $energyGain);
            // Update last_energy_update in database
        }
        
        // Similar calculations for health and happiness
    }

    public function attack(Player $defender): array {
        if ($this->stats['energy'] < 25) {
            throw new \Exception("Not enough energy to attack");
        }

        // Calculate attack result based on both players' stats
        $attackPower = $this->calculateAttackPower();
        $defenseValue = $defender->calculateDefenseValue();
        
        // Combat logic here
        
        return [
            'success' => true,
            'damage_dealt' => $damage,
            'experience_gained' => $exp
        ];
    }

    private function calculateAttackPower(): float {
        return ($this->stats['combat']['strength'] * 0.6 + 
                $this->stats['combat']['speed'] * 0.2 +
                $this->stats['combat']['dexterity'] * 0.2);
    }

    private function calculateDefenseValue(): float {
        return ($this->stats['combat']['defense'] * 0.7 +
                $this->stats['combat']['speed'] * 0.3);
    }
} 