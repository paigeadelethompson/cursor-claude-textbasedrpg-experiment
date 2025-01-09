import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@apollo/client';
import { GET_FACTION_WAR, GET_WAR_PARTICIPATION } from '../graphql/queries';
import { ATTACK_FACTION_MEMBER } from '../graphql/mutations';
import { KafkaConsumer } from '../utils/kafka';
import './FactionWar.css';

const FactionWar = ({ player, factionId, warId }) => {
    const [warStatus, setWarStatus] = useState(null);
    const [participation, setParticipation] = useState([]);
    const [rankings, setRankings] = useState([]);

    useEffect(() => {
        // Subscribe to war status updates
        const warConsumer = new KafkaConsumer('faction_wars');
        warConsumer.subscribe((message) => {
            const change = JSON.parse(message.value);
            if (change.after && change.after.id === warId) {
                setWarStatus(change.after);
            }
        });

        // Subscribe to participation updates
        const participationConsumer = new KafkaConsumer('faction_war_participation');
        participationConsumer.subscribe((message) => {
            const change = JSON.parse(message.value);
            if (change.after && change.after.war_id === warId) {
                setParticipation(prev => {
                    const updated = [...prev];
                    const index = updated.findIndex(p => p.player_id === change.after.player_id);
                    if (index >= 0) {
                        updated[index] = change.after;
                    } else {
                        updated.push(change.after);
                    }
                    return updated.sort((a, b) => b.points_contributed - a.points_contributed);
                });
            }
        });

        // Subscribe to faction rankings
        const rankingsConsumer = new KafkaConsumer('faction_rankings');
        rankingsConsumer.subscribe((message) => {
            const change = JSON.parse(message.value);
            if (change.after) {
                setRankings(prev => {
                    const updated = [...prev];
                    const index = updated.findIndex(r => r.faction_id === change.after.faction_id);
                    if (index >= 0) {
                        updated[index] = change.after;
                    } else {
                        updated.push(change.after);
                    }
                    return updated.sort((a, b) => b.rank_points - a.rank_points);
                });
            }
        });

        return () => {
            warConsumer.disconnect();
            participationConsumer.disconnect();
            rankingsConsumer.disconnect();
        };
    }, [warId]);

    // ... rest of component code ...
};

export default FactionWar; 