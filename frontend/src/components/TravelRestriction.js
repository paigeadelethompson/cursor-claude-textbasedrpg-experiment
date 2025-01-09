import React from 'react';
import { useQuery, gql } from '@apollo/client';
import './TravelRestriction.css';

const GET_ACCESSIBLE_FEATURES = gql`
  query GetAccessibleFeatures {
    accessibleFeatures {
      inventory
      market
      bank
      gym
      hospital
      combat
      faction
      travel
    }
    travelStatus {
      inProgress
      destination {
        name
      }
      arrivalTime
      travelTimeRemaining
    }
  }
`;

const TravelRestriction = ({ children, feature }) => {
    const { data, loading } = useQuery(GET_ACCESSIBLE_FEATURES, {
        pollInterval: 10000
    });

    if (loading) return null;

    const isAccessible = data?.accessibleFeatures[feature];
    const travelStatus = data?.travelStatus;

    if (!isAccessible && travelStatus?.inProgress) {
        return (
            <div className="travel-restriction">
                <div className="restriction-content">
                    <h3>Feature Unavailable During Travel</h3>
                    <p>You are currently flying to {travelStatus.destination.name}</p>
                    <p>Time remaining: {Math.floor(travelStatus.travelTimeRemaining / 3600)}h {Math.floor((travelStatus.travelTimeRemaining % 3600) / 60)}m</p>
                    <p className="restriction-note">This feature will be available again when you land.</p>
                </div>
            </div>
        );
    }

    return children;
};

export default TravelRestriction; 