<?php

$config = parse_ini_file('backup.ini',true);

foreach($config['accounts'] as $account){
	backup($account['host'],$account['user'],$account['pass']);
	echo $account[1]."\n";
}

function backup($domain,$cpuser,$cppass){
	$skin = "x3"; // Set to cPanel skin you use (script won't work if it doesn't match). Most people run the default x theme

	$ftp_conn = ftp_connect($config['ftp']['host']) or die("Could not connect to ".$config['ftp']['host']);
	$login = ftp_login($ftp_conn, $config['ftp']['user'], $config['ftp']['pass']);
	@ftp_mkdir($ftp_conn, $config['ftp']['path'].$cpuser);

	// Notification information
	$notifyemail = $config['general']['email']; // Email address to send results

	// Secure or non-secure mode
	$secure = 1; // Set to 1 for SSL (requires SSL support), otherwise will use standard HTTP

	// Set to 1 to have web page result appear in your cron log
	$debug = 0;

	// *********** NO CONFIGURATION ITEMS BELOW THIS LINE *********

	if ($secure) {
	$url = "ssl://".$domain;
	$port = 2083;
	} else {
	$url = $domain;
	$port = 2082;
	}

	$socket = fsockopen($url,$port);
	if (!$socket) { echo "Failed to open socket connection… Bailing out!\n"; exit; }

	// Encode authentication string
	$authstr = $cpuser.":".$cppass;
	$pass = base64_encode($authstr);

	$params = "dest=$ftpmode&email=$notifyemail&server=$ftphost&user=$ftpuser&pass=$ftppass&port=$ftpport&rdir=$rdir&submit=Generate Backup";

	// Make POST to cPanel
	fputs($socket,"POST /frontend/".$skin."/backup/dofullbackup.html?".$params." HTTP/1.0\r\n");
	fputs($socket,"Host: $domain\r\n");
	fputs($socket,"Authorization: Basic $pass\r\n");
	fputs($socket,"Connection: Close\r\n");
	fputs($socket,"\r\n");

	// Grab response even if we don't do anything with it.
	while (!feof($socket)) {
	$response = fgets($socket,4096);
	if ($debug) echo $response;
	}

	fclose($socket);
}

?>
