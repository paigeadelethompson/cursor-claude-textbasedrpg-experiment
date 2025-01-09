import React, { useState } from 'react';
import { useQuery, useMutation } from '@apollo/client';
import { GET_PLAYER_CDS } from '../graphql/queries';
import { CREATE_CD, WITHDRAW_CD } from '../graphql/mutations';
import './Bank.css';

const Bank = ({ player }) => {
    const [amount, setAmount] = useState('');
    const [termMonths, setTermMonths] = useState(3);

    const { data: cdsData, loading: cdsLoading } = useQuery(GET_PLAYER_CDS, {
        variables: { playerId: player.id }
    });

    const [createCD] = useMutation(CREATE_CD, {
        refetchQueries: [
            { query: GET_PLAYER_CDS, variables: { playerId: player.id } }
        ]
    });

    const [withdrawCD] = useMutation(WITHDRAW_CD, {
        refetchQueries: [
            { query: GET_PLAYER_CDS, variables: { playerId: player.id } }
        ]
    });

    const handleCreateCD = async (event) => {
        event.preventDefault();
        
        try {
            const response = await createCD({
                variables: {
                    input: {
                        amount: parseFloat(amount),
                        termMonths: parseInt(termMonths),
                        playerId: player.id
                    }
                }
            });

            if (response.data) {
                setAmount('');
                alert('Certificate of Deposit created successfully!');
            }
        } catch (error) {
            console.error('CD creation failed:', error);
            alert(error.message);
        }
    };

    const handleWithdraw = async (cdId, maturityDate) => {
        if (new Date(maturityDate) > new Date()) {
            alert("This CD has not yet matured and cannot be withdrawn.");
            return;
        }

        try {
            const response = await withdrawCD({
                variables: { id: cdId }
            });

            if (response.data) {
                const { amount, interestEarned, totalReturn } = response.data.withdrawCD;
                alert(`Successfully withdrawn CD!\nInitial Amount: $${amount.toFixed(2)}\nInterest Earned: $${interestEarned.toFixed(2)}\nTotal Return: $${totalReturn.toFixed(2)}`);
            }
        } catch (error) {
            console.error('CD withdrawal failed:', error);
            alert(error.message);
        }
    };

    if (cdsLoading) return <div>Loading certificates of deposit...</div>;

    const cds = cdsData?.player?.cds || [];
    const activeCDs = cds.filter(cd => !cd.isMatured);
    const maturedCDs = cds.filter(cd => cd.isMatured);

    const renderCDCard = (cd) => {
        const maturityDate = new Date(cd.maturityDate);
        const now = new Date();
        const isMatured = maturityDate <= now;
        const daysUntilMaturity = Math.ceil((maturityDate - now) / (1000 * 60 * 60 * 24));
        
        const estimatedReturn = cd.amount * (1 + (cd.interestRate / 100));

        return (
            <div key={cd.id} className={`cd-card ${isMatured ? 'matured' : ''}`}>
                <div className="cd-info">
                    <h3>${cd.amount.toFixed(2)}</h3>
                    <p>Interest Rate: {cd.interestRate}%</p>
                    <p>Estimated Return: ${estimatedReturn.toFixed(2)}</p>
                    <p>Start Date: {new Date(cd.startDate).toLocaleDateString()}</p>
                    <p>Maturity Date: {maturityDate.toLocaleDateString()}</p>
                    <p className={`maturity-status ${isMatured ? 'mature' : 'pending'}`}>
                        {isMatured 
                            ? 'Ready to withdraw'
                            : `Matures in ${daysUntilMaturity} days`
                        }
                    </p>
                    {isMatured && (
                        <button 
                            onClick={() => handleWithdraw(cd.id, cd.maturityDate)}
                            className="withdraw-button"
                        >
                            Withdraw (${estimatedReturn.toFixed(2)})
                        </button>
                    )}
                </div>
            </div>
        );
    };

    return (
        <div className="bank">
            <div className="create-cd">
                <h2>Create New Certificate of Deposit</h2>
                <form onSubmit={handleCreateCD}>
                    <div className="input-group">
                        <label>Amount ($)</label>
                        <input
                            type="number"
                            min="100"
                            step="0.01"
                            value={amount}
                            onChange={(e) => setAmount(e.target.value)}
                            required
                        />
                    </div>
                    <div className="input-group">
                        <label>Term Length</label>
                        <select 
                            value={termMonths}
                            onChange={(e) => setTermMonths(parseInt(e.target.value))}
                        >
                            <option value={3}>3 Months (2.3% APR)</option>
                            <option value={6}>6 Months (2.6% APR)</option>
                            <option value={12}>12 Months (3.2% APR)</option>
                            <option value={24}>24 Months (3.8% APR)</option>
                        </select>
                    </div>
                    <button 
                        type="submit"
                        disabled={player.money < parseFloat(amount)}
                    >
                        Create CD
                    </button>
                </form>
            </div>

            <div className="cd-lists">
                <div className="active-cds">
                    <h2>Active CDs</h2>
                    {activeCDs.map(cd => renderCDCard(cd))}
                    {activeCDs.length === 0 && (
                        <p className="no-data">No active certificates of deposit</p>
                    )}
                </div>

                <div className="matured-cds">
                    <h2>Matured CDs</h2>
                    {maturedCDs.map(cd => renderCDCard(cd))}
                    {maturedCDs.length === 0 && (
                        <p className="no-data">No matured certificates of deposit</p>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Bank; 