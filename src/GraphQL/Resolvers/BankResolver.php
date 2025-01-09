<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\Bank;
use Game\Player;

class BankResolver extends Resolver {
    public function createCD(array $args): array {
        $this->validatePlayer($args['input']['playerId']);
        
        $player = new Player($this->getPlayerById($args['input']['playerId']));
        $bank = new Bank($player);
        
        return $bank->createCD(
            $args['input']['amount'],
            $args['input']['termMonths']
        );
    }

    public function withdrawCD(array $args): array {
        $this->validatePlayer($args['playerId']);
        
        $player = new Player($this->getPlayerById($args['playerId']));
        $bank = new Bank($player);
        
        return $bank->withdrawCD($args['id']);
    }

    public function playerCDs(array $player): array {
        $stmt = $this->db->prepare("
            SELECT * FROM certificates_of_deposit
            WHERE player_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$player['id']]);
        return $stmt->fetchAll();
    }
} 