<?php
function get_route_data($ip) {
    $data = [];
    
    $hops_raw = shell_exec("traceroute -n -m 25 -w 2 -q 1 $ip 2>/dev/null");
    $data['raw'] = $hops_raw ?: "Traceroute not available";
    
    $hops = [];
    if ($hops_raw) {
        $lines = explode("\n", $hops_raw);
        foreach ($lines as $line) {
            if (preg_match('/^\s*(\d+)\s+(\S+)/', $line, $m)) {
                $hop_num = $m[1];
                $hop_ip = $m[2];
                
                if ($hop_ip !== '*' && filter_var($hop_ip, FILTER_VALIDATE_IP)) {
                    // Get geo for each hop
                    $geo = json_decode(@file_get_contents("http://ip-api.com/json/$hop_ip?fields=country,city,isp"), true);
                    $hops[$hop_num] = [
                        'ip' => $hop_ip,
                        'country' => $geo['country'] ?? '?',
                        'city' => $geo['city'] ?? '?',
                        'isp' => $geo['isp'] ?? '?'
                    ];
                } else {
                    $hops[$hop_num] = ['ip' => '*', 'country' => '?', 'city' => '?', 'isp' => '?'];
                }
            }
        }
    }
    
    $data['hops'] = $hops;
    $data['total_hops'] = count($hops);
    
    return $data;
}

function display_route($route) {
    echo "  \033[1;36m┌─ ROUTE INTELLIGENCE ($route[total_hops] hops)\033[0m\n";
    
    foreach ($route['hops'] as $num => $hop) {
        if ($hop['ip'] === '*') {
            echo "  \033[1;37m│  $num. *** (No response)\033[0m\n";
        } else {
            echo "  \033[1;32m│  $num.\033[0m {$hop['ip']} → {$hop['city']}, {$hop['country']} [{$hop['isp']}]\n";
        }
    }
    
    echo "  \033[1;36m└─\033[0m\n";
}
?>
