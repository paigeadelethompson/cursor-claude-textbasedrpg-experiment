import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@apollo/client';
import { GET_STOCKS, GET_PLAYER_PORTFOLIO } from '../graphql/queries';
import { BUY_STOCK, SELL_STOCK } from '../graphql/mutations';
import { KafkaConsumer } from '../utils/kafka';
import './StockMarket.css';

const StockMarket = ({ player }) => {
    const [stocks, setStocks] = useState([]);
    const [portfolio, setPortfolio] = useState([]);

    useEffect(() => {
        // Initial data load
        fetchStocks();
        fetchPortfolio();

        // Subscribe to stock price updates
        const priceConsumer = new KafkaConsumer('stock_prices');
        priceConsumer.subscribe((message) => {
            const change = JSON.parse(message.value);
            if (change.after) {
                setStocks(prev => {
                    const updated = [...prev];
                    const index = updated.findIndex(s => s.id === change.after.stock_id);
                    if (index >= 0) {
                        updated[index] = {
                            ...updated[index],
                            current_price: change.after.price,
                            price_change: change.after.price - updated[index].current_price
                        };
                    }
                    return updated;
                });
            }
        });

        // Subscribe to transaction updates
        const transactionConsumer = new KafkaConsumer('stock_transactions');
        transactionConsumer.subscribe((message) => {
            const change = JSON.parse(message.value);
            if (change.after && change.after.player_id === player.id) {
                fetchPortfolio(); // Refresh portfolio on new transactions
            }
        });

        return () => {
            priceConsumer.disconnect();
            transactionConsumer.disconnect();
        };
    }, [player.id]);

    // ... rest of the component code ...
};

export default StockMarket; 