# Online RPG Game

A browser-based multiplayer RPG game built with PHP, CockroachDB, and GraphQL.

## Features

- Player character creation and progression
- Real-time combat system with stats gained through satanic rituals
- Live marketplace for trading items between players
- Real-time stock market simulation with live price updates
- Banking system with CDs and interest rates
- Cult system with wars and alliances
- Hospital system for healing after combat
- Energy system for sacrifices and rituals
- Travel between different locations
- Inventory and equipment management

## Technology Stack

### Backend
- PHP 7.4+
- CockroachDB (with changefeeds for real-time updates)
- GraphQL API
- WebSocket servers for real-time communication
- Kafka for event streaming
- Supervisor for process management

### Frontend
- React with TypeScript
- Apollo Client for GraphQL
- WebSocket clients for real-time updates
- CSS Modules for styling

### Infrastructure
- Docker for containerization
- Supervisor for process management
- Prometheus for monitoring
- Kafka for event streaming

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/rpg-game.git
cd rpg-game
```

2. Install PHP dependencies:
```bash
composer install

# Generate autoload files
composer dump-autoload -o
```

3. Install frontend dependencies:
```bash
cd frontend
npm install
```

4. Set up CockroachDB:
```sql
# Start CockroachDB
cockroach start-single-node --insecure

# Create database and user
cockroach sql --insecure
CREATE DATABASE rpg_game;
CREATE USER rpg_user WITH PASSWORD 'your_password';
GRANT ALL ON DATABASE rpg_game TO rpg_user;
```

5. Set up Kafka:
```bash
# Start Zookeeper and Kafka
docker-compose up -d zookeeper kafka
```

6. Import database schema:
```bash
cockroach sql --insecure --database=rpg_game < database/schema.sql
```

7. Configure environment variables:
```bash
cp .env.example .env

# Edit .env with your configuration
vim .env
```

8. Start WebSocket servers:
```bash
# Install supervisor
sudo apt-get install supervisor

# Copy supervisor config
sudo cp config/supervisor/websockets.conf /etc/supervisor/conf.d/

# Reload and start services
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start websockets:*
```

9. Start development servers:

Backend:
```bash
php -S localhost:8000 -t public
```

Frontend:
```bash
cd frontend
npm run dev
```

## Architecture

### Real-time Updates
- CockroachDB changefeeds for database changes
- Kafka for event streaming
- WebSocket servers for client communication
- Separate socket servers for combat and market updates

### Monitoring
- Health check endpoints with Prometheus metrics
- Supervisor for process management
- Error tracking and logging
- Memory usage monitoring
- Client connection tracking

## Directory Structure

```
├── bin/                # CLI scripts
├── config/            # Configuration files
├── database/          # Database migrations and schema
├── frontend/          # React frontend application
├── public/            # Public web files
├── schema/            # GraphQL schema definitions
├── scripts/           # Deployment and maintenance scripts
├── src/               # PHP source code
│   ├── Auth/          # Authentication
│   ├── GraphQL/       # GraphQL handlers
│   ├── WebSocket/     # WebSocket servers
│   └── Models/        # Database models
└── tests/             # Test files
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## Development

### Testing
```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite=market

# Run with coverage report
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html coverage
```

### Starting WebSocket Servers
```bash
# Start combat socket
php bin/socket.php combat

# Start market socket
php bin/socket.php market
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Authors

- Your Name - Initial work

## Acknowledgments

- Thanks to all contributors
- Inspired by classic browser RPG games
