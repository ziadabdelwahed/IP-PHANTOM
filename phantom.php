#!/usr/bin/env php
<?php
error_reporting(0);
set_time_limit(0);
ini_set('memory_limit', '512M');
date_default_timezone_set('Africa/Cairo');

require_once __DIR__ . '/modules/geo.php';
require_once __DIR__ . '/modules/dns.php';
require_once __DIR__ . '/modules/ports.php';
require_once __DIR__ . '/modules/blacklist.php';
require_once __DIR__ . '/modules/network.php';
require_once __DIR__ . '/modules/route.php';
require_once __DIR__ . '/modules/device.php';
require_once __DIR__ . '/modules/threat.php';
require_once __DIR__ . '/modules/wifi.php';

function phantom_banner() {
    echo "\033[1;31m
    ╔══════════════════════════════════════════════════════════════╗
    ║             ██████╗ ██╗  ██╗ █████╗ ███╗   ██╗████████╗ ██████╗ ███╗   ███╗     ║
    ║             ██╔══██╗██║  ██║██╔══██╗████╗  ██║╚══██╔══╝██╔═══██╗████╗ ████║     ║
    ║             ██████╔╝███████║███████║██╔██╗ ██║   ██║   ██║   ██║██╔████╔██║     ║
    ║             ██╔═══╝ ██╔══██║██╔══██║██║╚██╗██║   ██║   ██║   ██║██║╚██╔╝██║     ║
    ║             ██║     ██║  ██║██║  ██║██║ ╚████║   ██║   ╚██████╔╝██║ ╚═╝ ██║     ║
    ║             ╚═╝     ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚═══╝   ╚═╝    ╚═════╝ ╚═╝     ╚═╝     ║
    ╠══════════════════════════════════════════════════════════════╣
    ║              FULL SPECTRUM INTELLIGENCE ENGINE v3.0           ║
    ║                    \"We see everything\"                        ║
    ╚══════════════════════════════════════════════════════════════╝
    \033[0m\n";
}

function full_trace($target_ip) {
    echo "\033[1;36m[>] INITIATING FULL SPECTRUM TRACE ON: $target_ip\033[0m\n";
    echo "\033[1;36m[>] DEPLOYING ALL 9 INTELLIGENCE LAYERS...\033[0m\n";
    echo str_repeat("═", 80) . "\n";

    $results = [];
    
    echo "\033[1;33m[LAYER 1/9] Multi-Source Geolocation...\033[0m\n";
    $results['geo'] = get_geo_data($target_ip);
    display_geo($results['geo']);
    
    echo "\n\033[1;33m[LAYER 2/9] DNS Intelligence...\033[0m\n";
    $results['dns'] = get_dns_data($target_ip);
    display_dns($results['dns']);
    
    echo "\n\033[1;33m[LAYER 3/9] Port & Service Intelligence...\033[0m\n";
    $results['ports'] = get_port_data($target_ip);
    display_ports($results['ports']);
    
    echo "\n\033[1;33m[LAYER 4/9] Blacklist & Reputation...\033[0m\n";
    $results['blacklist'] = get_blacklist_data($target_ip);
    display_blacklist($results['blacklist']);
    
    echo "\n\033[1;33m[LAYER 5/9] Network & ASN Intelligence...\033[0m\n";
    $results['network'] = get_network_data($target_ip);
    display_network($results['network']);
    
    echo "\n\033[1;33m[LAYER 6/9] Route Intelligence (Traceroute)...\033[0m\n";
    $results['route'] = get_route_data($target_ip);
    display_route($results['route']);
    
    echo "\n\033[1;33m[LAYER 7/9] Device Fingerprinting...\033[0m\n";
    $results['device'] = get_device_data($target_ip);
    display_device($results['device']);
    
    echo "\n\033[1;33m[LAYER 8/9] Threat Intelligence...\033[0m\n";
    $results['threat'] = get_threat_data($target_ip);
    display_threat($results['threat']);
    
    echo "\n\033[1;33m[LAYER 9/9] Local Wi-Fi/GPS Triangulation (Your Actual Location)...\033[0m\n";
    $results['wifi'] = get_wifi_data();
    display_wifi($results['wifi']);
    
    echo "\n" . str_repeat("═", 80) . "\n";
    echo "\033[1;32m[✓] FULL SPECTRUM TRACE COMPLETE\033[0m\n";
    echo str_repeat("═", 80) . "\n";
    
    save_report($target_ip, $results);
    
    return $results;
}

function save_report($ip, $data) {
    @mkdir('reports', 0777, true);
    $timestamp = date('Y-m-d_H-i-s');
    $report_file = "reports/report_{$ip}_{$timestamp}.txt";
    $json_file = "reports/report_{$ip}_{$timestamp}.json";
    
    $report = "PHANTOMTRACE FULL INTELLIGENCE REPORT\n";
    $report .= "========================================\n";
    $report .= "Target: $ip\n";
    $report .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $report .= "========================================\n\n";
    
    foreach($data as $layer => $content) {
        $report .= "[LAYER: $layer]\n";
        $report .= str_repeat("-", 40) . "\n";
        $report .= print_r($content, true) . "\n\n";
    }
    
    file_put_contents($report_file, $report);
    file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT));
    
    echo "\033[1;35m[✓] Report saved: $report_file\033[0m\n";
    echo "\033[1;35m[✓] JSON saved: $json_file\033[0m\n";
}

// Main execution
phantom_banner();

if ($argc < 2) {
    echo "\033[1;33mUsage:\033[0m\n";
    echo "  php phantom.php -t <target-ip>     Full trace\n";
    echo "  php phantom.php -m                 Trace your own IP\n";
    echo "  php phantom.php -l                 Your exact location (Wi-Fi/GPS)\n";
    echo "  php phantom.php -q <ip>            Quick trace (geo only)\n";
    exit(0);
}

switch ($argv[1]) {
    case '-t':
        if (!isset($argv[2]) || !filter_var($argv[2], FILTER_VALIDATE_IP)) {
            die("\033[1;31m[!] Valid IP required\033[0m\n");
        }
        full_trace($argv[2]);
        break;
    
    case '-m':
        $my_ip = trim(file_get_contents('http://ip-api.com/json/?fields=query'));
        $my_ip_json = json_decode($my_ip, true);
        $my_ip_clean = $my_ip_json['query'] ?? trim(shell_exec('curl -s ifconfig.me'));
        echo "\033[1;32m[+] Your IP: $my_ip_clean\033[0m\n";
        full_trace($my_ip_clean);
        break;
    
    case '-l':
        echo "\033[1;32m[+] Getting your exact location...\033[0m\n";
        $loc = get_wifi_data();
        display_wifi($loc);
        break;
    
    case '-q':
        if (!isset($argv[2]) || !filter_var($argv[2], FILTER_VALIDATE_IP)) {
            die("\033[1;31m[!] Valid IP required\033[0m\n");
        }
        $geo = get_geo_data($argv[2]);
        display_geo($geo);
        break;
    
    default:
        echo "\033[1;31m[!] Unknown option\033[0m\n";
}
?>
