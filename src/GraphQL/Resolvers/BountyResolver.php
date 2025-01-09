<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\Bounty;
use Game\Player;

class BountyResolver extends Resolver {
    public function activeBounties(): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $bounty = new Bounty($player);
        return $bounty->getActiveBounties();
    }

    public function bountiesOnPlayer(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $bounty = new Bounty($player);
        return $bounty->getBountiesOnPlayer($args['playerId']);
    }

    public function placeBounty(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $bounty = new Bounty($player);
        return $bounty->placeBounty($args['targetId'], $args['amount']);
    }

    public function claimBounty(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $bounty = new Bounty($player);
        return $bounty->claimBounty($args['bountyId'], $args['hospitalStayId']);
    }
} 