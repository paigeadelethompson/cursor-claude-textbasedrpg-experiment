<?php

namespace Game;

class Travel {
    private $db;
    private $player;
    private const TRAVEL_SPEED = 800; // km/h average flight speed
    private const MAX_TRAVEL_TIME = 4; // Maximum travel time in hours

    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCurrentCity(): array {
        $stmt = $this->db->prepare("
            SELECT c.* FROM cities c
            LEFT JOIN travel_history th ON th.player_id = ?
            WHERE th.status = 'completed'
            ORDER BY th.arrival_time DESC
            LIMIT 1
        ");
        $stmt->execute([$this->player->getId()]);
        $currentCity = $stmt->fetch();

        return $currentCity ?: $this->getMainCity();
    }

    public function travelTo(string $cityId): array {
        $currentCity = $this->getCurrentCity();
        $destinationCity = $this->getCityById($cityId);

        if (!$destinationCity) {
            throw new \Exception("Invalid destination city");
        }

        if ($this->isCurrentlyTraveling()) {
            throw new \Exception("Player is already traveling");
        }

        if ($this->player->getMoney() < $destinationCity['travel_cost']) {
            throw new \Exception("Insufficient funds for travel");
        }

        $distance = $this->calculateDistance(
            $currentCity['latitude'],
            $currentCity['longitude'],
            $destinationCity['latitude'],
            $destinationCity['longitude']
        );

        // Calculate travel time with maximum cap
        $travelTimeHours = min(
            self::MAX_TRAVEL_TIME,
            $distance / self::TRAVEL_SPEED
        );
        
        $arrivalTime = date('Y-m-d H:i:s', strtotime("+{$travelTimeHours} hours"));

        $stmt = $this->db->prepare("
            INSERT INTO travel_history 
            (player_id, origin_city_id, destination_city_id, arrival_time, cost, status)
            VALUES (?, ?, ?, ?, ?, 'in_progress')
        ");

        $stmt->execute([
            $this->player->getId(),
            $currentCity['id'],
            $destinationCity['id'],
            $arrivalTime,
            $destinationCity['travel_cost']
        ]);

        $this->player->deductMoney($destinationCity['travel_cost']);

        return [
            'success' => true,
            'destination' => $destinationCity['name'],
            'cost' => $destinationCity['travel_cost'],
            'arrival_time' => $arrivalTime,
            'travel_time_hours' => round($travelTimeHours, 1),
            'distance_km' => round($distance)
        ];
    }

    public function returnToMainCity(): array {
        $currentCity = $this->getCurrentCity();
        $mainCity = $this->getMainCity();

        if ($currentCity['id'] === $mainCity['id']) {
            throw new \Exception("Already in Seattle");
        }

        return $this->travelTo($mainCity['id']);
    }

    private function isCurrentlyTraveling(): bool {
        $stmt = $this->db->prepare("
            SELECT 1 FROM travel_history
            WHERE player_id = ? AND status = 'in_progress'
            AND arrival_time > CURRENT_TIMESTAMP
        ");
        $stmt->execute([$this->player->getId()]);
        return (bool) $stmt->fetch();
    }

    private function calculateDistance(
        float $lat1, 
        float $lon1, 
        float $lat2, 
        float $lon2
    ): float {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return $miles * 1.609344; // Convert to kilometers
    }

    private function getCityById(string $cityId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM cities WHERE id = ?");
        $stmt->execute([$cityId]);
        return $stmt->fetch();
    }

    private function getMainCity(): array {
        $stmt = $this->db->prepare("SELECT * FROM cities WHERE is_main_city = TRUE");
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getAccessibleFeatures(): array {
        if (!$this->isCurrentlyTraveling()) {
            return [
                'inventory' => true,
                'market' => true,
                'bank' => true,
                'gym' => true,
                'hospital' => true,
                'combat' => true,
                'faction' => true,
                'travel' => true
            ];
        }

        // During travel, only certain features are accessible
        return [
            'inventory' => true,
            'market' => false,
            'bank' => false,
            'gym' => false,
            'hospital' => false,
            'combat' => false,
            'faction' => true, // Can still chat with faction
            'travel' => false
        ];
    }

    private function calculateTravelCost(float $distance): float {
        // Base cost plus distance-based cost
        return 100 + ($distance * 0.5);
    }
} 