import React, { useState } from 'react';
import './Gym.css';

const Gym = ({ player, onTrainingComplete }) => {
    const [isTraining, setIsTraining] = useState(false);
    const [selectedStat, setSelectedStat] = useState(null);

    const trainStats = async (statType) => {
        setIsTraining(true);
        try {
            const response = await fetch('/api/gym/train', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    stat_type: statType,
                    player_id: player.id
                })
            });

            const result = await response.json();
            if (result.success) {
                onTrainingComplete(result);
            }
        } catch (error) {
            console.error('Training failed:', error);
        } finally {
            setIsTraining(false);
        }
    };

    return (
        <div className="gym-container">
            <h2>Gym Training</h2>
            <div className="training-options">
                <button 
                    onClick={() => trainStats('strength')}
                    disabled={isTraining || player.energy < 5}>
                    Train Strength (5 Energy)
                </button>
                <button 
                    onClick={() => trainStats('defense')}
                    disabled={isTraining || player.energy < 5}>
                    Train Defense (5 Energy)
                </button>
                <button 
                    onClick={() => trainStats('speed')}
                    disabled={isTraining || player.energy < 5}>
                    Train Speed (5 Energy)
                </button>
                <button 
                    onClick={() => trainStats('dexterity')}
                    disabled={isTraining || player.energy < 5}>
                    Train Dexterity (5 Energy)
                </button>
            </div>
            
            {isTraining && (
                <div className="training-progress">
                    Training in progress...
                </div>
            )}
        </div>
    );
};

export default Gym; 