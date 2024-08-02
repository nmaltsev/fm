<?php
// Don't needed in urldecoding
$url = $_REQUEST['url'];

define('BASE_URL', getenv('PROXY_BASE_URL') ?: '');
// php -S localhost:9097
$origin = null;

function parseContent(){
    // TODO
}

// make sure we have a valid URL and not file path
if (!preg_match("`https?\://`i", $url)) {
    if (file_exists($url)) {
        $mime_type = get_mime_type($url);
        $is_html = strpos($mime_type, 'text/html') === 0 /*|| endsWithBeforePHP8($path, '.html')*/;
        header('Content-Type: '.$mime_type);
        $content = file_get_contents($url);
        //// $content = preg_replace("/some-smart-regex-here/i", "$1 or $2 smart replaces", $content);
        if ($is_html) echo '<!-- PRX2 -->';
        echo $content;
        // TODO make it manageable by a query property
        if ($is_html) echo '<script>
function normalisePath(path, refererPath){
    if (path.startsWith("/")) {
        return path;
    }
    const segments = refererPath.split("/");
    const dirSegments = segments.slice(0, segments.length - 1);
    if (path.startsWith("./")) {
        dirSegments.push(path.substring(2));
        return dirSegments.join("/");
    }
    if (!path.startsWith("?")) {
        dirSegments.push(path);
        return dirSegments.join("/");
    } else {
        // TODO replace query
    }
}
window.addEventListener("error", function(e) {
    if (e.target.__fixed) return;
    const tagName = e.target.tagName;
    if (!tagName) {
        return;
    }
    const proxyLink = new URL(location.href);
    const resourcePath = proxyLink.searchParams.get("url");
    
    if (tagName.toLowerCase() === "img") {
        const originalLink = e.target.getAttribute("src");
        //// const nextLink = new URL(originalLink, location.href);
        const absResourcePath = normalisePath(originalLink,resourcePath);
        console.log("Image loading error `%s` %s,%s,%s", e.target.src, originalLink, resourcePath, absResourcePath);
        e.target.src="?url="+encodeURIComponent(absResourcePath);
        e.target.__fixed=1;
    }
    else if (tagName.toLowerCase() === "link") { // Does not work in FF, works buggy in Edge
        const originalLink = e.target.getAttribute("href");
        const absResourcePath = normalisePath(originalLink,resourcePath);
        console.log("Style loading error `%s` %s,%s,%s", e.target.href, originalLink, resourcePath, absResourcePath);
        e.target.href="?url="+encodeURIComponent(absResourcePath);
        // e.target.setAttribute("href","?url="+encodeURIComponent(absResourcePath));
        e.target.__fixed=1;
    } 
    else {
        console.log("Resource error");
        console.dir(e);
    }
}, true);</script>';
        die();
    } else {
        // For debugging
        echo '<pre>', $url, '<pre>';
    }
    die('Not a URL');
}
// TODO proxy request
$parts = explode('/', $url, 4);
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

function endsWithBeforePHP8($haystack, $needle) {
    $length = strlen($needle);
    if(!$length) {
        return true;
    }
    return substr($haystack, -$length) === $needle;
}
function get_mime_type($path) {
    $len4=strtolower(substr($path, -4));
    $len5=strtolower(substr($path, -5));
    if ($len5==='.html'||$len4==='.htm') {
        return 'text/html';
    }
    else if ($len4==='.css') {
        return 'text/css';
    } else {
        $finfo = finfo_open(FILEINFO_MIME);
        $mime_type = finfo_file($finfo, $path);
        return $mime_type;
    }
}