import React, { useState, useEffect } from 'react';
import './FactionMembers.css';

const FactionMembers = ({ faction, player, onMemberUpdate }) => {
    const [members, setMembers] = useState([]);
    const [invitedPlayers, setInvitedPlayers] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');

    useEffect(() => {
        fetchMembers();
    }, [faction.id]);

    const fetchMembers = async () => {
        const response = await fetch(`/api/factions/${faction.id}/members`);
        const data = await response.json();
        setMembers(data);
    };

    const handleSearch = async () => {
        if (searchTerm.length < 3) return;

        const response = await fetch(`/api/players/search?term=${searchTerm}`);
        const data = await response.json();
        setInvitedPlayers(data);
    };

    const handleInvite = async (playerId) => {
        try {
            const response = await fetch('/api/factions/invite', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    faction_id: faction.id,
                    player_id: playerId
                })
            });

            const result = await response.json();
            if (result.success) {
                setInvitedPlayers(prev => 
                    prev.filter(p => p.id !== playerId)
                );
            }
        } catch (error) {
            console.error('Failed to invite player:', error);
        }
    };

    const handlePromote = async (memberId) => {
        try {
            const response = await fetch('/api/factions/promote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    faction_id: faction.id,
                    member_id: memberId
                })
            });

            const result = await response.json();
            if (result.success) {
                fetchMembers();
                onMemberUpdate();
            }
        } catch (error) {
            console.error('Failed to promote member:', error);
        }
    };

    const handleKick = async (memberId) => {
        if (!window.confirm('Are you sure you want to kick this member?')) return;

        try {
            const response = await fetch('/api/factions/kick', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    faction_id: faction.id,
                    member_id: memberId
                })
            });

            const result = await response.json();
            if (result.success) {
                fetchMembers();
                onMemberUpdate();
            }
        } catch (error) {
            console.error('Failed to kick member:', error);
        }
    };

    return (
        <div className="faction-members">
            {(player.role === 'leader' || player.role === 'officer') && (
                <div className="invite-section">
                    <h3>Invite Players</h3>
                    <div className="search-bar">
                        <input
                            type="text"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            placeholder="Search players..."
                            minLength={3}
                        />
                        <button onClick={handleSearch}>Search</button>
                    </div>
                    <div className="search-results">
                        {invitedPlayers.map(p => (
                            <div key={p.id} className="player-card">
                                <span>{p.username}</span>
                                <span>Level {p.level}</span>
                                <button onClick={() => handleInvite(p.id)}>
                                    Invite
                                </button>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            <div className="members-list">
                <h3>Members ({members.length})</h3>
                <div className="members-grid">
                    {members.map(member => (
                        <div key={member.id} className="member-card">
                            <div className="member-info">
                                <h4>{member.username}</h4>
                                <span className={`role ${member.role}`}>
                                    {member.role}
                                </span>
                            </div>
                            <div className="member-stats">
                                <div>Level: {member.level}</div>
                                <div>Contribution: {member.contribution_points}</div>
                                <div>Joined: {new Date(member.joined_at).toLocaleDateString()}</div>
                            </div>
                            {player.role === 'leader' && member.id !== player.id && (
                                <div className="member-actions">
                                    {member.role === 'member' && (
                                        <button 
                                            onClick={() => handlePromote(member.id)}
                                            className="promote"
                                        >
                                            Promote to Officer
                                        </button>
                                    )}
                                    <button 
                                        onClick={() => handleKick(member.id)}
                                        className="kick"
                                    >
                                        Kick
                                    </button>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

export default FactionMembers; 