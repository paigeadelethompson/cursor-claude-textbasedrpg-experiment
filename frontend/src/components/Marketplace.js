import React, { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@apollo/client';
import { GET_MARKET_LISTINGS } from '../graphql/queries';
import { CREATE_MARKET_LISTING, BUY_MARKET_LISTING } from '../graphql/mutations';
import { KafkaConsumer } from '../utils/kafka';
import './Marketplace.css';

const Marketplace = ({ player }) => {
    const [filters, setFilters] = useState({
        type: '',
        maxPrice: ''
    });

    const [listings, setListings] = useState([]);
    
    useEffect(() => {
        // Initial load of listings
        fetchListings();
        
        // Subscribe to changefeed updates
        const consumer = new KafkaConsumer('marketplace_listings');
        consumer.subscribe((message) => {
            const change = JSON.parse(message.value);
            
            if (change.after) {
                // New or updated listing
                setListings(prev => {
                    const updated = [...prev];
                    const index = updated.findIndex(l => l.id === change.after.id);
                    if (index >= 0) {
                        updated[index] = change.after;
                    } else {
                        updated.push(change.after);
                    }
                    return updated;
                });
            } else if (change.deleted) {
                // Listing was removed
                setListings(prev => prev.filter(l => l.id !== change.deleted.id));
            }
        });

        return () => consumer.disconnect();
    }, []);

    const { data: listingsData, loading: listingsLoading } = useQuery(GET_MARKET_LISTINGS, {
        variables: { filter: filters },
        pollInterval: 30000 // Update every 30 seconds
    });

    const [createListing] = useMutation(CREATE_MARKET_LISTING, {
        refetchQueries: [
            { query: GET_MARKET_LISTINGS, variables: { filter: filters } }
        ]
    });

    const [buyListing] = useMutation(BUY_MARKET_LISTING, {
        refetchQueries: [
            { query: GET_MARKET_LISTINGS, variables: { filter: filters } }
        ]
    });

    const handleCreateListing = async (event) => {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        try {
            const response = await createListing({
                variables: {
                    input: {
                        itemId: formData.get('item_id'),
                        quantity: parseInt(formData.get('quantity')),
                        price: parseFloat(formData.get('price')),
                        sellerId: player.id
                    }
                }
            });

            if (response.data) {
                event.target.reset();
                alert('Listing created successfully!');
            }
        } catch (error) {
            console.error('Listing creation failed:', error);
            alert(error.message);
        }
    };

    const handleBuy = async (listingId) => {
        try {
            const response = await buyListing({
                variables: { id: listingId }
            });

            if (response.data) {
                const { cost, quantity } = response.data.buyMarketListing;
                alert(`Successfully purchased ${quantity} items for $${cost}`);
            }
        } catch (error) {
            console.error('Purchase failed:', error);
            alert(error.message);
        }
    };

    if (listingsLoading) return <div>Loading marketplace...</div>;

    return (
        <div className="marketplace">
            <div className="filters">
                <select 
                    value={filters.type}
                    onChange={(e) => setFilters({...filters, type: e.target.value})}
                >
                    <option value="">All Types</option>
                    <option value="WEAPON">Weapons</option>
                    <option value="ARMOR">Armor</option>
                    <option value="DRUG">Drugs</option>
                    <option value="MEDICINE">Medicine</option>
                    <option value="MISC">Miscellaneous</option>
                </select>
                <input
                    type="number"
                    placeholder="Max Price"
                    value={filters.maxPrice}
                    onChange={(e) => setFilters({...filters, maxPrice: parseFloat(e.target.value)})}
                />
            </div>

            <div className="listings-grid">
                {listings.map(listing => (
                    <div key={listing.id} className="listing-card">
                        <h3>{listing.item.name}</h3>
                        <div className="listing-details">
                            <p>Price: ${listing.price}</p>
                            <p>Quantity: {listing.quantity}</p>
                            <p>Seller: {listing.seller.username}</p>
                            <p>Type: {listing.item.type}</p>
                            {listing.item.effects && (
                                <div className="item-effects">
                                    {Object.entries(listing.item.effects)
                                        .filter(([_, value]) => value !== null)
                                        .map(([effect, value]) => (
                                            <p key={effect}>
                                                {effect}: {value > 0 ? '+' : ''}{value}
                                            </p>
                                        ))
                                    }
                                </div>
                            )}
                        </div>
                        <button
                            onClick={() => handleBuy(listing.id)}
                            disabled={player.money < listing.price * listing.quantity || 
                                    listing.seller.id === player.id}
                            className={listing.seller.id === player.id ? 'own-listing' : ''}
                        >
                            {listing.seller.id === player.id ? 'Your Listing' : 'Buy Now'}
                        </button>
                    </div>
                ))}
            </div>

            <div className="create-listing">
                <h3>Create New Listing</h3>
                <form onSubmit={handleCreateListing}>
                    <select name="item_id" required>
                        {player.inventory.map(item => (
                            <option key={item.id} value={item.id}>
                                {item.name} (x{item.quantity})
                            </option>
                        ))}
                    </select>
                    <input
                        type="number"
                        name="quantity"
                        min="1"
                        placeholder="Quantity"
                        required
                    />
                    <input
                        type="number"
                        name="price"
                        min="0.01"
                        step="0.01"
                        placeholder="Price per unit"
                        required
                    />
                    <button type="submit">Create Listing</button>
                </form>
            </div>
        </div>
    );
};

export default Marketplace; 