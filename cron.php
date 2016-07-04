<?php
require_once('config.php');

rb_dbsetup($db['server'], $db['user'], $db['password'], $db['db']);

require_once('model.php');

//cron.php?interval=*
//parse_str(implode('&', array_slice($argv, 1)), $_GET);

if (isset($_GET['interval']))
{
    switch($_GET['interval'])
    {
        case 'daily': //every day at 3pm
        	$now = time(NULL);

        	$d = date('j', $now);
        	$m = date('n', $now);
        	$yyyy = date('Y', $now);

        	$html = cashcraft_post($d, $m, $yyyy);

        	transaction::scrape($html);

        	echo 'daily';
        break;

        case '5min': //every 5 mins to get backlog
        	//get oldest stock data
            $min_stamp = transaction::min_timestamp(1412776800);

        	//subtract a day
            if ($min_stamp <> 0)
            {

                $min_stamp = strtotime('-24 hours', $min_stamp);

                //save it
                app::save_date_scrapped($min_stamp);

                $d = date('j', $min_stamp);
                $m = date('n', $min_stamp);
                $yyyy = date('Y', $min_stamp);

                $html = cashcraft_post($d, $m, $yyyy);

                transaction::scrape($html);
            }

            echo '5min';
            
        break;

        default:
        break;
    }
}

//transaction::scrape('./cashcraft/plistorder.asp 12.htm'); 

//look for last date from db, or check today's date
//echo transaction::max_timestamp(convert_date(time(NULL)));

//cashcraft_post();

function cashcraft_post($dd, $mm, $yy, $url = 'http://www.cashcraft.com/asps/plistorder.asp')
{
	$data = array(
		'postback' => 'true',
		'dd' => $dd,
		'mm' => $mm,
		'yy' => $yy,
		'qrydate' => 'Get Date'
		);

	// use key 'http' even if you send the request to https://...
	$options = array(
	    'http' => array(
	        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
	        'method'  => 'POST',
	        'content' => http_build_query($data),
	    ),
	);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);

	return $result;
}

?>