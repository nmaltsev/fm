<?php
// TODO use generators https://www.php.net/manual/en/language.generators.overview.php
function findAttribute($line, $keyword1, $keyword2) {
    $i = 0;
    $max = strlen($line);
    $key1_len = strlen($keyword1);
    $key2_len = strlen($keyword2);
    $st = $i;

    while($i < $max) {
        // Find the nearest keyword of two
        $pos1 = strpos($line, $keyword1, $i);
        $pos2 = strpos($line, $keyword2, $i);
        
        if ($pos1 === FALSE && $pos2 === FALSE) {
            break;
        }

        if ($pos1 === FALSE) {
            $pos = $pos2;
            $key_len = $key2_len;
        }
        else if ($pos2 === FALSE) {
            $pos = $pos1;
            $key_len = $key1_len;
        } else {
            $pos = $pos1 < $pos2 ? $pos1 : $pos2;
            $key_len = $pos1 < $pos2 ? $key1_len : $key2_len;
        }
        
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
        // TODO use resolvePath
        echo 'A ', $value, "\n";
        $st = $i;
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
        starts with './' or another symbol -> a relative path
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
findAttribute($code, 'src', 'href');
