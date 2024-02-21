<?php
include_once('zip.lib.php');

function getFileContent($path) {
    $fh = fopen($path, 'rb');
    if (!$fh) {
        die('Error: Unable to open file.');
    }
    $size = filesize($path);
    $content = fread($fh, $size);
    fclose($fh);
    return $content;
}
function fileWrite($path, $data) {
    $fh = fopen($path, 'wb');
    if (!$fh) {
        die('Error: Unable to write to file.');
    }
    fwrite($fh, $data);
    fclose($fh);
}
function createZip($zipArchive, $folder, $dir=''){
    if (is_dir($folder)) {
        if ($f = opendir($folder)) {
            $resCount = 0;
            while (($file = readdir($f)) !== false) {
                $resource = $folder.$file;
                if ($file == '' || $file == '.' || $file == '..') {
                    continue;
                }
                if (is_file($resource)) {
                    $content = getFileContent($resource);
                    $zipArchive->addFile($content, $dir.$file);
                    $resCount++;
                }
                else if (is_dir($resource)) {
                    createZip($zipArchive, $resource.'/', $dir.$file.'/');
                    $resCount++;
                }
            }
            // TODO does not work properly
            // if ($resCount < 1) {
            //     $zipArchive->addEmpty($dir.$file);
            // }
            closedir($f);
        } else {
            exit("Unable to open directory " . $folder);
        }
    } else {
        exit($folder . " is not a directory.");
    }
}
