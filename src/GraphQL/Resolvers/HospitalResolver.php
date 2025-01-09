<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\Hospital;
use Game\Player;

class HospitalResolver extends Resolver {
    public function hospitalizedPlayers(): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $hospital = new Hospital($player);
        return $hospital->getHospitalizedPlayers();
    }

    public function hospitalStatus(array $args, array $context): ?array {
        $player = new Player($this->getPlayerById($context['player_id']));
        $hospital = new Hospital($player);
        
        $stay = $hospital->getCurrentStay();
        if (!$stay) {
            return ['isHospitalized' => false];
        }

        $hospital->updateHealth();
        $timeRemaining = max(0, strtotime($stay['release_time']) - time());

        return [
            'isHospitalized' => true,
            'releaseTime' => $stay['release_time'],
            'currentHealth' => $stay['current_health'],
            'timeRemaining' => $timeRemaining,
            'admittedAt' => $stay['admitted_at'],
            'attacker' => $this->getPlayerById($stay['attacker_id']),
            'reason' => $stay['reason']
        ];
    }

    public function selfMedicate(array $args, array $context): array {
        $player = new Player($this->getPlayerById($context['player_id']));
        $hospital = new Hospital($player);
        return $hospital->selfMedicate($args['itemId']);
    }
} 