import React, { useState } from 'react';
import Login from './Login';
import Register from './Register';
import './Auth.css';

const Auth = ({ onAuthenticated }) => {
    const [isLogin, setIsLogin] = useState(true);

    const handleAuth = (token) => {
        onAuthenticated(token);
    };

    return (
        <div>
            {isLogin ? (
                <>
                    <Login onLogin={handleAuth} />
                    <div className="auth-toggle">
                        Don't have an account?{' '}
                        <button onClick={() => setIsLogin(false)}>
                            Register here
                        </button>
                    </div>
                </>
            ) : (
                <>
                    <Register onRegister={handleAuth} />
                    <div className="auth-toggle">
                        Already have an account?{' '}
                        <button onClick={() => setIsLogin(true)}>
                            Login here
                        </button>
                    </div>
                </>
            )}
        </div>
    );
};

export default Auth; 