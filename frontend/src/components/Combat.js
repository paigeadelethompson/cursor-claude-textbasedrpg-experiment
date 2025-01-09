import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@apollo/client';
import { GET_COMBAT_STATS, GET_COMBAT_LOGS } from '../graphql/queries';
import { ATTACK_PLAYER } from '../graphql/mutations';
import { KafkaConsumer } from '../utils/kafka';
import './Combat.css';

const Combat = ({ player }) => {
    const [combatLogs, setCombatLogs] = useState([]);
    const [combatStats, setCombatStats] = useState(null);
    const [hospitalStatus, setHospitalStatus] = useState(null);

    useEffect(() => {
        // Subscribe to combat logs
        const combatConsumer = new KafkaConsumer('combat_logs');
        combatConsumer.subscribe((message) => {
            const change = JSON.parse(message.value);
            if (change.after && 
                (change.after.attacker_id === player.id || 
                 change.after.defender_id === player.id)) {
                setCombatLogs(prev => [change.after, ...prev].slice(0, 20));
            }
        });

        // Subscribe to combat stats changes
        const statsConsumer = new KafkaConsumer('combat_stats');
        statsConsumer.subscribe((message) => {
            const change = JSON.parse(message.value);
            if (change.after && change.after.player_id === player.id) {
                setCombatStats(change.after);
            }
        });

        // Subscribe to hospital status
        const hospitalConsumer = new KafkaConsumer('hospital_stays');
        hospitalConsumer.subscribe((message) => {
            const change = JSON.parse(message.value);
            if (change.after && change.after.player_id === player.id) {
                setHospitalStatus(change.after);
            }
        });

        return () => {
            combatConsumer.disconnect();
            statsConsumer.disconnect();
            hospitalConsumer.disconnect();
        };
    }, [player.id]);

    // ... rest of component code ...
};

export default Combat; 