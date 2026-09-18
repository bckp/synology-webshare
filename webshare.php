<?php /** @noinspection SpellCheckingInspection */

/**
 * Class SynoFileHostingWebshare
 *
 * @author Radovan Kepak <radovan@kepak.eu>
 */
class SynoFileHostingWebshare
{
	/**
	 * Webshare API link
	 */
	const API_URL = 'https://webshare.cz/api';

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @var string
	 */
	protected $salt;

	/**
	 * @var string
	 */
	protected $token;

	/**
	 * SynoFileHostingWebshare constructor.
	 *
	 * @param $url      string
	 * @param $username string
	 * @param $password string
	 */
	public function __construct($url, $username, $password)
	{
		$this->url = $url;
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Try to get direct link for file
	 *
	 * @return array
	 */
	public function GetDownloadInfo()
	{
		if ($this->isDirectLink($this->url)) {
			return [DOWNLOAD_URL => $this->url];
		}

		$ident = $this->getIdent($this->url);
		if (!$ident) {
			return [DOWNLOAD_ERROR => ERR_NOT_SUPPORT_TYPE];
		}

		$link = $this->getDirectLink($ident);
		if (!$link) {
			return [DOWNLOAD_ERROR => ERR_FILE_NO_EXIST];
		}

		return [DOWNLOAD_URL => $link];
	}

	/**
	 * Get identifier from link
	 *
	 * @param string $url
	 * @return string|null
	 */
	protected function getIdent($url)
	{
		if (
			@preg_match('~^https?://(?:(?:www|beta)\.)?webshare\.cz(?:/|#|/#|#/|/#/)file/(?P<ident>\w+)(?:/.*)?$~i', trim($url), $matches)
			&& isset($matches['ident'])
		) {
			return $matches['ident'];
		}
		return null;
	}

	/**
	 * Is direct link inserted?
	 *
	 * @param string $url
	 * @return string|null
	 */
	protected function isDirectLink($url)
	{
		if (@preg_match('~^https?://(?:(?:free|vip)\.)?\d+\.dl\.(?:webshare\.cz|wsfiles\.cz)/.+$~i', trim($url))) {
			return $url;
		}
		return null;
	}

	/**
	 * Get link for download
	 *
	 * @param string $ident
	 * @return string|false
	 */
	protected function getDirectLink($ident)
	{
		// Public files can be resolved without an account. This is also useful when
		// Download Station cannot verify an otherwise valid Webshare account.
		$response = $this->makeRequest('file_link', $this->getFileLinkData($ident));
		if ($response && ($link = $this->getXmlParam($response, 'link'))) {
			return $link;
		}

		// A login remains necessary for files that Webshare does not expose publicly.
		if (empty($this->username) || empty($this->password) || !($token = $this->getToken())) {
			return false;
		}
		$data = $this->getFileLinkData($ident);
		$data['wst'] = $token;
		$response = $this->makeRequest('file_link', $data);
		return $response ? $this->getXmlParam($response, 'link') : false;
	}

	/**
	 * Parameters used by the current Webshare web client when creating a file link.
	 *
	 * @param string $ident
	 * @return array
	 */
	protected function getFileLinkData($ident)
	{
		return [
			'ident' => $ident,
			'download_type' => 'file_download',
			'force_https' => 1,
			'device_vendor' => 'Synology',
			'device_model' => 'Download Station',
		];
	}

	/**
	 * Get salt
	 *
	 * @return string|false
	 */
	protected function getSalt()
	{
		if ($this->salt === null) {
			$this->salt = false;
			$response = $this->makeRequest('salt', [
				'username_or_email' => $this->username,
			]);

			if ($response) {
				$this->salt = $this->getXmlParam($response, 'salt');
			}
		}

		return $this->salt;
	}

	/**
	 * @param string $action
	 * @param array $data
	 * @return bool|string
	 */
	protected function makeRequest($action, array $data)
	{
		$headers = [
			'Accept: text/xml; charset=UTF-8',
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
		];
		$url = self::API_URL . "/{$action}/";
		$response = $this->request($url, $headers, $data);
		$status = $this->getXmlParam($response, 'status');
		return $status === 'OK' ? $response : false;
	}

	/**
	 * @param string $url
	 * @param array $headers
	 * @param array $data
	 * @return string
	 */
	protected function request($url, array $headers = [], array $data = [])
	{
		if (!function_exists('curl_init')) {
			return $this->streamRequest($url, $headers, $data);
		}

		$curl = @curl_init();
		if (!$curl) {
			return $this->streamRequest($url, $headers, $data);
		}

		@curl_setopt($curl, CURLOPT_POST, true);
		@curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		@curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		@curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
		@curl_setopt($curl, CURLOPT_TIMEOUT, 15);
		@curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		@curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		@curl_setopt($curl, CURLOPT_URL, $url);

		$response = @curl_exec($curl);
		@curl_close($curl);

		return $response !== false ? $response : $this->streamRequest($url, $headers, $data);
	}

	/**
	 * Fallback for Download Station PHP runtimes where cURL is unavailable or
	 * cannot establish a connection. TLS certificate verification remains enabled.
	 *
	 * @param string $url
	 * @param array $headers
	 * @param array $data
	 * @return bool|string
	 */
	protected function streamRequest($url, array $headers, array $data)
	{
		if (!function_exists('stream_context_create')) {
			return false;
		}

		$context = @stream_context_create([
			'http' => [
				'method' => 'POST',
				'header' => implode("\r\n", $headers),
				'content' => http_build_query($data),
				'timeout' => 15,
				'ignore_errors' => true,
			],
		]);

		return @file_get_contents($url, false, $context);
	}

	/**
	 * Get param from XML response
	 *
	 * @param $xml string
	 * @param $key string
	 * @return bool|string
	 */
	protected function getXmlParamFallback($xml, $key)
	{
		$element = @preg_quote($key, '~');
		if (@preg_match('~<(' . $element . ')>(?P<value>.*?)</(?1)>~i', $xml, $matches)) {
			return (string)$matches['value'];
		}
		return false;
	}

	/**
	 * Get param from XML response
	 *
	 * @param $xml string
	 * @param $key string
	 * @return bool|string
	 */
	protected function getXmlParam($xml, $key)
	{
		if (!class_exists('SimpleXMLElement')) {
			return $this->getXmlParamFallback($xml, $key);
		}
		try {
			$data = new \SimpleXMLElement($xml);
			return isset($data->{$key}) ? (string) $data->{$key} : false;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Get token
	 *
	 * @param string salt
	 * @return null|string
	 */
	protected function getToken()
	{
		if ($this->token === null) {
			$this->token = false;
			if (!$salt = $this->getSalt()) {
				return false;
			}

			$response = $this->makeRequest('login', [
				'username_or_email' => $this->username,
				'password' => sha1(crypt($this->password, '$1$' . $salt . '$')),
				'keep_logged_in' => 0,
			]);

			if ($response) {
				$this->token = $this->getXmlParam($response, 'token');
			}
		}

		return $this->token;
	}

	/**
	 * Verify account
	 *
	 * @return int
	 */
	public function Verify()
	{
		if (!$this->getSalt()) {
			return LOGIN_FAIL;
		}

		if (!$token = $this->getToken()) {
			return LOGIN_FAIL;
		}

		$response = $this->makeRequest('user_data', ['wst' => $token]);
		if ((int)$this->getXmlParam($response, 'vip') === 1) {
			return USER_IS_PREMIUM;
		}
		return USER_IS_FREE;
	}
}
