-- Players table
CREATE TABLE players (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    level INT DEFAULT 1,
    experience INT DEFAULT 0,
    energy INT DEFAULT 100,
    happiness INT DEFAULT 100,
    last_energy_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_happiness_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    money DECIMAL(20,2) DEFAULT 1000.00
);

-- Combat stats table
CREATE TABLE combat_stats (
    player_id UUID PRIMARY KEY REFERENCES players(id),
    strength INT DEFAULT 10,
    defense INT DEFAULT 10,
    speed INT DEFAULT 10,
    dexterity INT DEFAULT 10,
    health INT DEFAULT 100,
    last_health_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    hospitalization_effectiveness INT DEFAULT 10
);

-- Financial stats table
CREATE TABLE financial_stats (
    player_id UUID PRIMARY KEY REFERENCES players(id),
    stock_value DECIMAL(20,2) DEFAULT 0,
    bank_deposits DECIMAL(20,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory table
CREATE TABLE inventory (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID REFERENCES players(id),
    item_id UUID REFERENCES items(id),
    quantity INT DEFAULT 1,
    equipped_slot VARCHAR(20) NULL -- 'primary', 'secondary', 'temporary', NULL
);

-- Items table
CREATE TABLE items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL, -- weapon, armor, drug, medicine, etc
    msrp DECIMAL(20,2) NOT NULL,
    description TEXT,
    effects JSONB -- Store item effects as JSON
);

-- Market listings table
CREATE TABLE market_listings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    seller_id UUID REFERENCES players(id),
    item_id UUID REFERENCES items(id),
    quantity INT NOT NULL,
    price DECIMAL(20,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Combat log table
CREATE TABLE combat_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    attacker_id UUID REFERENCES players(id),
    defender_id UUID REFERENCES players(id),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    result JSONB, -- Store combat results as JSON
    energy_cost INT DEFAULT 25
);

-- Gym training history
CREATE TABLE gym_training_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID REFERENCES players(id),
    training_type VARCHAR(20) NOT NULL, -- 'strength', 'defense', 'speed', 'dexterity'
    energy_spent INT NOT NULL,
    stat_gain INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stats history for graphs
CREATE TABLE stats_history (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID REFERENCES players(id),
    stat_type VARCHAR(50) NOT NULL,
    value INT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Stock market tables
CREATE TABLE stocks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    symbol VARCHAR(10) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    current_price DECIMAL(20,2) NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE stock_price_history (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    stock_id UUID REFERENCES stocks(id),
    price DECIMAL(20,2) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE player_stocks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID REFERENCES players(id),
    stock_id UUID REFERENCES stocks(id),
    quantity INT NOT NULL,
    purchase_price DECIMAL(20,2) NOT NULL,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bank CD tables
CREATE TABLE certificates_of_deposit (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID REFERENCES players(id),
    amount DECIMAL(20,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    maturity_date TIMESTAMP NOT NULL,
    is_matured BOOLEAN DEFAULT FALSE
);

-- Factions table
CREATE TABLE factions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    leader_id UUID REFERENCES players(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    level INT DEFAULT 1,
    experience INT DEFAULT 0,
    money DECIMAL(20,2) DEFAULT 0,
    member_count INT DEFAULT 1
);

-- Faction members table
CREATE TABLE faction_members (
    faction_id UUID REFERENCES factions(id),
    player_id UUID REFERENCES players(id),
    role VARCHAR(20) NOT NULL, -- 'leader', 'officer', 'member'
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    contribution_points INT DEFAULT 0,
    PRIMARY KEY (faction_id, player_id)
);

-- Faction wars table
CREATE TABLE faction_wars (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    attacker_faction_id UUID REFERENCES factions(id),
    defender_faction_id UUID REFERENCES factions(id),
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active', -- 'active', 'completed', 'cancelled'
    winner_faction_id UUID REFERENCES factions(id),
    points_at_stake INT NOT NULL,
    UNIQUE(attacker_faction_id, defender_faction_id, status) 
    WHERE status = 'active'
);

-- Faction war participation table
CREATE TABLE faction_war_participation (
    war_id UUID REFERENCES faction_wars(id),
    player_id UUID REFERENCES players(id),
    faction_id UUID REFERENCES factions(id),
    attacks_made INT DEFAULT 0,
    damage_dealt INT DEFAULT 0,
    points_contributed INT DEFAULT 0,
    PRIMARY KEY (war_id, player_id)
);

-- Faction rankings table
CREATE TABLE faction_rankings (
    faction_id UUID PRIMARY KEY REFERENCES factions(id),
    rank_points INT DEFAULT 0,
    wars_won INT DEFAULT 0,
    wars_lost INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cities table
CREATE TABLE cities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(50) NOT NULL,
    country VARCHAR(50) NOT NULL,
    latitude DECIMAL(10,8) NOT NULL,
    longitude DECIMAL(11,8) NOT NULL,
    travel_cost DECIMAL(10,2) NOT NULL,
    is_main_city BOOLEAN DEFAULT FALSE
);

-- Player travel history
CREATE TABLE travel_history (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID REFERENCES players(id),
    origin_city_id UUID REFERENCES cities(id),
    destination_city_id UUID REFERENCES cities(id),
    departure_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    arrival_time TIMESTAMP NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'in_progress' -- 'in_progress', 'completed', 'cancelled'
);

-- Insert major cities
INSERT INTO cities (name, country, latitude, longitude, travel_cost, is_main_city) VALUES
    ('Seattle', 'USA', 47.6062, -122.3321, 0, TRUE),
    ('Tokyo', 'Japan', 35.6762, 139.6503, 1200, FALSE),
    ('London', 'UK', 51.5074, -0.1278, 1500, FALSE),
    ('New York', 'USA', 40.7128, -74.0060, 800, FALSE),
    ('Shanghai', 'China', 31.2304, 121.4737, 1300, FALSE),
    ('Dubai', 'UAE', 25.2048, 55.2708, 1600, FALSE),
    ('Paris', 'France', 48.8566, 2.3522, 1400, FALSE),
    ('Singapore', 'Singapore', 1.3521, 103.8198, 1500, FALSE),
    ('Hong Kong', 'China', 22.3193, 114.1694, 1400, FALSE),
    ('Mumbai', 'India', 19.0760, 72.8777, 1300, FALSE),
    ('Sydney', 'Australia', -33.8688, 151.2093, 1700, FALSE),
    ('São Paulo', 'Brazil', -23.5505, -46.6333, 1600, FALSE),
    ('Moscow', 'Russia', 55.7558, 37.6173, 1500, FALSE),
    ('Istanbul', 'Turkey', 41.0082, 28.9784, 1400, FALSE),
    ('Mexico City', 'Mexico', 19.4326, -99.1332, 900, FALSE),
    ('Cairo', 'Egypt', 30.0444, 31.2357, 1500, FALSE),
    ('Los Angeles', 'USA', 34.0522, -118.2437, 500, FALSE),
    ('Seoul', 'South Korea', 37.5665, 126.9780, 1300, FALSE),
    ('Bangkok', 'Thailand', 13.7563, 100.5018, 1400, FALSE),
    ('Toronto', 'Canada', 43.6532, -79.3832, 700, FALSE); 

-- Hospital stays table
CREATE TABLE hospital_stays (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID REFERENCES players(id),
    attacker_id UUID REFERENCES players(id),
    admitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    release_time TIMESTAMP NOT NULL,
    initial_health INT NOT NULL,
    current_health INT NOT NULL,
    reason TEXT,
    status VARCHAR(20) DEFAULT 'admitted' -- 'admitted', 'self_discharged', 'released'
); 

-- Add to existing schema
CREATE TABLE sessions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID REFERENCES players(id),
    token VARCHAR(255) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add index for token lookups
CREATE INDEX idx_sessions_token ON sessions(token); 

-- Bounties table
CREATE TABLE bounties (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    issuer_id UUID REFERENCES players(id),
    target_id UUID REFERENCES players(id),
    amount DECIMAL(20,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    claimed_by UUID REFERENCES players(id),
    claimed_at TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active', -- 'active', 'claimed', 'cancelled'
    CONSTRAINT bounty_amount_min CHECK (amount >= 1000), -- Minimum bounty amount
    CONSTRAINT bounty_limit UNIQUE (issuer_id, target_id, status) 
    WHERE status = 'active'
);

-- Index for quick bounty lookups
CREATE INDEX idx_bounties_target ON bounties(target_id) WHERE status = 'active';
CREATE INDEX idx_bounties_issuer ON bounties(issuer_id); 

-- Stock market tables
CREATE TABLE stock_prices (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    stock_id UUID NOT NULL REFERENCES stocks(id),
    price DECIMAL(10,2) NOT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (stock_id, timestamp)
);

CREATE TABLE stock_transactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID NOT NULL REFERENCES players(id),
    stock_id UUID NOT NULL REFERENCES stocks(id),
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    type VARCHAR(10) NOT NULL CHECK (type IN ('buy', 'sell')),
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (player_id, timestamp)
);

-- Bank and CD tables
CREATE TABLE cd_rates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    term_days INT NOT NULL,
    rate DECIMAL(5,2) NOT NULL,
    effective_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (term_days, effective_date)
);

CREATE TABLE player_cds (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID NOT NULL REFERENCES players(id),
    amount DECIMAL(10,2) NOT NULL,
    term_days INT NOT NULL,
    rate DECIMAL(5,2) NOT NULL,
    start_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    maturity_date TIMESTAMP NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'matured', 'withdrawn')),
    interest_paid DECIMAL(10,2),
    INDEX (player_id, status)
);

CREATE TABLE interest_transactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    player_id UUID NOT NULL REFERENCES players(id),
    cd_id UUID REFERENCES player_cds(id),
    amount DECIMAL(10,2) NOT NULL,
    type VARCHAR(20) NOT NULL CHECK (type IN ('cd_maturity', 'savings_interest')),
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (player_id, type, timestamp)
);

-- Marketplace tables
CREATE TABLE marketplace_listings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    seller_id UUID NOT NULL REFERENCES players(id),
    item_id UUID NOT NULL REFERENCES items(id),
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'sold', 'cancelled')),
    INDEX (seller_id, status),
    INDEX (item_id, status, price)
);

-- Add changefeed support to tables
ALTER TABLE stock_prices SET (changefeed.enabled = true);
ALTER TABLE stock_transactions SET (changefeed.enabled = true);
ALTER TABLE cd_rates SET (changefeed.enabled = true);
ALTER TABLE player_cds SET (changefeed.enabled = true);
ALTER TABLE interest_transactions SET (changefeed.enabled = true);
ALTER TABLE marketplace_listings SET (changefeed.enabled = true);

-- Create changefeed jobs
CREATE CHANGEFEED FOR TABLE stock_prices INTO 'kafka://kafka:9092' WITH updated, resolved='5s';
CREATE CHANGEFEED FOR TABLE stock_transactions INTO 'kafka://kafka:9092' WITH updated, resolved='10s';
CREATE CHANGEFEED FOR TABLE cd_rates INTO 'kafka://kafka:9092' WITH updated, resolved='10s';
CREATE CHANGEFEED FOR TABLE player_cds INTO 'kafka://kafka:9092' WITH updated, resolved='10s';
CREATE CHANGEFEED FOR TABLE interest_transactions INTO 'kafka://kafka:9092' WITH updated, resolved='5s';
CREATE CHANGEFEED FOR TABLE marketplace_listings INTO 'kafka://kafka:9092' WITH updated, resolved='10s'; 

-- Add changefeed support for combat-related tables
ALTER TABLE combat_logs SET (changefeed.enabled = true);
ALTER TABLE hospital_stays SET (changefeed.enabled = true);
ALTER TABLE combat_stats SET (changefeed.enabled = true);

-- Add changefeed support for faction-related tables
ALTER TABLE faction_wars SET (changefeed.enabled = true);
ALTER TABLE faction_war_participation SET (changefeed.enabled = true);
ALTER TABLE faction_rankings SET (changefeed.enabled = true);

-- Create combat and faction changefeeds
CREATE CHANGEFEED FOR TABLE combat_logs 
INTO 'kafka://kafka:9092' 
WITH updated, resolved='2s';  -- Fast updates for combat

CREATE CHANGEFEED FOR TABLE hospital_stays 
INTO 'kafka://kafka:9092' 
WITH updated, resolved='5s';

CREATE CHANGEFEED FOR TABLE combat_stats 
INTO 'kafka://kafka:9092' 
WITH updated, resolved='5s';

CREATE CHANGEFEED FOR TABLE faction_wars 
INTO 'kafka://kafka:9092' 
WITH updated, resolved='5s';

CREATE CHANGEFEED FOR TABLE faction_war_participation 
INTO 'kafka://kafka:9092' 
WITH updated, resolved='5s';

CREATE CHANGEFEED FOR TABLE faction_rankings 
INTO 'kafka://kafka:9092' 
WITH updated, resolved='30s';  -- Rankings can update slower 