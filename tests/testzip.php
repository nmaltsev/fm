<?php
include_once('../src/mods/zip.php');
## Creating a recursive ZIP archive from a directory for streaming
$zip = new \Core\ZipFile;
\Utils\createZip($zip, './sampleDir/');
\Utils\fileWrite('arch1.zip', $zip->file());
