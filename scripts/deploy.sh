#!/bin/bash

# Install supervisor if not present
if ! command -v supervisord &> /dev/null; then
    apt-get update
    apt-get install -y supervisor
fi

# Copy websocket supervisor config
cp config/supervisor/websockets.conf /etc/supervisor/conf.d/

# Make socket script executable
chmod +x bin/socket.php

# Reload supervisor config
supervisorctl reread
supervisorctl update

# Restart websocket processes
supervisorctl restart websockets:*

# Check status
supervisorctl status websockets:* 