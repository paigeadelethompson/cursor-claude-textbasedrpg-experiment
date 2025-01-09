<?php

namespace Game\GraphQL\Resolvers;

use Game\GraphQL\Resolver;
use Game\Bank;
use Game\Player;

/**
 * Class BankResolver
 * Handles GraphQL operations for banking functionality
 * 
 * @package Game\GraphQL\Resolvers
 */
class BankResolver extends Resolver {
    /**
     * Create a new certificate of deposit
     *
     * @param array $args GraphQL arguments containing CD details
     * @return array CD creation result
     */
    public function createCD(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $bank = new Bank($player);
        
        return $bank->createCD(
            $args['input']['amount'],
            $args['input']['termMonths']
        );
    }

    /**
     * Withdraw a matured certificate of deposit
     *
     * @param array $args GraphQL arguments containing CD ID
     * @return array Withdrawal result
     */
    public function withdrawCD(array $args): array {
        $player = new Player($this->getPlayerById($this->context['player_id']));
        $bank = new Bank($player);
        
        return $bank->withdrawCD($args['id']);
    }

    /**
     * Get player's certificates of deposit
     *
     * @param array $player Player data
     * @return array Array of CDs
     */
    public function cds(array $player): array {
        $bank = new Bank(new Player($player));
        return $bank->getCDs();
    }

    /**
     * Get interest rate for a CD term
     *
     * @param array $args GraphQL arguments containing term length
     * @return float Interest rate percentage
     */
    public function cdInterestRate(array $args): float {
        $bank = new Bank(new Player($this->getPlayerById($this->context['player_id'])));
        return $bank->calculateInterestRate($args['termMonths']);
    }

    /**
     * Calculate potential returns for a CD
     *
     * @param array $args GraphQL arguments containing amount and term
     * @return array Projected returns
     */
    public function calculateCDReturns(array $args): array {
        $bank = new Bank(new Player($this->getPlayerById($this->context['player_id'])));
        $interestRate = $bank->calculateInterestRate($args['termMonths']);
        $amount = $args['amount'];
        
        return [
            'amount' => $amount,
            'interestRate' => $interestRate,
            'termMonths' => $args['termMonths'],
            'projectedReturn' => $amount * (1 + ($interestRate / 100))
        ];
    }
} 