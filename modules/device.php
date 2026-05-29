<?php
function get_device_data($ip) {
    $data = [];
    
    // Ping for TTL and latency
    $ping = shell_exec("ping -c 3 -W 2 $ip 2>/dev/null");
    $data['ping'] = $ping ?: 'No ping response';
    
    if ($ping) {
        // Extract TTL
        if (preg_match('/ttl=(\d+)/i', $ping, $ttl_match)) {
            $ttl = (int)$ttl_match[1];
            $data['ttl'] = $ttl;
            
            if ($ttl <= 64) $data['os'] = 'Linux/Unix/Android';
            elseif ($ttl <= 128 && $ttl > 64) $data['os'] = 'Windows';
            elseif ($ttl <= 60) $data['os'] = 'BSD/macOS/iOS';
            elseif ($ttl >= 250) $data['os'] = 'Cisco/Solaris/Network Device';
            else $data['os'] = "Unknown (TTL: $ttl)";
        }
        
        // Extract latency
        if (preg_match_all('/time=(\d+\.?\d*)/', $ping, $latency_matches)) {
            $latencies = array_map('floatval', $latency_matches[1]);
            $data['latency_min'] = min($latencies);
            $data['latency_avg'] = array_sum($latencies) / count($latencies);
            $data['latency_max'] = max($latencies);
            
            // Estimate distance based on speed of light in fiber (~200,000 km/s)
            $data['estimated_distance_km'] = round(($data['latency_avg'] / 1000) * 100000);
        }
    }
    
    // TCP fingerprint via nmap
    $nmap_os = shell_exec("nmap -O $ip 2>/dev/null | grep -A5 'OS details'");
    if ($nmap_os) {
        $data['nmap_os_detection'] = trim($nmap_os);
    }
    
    return $data;
}

function display_device($device) {
    echo "  \033[1;36m┌─ DEVICE FINGERPRINT\033[0m\n";
    
    if (isset($device['os'])) {
        echo "  \033[1;36m├─ Detected OS:\033[0m " . $device['os'] . "\n";
    }
    
    if (isset($device['ttl'])) {
        echo "  \033[1;36m├─ TTL:\033[0m " . $device['ttl'] . "\n";
    }
    
    if (isset($device['latency_avg'])) {
        echo "  \033[1;36m├─ Latency:\033[0m min=" . $device['latency_min'] . "ms, avg=" . round($device['latency_avg'], 1) . "ms, max=" . $device['latency_max'] . "ms\n";
        echo "  \033[1;36m├─ Est. Distance:\033[0m ~" . $device['estimated_distance_km'] . " km\n";
    }
    
    if (isset($device['nmap_os_detection'])) {
        echo "  \033[1;36m├─ NMAP OS Detection:\033[0m\n";
        echo "  \033[1;36m│  \033[0m" . str_replace("\n", "\n  │  ", $device['nmap_os_detection']) . "\n";
    }
    
    echo "  \033[1;36m└─\033[0m\n";
}
?>
