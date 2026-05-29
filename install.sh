#!/bin/bash
echo "[+] Installing PhantomTrace Dependencies..."

pkg update -y && pkg upgrade -y
pkg install php python git curl wget nmap traceroute dnsutils hydra hydra-wizard -y
pip install shodan requests dnspython colorama 2>/dev/null
pip3 install shodan requests dnspython colorama 2>/dev/null

mkdir -p reports data modules

chmod +x phantom.php

if ! grep -q "alias phantom=" ~/.bashrc 2>/dev/null; then
    echo "alias phantom='php ~/PhantomTrace/phantom.php'" >> ~/.bashrc
fi

echo "[+] Installation Complete"
echo "[+] Run: source ~/.bashrc"
echo "[+] Then: phantom -h"
