<?php

namespace Tests\GraphQL\Resolvers;

use PHPUnit\Framework\TestCase;
use Game\GraphQL\Resolvers\BankResolver;
use Game\Bank;

class BankResolverTest extends TestCase {
    private $resolver;
    private $bank;

    protected function setUp(): void {
        $this->bank = $this->createMock(Bank::class);
        $this->resolver = new BankResolver($this->bank);
    }

    public function testCreateCD(): void {
        $args = [
            'amount' => 1000.00,
            'termMonths' => 3
        ];

        $expectedResult = [
            'success' => true,
            'amount' => 1000.00,
            'termMonths' => 3,
            'interestRate' => 2.5,
            'maturityDate' => '2024-06-01'
        ];

        $this->bank->expects($this->once())
            ->method('createCD')
            ->with($args['amount'], $args['termMonths'])
            ->willReturn($expectedResult);

        $result = $this->resolver->createCD(null, $args);
        
        $this->assertEquals($expectedResult, $result);
    }

    public function testGetCDs(): void {
        $expectedCDs = [
            [
                'id' => 'cd-1',
                'amount' => 1000.00,
                'termMonths' => 3,
                'interestRate' => 2.5,
                'maturityDate' => '2024-06-01'
            ],
            [
                'id' => 'cd-2',
                'amount' => 2000.00,
                'termMonths' => 6,
                'interestRate' => 3.0,
                'maturityDate' => '2024-09-01'
            ]
        ];

        $this->bank->method('getCDs')
            ->willReturn($expectedCDs);

        $result = $this->resolver->getCDs();
        
        $this->assertEquals($expectedCDs, $result);
    }

    public function testWithdrawCD(): void {
        $args = ['cdId' => 'cd-1'];

        $expectedResult = [
            'success' => true,
            'amount' => 1000.00,
            'interestEarned' => 25.00,
            'totalReturn' => 1025.00
        ];

        $this->bank->expects($this->once())
            ->method('withdrawCD')
            ->with($args['cdId'])
            ->willReturn($expectedResult);

        $result = $this->resolver->withdrawCD(null, $args);
        
        $this->assertEquals($expectedResult, $result);
    }
} 