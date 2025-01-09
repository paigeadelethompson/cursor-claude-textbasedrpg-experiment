import React, { useState, useEffect } from 'react';
import FactionWars from './FactionWars';
import FactionMembers from './FactionMembers';
import './Faction.css';

const Faction = ({ player }) => {
    const [faction, setFaction] = useState(null);
    const [activeTab, setActiveTab] = useState('overview');

    useEffect(() => {
        fetchFactionData();
    }, [player.id]);

    const fetchFactionData = async () => {
        try {
            const response = await fetch(`/api/players/${player.id}/faction`);
            const data = await response.json();
            setFaction(data);
        } catch (error) {
            console.error('Failed to fetch faction data:', error);
        }
    };

    const handleCreateFaction = async (event) => {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        try {
            const response = await fetch('/api/factions/create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: formData.get('name'),
                    description: formData.get('description'),
                    leader_id: player.id
                })
            });

            const result = await response.json();
            if (result.success) {
                fetchFactionData();
            }
        } catch (error) {
            console.error('Failed to create faction:', error);
        }
    };

    if (!faction) {
        return (
            <div className="faction-create">
                <h2>Create a Faction</h2>
                <form onSubmit={handleCreateFaction}>
                    <input
                        type="text"
                        name="name"
                        placeholder="Faction Name"
                        required
                    />
                    <textarea
                        name="description"
                        placeholder="Faction Description"
                        required
                    />
                    <button type="submit">Create Faction</button>
                </form>
            </div>
        );
    }

    return (
        <div className="faction-container">
            <div className="faction-header">
                <h2>{faction.name}</h2>
                <div className="faction-stats">
                    <div>Level: {faction.level}</div>
                    <div>Members: {faction.member_count}</div>
                    <div>Rank Points: {faction.rank_points}</div>
                </div>
            </div>

            <div className="faction-tabs">
                <button 
                    className={activeTab === 'overview' ? 'active' : ''}
                    onClick={() => setActiveTab('overview')}
                >
                    Overview
                </button>
                <button 
                    className={activeTab === 'members' ? 'active' : ''}
                    onClick={() => setActiveTab('members')}
                >
                    Members
                </button>
                <button 
                    className={activeTab === 'wars' ? 'active' : ''}
                    onClick={() => setActiveTab('wars')}
                >
                    Wars
                </button>
            </div>

            <div className="faction-content">
                {activeTab === 'overview' && (
                    <div className="faction-overview">
                        <p>{faction.description}</p>
                        <div className="faction-stats-detailed">
                            <h3>Statistics</h3>
                            <div>Wars Won: {faction.wars_won}</div>
                            <div>Wars Lost: {faction.wars_lost}</div>
                            <div>Total Points: {faction.rank_points}</div>
                        </div>
                    </div>
                )}

                {activeTab === 'members' && (
                    <FactionMembers 
                        faction={faction}
                        player={player}
                        onMemberUpdate={fetchFactionData}
                    />
                )}

                {activeTab === 'wars' && (
                    <FactionWars
                        faction={faction}
                        player={player}
                        onWarUpdate={fetchFactionData}
                    />
                )}
            </div>
        </div>
    );
};

export default Faction; 