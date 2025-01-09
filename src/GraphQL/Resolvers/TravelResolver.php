<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\Travel;
use Game\Player;

class TravelResolver extends Resolver {
    public function cities(): array {
        $stmt = $this->db->prepare("SELECT * FROM cities ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function currentCity(array $args, array $context): array {
        $player = new Player($this->getPlayerById($context['player_id']));
        $travel = new Travel($player);
        return $travel->getCurrentCity();
    }

    public function travelStatus(array $args, array $context): ?array {
        $stmt = $this->db->prepare("
            SELECT th.*, c.* FROM travel_history th
            JOIN cities c ON c.id = th.destination_city_id
            WHERE th.player_id = ? 
            AND th.status = 'in_progress'
            AND th.arrival_time > CURRENT_TIMESTAMP
            ORDER BY th.departure_time DESC
            LIMIT 1
        ");
        $stmt->execute([$context['player_id']]);
        $travel = $stmt->fetch();

        if (!$travel) {
            return null;
        }

        $remainingSeconds = strtotime($travel['arrival_time']) - time();

        return [
            'inProgress' => true,
            'destination' => [
                'id' => $travel['id'],
                'name' => $travel['name'],
                'country' => $travel['country'],
                'latitude' => $travel['latitude'],
                'longitude' => $travel['longitude'],
                'travelCost' => $travel['travel_cost'],
                'isMainCity' => $travel['is_main_city']
            ],
            'arrivalTime' => $travel['arrival_time'],
            'travelTimeRemaining' => max(0, $remainingSeconds)
        ];
    }

    public function travelTo(array $args, array $context): array {
        $player = new Player($this->getPlayerById($context['player_id']));
        $travel = new Travel($player);
        return $travel->travelTo($args['cityId']);
    }

    public function returnToMainCity(array $args, array $context): array {
        $player = new Player($this->getPlayerById($context['player_id']));
        $travel = new Travel($player);
        return $travel->returnToMainCity();
    }
} 