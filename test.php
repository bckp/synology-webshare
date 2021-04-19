<?php

DEFINE('USER_IS_FREE', 1);
DEFINE('USER_IS_PREMIUM', 2);
DEFINE('LOGIN_FAIL', 0);

DEFINE('DOWNLOAD_URL', 'download_url');
DEFINE('DOWNLOAD_ERROR', 'download_error');

DEFINE('ERR_NOT_SUPPORT_TYPE', 'err_not_support_type');
DEFINE('ERR_FILE_NO_EXIST', 'err_file_no_exists');

DEFINE('DOWNLOAD_STATION_USER_AGENT', 'synology');

function getParam($num) {
	global $argv;
	return isset($argv[$num]) ? $argv[$num] : null;
}

$user = getParam(1);
$pass = getParam(2);
$link = getParam(3);

$msg = [
	'Running test script',
	'-------------------',
	"user: {$user}",
	"pass: {$pass}",
	"link: {$link}"
];

echo implode(PHP_EOL, $msg) . PHP_EOL . PHP_EOL;

require 'webshare.php';

# Client
$client = new SynoFileHostingWebshare($link, $user, $pass);

# Login
echo 'Testing login: ';
$resultMsg = [
	0 => 'Failed',
	1 => 'Passed - regular',
	2 => 'Passed - vip'
];
$result = $client->Verify();
echo $resultMsg[$result] . PHP_EOL;

# Link
echo 'Testing link: ';
$result = $client->GetDownloadInfo();
if (isset($result[DOWNLOAD_URL])) {
	echo 'Passed';
} else {
	echo 'Failed - ' . $result[DOWNLOAD_ERROR];
}

echo PHP_EOL . PHP_EOL;

# All good
echo 'Done' . PHP_EOL;
