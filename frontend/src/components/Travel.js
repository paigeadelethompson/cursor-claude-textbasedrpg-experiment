import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@apollo/client';
import { gql } from '@apollo/client';
import { MapContainer, TileLayer, CircleMarker, Popup } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import './Travel.css';

const GET_CITIES = gql`
  query GetCities {
    cities {
      id
      name
      country
      latitude
      longitude
      travelCost
      isMainCity
    }
    currentCity {
      id
      name
      country
    }
    travelStatus {
      inProgress
      destination {
        name
        country
      }
      arrivalTime
      travelTimeRemaining
    }
  }
`;

const TRAVEL_TO = gql`
  mutation TravelTo($cityId: ID!) {
    travelTo(cityId: $cityId) {
      success
      destination
      cost
      arrivalTime
      travelTimeHours
    }
  }
`;

const RETURN_TO_MAIN = gql`
  mutation ReturnToMain {
    returnToMainCity {
      success
      destination
      cost
      arrivalTime
      travelTimeHours
    }
  }
`;

const Travel = ({ player }) => {
    const [selectedCity, setSelectedCity] = useState(null);
    const [countdown, setCountdown] = useState(null);

    const { data, loading, refetch } = useQuery(GET_CITIES, {
        pollInterval: 10000 // Update every 10 seconds
    });

    const [travelTo] = useMutation(TRAVEL_TO);
    const [returnToMain] = useMutation(RETURN_TO_MAIN);

    useEffect(() => {
        if (data?.travelStatus?.inProgress) {
            const timer = setInterval(() => {
                const remaining = Math.floor((new Date(data.travelStatus.arrivalTime) - new Date()) / 1000);
                setCountdown(remaining > 0 ? remaining : null);
            }, 1000);
            return () => clearInterval(timer);
        }
    }, [data?.travelStatus]);

    const handleTravel = async () => {
        if (!selectedCity) return;

        try {
            await travelTo({
                variables: { cityId: selectedCity.id }
            });
            refetch();
            setSelectedCity(null);
        } catch (error) {
            alert(error.message);
        }
    };

    const handleReturn = async () => {
        try {
            await returnToMain();
            refetch();
        } catch (error) {
            alert(error.message);
        }
    };

    if (loading) return <div>Loading travel system...</div>;

    const { cities, currentCity, travelStatus } = data;

    return (
        <div className="travel-container">
            <div className="travel-status">
                <h2>Current Location: {currentCity.name}, {currentCity.country}</h2>
                {travelStatus?.inProgress && (
                    <div className="travel-progress">
                        <h3>Traveling to {travelStatus.destination.name}</h3>
                        <p>Arriving in: {Math.floor(countdown / 3600)}h {Math.floor((countdown % 3600) / 60)}m {countdown % 60}s</p>
                    </div>
                )}
            </div>

            <div className="map-container">
                <MapContainer
                    center={[20, 0]}
                    zoom={2}
                    style={{ height: '500px', width: '100%' }}
                >
                    <TileLayer
                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    />
                    {cities.map(city => (
                        <CircleMarker
                            key={city.id}
                            center={[city.latitude, city.longitude]}
                            radius={city.isMainCity ? 8 : 6}
                            fillColor={city.id === currentCity.id ? '#4CAF50' : '#2196F3'}
                            color="#fff"
                            weight={2}
                            opacity={1}
                            fillOpacity={0.7}
                            eventHandlers={{
                                click: () => setSelectedCity(city)
                            }}
                        >
                            <Popup>
                                <h3>{city.name}, {city.country}</h3>
                                <p>Travel Cost: ${city.travelCost}</p>
                                {!city.isMainCity && city.id !== currentCity.id && (
                                    <button
                                        onClick={handleTravel}
                                        disabled={travelStatus?.inProgress || player.money < city.travelCost}
                                    >
                                        Travel Here
                                    </button>
                                )}
                            </Popup>
                        </CircleMarker>
                    ))}
                </MapContainer>
            </div>

            {currentCity.id !== cities.find(c => c.isMainCity)?.id && (
                <button
                    className="return-button"
                    onClick={handleReturn}
                    disabled={travelStatus?.inProgress}
                >
                    Return to Seattle
                </button>
            )}
        </div>
    );
};

export default Travel; 