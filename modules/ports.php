<?php
function get_port_data($ip) {
    $data = [];
    $open_ports = [];
    $banners = [];
    
    $ports_to_check = [
        21 => 'FTP', 22 => 'SSH', 23 => 'Telnet', 25 => 'SMTP',
        53 => 'DNS', 80 => 'HTTP', 110 => 'POP3', 135 => 'MSRPC',
        139 => 'NetBIOS', 143 => 'IMAP', 443 => 'HTTPS', 445 => 'SMB',
        993 => 'IMAPS', 995 => 'POP3S', 1723 => 'PPTP', 3306 => 'MySQL',
        3389 => 'RDP', 5432 => 'PostgreSQL', 5900 => 'VNC', 6379 => 'Redis',
        8080 => 'HTTP-Alt', 8443 => 'HTTPS-Alt', 9090 => 'Cockpit',
        27017 => 'MongoDB', 11211 => 'Memcached', 1433 => 'MSSQL',
        1521 => 'Oracle', 25 => 'SMTP', 587 => 'SMTP-TLS', 465 => 'SMTPS',
        993 => 'IMAPS', 995 => 'POP3S', 2525 => 'SMTP-Alt'
    ];
    
    // Fast socket check
    foreach ($ports_to_check as $port => $service) {
        $socket = @fsockopen($ip, $port, $errno, $errstr, 1.5);
        if ($socket) {
            $open_ports[$port] = $service;
            
            // Grab banner
            if (in_array($port, [21, 22, 25, 80, 110, 143, 443, 3306, 3389])) {
                fwrite($socket, "HEAD / HTTP/1.0\r\n\r\n");
                $banner = fread($socket, 512);
                if ($banner) {
                    $banners[$port] = trim(preg_replace('/\s+/', ' ', substr($banner, 0, 100)));
                }
            }
            fclose($socket);
        }
    }
    
    $data['open_ports'] = $open_ports;
    $data['banners'] = $banners;
    $data['total_open'] = count($open_ports);
    
    // NMAP quick scan
    $nmap_cmd = "nmap -sS -sV -T4 --max-retries 1 --host-timeout 45s $ip 2>/dev/null";
    $nmap_raw = shell_exec($nmap_cmd);
    $data['nmap_scan'] = $nmap_raw ?: "NMAP not available or timed out";
    
    // Parse nmap for more details
    if ($nmap_raw) {
        preg_match_all('/(\d+)\/tcp\s+open\s+(\S+)/', $nmap_raw, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[1] as $i => $port) {
                $open_ports[(int)$port] = $matches[2][$i] ?? 'unknown';
            }
        }
    }
    
    return $data;
}

function display_ports($ports) {
    echo "  \033[1;36m┌─ PORT INTELLIGENCE\033[0m\n";
    echo "  \033[1;36m├─ Open Ports Found:\033[0m " . count($ports['open_ports']) . "\n";
    
    foreach ($ports['open_ports'] as $port => $service) {
        $banner = isset($ports['banners'][$port]) ? " - {$ports['banners'][$port]}" : "";
        echo "  \033[1;32m│  ✓ Port $port\033[0m → $service$banner\n";
    }
    
    if (empty($ports['open_ports'])) {
        echo "  \033[1;37m│  No common ports open (stealth/filtered)\033[0m\n";
    }
    
    echo "  \033[1;36m└─\033[0m\n";
}
?>
