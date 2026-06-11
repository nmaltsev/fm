<?php
// Don't needed in urldecoding
$url = $_REQUEST['url'];

define('BASE_URL', getenv('PROXY_BASE_URL') ?: '');
// php -S localhost:9097
$origin = null;
$auxilary_script = '<script>
function unhash(url) {
    const pos = url.lastIndexOf("#");
    if (pos != -1) {
        return [url.substring(0, pos), url.substring(pos)]
    } else {
        return [url]
    }
}
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
    else if (tagName.toLowerCase() === "use") {
        const [originalLink, hash] = unhash(e.target.getAttribute("xlink:href"));
        const absResourcePath = normalisePath(originalLink,resourcePath);
        console.log("Style loading error `%s` %s,%s", originalLink, resourcePath, absResourcePath);
        e.target.setAttribute("xlink:href", "?url=" + encodeURIComponent(absResourcePath) + (hash||""));
        e.target.__fixed=1;
    } 
    else {
        console.log("Resource error");
        console.dir(e);
    }
}, true);</script>';

// make sure we have a valid URL and not file path
if (!preg_match("`https?\://`i", $url)) {
    if (file_exists($url)) {
        $mime_type = get_mime_type($url);
        $is_html = strpos($mime_type, 'text/html') === 0 /*|| endsWithBeforePHP8($path, '.html')*/;
        header('Content-Type: '.$mime_type);
        $content = file_get_contents($url);
        //// $content = preg_replace("/some-smart-regex-here/i", "$1 or $2 smart replaces", $content);
        if (true) {
            echo parse_file_content($url, $content);
        } else {
            echo $content;
            // TODO make it manageable by a query property
            if ($is_html) echo $auxilary_script;
        }
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

function parse_file_content($url, $html) {
    $pattern = '~(?:src|href|xlink:href)\s*=\s*(?:"([^"]*?)"|\'([^\']*?)\'|([^\s>]*))~i';

    return preg_replace_callback($pattern, function ($m) use ($url) {
        $attrValue = $m[1] !== ''
            ? $m[1]
            : ($m[2] !== '' ? $m[2] : $m[3]);

        // Ignore empty values and special schemes
        if (
            $attrValue === '' ||
            preg_match('~^(?:data:|javascript:|mailto:|tel:)~i', $attrValue)
        ) {
            return $m[0];
        }

        // Split into path and hash
        $hashPos = strpos($attrValue, '#');
        if ($hashPos !== false) {
            $path = substr($attrValue, 0, $hashPos);
            $hash = substr($attrValue, $hashPos); // includes #
        } else {
            $path = $attrValue;
            $hash = '';
        }

        $absolute = absolute_path($url, $path);

        $newUrl = '/proxy.php?url=' . rawurlencode($absolute) . $hash;

        // Preserve original quote style
        if ($m[1] !== '') {
            return str_replace($m[1], $newUrl, $m[0]);
        }

        if ($m[2] !== '') {
            return str_replace($m[2], $newUrl, $m[0]);
        }

        return preg_replace(
            '~=\s*[^\s>]*$~',
            '="' . $newUrl . '"',
            $m[0]
        );
    }, $html);
}

/**
 * Resolve a relative URL against a base URL.
 */
function absolute_url($baseUrl, $relativeUrl)
{
    if ($relativeUrl === '') {
        return $baseUrl;
    }

    // Already absolute
    if (preg_match('~^[a-z][a-z0-9+\-.]*://~i', $relativeUrl)) {
        return $relativeUrl;
    }

    $base = parse_url($baseUrl);

    $scheme = $base['scheme'] ?? 'http';
    $host   = $base['host'] ?? '';
    $port   = isset($base['port']) ? ':' . $base['port'] : '';

    // Protocol-relative URL: //cdn.com/file.js
    if (strpos($relativeUrl, '//') === 0) {
        return $scheme . ':' . $relativeUrl;
    }

    // Root-relative URL
    if (strpos($relativeUrl, '/') === 0) {
        return $scheme . '://' . $host . $port . $relativeUrl;
    }

    $basePath = $base['path'] ?? '/';

    // If base URL points to a file, use its directory
    $dir = preg_replace('~/[^/]*$~', '/', $basePath);

    $path = $dir . $relativeUrl;

    // Normalize ./ and ../
    $segments = [];
    foreach (explode('/', $path) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            array_pop($segments);
            continue;
        }
        $segments[] = $segment;
    }

    return $scheme . '://' . $host . $port . '/' . implode('/', $segments);
}

function absolute_path(string $base, string $rel): string
{
    if ($rel === '' || $rel[0] === '/') {
        return $rel;
    }

    $dir = is_dir($base)
        ? $base
        : dirname($base);

    $path = $dir . '/' . $rel;

    $parts = [];

    foreach (explode('/', $path) as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }

        if ($part === '..') {
            array_pop($parts);
            continue;
        }

        $parts[] = $part;
    }

    return '/' . implode('/', $parts);
}