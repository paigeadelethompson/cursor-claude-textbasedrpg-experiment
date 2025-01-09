import React from 'react';
import { ApolloProvider } from '@apollo/client';
import client from './apollo-client';
import AuthWrapper from './components/Auth/AuthWrapper';
import Game from './components/Game'; // Your main game component

const App = () => {
    return (
        <ApolloProvider client={client}>
            <AuthWrapper>
                <Game />
            </AuthWrapper>
        </ApolloProvider>
    );
};

export default App; 