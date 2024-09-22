<?php
// php -S 127.0.0.1:8092 terminal.php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
define('VERSION','7.2024.09.17');

function getFrom($array, $key, $default) {
    return isset($array[$key]) ? $array[$key] : $default;
}

$action=getFrom($_GET, 'action', '');
session_start();

if ($action=='form') {
    layout_head();
    $command = '';

    if (isset($_SESSION['command'])){
        $command = $_SESSION['command'];
        unset($_SESSION['command']);
        session_destroy();
    } else if(isset($_POST['command'])){
        $command = $_POST['command'];
    }
    echo '<style>
    body,textarea,input{box-sizing:border-box;}
    body{margin:0;min-height:100vh;padding:.5rem;}
    body{display:flex;flex-direction:column;}
    header{flex:0 0 auto;}
    main{flex:1 1 auto;position:relative;}
    textarea{position:absolute;width:100%;height:100%;padding:.5rem;resize:none;}
    nav>a{margin-right:.5rem;}
    nav{margin:0 0 1rem 0;}
    form>button{margin-left:1rem;}
    </style>';
    echo '<header>',
    '<nav>
        <a href="?action=form">Home</a>
        <a href="?action=list_background">Background tasks</a>
        <a href="?action=list_jobs">Background jobs</a>
    </nav>';
    echo '<input type="hidden" name="rootdir" id="rootdir" value="', sys_get_temp_dir(), '"/>';
    echo '<form action="?action=cmd" method="POST">',
        '<input name="command" autofocus required value="', htmlspecialchars($command), '"/>',
        '<button type="submit">Submit</button>',
    '</form>';
    
    if (isset($_GET['pid'])) {
        echo '<p>PID: ', htmlspecialchars($_GET['pid']), 
        '&nbsp;<a id="cancelBtn" href="?action=kill&pid=',htmlspecialchars($_GET['pid']),
        '&back=',urlencode('?action=form'),
        '">Cancel</a>', '</p>';
        echo '</header>';
        // TODO add button to stop the command execution, clear interval
        
        echo '<main><textarea id="log"></textarea></main>';
        echo '<script>
const searchParams = new URLSearchParams(location.search);
const pid = searchParams.get("pid");
const rootDir = document.getElementById("rootdir")?.value;
console.log("RootDir %s", rootDir);
const resource = rootDir + "/pid." + pid + ".log";
let _start=0;

function checkProcess(){
    const start = _start;
    fetch("?action=readtail&f=" + start + "&r=" + resource).then(response => {
        return response.json();
    }).then(data => {
        _start = start + data.out.length;
        document.all.log.value += data.out;
        document.all.log.scrollTo(0, document.all.log.scrollHeight);
    });
}
window.onload = function(){        
    console.log("TODO %s", resource);
    document.all.log.value = "";
    // TODO call check process exist ?action=isrunning&pid=<>
    
    setInterval(checkProcess, 2*1000);
    checkProcess();
};

</script>';
        layout_tail();
    }
}
else if ($action=='cmd') {
    $user_command = $_POST['command'];
    $pid = execute_user_command($user_command);
    $_SESSION['command'] = $user_command;
    header('Location: '.'?action=form&pid='.$pid);
    die();
}
else if ($action=='list_background') {
    layout_head();
    $flags = isset($_GET['f']) && preg_match('/^[\-a-z0-9]+$/i', $_GET['f']) ? $_GET['f'] : '-f';
    list_background('ps '.$flags);
    layout_tail();
}
else if ($action=='list_jobs') {
    layout_head();
    list_background('jobs');
    layout_tail();
}
else if ($action=='read') {
    $path = getFrom($_GET, 'r', null);
    $from = intval(getFrom($_GET, 'f', null));
    $to = intval(getFrom($_GET, 't', null));
    $content = readFilePart($path, $from, $to);

    header('Content-Type: application/json');
    echo json_encode([
        'out' => $content,
    ]);
}
else if ($action=='readtail') {
    $path = getFrom($_GET, 'r', null);
    $from = intval(getFrom($_GET, 'f', null));
    $content = readTailOfFile($path, $from);

    header('Content-Type: application/json');
    echo json_encode([
        'out' => $content,
    ]);
}
else if ($action=='kill') {
    $pid = getFrom($_GET, 'pid', null);
    // TODO how to get error description?
    $res_b = posix_kill($pid, 9); // 9 is the SIGKILL signal

    $fallback = getFrom($_GET, 'back', '?action=list_background');
    
    if ($res_b) {
        header('Location: '.$fallback);
    } else {
        $message = 'An error occurred while killing a process with pid: '.$pid.'.';
        
        $e = error_get_last();
        echo '<pre>';
        print_r($e);
        echo '</pre>';
        if (isset($e)) {
            $message .= 'Message: '.$e['message'].' File: '.$e['file'].' Line: '.$e['line'];
        }

        $redirect = '?action=error&message=' . urlencode($message) . '&back=' . urlencode($fallback);
        header('Location: '.$redirect);
    }
}
else if ($action == 'error') {
    $message = urldecode($_GET['message']);
    $fallback = getFrom($_GET, 'back', '');
    layout_head();
    echo '<dir>';
    echo '<h3>', htmlspecialchars($message), '</h3>';
    echo '<a href="', $fallback, '">Back</a>';
    echo '</dir>';
    layout_tail();
}
else if ($action == 'isrunning') {
    header('Content-Type: application/json');
    if (isset($_GET['pid'])) {
        $pid = intval($_GET['pid']);
        
        echo json_encode([
            'isRunning' => posix_getpgid($pid) != false,
        ]);
        die();    
    }
    echo json_encode([
        'error' => 'Process number not specified'
    ]);
    die();
}

function readFilePart($path, $from=0, $to=0) {
    $fileSize = filesize($path);
    $from = min($from, $fileSize);
    $out = '';
    $fp = fopen($path, 'r');
    fseek($fp, $from);

    if ($to - $from > 0) {
        $out = fread($fp, $to - $from);
    } else {
        $size = 256;
    
        while(!feof($fp)) {
            // fseek($fp, $from);
            $data = fread($fp, $size);  // assumes lines are < $size characters
            $pos = strpos($data, "\n");
            if ($pos !== false) {
                $out .= substr($data, 0, $pos);
                break;
            } else {
                $out .= $data;
            }
            $from += $size;
        }
    }
    fclose($fp);
    return $out;
}

function readTailOfFile($path, $from=0) {
    $fileSize = filesize($path);
    $from = min($from, $fileSize);
    $out = '';
    $fp = fopen($path, 'r');

    fseek($fp, $from);
    while (!feof($fp)) {
        $out .= fread($fp, 8192);
    }
    fclose($fp);
    return $out;
}


function layout_head() {
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="utf-8"/>',
    '<title>TRMNL</title>';
    echo '</head>';
    echo '<body>';
}
    
function layout_tail(){
    echo '</body>';
    echo '</html>';
}
    
/*
`ps S` list of background processes
`jobs`
 */    

function list_background($command) {
    $myPid = posix_getpid();
    echo 'MyPid: '. $myPid . '<br>';
    // $execstring='ps -f -u www-data 2>&1';
    $execstring=$command.' 2>&1';
    $output="";
    exec($execstring, $output);
    echo '
<style>
.grid {
    display:grid;
    grid-gap:.5rem .5rem;
    white-space:nowrap;
}
.command{color:blue;}
</style>';
    
    $n_col=null;
    foreach($output as $row) {
        // $cells = explode(' ', $row);
        $parts = preg_split('/\s+/', $row);
        if (!isset($n_col)) {
            $n_col=count($parts) - 1;
            echo '<div class="grid" style="grid-template-columns:',str_repeat(' min-content', $n_col + 1),';">';
            foreach($parts as $cell) {
                echo '<div class="">', $cell, '</div>';
            }
        } else {
            $cmd=array_slice($parts, $n_col);
            $parts=array_slice($parts, 0, $n_col);
            foreach($parts as $i => $cell) {
                echo '<div class="">';
                if ($i===1) {
                    $pid = $cell;
                    echo '<a href="?action=kill&pid=',htmlspecialchars($pid),'&back=',urlencode('?action=list_background'),'">',$pid,'</a>';
                } else {
                    echo $cell;
                }
                echo '</div>';
            }
            if (isset($cmd)) {
                echo '<div class="command">',
                '<a href="?action=form&pid=',htmlspecialchars(isset($pid) ? $pid : ''),'">', implode(' ', $cmd), '</a></div>';
            }
        }
    }
    echo '</div>';
}

function execute_user_command($command) {
    $cmd_id = rand(5, 1500);
    $log_path = tempnam(sys_get_temp_dir(), 'out.'.$cmd_id.'.log');

    $execstring = $command . ' < /dev/null > ' . $log_path . ' 2>&1 & echo $!';
    $pid = exec($execstring, $output, $code);

    if (0) {
        echo 'Code: ', $code, '<br>';
        echo 'PID: ', $pid, '<br>';
        echo "Logfile: $log_path <br>";
    }
    if (!@symlink($log_path, sys_get_temp_dir().'/pid.'.$pid.'.log')) {
        // echo 'Cant create a symlink';
        rename($log_path, sys_get_temp_dir().'/pid.'.$pid.'.log');
    }
    return $pid;
}

function execute_cmd($cmd){
    $execstring = $cmd . ' 2>&1 &';
    ob_start();
    passthru($execstring);
    $data = ob_get_clean();
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

// Stress test. An example of infinite command: `tail -f /dev/null`

function execute_user_command3($command) {
    while (@ ob_end_flush()); // если есть, прекращает все буферы вывода
 
    $proc = popen($command, 'r');
    echo '<pre>';
    while (!feof($proc))
    {
        echo fread($proc, 4096);
        @ flush();
    }
    echo '</pre>';

}

function execute_user_command4() {
    header('X-Accel-Buffering: no');
    set_time_limit(0);              // making maximum execution time unlimited
    ob_implicit_flush(1);           // Send content immediately to the browser on every statement which produces output
    ob_end_flush();                 // deletes the topmost output buffer and outputs all of its contents

    sleep(1);
    echo json_encode(['data' => 'test 1']);

    sleep(2);
    echo json_encode(['data' => 'test 2']);

    sleep(1);
    echo json_encode(['data' => 'test 3']);
    die(1);
}
