import React, { useState } from 'react';
import { useQuery, useMutation } from '@apollo/client';
import { gql } from '@apollo/client';
import './Bounties.css';

const GET_BOUNTIES = gql`
  query GetBounties {
    activeBounties {
      id
      issuer {
        id
        username
      }
      target {
        id
        username
      }
      amount
      createdAt
      status
    }
  }
`;

const PLACE_BOUNTY = gql`
  mutation PlaceBounty($targetId: ID!, $amount: Float!) {
    placeBounty(targetId: $targetId, amount: $amount) {
      success
      bountyId
      amount
    }
  }
`;

const CLAIM_BOUNTY = gql`
  mutation ClaimBounty($bountyId: ID!, $hospitalStayId: ID!) {
    claimBounty(bountyId: $bountyId, hospitalStayId: $hospitalStayId) {
      success
      amount
    }
  }
`;

const Bounties = ({ player }) => {
    const [targetUsername, setTargetUsername] = useState('');
    const [amount, setAmount] = useState('');
    const [error, setError] = useState('');

    const { data, loading, refetch } = useQuery(GET_BOUNTIES);
    const [placeBounty] = useMutation(PLACE_BOUNTY);
    const [claimBounty] = useMutation(CLAIM_BOUNTY);

    const handlePlaceBounty = async (e) => {
        e.preventDefault();
        setError('');

        try {
            await placeBounty({
                variables: {
                    targetId: targetUsername, // You'll need to implement player search
                    amount: parseFloat(amount)
                }
            });
            setTargetUsername('');
            setAmount('');
            refetch();
        } catch (err) {
            setError(err.message);
        }
    };

    const handleClaimBounty = async (bountyId, hospitalStayId) => {
        try {
            const response = await claimBounty({
                variables: { bountyId, hospitalStayId }
            });
            if (response.data.claimBounty.success) {
                alert(`Claimed bounty for $${response.data.claimBounty.amount}`);
                refetch();
            }
        } catch (err) {
            alert(err.message);
        }
    };

    if (loading) return <div>Loading bounties...</div>;

    return (
        <div className="bounties-container">
            <div className="place-bounty">
                <h2>Place Bounty</h2>
                {error && <div className="error">{error}</div>}
                <form onSubmit={handlePlaceBounty}>
                    <div className="form-group">
                        <label>Target Player</label>
                        <input
                            type="text"
                            value={targetUsername}
                            onChange={(e) => setTargetUsername(e.target.value)}
                            placeholder="Username"
                            required
                        />
                    </div>
                    <div className="form-group">
                        <label>Amount ($1,000 minimum)</label>
                        <input
                            type="number"
                            min="1000"
                            step="100"
                            value={amount}
                            onChange={(e) => setAmount(e.target.value)}
                            required
                        />
                    </div>
                    <button type="submit">Place Bounty</button>
                </form>
            </div>

            <div className="active-bounties">
                <h2>Active Bounties</h2>
                <div className="bounties-list">
                    {data.activeBounties.map(bounty => (
                        <div key={bounty.id} className="bounty-card">
                            <div className="bounty-info">
                                <h3>${bounty.amount.toLocaleString()}</h3>
                                <p>Target: {bounty.target.username}</p>
                                <p>Issued by: {bounty.issuer.username}</p>
                                <p className="date">Posted: {new Date(bounty.createdAt).toLocaleDateString()}</p>
                            </div>
                        </div>
                    ))}
                    {data.activeBounties.length === 0 && (
                        <p className="no-bounties">No active bounties</p>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Bounties; 