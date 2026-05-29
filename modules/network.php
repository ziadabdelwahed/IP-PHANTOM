<?php
function get_network_data($ip) {
    $data = [];
    
    // BGP/Hurricane Electric
    $ch = curl_init("https://api.bgpview.io/ip/$ip");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $bgp_response = curl_exec($ch);
    curl_close($ch);
    
    if ($bgp_response) {
        $bgp = json_decode($bgp_response, true);
        $bgp_data = $bgp['data'] ?? [];
        
        $data['asn'] = $bgp_data['asn'] ?? 'Unknown';
        $data['asn_name'] = $bgp_data['asn_name'] ?? 'Unknown';
        $data['prefix'] = $bgp_data['prefix'] ?? 'Unknown';
        $data['rir'] = $bgp_data['rir_name'] ?? 'Unknown';
        $data['country'] = $bgp_data['country_code'] ?? '??';
        
        if (isset($bgp_data['prefixes'])) {
            $data['announced_prefixes'] = count($bgp_data['prefixes']);
            $data['prefix_list'] = array_slice($bgp_data['prefixes'], 0, 10);
        }
    }
    
    // WHOIS
    $whois_output = shell_exec("whois $ip 2>/dev/null | head -60");
    $data['whois'] = $whois_output ?: 'WHOIS lookup failed';
    
    // Extract useful whois fields
    $whois_fields = ['netname', 'descr', 'country', 'admin-c', 'tech-c', 'abuse-mailbox', 'org-name', 'mnt-by'];
    $data['whois_parsed'] = [];
    if ($whois_output) {
        foreach ($whois_fields as $field) {
            if (preg_match("/^$field:\s*(.+)/im", $whois_output, $match)) {
                $data['whois_parsed'][$field] = trim($match[1]);
            }
        }
    }
    
    return $data;
}

function display_network($net) {
    echo "  \033[1;36m┌─ NETWORK & ASN INTELLIGENCE\033[0m\n";
    echo "  \033[1;36m├─ ASN:\033[0m AS" . ($net['asn'] ?? '?') . "\n";
    echo "  \033[1;36m├─ ASN Name:\033[0m " . ($net['asn_name'] ?? '?') . "\n";
    echo "  \033[1;36m├─ Prefix:\033[0m " . ($net['prefix'] ?? '?') . "\n";
    echo "  \033[1;36m├─ RIR:\033[0m " . ($net['rir'] ?? '?') . "\n";
    echo "  \033[1;36m├─ Country:\033[0m " . ($net['country'] ?? '?') . "\n";
    
    if (isset($net['whois_parsed']) && !empty($net['whois_parsed'])) {
        echo "  \033[1;36m├─ WHOIS:\033[0m\n";
        foreach ($net['whois_parsed'] as $key => $val) {
            echo "  \033[1;36m│  ├─ $key:\033[0m $val\n";
        }
    }
    
    echo "  \033[1;36m└─\033[0m\n";
}
?>
