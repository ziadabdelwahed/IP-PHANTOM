<?php
function get_wifi_data() {
    $data = [];
    
    // Termux Wi-Fi scan
    $wifi_scan = shell_exec("termux-wifi-scaninfo 2>/dev/null");
    if ($wifi_scan) {
        $wifi_json = json_decode($wifi_scan, true);
        $data['wifi_networks'] = [];
        
        if (is_array($wifi_json)) {
            foreach ($wifi_json as $net) {
                $data['wifi_networks'][] = [
                    'ssid' => $net['ssid'] ?? 'Hidden',
                    'bssid' => $net['bssid'] ?? '?',
                    'rssi' => $net['rssi'] ?? 0,
                    'frequency' => $net['frequency'] ?? '?',
                    'security' => $net['capabilities'] ?? '?'
                ];
            }
        }
        $data['networks_found'] = count($data['wifi_networks']);
    }
    
    // GPS from device
    $gps = shell_exec("termux-location 2>/dev/null");
    if ($gps) {
        $gps_json = json_decode($gps, true);
        if (isset($gps_json['latitude'])) {
            $data['gps'] = [
                'latitude' => $gps_json['latitude'],
                'longitude' => $gps_json['longitude'],
                'accuracy' => $gps_json['accuracy'] ?? '?',
                'altitude' => $gps_json['altitude'] ?? '?',
                'speed' => $gps_json['speed'] ?? '?',
                'bearing' => $gps_json['bearing'] ?? '?'
            ];
            
            // Get address from coordinates
            if (isset($gps_json['latitude'], $gps_json['longitude'])) {
                $lat = $gps_json['latitude'];
                $lon = $gps_json['longitude'];
                $geo_url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon&zoom=18";
                $ch = curl_init($geo_url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 8,
                    CURLOPT_USERAGENT => 'PhantomTrace/3.0',
                    CURLOPT_HTTPHEADER => ['Accept-Language: en']
                ]);
                $address = curl_exec($ch);
                curl_close($ch);
                
                if ($address) {
                    $addr_json = json_decode($address, true);
                    $data['gps']['address'] = $addr_json['display_name'] ?? 'Unknown';
                }
            }
        }
    }
    
    // Cell tower info
    $cell = shell_exec("termux-telephony-cellinfo 2>/dev/null");
    if ($cell) {
        $data['cell_info'] = json_decode($cell, true);
    }
    
    return $data;
}

function display_wifi($wifi) {
    echo "  \033[1;36m┌─ LOCAL POSITION INTELLIGENCE (YOUR DEVICE)\033[0m\n";
    
    if (isset($wifi['gps'])) {
        $g = $wifi['gps'];
        echo "  \033[1;32m├─ GPS Location:\033[0m\n";
        echo "  \033[1;32m│  ├─ Coordinates:\033[0m {$g['latitude']}, {$g['longitude']}\n";
        echo "  \033[1;32m│  ├─ Accuracy:\033[0m {$g['accuracy']} meters\n";
        if (isset($g['address'])) {
            echo "  \033[1;32m│  ├─ Address:\033[0m {$g['address']}\n";
        }
        if (isset($g['altitude'])) {
            echo "  \033[1;32m│  ├─ Altitude:\033[0m {$g['altitude']} m\n";
        }
        if (isset($g['speed'])) {
            echo "  \033[1;32m│  └─ Speed:\033[0m {$g['speed']} m/s\n";
        }
    }
    
    if (isset($wifi['networks_found'])) {
        echo "  \033[1;36m├─ Wi-Fi Networks Found:\033[0m {$wifi['networks_found']}\n";
        foreach (array_slice($wifi['wifi_networks'], 0, 5) as $net) {
            $signal = ($net['rssi'] > -50) ? "\033[1;32m" : (($net['rssi'] > -70) ? "\033[1;33m" : "\033[1;31m");
            echo "  \033[1;36m│  ├─ {$net['ssid']}\033[0m ({$net['bssid']}) {$signal}{$net['rssi']} dBm\033[0m\n";
        }
    }
    
    if (isset($wifi['cell_info'])) {
        echo "  \033[1;36m├─ Cell Tower Info:\033[0m\n";
        echo "  \033[1;36m│  └─\033[0m " . json_encode($wifi['cell_info']) . "\n";
    }
    
    echo "  \033[1;36m└─\033[0m\n";
}
?>
