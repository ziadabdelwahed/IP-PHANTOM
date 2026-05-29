<?php
function get_blacklist_data($ip) {
    $data = [];
    
    // AbuseIPDB
    $ch = curl_init("https://api.abuseipdb.com/api/v2/check?ipAddress=$ip&maxAgeInDays=90&verbose");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Key: 7f1e2d3c4b5a69788796a5b4c3d2e1f0',  // Demo key
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    $abuse = curl_exec($ch);
    curl_close($ch);
    if ($abuse) {
        $abuse_json = json_decode($abuse, true);
        $abuse_data = $abuse_json['data'] ?? [];
        $data['abuseipdb'] = [
            'abuse_score' => $abuse_data['abuseConfidenceScore'] ?? 0,
            'total_reports' => $abuse_data['totalReports'] ?? 0,
            'last_reported' => $abuse_data['lastReportedAt'] ?? 'Never',
            'is_whitelisted' => $abuse_data['isWhitelisted'] ?? false,
            'country' => $abuse_data['countryCode'] ?? '??',
            'isp' => $abuse_data['isp'] ?? 'Unknown',
            'domain' => $abuse_data['domain'] ?? 'N/A',
            'usage_type' => $abuse_data['usageType'] ?? 'Unknown'
        ];
    }
    
    // Additional: Check via blocklist.de
    $bl_ch = curl_init("http://api.blocklist.de/api.php?ip=$ip&format=json");
    curl_setopt($bl_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($bl_ch, CURLOPT_TIMEOUT, 8);
    $blocklist = curl_exec($bl_ch);
    curl_close($bl_ch);
    if ($blocklist) {
        $bl_json = json_decode($blocklist, true);
        $data['blocklist_de'] = $bl_json ?? [];
    }
    
    return $data;
}

function display_blacklist($bl) {
    echo "  \033[1;36m┌─ REPUTATION INTELLIGENCE\033[0m\n";
    
    if (isset($bl['abuseipdb'])) {
        $ab = $bl['abuseipdb'];
        $score = $ab['abuse_score'];
        $color = ($score > 50) ? "\033[1;31m" : (($score > 10) ? "\033[1;33m" : "\033[1;32m");
        
        echo "  \033[1;36m├─ AbuseIPDB Score:\033[0m {$color}{$score}%\033[0m\n";
        echo "  \033[1;36m├─ Total Reports:\033[0m " . $ab['total_reports'] . "\n";
        echo "  \033[1;36m├─ Last Reported:\033[0m " . $ab['last_reported'] . "\n";
        echo "  \033[1;36m├─ Usage Type:\033[0m " . $ab['usage_type'] . "\n";
        echo "  \033[1;36m├─ ISP:\033[0m " . $ab['isp'] . "\n";
        
        if ($score > 50) {
            echo "  \033[1;31m│  ⚠ HIGH RISK - This IP has been reported for malicious activity\033[0m\n";
        } elseif ($score > 10) {
            echo "  \033[1;33m│  ⚡ MODERATE RISK - Some reports found\033[0m\n";
        } else {
            echo "  \033[1;32m│  ✓ LOW RISK\033[0m\n";
        }
    }
    
    if (isset($bl['blocklist_de']) && !empty($bl['blocklist_de'])) {
        echo "  \033[1;31m│  ⚠ Listed on blocklist.de\033[0m\n";
    }
    
    echo "  \033[1;36m└─\033[0m\n";
}
?>
