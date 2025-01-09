import React, { useState, useEffect } from 'react';
import { Line } from 'react-chartjs-2';

const PlayerStats = ({ playerId }) => {
    const [stats, setStats] = useState(null);
    const [statsHistory, setStatsHistory] = useState(null);

    useEffect(() => {
        fetchPlayerStats();
        fetchStatsHistory();
    }, [playerId]);

    const fetchPlayerStats = async () => {
        const response = await fetch(`/api/players/${playerId}/stats`);
        const data = await response.json();
        setStats(data);
    };

    const fetchStatsHistory = async () => {
        const response = await fetch(`/api/players/${playerId}/stats/history`);
        const data = await response.json();
        setStatsHistory(data);
    };

    if (!stats) return <div>Loading...</div>;

    return (
        <div className="player-stats">
            <h2>Player Stats</h2>
            
            <div className="stats-grid">
                <div className="combat-stats">
                    <h3>Combat Stats</h3>
                    <div>Strength: {stats.combat.strength}</div>
                    <div>Defense: {stats.combat.defense}</div>
                    <div>Speed: {stats.combat.speed}</div>
                    <div>Dexterity: {stats.combat.dexterity}</div>
                </div>

                <div className="status-bars">
                    <div className="energy-bar">
                        Energy: {stats.energy}/100
                        <div className="progress-bar" 
                             style={{width: `${stats.energy}%`}} />
                    </div>
                    <div className="health-bar">
                        Health: {stats.health}/100
                        <div className="progress-bar" 
                             style={{width: `${stats.health}%`}} />
                    </div>
                </div>

                {statsHistory && (
                    <div className="stats-graph">
                        <Line
                            data={statsHistory}
                            options={{
                                responsive: true,
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }}
                        />
                    </div>
                )}
            </div>
        </div>
    );
};

export default PlayerStats; 