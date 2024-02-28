<?php
function findAttribute($line, $keyword) {
    $i = 0;
    $max = strlen($line);
    $key_len = strlen($keyword);
    $st = $i;

    while($i < $max) {
        // TODO find the nearest keyword of two
        $pos = strpos($line, $keyword, $i);
        if ($pos === FALSE) {
            break;
        } else {
            $i = $pos + $key_len;
            while (($line[$i] === ' ' || $line[$i] === '=') && $i < $max) $i++;
            $start = $i++;
            $next = $line[$start];
            if ($next === '"' || $next === '\'') $start++;
            
            while($i < $max && (
                $next === '"' 
                    ? ($line[$i] !== '"')
                    : ($next === '\''
                        ? $line[$i] !== '\''
                        : ($line[$i] !== '/' && $line[$i] !== ' ')
                    )
            )) $i++;

            $value = substr($line, $start, $i - $start);

            echo 'F ', substr($line, $st, $start - $st),  "\n";
            echo 'A ', $value, "\n";
            $st = $i;
        }
    }
    if ($i < $max) {
        echo 'Final ', substr($line, $i),  "\n";
    }
}

function resolvePath($path, $referer, $origin=null) {
    // for local resources, $referer an absolute path of a parent document, $origin = null
    // //domain.name/abc
    // https?://
    // /abc
    // abc | ./abc
    /*
        start with '//' or 'http' -> with a domain name
        starts with '/' -> an absolute path
        starts with './' or aother symbol -> a relative path
    */
}

$code = '
<!DOCTYPE html>
<html>
    <head>
        <link rel="stylesheet" type="text.css" href="global.css">
    </head>
    <body>
        <a href="page1.html">Link1</a>
        <img src = test1.jpg/>
        <img src=test2.jpg />
        <iframe src = "https://google.com"></iframe>
        <script src  =  "./main.js"></script>
        <script src=\'//main.js\'></script>
    </body>
</html>
';
findAttribute($code, 'src');