<?php
function get_dns_data($ip) {
    $data = [];
    
    // Reverse DNS
    $data['reverse_dns'] = gethostbyaddr($ip);
    if ($data['reverse_dns'] === $ip) $data['reverse_dns'] = "No PTR record";
    
    // DNSBL checks
    $reversed = implode('.', array_reverse(explode('.', $ip)));
    $zones = [
        'zen.spamhaus.org',
        'bl.spamcop.net',
        'dnsbl.sorbs.net',
        'b.barracudacentral.org',
        'xbl.spamhaus.org',
        'pbl.spamhaus.org',
        'sbl.spamhaus.org',
        'cbl.abuseat.org',
        'dnsbl-1.uceprotect.net',
        'ips.backscatterer.org',
        'truncate.gbudb.net',
        'db.wpbl.info',
        'spam.dnsbl.sorbs.net',
        'dnsbl.dronebl.org',
        'rbl.interserver.net'
    ];
    
    $data['dnsbl_listed'] = [];
    foreach ($zones as $zone) {
        $check = "$reversed.$zone";
        $result = gethostbyname($check);
        if ($result !== $check && filter_var($result, FILTER_VALIDATE_IP)) {
            $data['dnsbl_listed'][] = $zone;
        }
    }
    
    // Get hostnames pointing to this IP (if domain known)
    $hostname = $data['reverse_dns'];
    if ($hostname !== "No PTR record") {
        $data['forward_dns'] = gethostbynamel($hostname) ?: [];
    }
    
    return $data;
}

function display_dns($dns) {
    echo "  \033[1;36m┌─ DNS INTELLIGENCE\033[0m\n";
    echo "  \033[1;36m├─ Reverse DNS:\033[0m " . $dns['reverse_dns'] . "\n";
    echo "  \033[1;36m├─ DNSBL Listed:\033[0m " . count($dns['dnsbl_listed']) . " blacklists\n";
    foreach ($dns['dnsbl_listed'] as $bl) {
        echo "  \033[1;31m│  ⚠ LISTED ON: $bl\033[0m\n";
    }
    if (!empty($dns['forward_dns'])) {
        echo "  \033[1;36m├─ Forward DNS:\033[0m\n";
        foreach ($dns['forward_dns'] as $name) {
            echo "  \033[1;36m│  └─\033[0m $name\n";
        }
    }
    echo "  \033[1;36m└─\033[0m\n";
}
?>
