<?php
function get_threat_data($ip) {
    $data = [];
    
    // VirusTotal check (public API)
    $vt_api_key = 'YOUR_VIRUSTOTAL_API_KEY'; // Replace with actual key for full data
    $ch = curl_init("https://www.virustotal.com/api/v3/ip_addresses/$ip");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["x-apikey: $vt_api_key"],
        CURLOPT_TIMEOUT => 12
    ]);
    $vt_response = curl_exec($ch);
    curl_close($ch);
    
    if ($vt_response) {
        $vt = json_decode($vt_response, true);
        $vt_attrs = $vt['data']['attributes'] ?? [];
        
        $data['virustotal'] = [
            'last_analysis_stats' => $vt_attrs['last_analysis_stats'] ?? [],
            'reputation' => $vt_attrs['reputation'] ?? 0,
            'total_votes' => $vt_attrs['total_votes'] ?? [],
        ];
    }
    
    // AlienVault OTX
    $otx_ch = curl_init("https://otx.alienvault.com/api/v1/indicators/IPv4/$ip/general");
    curl_setopt($otx_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($otx_ch, CURLOPT_TIMEOUT, 10);
    $otx_response = curl_exec($otx_ch);
    curl_close($otx_ch);
    
    if ($otx_response) {
        $otx = json_decode($otx_response, true);
        $data['alienvault'] = [
            'pulse_count' => $otx['pulse_info']['count'] ?? 0,
            'pulses' => [],
            'reputation' => $otx['reputation'] ?? 0
        ];
        
        if (!empty($otx['pulse_info']['pulses'])) {
            foreach (array_slice($otx['pulse_info']['pulses'], 0, 5) as $pulse) {
                $data['alienvault']['pulses'][] = [
                    'name' => $pulse['name'] ?? 'Unknown',
                    'created' => $pulse['created'] ?? '',
                    'tags' => $pulse['tags'] ?? []
                ];
            }
        }
    }
    
    // Check common C2 patterns
    $data['c2_indicators'] = check_c2_patterns($ip);
    
    return $data;
}

function check_c2_patterns($ip) {
    $paths = [
        '/admin/get.php' => 'Empire C2',
        '/news.php' => 'Empire C2',
        '/login/process.php' => 'Empire C2',
        '/ca' => 'Cobalt Strike',
        '/dpixel' => 'Cobalt Strike',
        '/submit.php' => 'Cobalt Strike',
        '/jquery-3.3.1.min.js' => 'Cobalt Strike',
        '/api/v1/' => 'Metasploit',
        '/INITJM' => 'Metasploit',
        '/css/' => 'PoshC2',
        '/js/' => 'PoshC2'
    ];
    
    $matches = [];
    foreach ($paths as $path => $framework) {
        $url = "http://$ip$path";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_NOBODY => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code > 0) {
            $matches[] = "$path → HTTP $http_code [$framework]";
        }
    }
    
    return $matches;
}

function display_threat($threat) {
    echo "  \033[1;36m┌─ THREAT INTELLIGENCE\033[0m\n";
    
    if (isset($threat['virustotal'])) {
        $vt = $threat['virustotal'];
        $malicious = $vt['last_analysis_stats']['malicious'] ?? 0;
        $suspicious = $vt['last_analysis_stats']['suspicious'] ?? 0;
        $harmless = $vt['last_analysis_stats']['harmless'] ?? 0;
        
        echo "  \033[1;36m├─ VirusTotal:\033[0m\n";
        echo "  \033[1;31m│  ├─ Malicious:\033[0m $malicious\n";
        echo "  \033[1;33m│  ├─ Suspicious:\033[0m $suspicious\n";
        echo "  \033[1;32m│  ├─ Harmless:\033[0m $harmless\n";
        echo "  \033[1;36m│  └─ Reputation:\033[0m " . $vt['reputation'] . "\n";
    }
    
    if (isset($threat['alienvault'])) {
        $av = $threat['alienvault'];
        echo "  \033[1;36m├─ AlienVault OTX:\033[0m\n";
        echo "  \033[1;36m│  ├─ Pulse Count:\033[0m " . $av['pulse_count'] . "\n";
        echo "  \033[1;36m│  └─ Reputation:\033[0m " . $av['reputation'] . "\n";
    }
    
    if (!empty($threat['c2_indicators'])) {
        echo "  \033[1;31m├─ C2 Indicators Detected:\033[0m\n";
        foreach ($threat['c2_indicators'] as $indicator) {
            echo "  \033[1;31m│  ⚠ $indicator\033[0m\n";
        }
    }
    
    echo "  \033[1;36m└─\033[0m\n";
}
?>
