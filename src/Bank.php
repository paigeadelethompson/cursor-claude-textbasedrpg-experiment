<?php

namespace Game;

class Bank {
    private $db;
    private $player;

    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    public function createCD(float $amount, int $termMonths): array {
        if ($this->player->getMoney() < $amount) {
            throw new \Exception("Insufficient funds");
        }

        // Calculate interest rate based on term length
        $interestRate = $this->calculateInterestRate($termMonths);
        $maturityDate = date('Y-m-d H:i:s', strtotime("+$termMonths months"));

        $stmt = $this->db->prepare("
            INSERT INTO certificates_of_deposit 
            (player_id, amount, interest_rate, maturity_date)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $this->player->getId(),
            $amount,
            $interestRate,
            $maturityDate
        ]);

        $this->player->deductMoney($amount);

        return [
            'success' => true,
            'amount' => $amount,
            'interest_rate' => $interestRate,
            'maturity_date' => $maturityDate
        ];
    }

    public function withdrawCD(string $cdId): array {
        $cd = $this->getCDInfo($cdId);

        if (!$cd) {
            throw new \Exception("CD not found");
        }

        // Check if CD has matured
        $maturityDate = new \DateTime($cd['maturity_date']);
        $now = new \DateTime();
        
        if ($maturityDate > $now) {
            $daysRemaining = $maturityDate->diff($now)->days;
            throw new \Exception("CD cannot be withdrawn. {$daysRemaining} days remaining until maturity.");
        }

        $totalReturn = $cd['amount'] * (1 + ($cd['interest_rate'] / 100));

        $stmt = $this->db->prepare("
            DELETE FROM certificates_of_deposit WHERE id = ?
        ");
        $stmt->execute([$cdId]);

        $this->player->addMoney($totalReturn);

        return [
            'success' => true,
            'amount' => $cd['amount'],
            'interestEarned' => $totalReturn - $cd['amount'],
            'totalReturn' => $totalReturn
        ];
    }

    private function calculateInterestRate(int $termMonths): float {
        // Base rate of 2% + 0.1% per month of term
        return 2.0 + ($termMonths * 0.1);
    }

    private function getCDInfo(string $cdId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM certificates_of_deposit WHERE id = ?
        ");
        $stmt->execute([$cdId]);
        $cd = $stmt->fetch();

        if (!$cd) {
            throw new \Exception("CD not found");
        }

        return $cd;
    }
} 