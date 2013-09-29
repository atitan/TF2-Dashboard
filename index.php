<?php

require './starter.php';

$dblink = dbInit();

if(isset($_GET['sync']) && $_GET['sync'] == 'go') updateSteamID();

if(!$banData = getBanData()) die('Failed retrieving data.');

require './template.php';

asyncUpdateCheck(curPageURL(), array('sync' => 'go'), 'GET');