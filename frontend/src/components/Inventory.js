import React, { useState, useEffect } from 'react';
import './Inventory.css';

const Inventory = ({ player }) => {
    const [inventory, setInventory] = useState([]);
    const [selectedItem, setSelectedItem] = useState(null);
    const [equippedItems, setEquippedItems] = useState({
        primary: null,
        secondary: null,
        temporary: null
    });

    useEffect(() => {
        fetchInventory();
    }, []);

    const fetchInventory = async () => {
        const response = await fetch(`/api/players/${player.id}/inventory`);
        const data = await response.json();
        setInventory(data);
        
        // Update equipped items
        const equipped = {
            primary: null,
            secondary: null,
            temporary: null
        };
        data.forEach(item => {
            if (item.equipped_slot) {
                equipped[item.equipped_slot] = item;
            }
        });
        setEquippedItems(equipped);
    };

    const handleEquip = async (itemId, slot) => {
        try {
            const response = await fetch('/api/inventory/equip', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: itemId,
                    slot: slot,
                    player_id: player.id
                })
            });

            const result = await response.json();
            if (result.success) {
                fetchInventory();
            }
        } catch (error) {
            console.error('Equipment failed:', error);
        }
    };

    const handleUse = async (itemId) => {
        try {
            const response = await fetch('/api/inventory/use', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: itemId,
                    player_id: player.id
                })
            });

            const result = await response.json();
            if (result.success) {
                fetchInventory();
                // Update player stats based on item effects
            }
        } catch (error) {
            console.error('Item use failed:', error);
        }
    };

    return (
        <div className="inventory">
            <div className="equipped-items">
                <h3>Equipped Items</h3>
                <div className="equipment-slots">
                    {Object.entries(equippedItems).map(([slot, item]) => (
                        <div key={slot} className="equipment-slot">
                            <h4>{slot.charAt(0).toUpperCase() + slot.slice(1)}</h4>
                            {item ? (
                                <div className="equipped-item">
                                    <img src={`/images/items/${item.type}.png`} alt={item.name} />
                                    <p>{item.name}</p>
                                </div>
                            ) : (
                                <div className="empty-slot">Empty</div>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            <div className="inventory-grid">
                {inventory.map(item => (
                    <div 
                        key={item.id} 
                        className={`inventory-item ${selectedItem?.id === item.id ? 'selected' : ''}`}
                        onClick={() => setSelectedItem(item)}
                    >
                        <img src={`/images/items/${item.type}.png`} alt={item.name} />
                        <div className="item-details">
                            <h4>{item.name}</h4>
                            <p>Quantity: {item.quantity}</p>
                            {item.type === 'weapon' || item.type === 'armor' ? (
                                <div className="equipment-actions">
                                    <button onClick={() => handleEquip(item.id, 'primary')}>
                                        Primary
                                    </button>
                                    <button onClick={() => handleEquip(item.id, 'secondary')}>
                                        Secondary
                                    </button>
                                    {item.type === 'weapon' && (
                                        <button onClick={() => handleEquip(item.id, 'temporary')}>
                                            Temporary
                                        </button>
                                    )}
                                </div>
                            ) : (
                                <button 
                                    onClick={() => handleUse(item.id)}
                                    className="use-button"
                                >
                                    Use
                                </button>
                            )}
                        </div>
                    </div>
                ))}
            </div>

            {selectedItem && (
                <div className="item-details-panel">
                    <h3>{selectedItem.name}</h3>
                    <p>{selectedItem.description}</p>
                    <div className="item-effects">
                        {Object.entries(JSON.parse(selectedItem.effects)).map(([effect, value]) => (
                            <p key={effect}>
                                {effect}: {value > 0 ? '+' : ''}{value}
                            </p>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
};

export default Inventory; 