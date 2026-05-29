<?php
function get_geo_data($ip) {
    $sources = [
        "http://ip-api.com/json/$ip",
        "http://ipwhois.app/json/$ip",
        "https://ipinfo.io/$ip/json",
        "http://www.geoplugin.net/json.gp?ip=$ip",
        "https://freegeoip.app/json/$ip",
        "http://extreme-ip-lookup.com/json/$ip"
    ];
    
    $results = [];
    foreach ($sources as $url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response) {
            $decoded = json_decode($response, true);
            if ($decoded) $results[] = $decoded;
        }
        usleep(200000); // Delay to avoid rate limiting
    }
    
    return consolidate_geo($results);
}

function consolidate_geo($results) {
    $merged = [];
    $votes = [];
    
    foreach ($results as $result) {
        foreach ($result as $key => $value) {
            if (empty($value) || $key === 'status') continue;
            if (!isset($votes[$key])) $votes[$key] = [];
            if (!isset($votes[$key][$value])) $votes[$key][$value] = 0;
            $votes[$key][$value]++;
        }
    }
    
    foreach ($votes as $key => $values) {
        arsort($values);
        $merged[$key] = array_key_first($values);
    }
    
    $merged['sources_queried'] = count($results);
    return $merged;
}

function display_geo($geo) {
    echo "  \033[1;36m┌─ GEOLOCATION RESULTS\033[0m\n";
    echo "  \033[1;36m├─ IP:\033[0m " . ($geo['query'] ?? 'N/A') . "\n";
    echo "  \033[1;36m├─ Country:\033[0m " . ($geo['country'] ?? 'N/A') . " [" . ($geo['countryCode'] ?? 'N/A') . "]\n";
    echo "  \033[1;36m├─ Region:\033[0m " . ($geo['regionName'] ?? 'N/A') . "\n";
    echo "  \033[1;36m├─ City:\033[0m " . ($geo['city'] ?? 'N/A') . "\n";
    echo "  \033[1;36m├─ Zip:\033[0m " . ($geo['zip'] ?? 'N/A') . "\n";
    echo "  \033[1;36m├─ Coordinates:\033[0m " . ($geo['lat'] ?? '?') . ", " . ($geo['lon'] ?? '?') . "\n";
    echo "  \033[1;36m├─ ISP:\033[0m " . ($geo['isp'] ?? 'N/A') . "\n";
    echo "  \033[1;36m├─ Organization:\033[0m " . ($geo['org'] ?? 'N/A') . "\n";
    echo "  \033[1;36m├─ Timezone:\033[0m " . ($geo['timezone'] ?? 'N/A') . "\n";
    echo "  \033[1;36m└─ Sources Queried:\033[0m " . ($geo['sources_queried'] ?? 0) . "\n";
}
?>
