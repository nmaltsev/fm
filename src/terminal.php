<?php
// php -S 127.0.0.1:8092 terminal.php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
define('VERSION','5.2024.08.07');

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
    main{flex:1 1 auto;}
    textarea{width:100%;height:100%;padding:.5rem;resize:none;}
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
    echo '<form action="?action=form" method="POST">',
        '<input name="command" autofocus required value="', htmlspecialchars($command), '"/>',
        '<button type="submit">Submit</button>',
    '</form>';
    
    if (isset($_POST['command'])) {
        echo '</header>';
        layout_tail();
        $user_command = $_POST['command'];
        execute_user_command($user_command);
    }
    else if (isset($_GET['pid'])) {
        echo '<p>PID: ', htmlspecialchars($_GET['pid']), '&nbsp;<a href="?action=kill&pid=',htmlspecialchars($_GET['pid']),'">Cancel</a>', '</p>';
        echo '</header>';
        // TODO add button to stop the command execution, clear interval
        
        echo '<main><textarea id="log"></textarea></main>';
        echo '<script>
const searchParams = new URLSearchParams(location.search);
const pid = searchParams.get("pid");
const resource = "/tmp/pid." + pid + ".log";
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
    
    setInterval(checkProcess, 2*1000);
    checkProcess();
};

</script>';
        layout_tail();
    }
}
else if ($action=='list_background') {
    layout_head();
    list_background('ps -f');
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
    $res_b = posix_kill($pid, 9); // 9 is the SIGKILL signal
    
    if ($res_b) {
        header('Location: ?action=list_background');
    } else {
        // TODO
        echo 'Status: ', $res_b;
    }
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
                    echo '<a href="?action=kill&pid=',htmlspecialchars($cell),'">',$cell,'</a>';
                } else {
                    echo $cell;
                }
                echo '</div>';
            }
            if (isset($cmd)) {
                echo '<div class="command">', implode(' ', $cmd), '</div>';
            }
        }
    }
    echo '</div>';
}

function execute_user_command($command) {
    $cmd_id = rand(5, 1500);
    $log_path = '/tmp/out.'.$cmd_id.'.log';

    $execstring = $command . ' < /dev/null > ' . $log_path . ' 2>&1 & echo $!';
    $pid = exec($execstring, $output, $code);

    if (0) {
        echo 'Code: ', $code, '<br>';
        echo 'PID: ', $pid, '<br>';
        echo "Logfile: $log_path <br>";
    }
    
    symlink($log_path, '/tmp/pid.'.$pid.'.log');
    session_start();
    $_SESSION['command'] = $command;
    header('Location: '.'?action=form&tt&pid='.$pid);
    
    // sleep(1);
    // $is_running =  posix_getpgid($pid); 
    // echo 'Executing: ', ($is_running ? 'Yes' : 'No'), '<br>';
    
    // $fh = fopen($log_path, 'r');
    // echo '<pre>';
    // while ($line = fgets($fh)) {
    //     echo htmlspecialchars($line);
    // }
    // fclose($fh);
    // echo '</pre>';
    // if (!$is_running) unlink($log_path);

    // 
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
