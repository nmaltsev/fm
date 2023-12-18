<?php
// php -S 127.0.0.1:8092 terminal.php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

function getfrom($array, $key, $default) {
    return isset($array[$key]) ? $array[$key] : $default;
}

$action=getfrom($_GET, 'action', '');

if ($action=='form') {
    layout_head();
    $writeinlog = isset($_POST['writeinlog']) ? $_POST['writeinlog'] == 'on' : false;
    echo '<h1>Non-interactive shell</h1>';
    echo '<a href="?action=list_background">Background tasks</a>';
    echo '<a href="?action=list_jobs">Background jobs</a>';
    echo '<form action="?action=form" method="POST">';
    echo '<input name="command" autofocus required value="', htmlspecialchars(getfrom($_POST, 'command', '')), '"/>';
    echo '<label>',
        '<input type="checkbox" name="writeinlog" id="dd" ', 
        ($writeinlog ? 'checked' : ''),
        '/>',
        '&nbsp;Write in log</label>';
    echo '<button type="submit">Submit</button>';
    echo '</form>';

    if (isset($_POST['command'])) {
        $user_command = $_POST['command'];
        execute_user_command($user_command, $writeinlog);
        // _execute_user_command($user_command, $writeinlog);
    }

    layout_tail();
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

function execute_user_command($command, $writeinlog) {
    $cmd_id = rand(5, 1500);

    if ($writeinlog) {
        $log_path = '/tmp/out.'.$cmd_id.'.log';
        $execstring = /*'nohup ' .*/ $command . ' 2>>' . $log_path . ' 1>>' . $log_path .' & echo $!';
        $pid = exec($execstring, $output, $code);
        echo 'Code: ', $code, '<br>';

        echo 'PID: ', $pid, '<br>';
        echo "Logfile: $log_path <br>";
        
        sleep(1);
        $is_running =  posix_getpgid($pid); 
        echo 'Executing: ', ($is_running ? 'Yes' : 'No'), '<br>';
        
        $fh = fopen($log_path, 'r');
        echo '<pre>';
        while ($line = fgets($fh)) {
            echo htmlspecialchars($line);
        }
        fclose($fh);
        echo '</pre>';
        if (!$is_running) unlink($log_path);
    } else {
        // 'nohup '
        $execstring = /*'nohup ' .*/ $command . ' 2>&1 &';
        // echo 'CMD: ', $execstring, '<br>';
        ob_start();
        passthru($execstring);
        
        $data = ob_get_clean();

        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}

// tail -f /dev/null

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
