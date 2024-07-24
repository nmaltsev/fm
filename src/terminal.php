<?php
// php -S 127.0.0.1:8092 terminal.php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
define('VERSION','2.2024.07.22');

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
    
    echo '<a href="?action=form">Home</a>';
    echo '<a href="?action=list_background">Background tasks</a>';
    echo '<a href="?action=list_jobs">Background jobs</a>';
    echo '<br><form action="?action=form" method="POST">',
        '<input name="command" autofocus required value="', htmlspecialchars($command), '"/>',
        '<button type="submit">Submit</button>',
    '</form>';
    
    if (isset($_POST['command'])) {
        layout_tail();
        $user_command = $_POST['command'];
        execute_user_command($user_command);
    }
    else if (isset($_GET['pid'])) {
        // TODO long poling
        echo '<p>PID: ',$_GET['pid'],'</p>';
        echo '<textarea id="log"></textarea>';
        echo '<script>
window.onload = function(){        
    let start=0;
    console.log("TODO");
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

    echo json_encode([
        'out' => $content,
    ]);
}

function readFilePart($path, $from=0, $to=0) {
    $fileSize = filesize($path);
    $from = min($from, $fileSize);
    $out = '';
    $fp = fopen($path, 'r');

    if ($to - $from > 0) {
        fseek($fp, $from);
        $out = fread($fp, $to - $from);
    } else {
        $size = 256;
    
        while(!feof($fp)) {
            fseek($fp, $from);
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

function layout_head() {
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="utf-8"/>';
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
    foreach($output as $row) {
        echo $row;
        echo '<br>';
    }
    // print_r($output);
}

function execute_user_command($command) {
    $cmd_id = rand(5, 1500);
    $log_path = '/tmp/out.'.$cmd_id.'.log';

    $execstring = $command . ' < /dev/null > ' . $log_path . '2>&1 & echo $!';
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
