import React from 'react';
import TravelRestriction from './TravelRestriction';

const Game = () => {
    return (
        <div className="game">
            {/* Always accessible */}
            <Inventory />
            
            {/* Restricted during travel */}
            <TravelRestriction feature="market">
                <Marketplace />
            </TravelRestriction>

            <TravelRestriction feature="bank">
                <Bank />
            </TravelRestriction>

            <TravelRestriction feature="gym">
                <Gym />
            </TravelRestriction>

            {/* etc... */}
        </div>
    );
}; 