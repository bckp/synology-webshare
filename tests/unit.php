<?php

define('USER_IS_FREE', 1);
define('USER_IS_PREMIUM', 2);
define('LOGIN_FAIL', 0);

define('DOWNLOAD_URL', 'download_url');
define('DOWNLOAD_ERROR', 'download_error');

define('ERR_NOT_SUPPORT_TYPE', 'err_not_support_type');
define('ERR_FILE_NO_EXIST', 'err_file_no_exists');

define('DOWNLOAD_STATION_USER_AGENT', 'synology');

require dirname(__DIR__) . '/webshare.php';

class TestWebshareClient extends SynoFileHostingWebshare
{
	public $requests = [];
	private $responses;

	public function __construct($url, $username, $password, array $responses)
	{
		parent::__construct($url, $username, $password);
		$this->responses = $responses;
	}

	protected function request($url, array $headers = [], array $data = [])
	{
		preg_match('~/api/(?P<action>[^/]+)/$~', $url, $matches);
		$action = $matches['action'];
		$this->requests[] = ['action' => $action, 'data' => $data];

		if (empty($this->responses[$action])) {
			return false;
		}
		return array_shift($this->responses[$action]);
	}
}

function response($status, array $values = [])
{
	$xml = '<response><status>' . $status . '</status>';
	foreach ($values as $key => $value) {
		$xml .= '<' . $key . '>' . htmlspecialchars($value, ENT_XML1) . '</' . $key . '>';
	}
	return $xml . '</response>';
}

function assertSameValue($expected, $actual, $description)
{
	if ($expected !== $actual) {
		throw new Exception($description . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
	}
}

$publicLink = 'https://free.19.dl.wsfiles.cz/1/abc/1/token/file.mkv';
$shareUrl = 'https://webshare.cz/#/file/Abc123/';

$tests = [
	'direct wsfiles URL is returned unchanged' => function () use ($publicLink) {
		$client = new TestWebshareClient($publicLink, '', '', []);
		assertSameValue([DOWNLOAD_URL => $publicLink], $client->GetDownloadInfo(), 'Direct URL result');
		assertSameValue([], $client->requests, 'Direct URL must not call the API');
	},
	'public Webshare link returns a forced HTTPS download URL' => function () use ($shareUrl, $publicLink) {
		$client = new TestWebshareClient($shareUrl, '', '', [
			'file_link' => [response('OK', ['link' => $publicLink])],
		]);
		assertSameValue([DOWNLOAD_URL => $publicLink], $client->GetDownloadInfo(), 'Public file result');
		assertSameValue('file_download', $client->requests[0]['data']['download_type'], 'Download type');
		assertSameValue(1, $client->requests[0]['data']['force_https'], 'HTTPS flag');
	},
	'unsupported URL reports the Download Station error' => function () {
		$client = new TestWebshareClient('https://example.com/file/Abc123', '', '', []);
		assertSameValue([DOWNLOAD_ERROR => ERR_NOT_SUPPORT_TYPE], $client->GetDownloadInfo(), 'Unsupported URL result');
	},
	'temporarily unavailable public file reports file unavailable' => function () use ($shareUrl) {
		$client = new TestWebshareClient($shareUrl, '', '', [
			'file_link' => [response('FATAL', ['code' => 'FILE_LINK_FATAL_4'])],
		]);
		assertSameValue([DOWNLOAD_ERROR => ERR_FILE_NO_EXIST], $client->GetDownloadInfo(), 'Unavailable file result');
	},
	'account fallback retries file link with the API token' => function () use ($shareUrl, $publicLink) {
		$client = new TestWebshareClient($shareUrl, 'test@example.com', 'secret', [
			'file_link' => [
				response('FATAL', ['code' => 'FILE_LINK_FATAL_6']),
				response('OK', ['link' => $publicLink]),
			],
			'salt' => [response('OK', ['salt' => 'testsalt'])],
			'login' => [response('OK', ['token' => 'test-token'])],
		]);
		assertSameValue([DOWNLOAD_URL => $publicLink], $client->GetDownloadInfo(), 'Authenticated fallback result');
		assertSameValue('test-token', $client->requests[3]['data']['wst'], 'Authenticated request token');
	},
];

$passed = 0;
foreach ($tests as $name => $test) {
	$test();
	$passed++;
	echo "PASS: {$name}" . PHP_EOL;
}

echo PHP_EOL . "{$passed} tests passed" . PHP_EOL;
