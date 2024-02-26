<?php
$url = $_REQUEST['url'];
$url = urldecode($url);
define('BASE_URL', getenv('PROXY_BASE_URL') ?: '');
// php -S localhost:9097
$origin = null;

function parseContent(){
    // TODO
}

// make sure we have a valid URL and not file path
if (!preg_match("`https?\://`i", $url)) {
    if (file_exists($url)) {
        $content = file_get_contents($url);
        // $content = preg_replace("/some-smart-regex-here/i", "$1 or $2 smart replaces", $content);
        echo $content;
        die();
    }
    die('Not a URL');
}
// TODO proxy request
$parts = explode("/", $url, 4);
$origin = isset($parts[2]) ? $parts[2] : null; 

$ch = curl_init($url);
$uagent = getenv('PROXY_USER_AGENT') ?: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 Edg/122.0.0.0';
$options = [
    CURLOPT_RETURNTRANSFER => true,     // return web page
    CURLOPT_HEADER         => true,     // return headers
    CURLOPT_FOLLOWLOCATION => true,     // follow redirects
    CURLOPT_ENCODING       => "",       // handle all encodings
    CURLOPT_AUTOREFERER    => true,     // set referer on redirect
    CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
    CURLOPT_TIMEOUT        => 120,      // timeout on response
    CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    CURLOPT_USERAGENT      => $uagent, 
];
curl_setopt_array($ch, $options);

// Execute the cURL request
$response = curl_exec($ch);
// Get the response headers
$responseHeaders = curl_getinfo($ch);
// Close the cURL session
curl_close($ch);
// Forward the response headers to the client
foreach ($responseHeaders as $headerName => $headerValue) {
    header($headerName.': '.$headerValue);
}
// TODO is at an html doc?
// Forward the response body to the client
echo $response;