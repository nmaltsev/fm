<?php
namespace Utils;
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
            $file_data = $zipArchive->getModTime($folder);
            $zipArchive->addDir($dir, $file_data['file_mtime'], $file_data['file_mdate']);
            while (($file = readdir($f)) !== false) {
                $resource = $folder.$file;
                if ($file == '' || $file == '.' || $file == '..') {
                    continue;
                }
                if (is_file($resource)) {
                    $content = getFileContent($resource);
                    $date = \Core\getSaveModTime($dir.$file);
                    $zipArchive->addFile($content, $dir.$file, $date);
                } else if (is_dir($resource)) {
                    $file_data = $zipArchive->getModTime($resource);
                    createZip($zipArchive, $resource.'/', $dir.$file.'/');
                    $zipArchive->addDir($resource, $file_data['file_mtime'], $file_data['file_mdate']);
                }
            }
            closedir($f);
        } else {
            exit("Unable to open directory " . $folder);
        }
    } else {
        exit($folder . " is not a directory.");
    }
}
