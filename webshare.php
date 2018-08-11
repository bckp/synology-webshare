<?php

/**
 * Webshare download plugin for Synology download manager
 * @author Radovan Kepak <radovan@kepak.eu>
 */

/**
 * For develop only, on NAS should be ignored
 */
if (!defined('DOWNLOAD_STATION_USER_AGENT')) {
	define('ERR_NOT_SUPPORT_TYPE', 116);
	define('LOGIN_FAIL', 4);
	define('ERR_FILE_NO_EXIST', 114);
	define('DOWNLOAD_URL', 'DOWNLOAD_URL');
	define('DOWNLOAD_ERROR', 'DOWNLOAD_ERROR');
	define('DOWNLOAD_STATION_USER_AGENT', 'Mozilla/4.0 (compatible; MSIE 6.1; Windows XP)');
	define('USER_IS_FREE', 5);
	define('USER_IS_PREMIUM', 6);
}

/**
 * Class SynoFileHostingWebshare
 * @author Radovan Kepak <radovan@kepak.eu>
 */
class SynoFileHostingWebshare {
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
	protected $host;

	/**
	 * SynoFileHostingWebshare constructor.
	 *
	 * @param $url      string
	 * @param $username string
	 * @param $password string
	 * @param $host     string
	 */
	public function __construct($url, $username, $password, $host) {
		$this->url = $url;
		$this->username = $username;
		$this->password = $password;
		$this->host = $host;
	}

	public function GetDownloadInfo() {
		try {
			$ident = $this->getIdent($this->url);

			if (!$ident)
				throw new \Exception('Identifier not found', ERR_NOT_SUPPORT_TYPE);

			if (!$link = $this->getDirectLink($ident))
				throw new \Exception('Link not found', ERR_FILE_NO_EXIST);

			return [DOWNLOAD_URL => $this->getDirectLink($ident)];
		} catch (\Exception $e) {
			return [DOWNLOAD_ERROR => $e->getCode()];
		}
	}

	/**
	 * Get identifier from link
	 *
	 * @param string $url
	 * @return string
	 */
	protected function getIdent($url) {
		if (@preg_match('~^https?://webshare\.cz/(#/)?file/(?P<ident>[^/]+)/(.*)$~i', $url, $matches)) {
			if (isset($matches['ident']))
				return $matches['ident'];
		}
		return false;
	}

	/**
	 * Get link for download
	 *
	 * @param string $ident
	 * @return string|false
	 * @throws \Exception
	 */
	protected function getDirectLink($ident) {
		if (!$salt = $this->getSalt()) {
			throw new \Exception('Salt can`t be loaded', LOGIN_FAIL);
		}

		if (!$token = $this->getToken($salt)) {
			throw new \Exception('User can`t be logged!', LOGIN_FAIL);
		}

		$data = ['wst' => $token, 'ident' => $ident];
		$response = $this->makeRequest('file_link', $data);
		return $response ? $this->getXmlParam($response, 'link') : false;
	}

	/**
	 * Get salt
	 *
	 * @return string|false
	 */
	protected function getSalt() {
		$response = $this->makeRequest('salt', [
			'username_or_email' => $this->username,
		]);

		if ($response) {
			return $this->getXmlParam($response, 'salt');
		}

		return false;
	}

	/**
	 * @param string $action
	 * @param array  $data
	 * @return bool|string
	 */
	protected function makeRequest($action, array $data) {
		$headers = ['Accept' => 'application/json'];
		$url = self::API_URL . "/{$action}/";
		$response = $this->request($url, $headers, $data);
		$status = $this->getXmlParam($response, 'status');
		return $status == 'OK' ? $response : false;
	}

	/**
	 * @param string $url
	 * @param array  $headers
	 * @param array  $data
	 * @return string
	 */
	protected function request($url, array $headers = [], array $data = []) {
		$curl = @curl_init();

		@curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		@curl_setopt($curl, CURLOPT_POST, true);
		@curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		@curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		@curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
		@curl_setopt($curl, CURLOPT_COOKIEFILE, '/tmp/webshare.cookies.l');
		@curl_setopt($curl, CURLOPT_TIMEOUT, 15);
		@curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		@curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		@curl_setopt($curl, CURLOPT_URL, $url);

		$response = @curl_exec($curl);
		@curl_close($curl);

		return $response;
	}

	/**
	 * Get param from XML response
	 *
	 * @param $xml string
	 * @param $key string
	 * @return bool|string
	 */
	protected function getXmlParam($xml, $key) {
		try {
			$data = new \SimpleXMLElement($xml);
			return isset($data->{$key}) ? strval($data->{$key}) : false;
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
	protected function getToken($salt) {
		$response = $this->makeRequest('login', [
			'username_or_email' => $this->username,
			'password' => sha1(crypt($this->password, '$1$' . $salt . '$')),
			'digest' => md5($this->username . ':Webshare:' . $this->password),
		]);

		if ($response) {
			return $this->getXmlParam($response, 'token');
		}

		return false;
	}

	/**
	 * Verify account
	 *
	 * @return int
	 */
	public function Verify() {
		if (!$salt = $this->getSalt())
			return LOGIN_FAIL;

		if (!$token = $this->getToken($salt))
			return LOGIN_FAIL;

		$response = $this->makeRequest('user_data', ['wst' => $token]);
		if ((int) $this->getXmlParam($response, 'vip') === 1)
			return USER_IS_PREMIUM;
		return USER_IS_FREE;
	}
}
