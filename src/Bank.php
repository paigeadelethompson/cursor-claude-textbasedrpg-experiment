<?php

namespace Game;

/**
 * Class Bank
 * Handles banking operations, CDs, and interest calculations
 * 
 * @package Game
 */
class Bank {
    /** @var \PDO Database connection instance */
    private $db;

    /** @var Player The player instance */
    private $player;

    /**
     * Bank constructor
     *
     * @param Player $player The player instance
     */
    public function __construct(Player $player) {
        $this->player = $player;
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Initialize bank changefeeds
     * 
     * @return void
     */
    public function initializeChangefeeds(): void {
        // Create changefeed for CD rates
        $stmt = $this->db->prepare("
            CREATE CHANGEFEED FOR TABLE cd_rates 
            INTO 'kafka://kafka:9092'
            WITH updated, resolved='10s'
        ");
        $stmt->execute();

        // Create changefeed for player CDs
        $stmt = $this->db->prepare("
            CREATE CHANGEFEED FOR TABLE player_cds 
            INTO 'kafka://kafka:9092'
            WITH updated, resolved='10s'
        ");
        $stmt->execute();

        // Create changefeed for interest payments
        $stmt = $this->db->prepare("
            CREATE CHANGEFEED FOR TABLE interest_transactions 
            INTO 'kafka://kafka:9092'
            WITH updated, resolved='5s'
        ");
        $stmt->execute();
    }

    /**
     * Update CD interest rates
     *
     * @return void
     */
    public function updateCDRates(): void {
        $this->db->beginTransaction();

        try {
            $baseRate = $this->calculateBaseRate();
            $terms = [30, 60, 90, 180, 360]; // Days

            foreach ($terms as $term) {
                $rate = $this->calculateTermRate($baseRate, $term);
                
                $stmt = $this->db->prepare("
                    INSERT INTO cd_rates (term_days, rate, effective_date)
                    VALUES (?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$term, $rate]);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Process CD maturity and interest payments
     *
     * @return void
     */
    public function processCDMaturity(): void {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT * FROM player_cds 
                WHERE maturity_date <= CURRENT_TIMESTAMP 
                AND status = 'active'
            ");
            $stmt->execute();
            
            foreach ($stmt->fetchAll() as $cd) {
                $interest = $this->calculateCDInterest($cd);
                
                // Record interest payment
                $stmt = $this->db->prepare("
                    INSERT INTO interest_transactions 
                    (player_id, cd_id, amount, type)
                    VALUES (?, ?, ?, 'cd_maturity')
                ");
                $stmt->execute([$cd['player_id'], $cd['id'], $interest]);

                // Update CD status
                $stmt = $this->db->prepare("
                    UPDATE player_cds 
                    SET status = 'matured', 
                        interest_paid = ?
                    WHERE id = ?
                ");
                $stmt->execute([$interest, $cd['id']]);

                // Pay player
                $player = new Player($this->getPlayerById($cd['player_id']));
                $player->addMoney($cd['amount'] + $interest);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Create a new certificate of deposit
     *
     * @param float $amount Amount to deposit
     * @param int $termMonths Term length in months
     * @return array CD creation result
     * @throws \Exception If validation fails or insufficient funds
     */
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

    /**
     * Withdraw a matured CD
     *
     * @param string $cdId CD identifier
     * @return array Transaction result
     * @throws \Exception If CD not found or not matured
     */
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

    /**
     * Get all CDs for the player
     *
     * @return array Array of CD information
     */
    public function getCDs(): array {
        // ... existing code ...
    }

    /**
     * Calculate interest rate based on term length
     *
     * @param int $termMonths Term length in months
     * @return float Annual interest rate
     */
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