<?php
function removeDir(string $source): bool {
    if (empty($source) || file_exists($source) === false) {
        return false;
    }

    if (is_file($source) || is_link($source)) {
        return unlink($source);
    }
    $it = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
    $status = true;
    // $file as SplFileInfo
    foreach ($files as $file) {
        if ($file->isDir() && !$file->isLink()){
            $op_status = rmdir($file->getPathname());
        } else {
            $op_status = unlink($file->getPathname());
        }
        if (!$op_status) $status = false;
    }
    $op_status = rmdir($source);
    if (!$op_status) $status = false;
    return $status;
}
$res = removeDir('./sampleDir');
echo ($res ? 'Ok' : 'Fail'), "\n";