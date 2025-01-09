<?php

namespace Game;

/**
 * Class Travel
 * Handles player travel between cities and travel-related restrictions
 * 
 * @package Game
 */
class Travel {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var Player The player instance */
    private $player;

    /** @var float Average flight speed in kilometers per hour */
    private const TRAVEL_SPEED = 800;

    /** @var int Maximum travel time in hours */
    private const MAX_TRAVEL_TIME = 4;

    /**
     * Travel constructor
     *
     * @param Player $player The player instance
     */
    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get the player's current city
     *
     * @return array City information
     */
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

    /**
     * Initiate travel to a new city
     *
     * @param string $cityId The destination city ID
     * @return array Travel result containing success status, destination, cost, and timing information
     * @throws \Exception If travel conditions are not met
     */
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

    /**
     * Return to the main city (Seattle)
     *
     * @return array Travel result
     * @throws \Exception If already in Seattle
     */
    public function returnToMainCity(): array {
        $currentCity = $this->getCurrentCity();
        $mainCity = $this->getMainCity();

        if ($currentCity['id'] === $mainCity['id']) {
            throw new \Exception("Already in Seattle");
        }

        return $this->travelTo($mainCity['id']);
    }

    /**
     * Check if player is currently traveling
     *
     * @return bool True if player is traveling
     */
    private function isCurrentlyTraveling(): bool {
        $stmt = $this->db->prepare("
            SELECT 1 FROM travel_history
            WHERE player_id = ? AND status = 'in_progress'
            AND arrival_time > CURRENT_TIMESTAMP
        ");
        $stmt->execute([$this->player->getId()]);
        return (bool) $stmt->fetch();
    }

    /**
     * Calculate distance between two points using Haversine formula
     *
     * @param float $lat1 Starting latitude
     * @param float $lon1 Starting longitude
     * @param float $lat2 Destination latitude
     * @param float $lon2 Destination longitude
     * @return float Distance in kilometers
     */
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

    /**
     * Get city information by ID
     *
     * @param string $cityId The city ID
     * @return array|null City information or null if not found
     */
    private function getCityById(string $cityId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM cities WHERE id = ?");
        $stmt->execute([$cityId]);
        return $stmt->fetch();
    }

    /**
     * Get main city (Seattle) information
     *
     * @return array Main city information
     */
    private function getMainCity(): array {
        $stmt = $this->db->prepare("SELECT * FROM cities WHERE is_main_city = TRUE");
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get features accessible during travel
     *
     * @return array Associative array of feature accessibility
     */
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

    /**
     * Calculate travel cost based on distance
     *
     * @param float $distance Distance in kilometers
     * @return float Travel cost in game currency
     */
    private function calculateTravelCost(float $distance): float {
        // Base cost plus distance-based cost
        return 100 + ($distance * 0.5);
    }
} 