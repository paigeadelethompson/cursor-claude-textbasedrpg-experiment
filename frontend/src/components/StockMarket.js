import React, { useState } from 'react';
import { useQuery, useMutation } from '@apollo/client';
import { Line } from 'react-chartjs-2';
import { GET_STOCKS, GET_PLAYER_STOCKS } from '../graphql/queries';
import { BUY_STOCK, SELL_STOCK } from '../graphql/mutations';
import './StockMarket.css';

const StockMarket = ({ player }) => {
    const [selectedStock, setSelectedStock] = useState(null);
    const [quantity, setQuantity] = useState(1);

    const { data: stocksData, loading: stocksLoading } = useQuery(GET_STOCKS, {
        pollInterval: 60000 // Update every minute
    });

    const { data: playerStocksData } = useQuery(GET_PLAYER_STOCKS, {
        variables: { playerId: player.id }
    });

    const [buyStock] = useMutation(BUY_STOCK, {
        refetchQueries: [
            { query: GET_PLAYER_STOCKS, variables: { playerId: player.id } }
        ]
    });

    const [sellStock] = useMutation(SELL_STOCK, {
        refetchQueries: [
            { query: GET_PLAYER_STOCKS, variables: { playerId: player.id } }
        ]
    });

    const handleStockSelect = (stock) => {
        setSelectedStock(stock);
    };

    const handleTransaction = async (type) => {
        try {
            const mutation = type === 'buy' ? buyStock : sellStock;
            const response = await mutation({
                variables: {
                    input: {
                        stockId: selectedStock.id,
                        quantity: quantity,
                        playerId: player.id
                    }
                }
            });

            if (response.data) {
                // Show success message or update UI
                const action = type === 'buy' ? 'bought' : 'sold';
                alert(`Successfully ${action} ${quantity} shares of ${selectedStock.symbol}`);
            }
        } catch (error) {
            console.error(`${type} failed:`, error);
            alert(error.message);
        }
    };

    if (stocksLoading) return <div>Loading stocks...</div>;

    const playerStocks = playerStocksData?.player?.stocks || [];
    const stocks = stocksData?.stocks || [];

    const formatPriceHistory = (history) => ({
        labels: history.map(h => new Date(h.timestamp).toLocaleTimeString()),
        datasets: [{
            label: 'Price',
            data: history.map(h => h.price),
            borderColor: '#4CAF50',
            tension: 0.1
        }]
    });

    return (
        <div className="stock-market">
            <div className="stock-list">
                <h2>Available Stocks</h2>
                {stocks.map(stock => (
                    <div 
                        key={stock.id} 
                        className={`stock-item ${selectedStock?.id === stock.id ? 'selected' : ''}`}
                        onClick={() => handleStockSelect(stock)}
                    >
                        <div className="stock-symbol">{stock.symbol}</div>
                        <div className="stock-price">${stock.currentPrice}</div>
                        {playerStocks.find(ps => ps.stock.id === stock.id) && (
                            <div className="owned-indicator">Owned</div>
                        )}
                    </div>
                ))}
            </div>

            {selectedStock && (
                <div className="stock-detail">
                    <h3>{selectedStock.name}</h3>
                    <div className="transaction-panel">
                        <input
                            type="number"
                            min="1"
                            value={quantity}
                            onChange={(e) => setQuantity(parseInt(e.target.value))}
                        />
                        <button 
                            onClick={() => handleTransaction('buy')}
                            disabled={player.money < selectedStock.currentPrice * quantity}
                        >
                            Buy (${(selectedStock.currentPrice * quantity).toFixed(2)})
                        </button>
                        {playerStocks.find(ps => ps.stock.id === selectedStock.id) && (
                            <button 
                                onClick={() => handleTransaction('sell')}
                            >
                                Sell (${(selectedStock.currentPrice * quantity).toFixed(2)})
                            </button>
                        )}
                    </div>

                    {selectedStock.priceHistory && (
                        <div className="price-chart">
                            <Line
                                data={formatPriceHistory(selectedStock.priceHistory)}
                                options={{
                                    responsive: true,
                                    scales: {
                                        y: {
                                            beginAtZero: false
                                        }
                                    }
                                }}
                            />
                        </div>
                    )}

                    <div className="stock-holdings">
                        {playerStocks.find(ps => ps.stock.id === selectedStock.id) && (
                            <div className="holdings-info">
                                <h4>Your Holdings</h4>
                                <p>Quantity: {ps.quantity}</p>
                                <p>Purchase Price: ${ps.purchasePrice}</p>
                                <p>Current Value: ${(ps.quantity * selectedStock.currentPrice).toFixed(2)}</p>
                                <p>Profit/Loss: ${((selectedStock.currentPrice - ps.purchasePrice) * ps.quantity).toFixed(2)}</p>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

export default StockMarket; 