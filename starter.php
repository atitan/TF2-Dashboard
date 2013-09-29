<?php

include './config.php';

function dbInit()
{
	// Create DB link
	$dblink = mysqli_init();

	// Attempt to connect
	if (!mysqli_real_connect($dblink, TF_DB_HOST, TF_DB_USER, TF_DB_PASS, TF_DB_NAME, TF_DB_PORT)) {
    	die('DB Connection Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
	}

	mysqli_set_charset($dblink, 'utf8');

	$newtable = 'CREATE TABLE IF NOT EXISTS `tf_table` (
		`cid` INT unsigned NOT NULL,
  		`player_name` LONGTEXT NOT NULL,
  		`profile_url` LONGTEXT NOT NULL,
  		`admin_name` LONGTEXT NOT NULL,
  		PRIMARY KEY (`cid`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
	
	// Create table if not exists
	mysqli_real_query($dblink, $newtable);

	return $dblink;
}

function updateSteamID()
{
	// Retrieve DB link
	global $dblink;

	// id comparison
	$checkid = 'SELECT IF( IFNULL((SELECT MAX(id) FROM `sm_record_meta`), 0) > IFNULL((SELECT MAX(cid) FROM `tf_table`), 0), 1, 0)';

	// exit on failure
	if (!$result = mysqli_query($dblink, $checkid)) die('Failed checking data.');

	// set data offset, fetch row, free result
    mysqli_data_seek($result, 0);
    $row = mysqli_fetch_row($result);
   
    // no need to update
    if($row[0] == 0) die('No data need to update.');

    // get unsync data
    $getnewdata = 'SELECT * FROM `sm_record_meta` WHERE id>(IFNULL((SELECT MAX(cid) FROM `tf_table`), 0));';

    // exit on failure
    if (!$result = mysqli_query($dblink, $getnewdata)) die('Failed get unsync data.');

    // convert steam id
    for ($i=0; $i < $old_rows = mysqli_num_rows($result); $i++) {
    	mysqli_data_seek($result, $i);
		$row = mysqli_fetch_row($result);
		$steam64id['user'][$i] = steamIdConverter($row[1]);
		$steam64id['admin'][$i] = steamIdConverter($row[6]);
	}

	// seprate ids and remove duplicated ones
	$sep_id = implode(",", array_unique(arrayPlusArray($steam64id['user'], $steam64id['admin'])));

	// http request
	$json = json_decode(file_get_contents('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key='.TF_STEAM_API_KEY.'&steamids='.$sep_id.'&format=json'), true);

	// get current id
	$tableid = 'SELECT IFNULL((SELECT MAX(id) FROM `sm_record_meta`), 0)';

	// exit on failure
	if (!$result = mysqli_query($dblink, $tableid)) die('Failed checking id number.');

	// get current row number
    mysqli_data_seek($result, 0);
    $row = mysqli_fetch_row($result);
    $currentid = $row[0] - $old_rows + 1;

	// split data
	$person = $json['response']['players'];
	for ($i=0; $i < count($steam64id['user']); $i++) { 
		for ($o=0; $o < count($person); $o++) { 
			if ($person[$o]['steamid'] == $steam64id['user'][$i]) {
				$username = $person[$o]['personaname'];
				$userurl = $person[$o]['profileurl'];
			}
			if ($person[$o]['steamid'] == $steam64id['admin'][$i]) {
				$adminname = $person[$o]['personaname'];
			}
		}
		$insertlist[$i] = '('.($currentid + $i).', "'.$username.'", "'.$userurl.'", "'.$adminname.'")';
	}
  	
  	$sql = 'INSERT INTO `tf_table` (cid, player_name, profile_url, admin_name) VALUES ';
  	$sql .= implode(', ', $insertlist);

    if (!$result = mysqli_query($dblink, $sql)) die('Failed insert data.');

    die('ok');
}

function getBanData()
{
	// Retrieve DB link
	global $dblink;

	$getlastdata = 'SELECT * FROM `tf_table`, `sm_record_meta` WHERE id = cid GROUP BY id DESC LIMIT 30';

	// exit on failure
    if (!$result = mysqli_query($dblink, $getlastdata)) return false;

    // split data into arrays
    for ($i=0; $i < mysqli_num_rows($result); $i++) {
    	mysqli_data_seek($result, $i);
		$row = mysqli_fetch_row($result);
		$banData[$i] = array(
			$row[1], // player_name
			$row[5], // steam_id
			$row[2], // profile_url
			$row[8], // action
			$row[9], // addition
			$row[11], // reason
			$row[6], // timestamp
			$row[7], // expired
			$row[3], // admin_name
		);
	}

	return $banData;
}

function asyncUpdateCheck($url, $params, $type='POST')
{
      foreach ($params as $key => &$val) {
        if (is_array($val)) $val = implode(',', $val);
        $post_params[] = $key.'='.urlencode($val);
      }
      $post_string = implode('&', $post_params);

      $parts=parse_url($url);

      $fp = fsockopen($parts['host'],
          isset($parts['port'])?$parts['port']:80,
          $errno, $errstr, 30);

      // Data goes in the path for a GET request
      if('GET' == $type) $parts['path'] .= '?'.$post_string;

      $out = "$type ".$parts['path']." HTTP/1.1\r\n";
      $out.= "Host: ".$parts['host']."\r\n";
      $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
      $out.= "Content-Length: ".strlen($post_string)."\r\n";
      $out.= "Connection: Close\r\n\r\n";
      // Data goes in the request body for a POST request
      if ('POST' == $type && isset($post_string)) $out.= $post_string;

      fwrite($fp, $out);
      fclose($fp);
}

function curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["SERVER_PORT"] == "443") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80" || $_SERVER["SERVER_PORT"] != "443") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

function steamIdConverter($steam_id)
{
	preg_match('/^STEAM_([0-9]):([0-9]):([0-9]+)$/', $steam_id, $matches);

	return ($matches[3] * 2) + $matches[2] + 76561197960265728;
}

function arrayPlusArray(array $array1, array $array2)
{
	foreach ($array1 as $key => $value) {
		$temp[] = $value;
	}

	foreach ($array2 as $key => $value) {
		$temp[] = $value;
	}

	return array_unique($temp);
}