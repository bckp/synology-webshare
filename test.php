<?php

DEFINE('USER_IS_FREE', 1);
DEFINE('USER_IS_PREMIUM', 2);
DEFINE('LOGIN_FAIL', 0);

DEFINE('DOWNLOAD_URL', 'download_url');
DEFINE('DOWNLOAD_ERROR', 'download_error');

DEFINE('ERR_NOT_SUPPORT_TYPE', 'err_not_support_type');
DEFINE('ERR_FILE_NO_EXIST', 'err_file_no_exists');

DEFINE('DOWNLOAD_STATION_USER_AGENT', 'synology');

$user = getenv('WEBSHARE_USERNAME');
$pass = getenv('WEBSHARE_PASSWORD');
$link = isset($argv[1]) ? $argv[1] : null;

$user = $user === false ? '' : $user;
$pass = $pass === false ? '' : $pass;

if (count($argv) > 2) {
	fwrite(STDERR, 'Credentials must be provided through WEBSHARE_USERNAME and WEBSHARE_PASSWORD.' . PHP_EOL);
	exit(2);
}

if (empty($link)) {
	fwrite(STDERR, 'Usage: php test.php <Webshare URL>' . PHP_EOL);
	exit(2);
}

$msg = [
	'Running test script',
	'-------------------',
	'account: ' . (!empty($user) && !empty($pass) ? 'supplied via environment' : 'not supplied'),
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
if (empty($user) || empty($pass)) {
	echo 'Skipped (no account supplied)' . PHP_EOL;
} else {
	$result = $client->Verify();
	echo $resultMsg[$result] . PHP_EOL;
}

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
