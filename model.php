<?php

require_once('simple_html_dom.php');

class app
{
	public static function save_date_scrapped($timestamp)
	{
		$object = R::dispense('scrapped');
		$object->timestamp = $timestamp;
		$object->date = date('M jS, Y', time(NULL)) ;

		$id = R::store($object);

		return $id;
	}

	public static function delta($d_upper, $d_lower = 0)
	{
		$output = '';
		if ($d_upper > $d_lower)
		{
			//increment
			$output .= '<i class="fa fa-arrow-up fa-fw inc"></i>';
		}
		else if ($d_upper < $d_lower)
		{
			//decrement
			$output .= '<i class="fa fa-arrow-down fa-fw dec"></i>';
		}
		else
		{
			$output .= '<i class="fa fa-minus fa-fw eql"></i>';
		}

		//delta
		if ($d_lower <> 0)
		{
			$delta = abs(($d_upper - $d_lower) / $d_lower) * 100;
		}
		else
		{
			$delta = 0;
		}

		$output .= number_format($delta, 2) . '%';

		return $output;
	}
}

class exchange
{
	private $object = null;

	public static function store()
	{
		$object = R::dispense('exchange');
		$object->title = 'Nigerian Stock Exchange';
		$object->code = 'NSE';
		$object->currency = 'Naira';

		$id = R::store($object);

		return $id;
	}

	public static function history()
	{
		$rows = R::getAssocRow(
			'SELECT timestamp, value FROM
				(SELECT
					timestamp,
					SUM(close * volume) AS value
				FROM `transaction` as t1 GROUP BY timestamp
				ORDER BY t1.timestamp DESC
				LIMIT 30) AS t2
			ORDER BY t2.timestamp ASC'
			);

		$output = '';

		$output .= '[[\'Timestamp\', \'Value\'],';
		$counter = 0;
		foreach ($rows as $singlerow)
		{
			$output .= '[\'' . date('M jS', $singlerow['timestamp']) . '\', ' . $singlerow['value'] . ']';
		    $output .= ',';
		    ++$counter;
		}
		$output = substr($output, 0, strlen($output) - 1);
		$output .= ']';

		echo $output;
	}

	public static function delta()
	{
		$rows = R::getAssocRow(
			'SELECT
				timestamp,
				SUM(close * volume) AS value
			FROM `transaction` GROUP BY timestamp
			ORDER BY timestamp DESC
			LIMIT 2'
			);

		
		$counter = 0;
		$d_upper = 0; $d_lower = 0;
		foreach ($rows as $singlerow)
		{
		    ++$counter;
		    if ($counter == 1)
		    {
		    	$d_upper = $singlerow['value'];
		    }
		    else
		    {
		    	$d_lower = $singlerow['value'];
		    }
		}

		return app::delta($d_upper, $d_lower);
	}

	public static function display_all()
	{
		$rows = R::getAssocRow(
			'SELECT
				id, title, code, currency
			FROM `exchange`
			ORDER BY title ASC
			LIMIT 30'
			);

		$output = '';
		$counter = 0;


		foreach ($rows as $singlerow)
		{
			$output .= '<div class="title">
              <a href="./?context=exchange&id=' . $singlerow['id'] . '">
                <strong>' . $singlerow['code'] . '</strong>' . exchange::delta() . ' 
              </a>
              <div class="title3">' . $singlerow['title'] . '</div>
            </div>';
		    ++$counter;
		}

		echo $output;
	}
}

class sector
{
	private $object = null;

	public static function store()
	{
		$object = R::dispense('sector');
		$object->code = '2TIER';
		$object->title = 'Second Tier Securities';
		$object->exchange = 1;

		$id = R::store($object);

		return $id;
	}

	public static function import()
	{
	    global $db;

	    $conn = get_db_instance($db['server'], $db['user'], $db['password'], $db['db']);

	    if($conn->connect_errno <= 0)
	    {
	        $statement = $conn->prepare("SELECT id, name FROM tblsector_sf");
	        if ($result = $statement->execute())
	        {
	            $statement->bind_result($code, $title);
	            while($statement->fetch())
	            {
	                $object = R::dispense('sector');
					$object->code = $code;
					$object->title = $title;

					$id = R::store($object);
	            }
	            $statement->free_result();
	            return TRUE;
	        }
	    }
	    return FALSE;
	}

	public static function history($sectorid)
	{
		$rows = R::getAssocRow(
			'SELECT timestamp, value, title
			FROM
				(SELECT
					timestamp,
					SUM(close * volume) AS value,
					(SELECT title FROM sector WHERE sector.id = :sectorid) AS title
				FROM `transaction` AS t1
				WHERE symbol IN (SELECT symbol FROM instrument WHERE sector = :sectorid)
				GROUP BY t1.timestamp
				ORDER BY t1.timestamp DESC
				LIMIT 30)
			AS t2
			ORDER BY t2.timestamp ASC',
			[':sectorid' => $sectorid]
			);

		$output = '';

		$counter = 0;
		foreach ($rows as $singlerow)
		{
			if ($counter == 0)
			{				
				$output .= '[[\'Timestamp\', \'' . $singlerow['title'] . '\'],';
			}
			$output .= '[\'' . date('M jS', $singlerow['timestamp']) . '\', ' . $singlerow['value'] . ']';
		    $output .= ',';
		    ++$counter;
		}
		$output = substr($output, 0, strlen($output) - 1);
		$output .= ']';

		echo $output;
	}

	public static function delta($sectorid)
	{
		$rows = R::getAssocRow(
			'SELECT
				timestamp,
				SUM(close * volume) AS value
			FROM `transaction`
			WHERE symbol IN (SELECT symbol FROM instrument WHERE sector = :sectorid)
			GROUP BY timestamp
			ORDER BY timestamp DESC
			LIMIT 2',
			[':sectorid' => $sectorid]
			);

		
		$counter = 0;
		$d_upper = 0; $d_lower = 0;
		foreach ($rows as $singlerow)
		{
		    ++$counter;
		    if ($counter == 1)
		    {
		    	$d_upper = $singlerow['value'];
		    }
		    else
		    {
		    	$d_lower = $singlerow['value'];
		    }
		}

		return app::delta($d_upper, $d_lower);
	}

	public static function display_all_exchange($exchange_id)
	{
		$rows = R::getAssocRow(
			'SELECT
				id, code, title, exchange,
				(SELECT COUNT(symbol) FROM instrument WHERE sector = sector.id) AS inst_count
			FROM `sector`
			WHERE exchange = :exchange_id
			ORDER BY inst_count DESC
			LIMIT 30',
			[':exchange_id' => $exchange_id]
			);


		$output = '';
		$counter = 0;
		foreach ($rows as $singlerow)
		{
			$output .= '<div class="title">
              <a class="title2" href="./?context=sector&id=' . $singlerow['id'] . '">
                ' . $singlerow['title'] . sector::delta($singlerow['id']) . '
              </a>
              <!--div class="title3">' . $singlerow['title'] . '</div-->
            </div><hr class="divider">';
		    ++$counter;
		}

		echo $output;
	}
}

class instrument
{
	private $object = null;

	public static function store()
	{
		$object = R::dispense('instrument');
		$object->symbol = 'GTB';
		$object->title = 'Guaranty Trust Bank';
		$object->sector = 1;

		$id = R::store($object);

		return $id;
	}

	public static function import()
	{
	    global $db;

	    $conn = get_db_instance($db['server'], $db['user'], $db['password'], $db['db']);

	    if($conn->connect_errno <= 0)
	    {
	        $statement = $conn->prepare("SELECT symbol, name, sectorid, (SELECT id FROM sector WHERE code = sectorid) FROM tblcompany_sf");
	        if ($result = $statement->execute())
	        {
	            $statement->bind_result($symbol, $title, $sectorid, $sector);
	            while($statement->fetch())
	            {
	                $object = R::dispense('instrument');
					$object->symbol = $symbol;
					$object->title = $title;
					$object->sector = $sector;

					$id = R::store($object);
	            }
	            $statement->free_result();
	            return TRUE;
	        }
	    }
	    return FALSE;
	}

	public static function delta($symbol)
	{
		$rows = R::getAssocRow(
			'SELECT id, timestamp, symbol, open, high, low, close, deals, volume
			FROM `transaction`
			WHERE symbol=:symbol
			ORDER BY timestamp DESC
			LIMIT 2',
			[':symbol' => $symbol]
			);

		
		$counter = 0;
		$d_upper = 0; $d_lower = 0;
		foreach ($rows as $singlerow)
		{
		    ++$counter;
		    if ($counter == 1)
		    {
		    	$d_upper = $singlerow['close'];
		    }
		    else
		    {
		    	$d_lower = $singlerow['close'];
		    }
		}

		return app::delta($d_upper, $d_lower);
	}

	public static function display_all_sector($sector_id)
	{
		$rows = R::getAssocRow(
			'SELECT
				id, symbol, title, sector
			FROM `instrument`
			WHERE sector = :sector_id
			ORDER BY title ASC
			LIMIT 30',
			[':sector_id' => $sector_id]
			);

		$output = '';
		$counter = 0;
		foreach ($rows as $singlerow)
		{
			$output .= '<div class="title">
              <a class="title2" href="./?context=instrument&id=' . $singlerow['symbol'] . '">
                ' . $singlerow['symbol'] . instrument::delta($singlerow['symbol']) . '
              </a>
              <div class="title3">' . $singlerow['title'] . '</div>
            </div><hr class="divider">';
		    ++$counter;
		}

		echo $output;
	}
}

class transaction
{
	private $object = null;

	public static function store()
	{
		$object = R::dispense('transaction');
		$object->timestamp = 1410466494;
		$object->symbol = 'GTB';
		$object->open = 2.01;
		$object->high = 2.01;
		$object->low = 2.01;
		$object->close = 2.01;
		$object->deals = 200;
		$object->volume = 3000;

		$id = R::store($object);

		return $id;
	}

	public static function import()
	{
	    global $db;

	    $conn = get_db_instance($db['server'], $db['user'], $db['password'], $db['db']);

	    if($conn->connect_errno <= 0)
	    {
	        $statement = $conn->prepare("SELECT stamp, symbol, open, high, low, close, deals, volume FROM tbltransaction_sf");
	        if ($result = $statement->execute())
	        {
	            $statement->bind_result($timestamp, $symbol, $open, $high, $low, $close, $deals, $volume);
	            while($statement->fetch())
	            {
	                $object = R::dispense('transaction');
					$object->timestamp = $timestamp;
					$object->symbol = $symbol;
					$object->open = $open;
					$object->high = $high;
					$object->low = $low;
					$object->close = $close;
					$object->deals = $deals;
					$object->volume = $volume;

					$id = R::store($object);
	            }
	            $statement->free_result();
	            return TRUE;
	        }
	    }
	    return FALSE;
	}

	public static function display($symbol)
	{
		$rows = R::getAssocRow(
			'SELECT id, timestamp, symbol, open, high, low, close, deals, volume
			FROM
				(SELECT id, timestamp, symbol, open, high, low, close, deals, volume
				FROM `transaction` AS t1
				WHERE symbol=:symbol
				ORDER BY t1.timestamp DESC
				LIMIT 30)
			AS t2 ORDER BY t2.timestamp ASC',
			[':symbol' => $symbol]
			);

		$output = '';
		$output .= '[[\'stamp\', \'low\', \'open\', \'close\', \'high\'],';
		$counter = 0;
		foreach ($rows as $singlerow)
		{
			$output .=  '[\'' . 
				//date('M jS, Y g:ia', $singlerow['timestamp']) . '\', ' .
				date('M jS', $singlerow['timestamp']) . '\', ' .
				$singlerow['low'] . ', ' .
				$singlerow['open'] . ', ' .
				$singlerow['close'] . ', ' .
				$singlerow['high'] .
				']';
		    
		    $output .= ',';
		    ++$counter;
		}
		$output = substr($output, 0, strlen($output) - 1);
		$output .= ']';

		echo $output;
	}

	public static function symbolcount($timestamp)
	{
		$rows = R::getAssocRow(
			'SELECT COUNT(symbol) AS count FROM `transaction` WHERE timestamp=:timestamp',
			[':timestamp' => $timestamp]
			);

		$count = 0;

		foreach ($rows as $singlerow)
		{
			$count =  $singlerow['count'];
		}

		return $count;
	}

	public static function max_timestamp($maxstamp)
	{
		$rows = R::getAssocRow(
			'SELECT MAX(timestamp) AS maxstamp FROM `transaction`'
			);

		$max_stamp = $maxstamp;

		foreach ($rows as $singlerow)
		{
			$max_stamp =  $singlerow['maxstamp'];
		}

		return $maxstamp;
	}

	public static function min_timestamp($minstamp)
	{
		/*$rows = R::getAssocRow(
			'SELECT MIN(timestamp) AS minstamp FROM `transaction`'
			);*/

		$rows = R::getAssocRow(
			'SELECT MIN(timestamp) AS minstamp FROM `scrapped`'
			);

		$min_stamp = $minstamp;

		foreach ($rows as $singlerow)
		{
			$min_stamp =  $singlerow['minstamp'];
		}

		return $min_stamp;
	}

	//public static function scrape($path)
	public static function scrape($src)
	{

		$html = str_get_html($src);

		$new_stamp = 0;

		foreach($html->find('p') as $element)
		{
			if ($element->class == 'lead')
			//echo $element->plaintext;
			$timestamp = strtotime(substr($element->plaintext, strlen('Pricelist as at ') - 1));
			//mktime int hour, int minute, int second, int month, int day, int year [, int is_dst]
			$new_stamp = convert_date($timestamp);

			//mon to fri check
			switch (strtolower(date('D', $new_stamp)))
			{
				case 'mon':
				case 'tue':
				case 'wed':
				case 'thu':
				case 'fri':
					//pass, do check on whether this data has been scraped
					if (transaction::symbolcount($new_stamp) != 0)
					{
						echo 'already scraped';
						return 0;
					}
				break;

				default:
					echo 'not mon-fri';
					return 0;
				break;
			}
		}

		$tag_count = 0;

		$object = R::dispense('transaction');

		foreach ($html->find('table.table-blue td') as $td)
		{
			$tag_count += 1;
			//echo $td->plaintext . '<br >';

			$object->timestamp = $new_stamp;

			switch ($tag_count)
			{
				/*
				1. Security
				2. Price
				3. Change
				4. PClose
				5. Open
				6. High
				7. Low
				8. Volume
				9. Value
				*/
				case 1:
					$symbol = $td->plaintext;
					$object->symbol = $symbol;
				break;

				case 2:
					//price
				break;

				case 3:
					//change
				break;

				case 4:
					$close = $td->plaintext;
					$object->close = $close;
				break;

				case 5:		
					$open = $td->plaintext;			
					$object->open = $open;
				break;

				case 6:	
					$high = $td->plaintext;				
					$object->high = $high;
				break;

				case 7:	
					$low = $td->plaintext;				
					$object->low = $low;
				break;

				case 8:	
					$volume = $td->plaintext;
					//volume is comma seperated sp				
					$object->volume = str_replace(',', '', $volume);
				break;

				case 9:
					//value
					$deals = 0;
					$object->deals = $deals;

					//$object = R::dispense('transaction');
					/*$object->open = $open;
					$object->high = $high;
					$object->low = $low;
					$object->close = $close;
					$object->deals = $deals;
					$object->volume = $volume;*/

					$id = R::store($object);

					//echo $id . '\n';
					//echo '-------------<br >';
					//var_dump($object);
					$tag_count = 0;
					$object = R::dispense('transaction');
				break;
			}

			/*if ($tag_count == 9)
			{
				echo '-------------<br >';
				$tag_count = 0;
				$object = R::dispense('transaction');
			}*/
		}

		echo $new_stamp;
	}
}

class portfolio
{
	private $object = null;

	public static function store($storage)
	{
		$object = R::dispense($storage);
		$object->customer = 'Stephen Igwue';
		$object->instrument = 'GTB';
		$object->quantity = 200;
		$object->timebought = 1410466494;
		$object->timesold = 1410466500;

		$id = R::store($object);

		return $id;
	}

}

?>