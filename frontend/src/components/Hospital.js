import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@apollo/client';
import { gql } from '@apollo/client';
import './Hospital.css';

const GET_HOSPITAL_STATUS = gql`
  query GetHospitalStatus {
    hospitalStatus {
      isHospitalized
      releaseTime
      currentHealth
      timeRemaining
      admittedAt
      attacker {
        username
      }
      reason
    }
    hospitalizedPlayers {
      playerName
      attackerName
      admittedAt
      releaseTime
      currentHealth
      reason
    }
  }
`;

const SELF_MEDICATE = gql`
  mutation SelfMedicate($itemId: ID!) {
    selfMedicate(itemId: $itemId) {
      success
      released
      timeReduced
      newReleaseTime
    }
  }
`;

const Hospital = ({ player }) => {
    const [countdown, setCountdown] = useState(null);
    const { data, loading, refetch } = useQuery(GET_HOSPITAL_STATUS, {
        pollInterval: 10000
    });

    const [selfMedicate] = useMutation(SELF_MEDICATE);

    useEffect(() => {
        if (data?.hospitalStatus?.timeRemaining) {
            const timer = setInterval(() => {
                const remaining = Math.max(0, data.hospitalStatus.timeRemaining - 1);
                setCountdown(remaining);
            }, 1000);
            return () => clearInterval(timer);
        }
    }, [data?.hospitalStatus]);

    const handleSelfMedicate = async (itemId) => {
        try {
            const response = await selfMedicate({
                variables: { itemId }
            });
            if (response.data.selfMedicate.released) {
                alert('You have been released from the hospital!');
            } else {
                alert(`Hospital time reduced by ${response.data.selfMedicate.timeReduced} minutes`);
            }
            refetch();
        } catch (error) {
            alert(error.message);
        }
    };

    if (loading) return <div>Loading hospital status...</div>;

    const { hospitalStatus, hospitalizedPlayers } = data;

    if (!hospitalStatus.isHospitalized) {
        return (
            <div className="hospital-container">
                <h2>Hospital</h2>
                <div className="hospitalized-players">
                    <h3>Currently Hospitalized Players</h3>
                    {hospitalizedPlayers.map((patient, index) => (
                        <div key={index} className="patient-card">
                            <h4>{patient.playerName}</h4>
                            <p>Attacked by: {patient.attackerName || 'Unknown'}</p>
                            <p>Health: {patient.currentHealth}%</p>
                            <p>Release in: {Math.ceil((new Date(patient.releaseTime) - new Date()) / 60000)} minutes</p>
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    const healingItems = player.inventory.filter(item => 
        item.effects?.health > 0
    );

    return (
        <div className="hospital-container hospitalized">
            <div className="hospital-status">
                <h2>You are in the Hospital</h2>
                <p>Current Health: {hospitalStatus.currentHealth}%</p>
                <p>Time Remaining: {Math.floor(countdown / 3600)}h {Math.floor((countdown % 3600) / 60)}m {countdown % 60}s</p>
                {hospitalStatus.attacker && (
                    <p>Attacked by: {hospitalStatus.attacker.username}</p>
                )}
                {hospitalStatus.reason && (
                    <p>Reason: {hospitalStatus.reason}</p>
                )}
            </div>

            <div className="self-medicate">
                <h3>Self Medicate</h3>
                <div className="healing-items">
                    {healingItems.map(item => (
                        <div key={item.id} className="healing-item">
                            <h4>{item.name}</h4>
                            <p>Healing: +{item.effects.health}</p>
                            <p>Quantity: {item.quantity}</p>
                            <button 
                                onClick={() => handleSelfMedicate(item.id)}
                                disabled={item.quantity === 0}
                            >
                                Use Item
                            </button>
                        </div>
                    ))}
                    {healingItems.length === 0 && (
                        <p>No healing items available</p>
                    )}
                </div>
            </div>

            <div className="other-patients">
                <h3>Other Patients</h3>
                {hospitalizedPlayers.map((patient, index) => (
                    <div key={index} className="patient-card">
                        <h4>{patient.playerName}</h4>
                        <p>Attacked by: {patient.attackerName || 'Unknown'}</p>
                        <p>Health: {patient.currentHealth}%</p>
                        <p>Release in: {Math.ceil((new Date(patient.releaseTime) - new Date()) / 60000)} minutes</p>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default Hospital; 