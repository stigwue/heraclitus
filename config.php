<?php

require('rb.php');

//database
global $conn;
global $db;

//default
$db['server'] = "localhost";

$db['db'] = "crowdos_heraclitus";

$db['user'] = "crowdos_crowdos";

$db['password'] = "some secure password";


date_default_timezone_set('Africa/Lagos');


function get_db_instance($server, $user, $password, $db)
{
    $conn = mysqli_connect($server, $user, $password, $db);
    return $conn;
}

function get_db()
{
    global $db;
    global $conn;

    $conn = get_db_instance($db['server'], $db['user'], $db['password'], $db['db']);
}
get_db();

function rb_dbsetup($server, $user, $password, $db)
{
    //R::setup('mysql:host=localhost;dbname=mydatabase','user','password')
        
    R::setup('mysql:host=' . $server . ';dbname=' . $db . '', $user, $password);
}

/*function custom_number_format($n, $precision = 3) {
    if ($n < 1000000) {
        // Anything less than a million
        $n_format = number_format($n);
    } else if ($n < 1000000000) {
        // Anything less than a billion
        $n_format = number_format($n / 1000000, $precision) . 'M';
    } else {
        // At least a billion
        $n_format = number_format($n / 1000000000, $precision) . 'B';
    }

    return $n_format;
}*/

function convert_date($timestamp)
{
    $new_stamp = mktime(15, 0, 0, date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp));
    return $new_stamp;
}

?>
