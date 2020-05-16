<?php /** @noinspection SpellCheckingInspection */

/**
 * Class SynoFileHostingWebshare
 *
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
	public function __construct($url, $username, $password) {
		$this->url = $url;
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Try to get direct link for file
	 *
	 * @return array
	 */
	public function GetDownloadInfo() {
		try {
			if ($this->isDirectLink($this->url)) {
				$link = $this->url;
			} else {
				$ident = $this->getIdent($this->url);

				if (!$ident)
					throw new \Exception('Identifier not found', ERR_NOT_SUPPORT_TYPE);

				if (!$link = $this->getDirectLink($ident))
					throw new \Exception('Link not found', ERR_FILE_NO_EXIST);
			}

			return [DOWNLOAD_URL => $link];
		} catch (\Exception $e) {
			return [DOWNLOAD_ERROR => $e->getCode()];
		}
	}

	/**
	 * Get identifier from link
	 *
	 * @param string $url
	 * @return string|null
	 */
	protected function getIdent($url) {
		if (@preg_match('~^https?://(?:beta\.)?webshare\.cz(?:/|#|/#|#/|/#/)file/(?P<ident>\w+)(?:/.*)?$~i', trim($url), $matches)) {
			if (isset($matches['ident']))
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
	protected function isDirectLink($url) {
		if(@preg_match('~^https?://vip\.\d+\.dl\.webshare\.cz/.*$~i', trim($url))){
			return $url;
		}
		return null;
	}

	/**
	 * Get link for download
	 *
	 * @param string $ident
	 * @return string|false
	 * @throws \Exception
	 */
	protected function getDirectLink($ident) {
		if (!$this->getSalt()) {
			throw new \Exception('Salt can`t be loaded', LOGIN_FAIL);
		}

		if (!$token = $this->getToken()) {
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
	protected function makeRequest($action, array $data) {
		$headers = ['Accept' => 'application/json'];
		$url = self::API_URL . "/{$action}/";
		$response = $this->request($url, $headers, $data);
		$status = $this->getXmlParam($response, 'status');
		return $status == 'OK' ? $response : false;
	}

	/**
	 * @param string $url
	 * @param array $headers
	 * @param array $data
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
	protected function getToken() {
		if ($this->token === null) {
			$this->token = false;
			if (!$salt = $this->getSalt())
				return false;

			$response = $this->makeRequest('login', [
				'username_or_email' => $this->username,
				'password' => sha1(crypt($this->password, '$1$' . $salt . '$')),
				'digest' => md5($this->username . ':Webshare:' . $this->password),
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
	public function Verify() {
		if (!$this->getSalt())
			return LOGIN_FAIL;

		if (!$token = $this->getToken())
			return LOGIN_FAIL;

		$response = $this->makeRequest('user_data', ['wst' => $token]);
		if ((int) $this->getXmlParam($response, 'vip') === 1)
			return USER_IS_PREMIUM;
		return USER_IS_FREE;
	}
}
