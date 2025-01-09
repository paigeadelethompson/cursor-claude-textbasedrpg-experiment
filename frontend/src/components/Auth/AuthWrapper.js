import React, { useState, useEffect } from 'react';
import { useQuery, gql } from '@apollo/client';
import Auth from './Auth';

const CHECK_AUTH = gql`
  query CheckAuth {
    player {
      id
      username
    }
  }
`;

const AuthWrapper = ({ children }) => {
    const [isAuthenticated, setIsAuthenticated] = useState(false);
    const [isLoading, setIsLoading] = useState(true);

    const { loading, error, data } = useQuery(CHECK_AUTH, {
        fetchPolicy: 'network-only'
    });

    useEffect(() => {
        const token = localStorage.getItem('token');
        const tokenExpiry = localStorage.getItem('tokenExpiry');
        
        if (!token || !tokenExpiry || new Date(tokenExpiry) < new Date()) {
            localStorage.removeItem('token');
            localStorage.removeItem('tokenExpiry');
            setIsAuthenticated(false);
            setIsLoading(false);
            return;
        }

        if (data?.player) {
            setIsAuthenticated(true);
        } else if (error) {
            localStorage.removeItem('token');
            localStorage.removeItem('tokenExpiry');
            setIsAuthenticated(false);
        }
        
        if (!loading) {
            setIsLoading(false);
        }
    }, [loading, error, data]);

    const handleAuthenticated = (token) => {
        setIsAuthenticated(true);
    };

    if (isLoading) {
        return (
            <div className="loading-screen">
                <div className="loading-spinner"></div>
                <p>Loading game...</p>
            </div>
        );
    }

    if (!isAuthenticated) {
        return <Auth onAuthenticated={handleAuthenticated} />;
    }

    return children;
};

export default AuthWrapper; 