#!/usr/bin/env python3
import sys
import os
import requests
from supervisor.childutils import listener

def write_stdout(s):
    sys.stdout.write(s)
    sys.stdout.flush()

def write_stderr(s):
    sys.stderr.write(s)
    sys.stderr.flush()

def check_health(socket_type):
    try:
        response = requests.get(f'http://localhost/health.php?type={socket_type}')
        return response.status_code == 200
    except:
        return False

def main():
    while True:
        headers, payload = listener.wait()
        if headers['eventname'] == 'TICK_60':
            if not check_health('combat'):
                os.system('supervisorctl restart combat_socket')
            if not check_health('market'):
                os.system('supervisorctl restart market_socket')
        write_stdout('OK')
        write_stdout('\n')

if __name__ == '__main__':
    main() 