<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\Gym;
use Game\Player;

/**
 * Class GymResolver
 * Handles GraphQL operations for the gym and training system
 * 
 * @package Game\GraphQL\Resolvers
 */
class GymResolver extends Resolver {
    /**
     * Train a combat stat
     *
     * @param array $args GraphQL arguments containing training details
     * @return array Training result
     */
    public function train(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $gym = new Gym($player);
        
        return $gym->train(
            $args['input']['stat'],
            $args['input']['intensity'] ?? 1
        );
    }

    /**
     * Get training history
     *
     * @param array $args GraphQL arguments containing player ID
     * @return array Array of training sessions
     */
    public function trainingHistory(array $args): array {
        $stmt = $this->db->prepare("
            SELECT * FROM training_history
            WHERE player_id = ?
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$args['playerId']]);
        return $stmt->fetchAll();
    }
} 