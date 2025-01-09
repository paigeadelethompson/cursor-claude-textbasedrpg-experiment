import React, { useState, useEffect } from 'react';
import './FactionWars.css';

const FactionWars = ({ faction, player, onWarUpdate }) => {
    const [activeWars, setActiveWars] = useState([]);
    const [warHistory, setWarHistory] = useState([]);
    const [availableFactions, setAvailableFactions] = useState([]);
    const [pointsAtStake, setPointsAtStake] = useState(100);

    useEffect(() => {
        fetchWars();
        fetchAvailableFactions();
    }, [faction.id]);

    const fetchWars = async () => {
        const response = await fetch(`/api/factions/${faction.id}/wars`);
        const data = await response.json();
        setActiveWars(data.active);
        setWarHistory(data.history);
    };

    const fetchAvailableFactions = async () => {
        const response = await fetch('/api/factions/available');
        const data = await response.json();
        setAvailableFactions(data);
    };

    const handleDeclareWar = async (defenderId) => {
        try {
            const response = await fetch('/api/factions/declare-war', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    attacker_id: faction.id,
                    defender_id: defenderId,
                    points_at_stake: pointsAtStake
                })
            });

            const result = await response.json();
            if (result.success) {
                fetchWars();
                onWarUpdate();
            }
        } catch (error) {
            console.error('Failed to declare war:', error);
        }
    };

    return (
        <div className="faction-wars">
            <div className="active-wars">
                <h3>Active Wars</h3>
                {activeWars.map(war => (
                    <div key={war.id} className="war-card">
                        <div className="war-header">
                            <span>{war.attacker_name} vs {war.defender_name}</span>
                            <span>{war.points_at_stake} points at stake</span>
                        </div>
                        <div className="war-stats">
                            <div>Attacks: {war.total_attacks}</div>
                            <div>Damage: {war.total_damage}</div>
                            <div>Duration: {war.duration}</div>
                        </div>
                    </div>
                ))}
            </div>

            {player.role === 'leader' && (
                <div className="declare-war">
                    <h3>Declare War</h3>
                    <select onChange={(e) => setPointsAtStake(parseInt(e.target.value))}>
                        <option value="100">100 points</option>
                        <option value="250">250 points</option>
                        <option value="500">500 points</option>
                        <option value="1000">1000 points</option>
                    </select>
                    <div className="available-factions">
                        {availableFactions.map(f => (
                            <div key={f.id} className="faction-card">
                                <h4>{f.name}</h4>
                                <p>Level: {f.level}</p>
                                <p>Members: {f.member_count}</p>
                                <button onClick={() => handleDeclareWar(f.id)}>
                                    Declare War
                                </button>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="war-history">
                <h3>War History</h3>
                {warHistory.map(war => (
                    <div key={war.id} className="history-card">
                        <div className="war-result">
                            <span>{war.winner_name} defeated {war.loser_name}</span>
                            <span>{war.points_exchanged} points</span>
                        </div>
                        <div className="war-details">
                            <div>Total Attacks: {war.total_attacks}</div>
                            <div>Total Damage: {war.total_damage}</div>
                            <div>Duration: {war.duration}</div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default FactionWars; 