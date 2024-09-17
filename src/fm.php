<?php
define('VERSION','27.2024.09.17');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function getfrom($array, $key, $default) {return isset($array[$key]) ? $array[$key] : $default;}

$path = getfrom($_GET, 'path', '');
$action = getfrom($_GET, 'action', '');
// TODO do not show the size for directories!

function layoutHeader() {
    return '<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="utf-8"/>
        <meta name="robots" content="noindex, nofollow">
        <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
        <link rel="icon" href="./favicon.svg" type="image/svg+xml" />
        <style>
:root{--blue1:#0071ce;--dialog-width:480px;--fucsia:#b31edd;}
body{margin:0;width:100vw;height:100vh;font:13px/15px Arial;}
fieldset{border:none;}
fieldset>legend{float:left;display:block;width:100%;}
fieldset,fieldset>legend{padding:0;margin:0;}
.v-btn,button{cursor:pointer;border:none;padding:.5rem 1rem;transition:background-color .2s,color .2s,box-shadow .2s;}
._btn-a{border:1px solid #fff;color:#fff;background:transparent;}
._btn-a:hover,
._btn-a:focus{text-decoration:none;background:#fff;color:#333;}
._btn-a:active{outline:1px solid #fff;outline-offset:1px;}
button+button,.v-btn+.v-btn,button+.v-btn,button+a{margin-left:.8rem;}
.__b-primary{background:var(--blue1);color:#fff;}
.__b-primary:hover,.__b-primary:focus{background:var(--fucsia);}
.__b-primary:disabled,.__b-primary:disabled:hover,.__b-primary:disabled:focus{background:#ccc;}
.__b-secondary1{background:#fff;outline: 1px solid var(--blue1);color: var(--blue1);}
textarea{outline:none;background:#f7f8f9;color:#222;}
textarea:focus{outline:2px solid #b3b2be;outline-offset:-1px;}
input{color:#222;background:#f7f8f9;border:1px solid #ece7e7;padding:.5rem;font-size:1rem;line-height:1.2rem;}
input:focus{border-color:#b3b2be;}
.target-input{display:block;width:100%;margin:1rem 0;box-sizing:border-box;}
.wrapper{display:flex;width:100%;height:100%;flex-direction:column;padding:0;margin:0;overflow:auto;}
.centered,.__middle{justify-content:center;align-items:center;text-align:center;}
.files{list-style:none;margin:0;display:grid;grid-gap:0;white-space:nowrap;flex:1 1 auto;overflow:auto;padding:0 0 .5rem 0;
    grid-auto-rows:min-content;font-size:.9rem;line-height:1.2rem;
    grid-template-columns:repeat(7, min-content) auto;}
.files>li{display:contents;}
.files>li:not(:first-child)>a:first-child{overflow:hidden;max-width:50vw;text-overflow:ellipsis;position:sticky;left:0;background:#fff;}
.files>li:hover>span, .files>li:hover>i, .files>li:hover>a{background:#ddf4ffd6;}
.files>li > span, .files>li > a{padding-left:.5rem;}
.files .skip-columns{grid-column:span 7;}
.stick2top{position:sticky;background:#fff;top:0;}
.files-panel{color:#fff;padding:.5rem;font-size:1rem;line-height:1.2rem;align-items:center;}
.files-panel>a{margin:0 0 0 1rem;}
.fm_ellipsis{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
a{text-decoration:none;color:#0049fd;}
a:hover,a:focus,a:active{text-decoration:underline;}
a.resource:visited{color:var(--fucsia);}
.d-flex,.f-col,.f-row{display:flex;}
.d-flex.__column,.f-col{flex-direction:column;}
.f-row{align-items:baseline;}
.f-min{flex:0 0 auto;}
.f-max{flex:1 1 auto;}
.f-aitems-cntr{align-items:center;}
.f-cjustify-spbtw{justify-content:space-between;}
.f-cjustify-cntr{justify-content:center;}
.cpanel{background:var(--blue1);}
.cpanel a{color:#fff;}
.f-wrapper{display:flex;overflow:hidden;}
.__primary-transparent {
	background-color: #fff;
	color: #000;
}
.__primary-transparent:hover,
.__primary-transparent:focus {
	background-color: #f1f1f1;
}
.mb1{margin-bottom:1rem;}
.dialog{width:var(--dialog-width);}
        </style>
    </head>
    <body>';
}
function layoutTail() {
    return '</dir></body></html>';
}

if ($action == 'dir') {
    function endsWithBeforePHP8($haystack, $needle) {
        $length = strlen($needle);
        if(!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
    
    if (!is_readable($path)) {
        $referer = $_SERVER['HTTP_REFERER'];
        $redirect = '?action=error&message=' . urlencode('Read permission denied to '.$path) . '&path='.urlencode('?action=dir&path=' . urlencode($referer));
        header('Location: '.$redirect);
        return;
    }
    $files = is_dir($path) ? @scandir($path) : false;

    echo layoutHeader();
    echo '<dir class="wrapper">';
    
    if ($files !== false) { // Show the list of files
        echo '<header class="files-panel f-row f-min cpanel">', '<span class="fm_ellipsis">', htmlspecialchars($path), '</span>';
        $newlink = '?action=new_form&path='.urlencode($path).'&redirect='.urlencode('?action=dir&path='.urlencode($path));
        echo '<a class="v-btn _btn-a" href="',$newlink,'">New</a>';
        $newlink = '?action=upload_form&path='.urlencode($path).'&redirect='.urlencode('?action=dir&path='.urlencode($path));
        echo '<a class="v-btn _btn-a" href="',$newlink,'">Upload</a>', '</header>';
        echo '<ol class="files">';
        if ($path !== '/') {
            echo '<li><a class="stick2top" href="','?action=dir&path='.urlencode(dirname($path)),'">..</a>',
            '<span class="skip-columns stick2top"></span></li>';
        }
            
        foreach($files as $i => $file) {
            if ($file == '.' || $file == '..') continue;
            $next_path = $path . ($path !== '/' ? '/' : '') . $file;
            $nav_link = '?action=dir&path='.urlencode($next_path);
            echo '<li>';
            echo '<a href="',$nav_link,'" class="resource ',(is_dir($next_path) ? '__dir' : '__file'),'">',$file,'</a>';
            // change mode
            $nav_link = '?action=perm&path='.urlencode($next_path).'&redirect='.urlencode('?action=dir&path='.urlencode($path));
            $octal_perm = substr(sprintf('%o', @fileperms($next_path)), -4);
            // TODO use the folowing link to get a type of resources: file, dir, symlink and etc
            // TODO use settings to choose the prefered permission format `-rw-r--r--` or `0777` 
            // TODO show `-rw-r--r--` in a tooltip
            // https://phpdoctest.github.io/en/function.fileperms.html
            echo '<a href="',$nav_link,'">',$octal_perm,'</a>';
            $file_size = @filesize($next_path);
            echo '<span title="', $file_size, ' bytes">', intword($file_size), '</span>';

            // TODO `$diff = time() - filemtime($file);`
            $mod_time = @filemtime($next_path);
            echo '<span data-time="', $mod_time, '">', date('Y-m-d H:i:s', $mod_time), '</span>';

            // delete file
            $nav_link = '?action=delete&path='.urlencode($next_path).'&redirect='.urlencode('?action=dir&path='.urlencode($path));
            echo '<a href="',$nav_link,'" title="Delete file">Del</a>';

            // save as file
            $nav_link = '?action=saveas_form&path='.urlencode($next_path).'&redirect='.urlencode('?action=dir&path='.urlencode($path));
            echo '<a href="',$nav_link,'" title="Save as">Save as</a>';

            // download resource
            $nav_link = '?action=download&path='.urlencode($next_path).'&redirect='.urlencode('?action=dir&path='.urlencode($path));
            echo '<a href="',$nav_link,'" title="Download">Download</a><i></i></li>';
        }
        echo '</ol>';
    } else {
        $back_link = '?action=dir&path='.urlencode(dirname($path));
        
        $finfo = finfo_open(FILEINFO_MIME);
        $mime_type = finfo_file($finfo, $path);
        echo '<header class="files-panel f-min f-row cpanel">', 
            '<span class="fm_ellipsis">', htmlspecialchars($path), ' (', $mime_type, ')','</span>',
            // Enable the edit button for SVG images
            (strpos($mime_type, 'image/svg') === 0 ? '<a class="v-btn _btn-a" href="?action=dir&t&path='.urlencode($path).'">Edit</a>': ''),
            '<a class="v-btn _btn-a" href="', $back_link, '">Back</a>',
        '</header>';

        // Skip viewers in case of `&t`
        if (!isset($_GET['t'])) {
            if (strpos($mime_type, 'video/') === 0) {
                $next_path = '?action=media&path='.urlencode($path);
                echo '<div class="f-wrapper">',
                        '<video playsinline loop autoplay preload=auto controls class="f-max" style="background:#111;">',
                            '<source src="',$next_path,'" type="',$mime_type,'"/>',
                        '</video>',
                    '</div>';
                return;
            }
            if (strpos($mime_type, 'application/pdf') === 0) {
                $next_path = '?action=forward2&path='.urlencode($path);
                echo '<iframe class="f-max" src="',$next_path,'"/>';
                return;
            }
            if (strpos($mime_type, 'audio/') === 0) {
                $next_path = '?action=forward2&path='.urlencode($path);
                echo '<div class="f-wrapper f-max centered"><audio controls autoplay style="width:100%">',
                    '<source src="',$next_path,'" type="',$mime_type,'"/>',
                '</audio></div>';
                return;
            }
            if (strpos($mime_type, 'image/') === 0)  {
                $next_path = '?action=forward2&path='.urlencode($path);
                echo '',
            '<style>
            .imageviewer_form{width:100%;height:100%;overflow:hidden;position:relative;text-align:center;background:#ccc;}
            .imageviewer{display:block;width:100%;height:100%;overflow:auto;}
            .sliderbar{display:inline-block;position:absolute;top:0;width:100%;left:0;text-align:center;opacity:0;}
            .sliderbar>a,
            .sliderbar>label,
            .sliderbar>input{display:inline-block;vertical-align:middle;margin:0 0 0 .5rem;}
            .sliderbar:hover{opacity:1;background-color:#ffffff78;}
            .img_wrapper{width:100%;height:100%;}
            .img_wrapper::after{content:"";display:inline-block;vertical-align:middle;width:0;height:100%;}
            .img_wrapper>img{display:inline-block;vertical-align:middle;max-width:100%;max-height:100%;background:#e5e5e5;}
            </style>',
            '<form class="imageviewer_form" onchange="imageViewerChange(event)">
                <div class="imageviewer"><div class="img_wrapper"><img src="',$next_path,'"/></div></div>
                <div class="sliderbar">
                    <label><input type="checkbox" checked name="mode1"/></label>
                    <input name="mode2" type="range" step="0.1" value="1" min="0.1" max="2" />
                </div>
            </form>',
    '<script>
    // state:
    //  scale: undefined - maxWidth:100%
    //  scale: 0.1 .. 2 - maxWidth: max-content
    function imageViewerChange(event) {
        const $form = event.target.form;
        const elements = $form.elements;
        const $img = $form.querySelector("img");
        if (!$img) return;
        let scale = undefined;

        if (event.target.name === "mode1") {
            if (elements.mode1.checked) {
                scale = undefined;
            } else {
                scale = parseFloat(elements.mode2.value);
            }
        } else if (event.target.name === "mode2") {
            scale = parseFloat(elements.mode2.value);
        }

        if (scale === undefined) {
            elements.mode1.checked = true;
            $img.style.height = "";
            $img.style.maxHeight = "";
            $img.style.maxWidth = "";
        } else {
            elements.mode1.checked = false;
            // SVG images do not have naturalHeight
            const imageHeight = $img.naturalHeight || $form.clientHeight;
            $img.style.height = scale * imageHeight + "px";
            $img.style.maxHeight = "max-content";
            $img.style.maxWidth = "unset";
        }
    }
    </script>';
                return;
            }
        }
        $handler='?action=update';
        echo '<form class="f-max" method="POST" action="', $handler, '" style="margin:0;display:flex;flex-direction:column;">',
            '<input type="hidden" name="path" value="',htmlspecialchars($path),'"/>',
            '<input type="hidden" name="redirect" value="',htmlspecialchars($back_link),'"/>',
        '<textarea class="f-max" name="content" style="resize:none;box-sizing:border-box;padding:.3rem;border:none;" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">';
        //  TODO handle exception
        $fh = fopen($path, 'r');
        while ($line = fgets($fh)) {
            echo htmlspecialchars($line);
        }
        fclose($fh);
        echo '</textarea>';
        echo '<div class="f-min f-row f-aitems-cntr cpanel" style="padding:.5rem;">';
        echo '<button type="submit" class="__primary-transparent">Submit</button>';
        echo '<button type="reset" class="_btn-a">Reset</button>';
        if (strpos($mime_type, 'text/html') === 0 || endsWithBeforePHP8($path, '.html')) {
            echo '<a href="','proxy.php?url=',urlencode($path),'" target="_blank">View via proxy</a>';
            echo '<!--', $path, '-->';
            echo '<!--', urlencode($path), '-->';
        }
        echo '</div>';
        echo '</form>';
    }
    echo layoutTail();
}
else if ($action === 'perm') {
    $redirect = $_GET['redirect'];
    chmod($path, 0777);
    header('Location: '.$redirect);
}

else if ($action === 'forward') {
    if (!preg_match('/^[^.][-a-z0-9_.,\s\/@\(\)\[\];\']+$/i', $path)) {
        die('Invalid file name! ['.$path.']');
    }
    if (!file_exists($path)) {
        http_response_code(404);
        die();
    }
    $finfo = finfo_open(FILEINFO_MIME);
    $mime_type = fix_mime(finfo_file($finfo, $path));
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($path));
        
    $writableStream = fopen('php://output', 'wb');
    if ($readableStream = fopen($path, 'rb')) {
        stream_copy_to_stream($readableStream, $writableStream);
        ob_flush();
        flush();
    } else die('Error - can not open file.');
    die();
}
else if ($action === 'forward2') {
    if (!preg_match('/^[^.][-a-z0-9_.,\s\/@\(\)\[\];\']+$/i', $path)) {
        die('Invalid file name! ['.$path.']');
    }
    if (!file_exists($path)) {
        http_response_code(404);
        die();
    }
    
    $finfo = finfo_open(FILEINFO_MIME);
    $mime_type = fix_mime(finfo_file($finfo, $path));
    header('Content-Type: ' . $mime_type);
    header('Content-Length: ' . filesize($path));
        
    $maxRead = 1 * 1024 * 1024; // 1MB
    // Open a file in read mode.
    $fh = fopen($path, 'r');
    // Run this until we have read the whole file.
    // feof (eof means "end of file") returns `true` when the handler
    // has reached the end of file.
    while (!feof($fh)) {
        // Read and output the next chunk.
        echo fread($fh, $maxRead);
        // Flush the output buffer to free memory.
        ob_flush();
    }
    die();
}
else if ($action==='media') {
    if (!preg_match('/^[^.][-a-z0-9_.,\s\/@\(\)\[\];\']+$/i', $path)) {
        die('Invalid file name! ['.$path.']');
    }
    if (!file_exists($path)) {
        http_response_code(404);
        die();
    }
    require_once('./mods/video.php');
    stream_video($path);
    die();
}
else if ($action === 'download') {
    // if (!preg_match('/^[^.][-a-z0-9_.,\s\/@\(\)\[\]]+$/i', $path)) {
    //     die('Invalid file name! ['.$path.']');
    // }
    if (!file_exists($path)) {
        http_response_code(404);
        die();
    }
    if (is_dir($path)) {
        // TODO check PHP version > 5.5
        // if (true) {
        //     return;
        // }
        require_once('./mods/zip.php');
        // TODO move in a function from mods/zip.php
        // stream_zip($path);
        if (class_exists(Core\ZipFile::class)) {
            // Create an archive from a directory
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($path).'.zip"');
            $zip = new Core\ZipFile;
            Utils\createZip($zip, $path.'/');
            file_put_contents("php://output", $zip->file());
        }
        die();
    }
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($path) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($path));
    flush(); // Flush system output buffer
    readfile($path);
    die();
}
else if ($action == 'delete') {
    function removeDir($source) {
        if (empty($source) || file_exists($source) === false) {
            return false;
        }
    
        if (is_file($source) || is_link($source)) {
            return @unlink($source);
        }
        $it = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        $status = true;
        // $file as SplFileInfo
        foreach ($files as $file) {
            if ($file->isDir() && !$file->isLink()){
                $op_status = @rmdir($file->getPathname());
            } else {
                $op_status = @unlink($file->getPathname());
            }
            if (!$op_status) $status = false;
        }
        $op_status = @rmdir($source);
        if (!$op_status) $status = false;
        return $status;
    }

    $is_success = removeDir($path);
    
    if (!$is_success) {
        $e = error_get_last();
        $message = 'An error occurred while deleting '.$path.'. Message: '.$e['message'].' File: '.$e['file'].' Line: '.$e['line'];
        $redirect = '?action=error&message=' . urlencode($message) . '&path='.urlencode('?action=dir&path='.urlencode(dirname($path)));
    } else {
        $redirect = $_GET['redirect'];
    }
    
    header('Location: '.$redirect);
}
else if ($action == 'update') {
    if(isset($_POST['path']) && isset($_POST['redirect'])) {
        // echo 'Path: '.$_POST['path'].' Redirect: '. $_POST['redirect'] . '<br/>';
        // print_r($_POST['content']);
        // TODO check presents of `..`
        $ppath = $_POST['path'];
        if (@file_put_contents($ppath, $_POST['content']) !== false) {
            $redirect = $_POST['redirect'];
        } else {
            $e = error_get_last();
            $message = 'An error occurred while rewriting '.$ppath.'. Message: '.$e['message'].' File: '.$e['file'].' Line: '.$e['line'];
            $redirect = '?action=error&message=' . urlencode($message) . '&path='.urlencode('?action=dir&path='.urlencode(dirname($ppath)));
        }
        header('Location: '.$redirect);
    }
    else {
        echo 'Path & redirect are not defined!';
    }
}
else if ($action == 'error') {
    $message = urldecode($_GET['message']);
    echo layoutHeader();
    echo '<dir class="wrapper __middle">';
    echo '<dir>';
    echo '<h3>', htmlspecialchars($message), '</h3>';
    echo '<a href="', $path, '">Back</a>';
    echo '</dir>';
    echo layoutTail();
}
else if ($action == 'init') {
    echo layoutHeader();
    echo '<dir class="wrapper">';
    echo '<h5>',VERSION,'</h5>';
    echo '<a href="?action=info">Info</a><br>';
    $currentPath = urlencode(realpath(dirname(__FILE__)));
    echo '<a href="?action=dir&path='.$currentPath.'">Files</a><br>';
    echo layoutTail();
}
else if ($action == 'saveas_form') {
    $redirect = $_GET['redirect'];
    echo layoutHeader();
    echo '<dir class="wrapper __middle">',
            '<form method="POST" action="?action=saveas_handler" class="dialog">',
                '<p>Save file <b>',htmlspecialchars($path),'</b> as:</p>',
                '<input type="hidden" name="redirect" value="',$redirect,'"/>',
                '<input type="hidden" name="path" value="',urlencode($path),'"/>',
                '<input class="target-input" type="text" name="next_path" value="',htmlspecialchars($path),'" required autofocus/>',
                '<button type="submit" class="__b-primary">Save</button>',
                '<button type="reset">Reset</button>',
                '<a href="',$redirect,'">Cancel</a>',
        '</form></div>';
    echo layoutTail();
}
else if ($action == 'new_form') {
    $redirect = $_GET['redirect'];
    echo layoutHeader();
    echo '<dir class="wrapper __middle">',
            '<form method="POST" action="?action=new_handler" class="dialog">',
                '<p>Create new: ',
                    '<label><input type="radio" name="type" value="file"> <b>file</b></label>',
                    '<label><input type="radio" name="type" value="dir" checked> <b>directory</b></label>',
                '</p>',
                '<input type="hidden" name="redirect" value="', $redirect,'"/>',
                '<input class="target-input" type="text" name="path" value="', htmlspecialchars($path), '/" required autofocus/>',
                '<button type="submit" class="__b-primary">Create</button>',
                '<button type="reset" class="">Reset</button>',
                '<a href="', $redirect, '">Cancel</a>',
        '</form></div>';
    echo layoutTail();
}
else if ($action == 'upload_form') {
    $redirect = $_GET['redirect'];
    echo layoutHeader();
    echo '<dir class="wrapper __middle">',
        '<form onSubmit="uploadHandler(event)" method="POST" class="dialog mb1">',
            '<fieldset id="uploader">',
                '<input type="hidden" name="redirect" value="',$redirect,'"/>',
                '<input type="hidden" name="path" value="',$path,'"/>',
                '<label><h4>Select a File to Upload:</h4>',
                    '<input type="file" name="file" id="file" class="target-input"/>',
                '</label>',
                '<button type="submit" class="__b-primary">Upload</button>',
                '<button type="reset">Reset</button>',
                '<a href="', $redirect, '">Back</a>',
            '</fieldset>',
        '</form>',
        '<div id="progres"></div>';
    echo '<script>';
echo "
const chunk_size = 512*1024; /* 1048570 1MB chunk size*/
function* readFile(file) {
    const filesize = file.size;
    const filename = file.name;
    let pos = 0, chunk;
    while(pos < filesize) {
        chunk = file.slice(pos, pos+chunk_size);
        pos += chunk_size;
        const formData = new FormData();
        formData.append('chunk', chunk);
        formData.append('filename', filename);
        yield [formData, pos];
    }
};
async function uploadHandler(event){
    event.preventDefault();
    event.stopPropagation();
    const file = document.getElementById('file').files[0];
    const uploaderFieldset = document.getElementById('uploader');
    const basepath = document.querySelector('input[name=path]').value;
    const progresNode = document.getElementById('progres');
    
    uploaderFieldset.setAttribute('disabled', true);
    progresNode.textContent = '0 / ' + file.size;

    for (const [chunk, bytes] of readFile(file)) { 
        console.log('Chunk:')
        console.dir(chunk);
        
        const response = await fetch('?action=uploadaction', {method:'POST',body:chunk})
            .then(function(response){
                if (response.status >= 400 && response.status < 600) {
                    throw new Error('Bad response from server');
                }
                return response;
            })
            .catch((error) => {
                console.log('Err: ', error)
            });
        console.log('Progress pos: %s/%s', bytes, file.size);
        console.dir(response);
        progresNode.textContent = bytes + ' / ' + file.size;
        const content = await response.json().catch((error) => {
            console.log('Parse error1:');
            console.dir(error);
            return {status:'error',body:error}
        });
        console.log('Resp:', content);
    }; 
    const destinationData = new FormData(); 
    destinationData.append('filename', file.name);
    // Must be an absolute path
    destinationData.append('basepath', basepath); 
    
    const finalResponse = await (fetch('?action=uploadaction', {method:'POST',body:destinationData})
        .then(function(response){
            if (response.status >= 400 && response.status < 600) {
                throw new Error('Bad response from server');
            }
            return response;
        })
        .catch((error) => {
            console.log('Err: ', error)
        }));
    progresNode.textContent = file.size + ' / ' + file.size;
    console.log('Final req status:', finalResponse.ok);
    console.dir(finalResponse);
    const content = await (finalResponse.json().catch((error) => {
        console.log('Parse error2:')
        console.dir(error);
        return {status:'error',body:error}
    }));
    console.log('Final', content);
    if (content?.status == 'error') {
        alert('Fail ' + JSON.stringify(content, null, '\t'));
    } else {
        alert('Done. File ' + file.name + ' was uploaded');
    }
    uploaderFieldset.removeAttribute('disabled');
    event.target.reset();
}";
    echo '</script>';
    echo layoutTail();
}
else if ($action == 'uploadaction') {
    session_start();
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $filename = $_POST['filename'];
    
        if (!isset($_SESSION[$filename])) {
            $_SESSION[$filename] = tempnam(sys_get_temp_dir(), 'upl');
        }
    
        $tmpfile = $_SESSION[$filename];
        header('Content-Type: application/json; charset=utf-8');
    
        if (isset($_FILES["chunk"])) {
            $chunk = $_FILES["chunk"]["tmp_name"];
            file_put_contents($tmpfile, file_get_contents($chunk), FILE_APPEND);
            echo json_encode([
                'status' => 'chunk',
                'filename' => $filename,
                'tmpfile' => $tmpfile
            ]);
        } else {
            $basepath = $_POST['basepath'];
            if (isset($basepath)) {
                $filename = $basepath.'/'.$filename;
            }
            // Attention: the $filename must be an absolute path
            // TODO the filename may include the path manipulations like path injections: '/home/user/uploads/' + '../../../passwd'
            // $ext = get_ext($path);
            // ? sprintf('./uploads/%s.%s',sha1_file($filename),$ext);
            if (preg_match('/\.{1,2}\//', $filename) == 1) {
                @unlink($tmpfile);
                die('{"status":"error","error":"Invalid file path","filename":"'.$filename.'"}');
            }
            $is_success = @rename($tmpfile, $filename);

            if ($is_success) {
                echo json_encode([
                    'status' => 'end',
                    'filename' => $filename,
                    'tmpfile' => $tmpfile
                ]);
            } else {
                $e = error_get_last();
                echo json_encode([
                    'status' => 'error', 
                    'error' => $e,
                    'filename' => $filename,
                    'tmpfile' => $tmpfile
                ]);
            }
        }
        // exit();
    }
}

else if ($action == 'new_handler') {
    if (
        isset($_POST['redirect']) && 
        isset($_POST['type']) &&
        isset($_POST['path'])
    ) {
        $redirect = $_POST['redirect'];
        $path = $_POST['path'];
        $type = $_POST['type'];
        
        if ($type === 'dir') {
            mkdir($path, 0777, true);
        } else if ($type === 'file') {
            touch($path);
        }

        header('Location: '.$redirect);
    }
}
else if ($action == 'saveas_handler') {
    if (
        isset($_POST['redirect']) && 
        isset($_POST['path']) &&
        isset($_POST['next_path'])
    ) {
        $redirect = $_POST['redirect'];
        $path = urldecode($_POST['path']);
        $next_path = $_POST['next_path'];
        $is_success = copy($path, $next_path);

        if (!$is_success) {
            $errors = error_get_last();
            $redirect = '?action=error&message=' . 
                urlencode($errors['type'].'\n'.$errors['message']) . 
                '&path='.urlencode(dirname($path));
        }
        header('Location: '.$redirect);
    }
}
else if ($action == 'info') {
    phpinfo();
}
function get_ext($s) {$n = strrpos($s,"."); if($n===false) return "";return substr($s,$n+1);}
function intword($number, $units=null, $kilo=null, $decimals=null){
	if ($units == null) $units = ['', 'Kb', 'Mb', 'Gb', 'Tb'];
	if ($kilo == null) $kilo=1024;
	if ($decimals == null) $decimals=2;
	$unit=0;
	
	for ($i=0; $i < count($units); $i++) {
        if ($number < pow($kilo, $i+1)) {
        	$unit = $i;
        	break;
        }
    }
    $humanized = $number / pow($kilo, $unit);
	// return $number;
	return round($humanized, $decimals) . $units[$unit];
} 

function fix_mime($mime) {
    return str_replace('image/svg;', 'image/svg+xml;', $mime);
}
