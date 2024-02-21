<?php
include_once('zip.php');
## Creating a recursive ZIP archive from a directory for streaming
$zip = new ZipFile;
createZip($zip, '/home/nmaltsev/Documents/dev/nm_helsinki/src/');
fileWrite('arch1.zip', $zip->file());