<?php
if (defined('WFWAF_VERSION') && !defined('WFWAF_RUN_COMPLETE')) {

interface wfWAFRequestInterface {

	public function getBody();
	
	public function getRawBody();
	
	public function getMd5Body();

	public function getJsonBody();

	public function getQueryString();
	
	public function getMd5QueryString();

	public function getHeaders();

	public function getCookies();

	public function getFiles();

	public function getFileNames();

	public function getHost();

	public function getURI();
	
	public function setMetadata($metadata);
	public function getMetadata();

	public function getPath();

	public function getIP();

	public function getMethod();

	public function getProtocol();

	public function getAuth();

	public function getTimestamp();

	public function __toString();

}

abstract class wfCookieRedactor {

	const REDACTION_MESSAGE = '[redacted]';

	public abstract function redact(&$name, &$value);

	public static function load() {
		$patterns = null;
		$waf = wfWAF::getInstance();
		if ($waf !== null) {
			$patterns = $waf->getCookieRedactionPatterns();
		}
		if ($patterns === null) {
			return new wfGlobalCookieRedactor();
		}
		else {
			return new wfPatternCookieRedactor($waf->getCookieRedactionPatterns());
		}
	}

	public static function loadFromWaf() {
		return new self($patterns);
	}

	public static function getEncodedRedactionMessage() {
		static $encoded = null;
		if ($encoded === null)
			$encoded = urlencode(self::REDACTION_MESSAGE);
		return $encoded;
	}

}

class wfGlobalCookieRedactor extends wfCookieRedactor {

	public function redact(&$name, &$value) {
		$name = self::getEncodedRedactionMessage();
		$value = self::REDACTION_MESSAGE;
	}

}

class wfPatternCookieRedactor extends wfCookieRedactor {

	private $patterns;

	public function __construct($patterns) {
		$this->patterns = $patterns;
	}

	private static function replaceName($matches) {
		if (count($matches) < 2)
			return self::getEncodedRedactionMessage();
		$name = $matches[0][0];
		$redacted = array();
		$position = 0;
		for ($i = 1; $i < count($matches); $i++) {
			$retained = $matches[$i][0];
			$retainedStart = $matches[$i][1];
			$retainedLength = strlen($retained);
			if ($retainedStart > $position)
				$redacted[] = self::getEncodedRedactionMessage();
			$redacted[] = $retained;
			$position = $retainedStart + $retainedLength;
		}
		if ($position < strlen($name))
			$redacted []= self::getEncodedRedactionMessage();
		return implode('', $redacted);
	}

	/**
	 * TODO: Remove this fallback support for PHP versions earlier than 7.4 is no longer required
	 */
	private static function replaceNameFallback($matches) {
		$completeMatch = array_shift($matches);
		$completeRetained = implode('', $matches);
		if ($completeMatch === $completeRetained)
			return $completeRetained;
		$matches[] = '';
		return implode(self::getEncodedRedactionMessage(), $matches);
	}

	public function redact(&$name, &$value) {
		$pregOffsetCaptureSupported = version_compare(PHP_VERSION, '7.4.0', '>=');
		$nameCallback = array($this, $pregOffsetCaptureSupported ? 'replaceName' : 'replaceNameFallback');
		foreach ($this->patterns as $namePattern => $valuePatterns) {
			if ($pregOffsetCaptureSupported) {
				$nameRedacted = preg_replace_callback($namePattern, $nameCallback, $name, 1, $matchCount, PREG_OFFSET_CAPTURE);
			}
			else {
				$nameRedacted = preg_replace_callback($namePattern, $nameCallback, $name, 1, $matchCount);
			}
			if ($matchCount === 1 && $nameRedacted !== null) {
				$name = $nameRedacted;
				if ($valuePatterns === null)
					return;
				if (is_string($valuePatterns))
					$valuePatterns = array($valuePatterns);
				if (is_array($valuePatterns)) {
					$valueMatched = false;
					foreach ($valuePatterns as $valuePattern) {
						if (preg_match($valuePattern, $value) === 1) {
							$valueMatched = true;
							break;
						}
					}
					if (!$valueMatched)
						return;
				}
				$value = self::REDACTION_MESSAGE;
				break;
			}
		}
	}

}

class wfWAFRequest implements wfWAFRequestInterface {

	/**
	 * @param string $requestString
	 * @return wfWAFRequest
	 */
	public static function parseString($requestString) {
		if (!is_string($requestString)) {
			throw new InvalidArgumentException(__METHOD__ . ' expects a string for first parameter, recieved ' . gettype($requestString));
		}

		if (version_compare(phpversion(), '5.3.0') > 0) {
			$class = get_called_class();
			$request = new $class();
		} else {
			$request = new self();
		}

		$request->setAuth(array());
		$request->setBody(array());
		$request->setMd5Body(array());
		$request->setCookies(array());
		$request->setFileNames(array());
		$request->setFiles(array());
		$request->setHeaders(array());
		$request->setHost('');
		$request->setIP('');
		$request->setMethod('');
		$request->setPath('');
		$request->setProtocol('');
		$request->setQueryString(array());
		$request->setMd5QueryString(array());
		$request->setTimestamp('');
		$request->setURI('');
		$request->setMetadata(array());

		list($headersString, $bodyString) = explode("\n\n", $requestString, 2);
		$headersString = trim($headersString);
		$bodyString = trim($bodyString);
		
		if (defined('WFWAF_DISABLE_RAW_BODY') && WFWAF_DISABLE_RAW_BODY) {
			$request->setRawBody('');
		}
		else {
			$request->setRawBody($bodyString);
		}
		
		$headers = explode("\n", $headersString);
		// Assume first is method
		if (preg_match('/^([a-z]+) (.*?) HTTP\/1.[0-9]/i', $headers[0], $matches)) {
			$request->setMethod($matches[1]);
			$uri = $matches[2];
			$request->setUri($uri);
			if (($pos = wfWAFUtils::strpos($uri, '?')) !== false) {
				$queryString = wfWAFUtils::substr($uri, $pos + 1);
				parse_str($queryString, $queryStringArray);
				$request->setQueryString($queryStringArray);

				$path = wfWAFUtils::substr($uri, 0, $pos);
				$request->setPath($path);
			} else {
				$request->setPath($uri);
			}
		}
		$kvHeaders = array();
		for ($i = 1; $i < count($headers); $i++) {
			$headerString = $headers[$i];
			list($header, $headerValue) = explode(':', $headerString, 2);
			$header = trim($header);
			$headerValue = trim($headerValue);
			$kvHeaders[$header] = $headerValue;

			switch (wfWAFUtils::strtolower($header)) {
				case 'authorization':
					if (preg_match('/basic ([A-Za-z0-9\+\/=]+)/i', $headerValue, $matches)) {
						list($authUser, $authPass) = explode(':', base64_decode($matches[1]), 2);
						$auth['user'] = $authUser;
						$auth['password'] = $authPass;
						$request->setAuth($auth);
					}
					break;

				case 'host':
					$request->setHost($headerValue);
					break;

				case 'cookie':
					$cookieArray = array();
					$cookies = str_replace('&', '%26', $headerValue);
					$cookies = preg_replace('/\s*;\s*/', '&', $cookies);
					parse_str($cookies, $cookieArray);
					$request->setCookies($cookieArray);
					break;
			}

		}
		$request->setHeaders($kvHeaders);

		if (wfWAFUtils::strlen($bodyString) > 0) {
			if (preg_match('/^multipart\/form\-data; boundary=(.*?)$/i', $request->getHeaders('Content-Type'), $boundaryMatches)) {
				$body = '';
				$files = array();
				$fileNames = array();

				$boundary = $boundaryMatches[1];
				$bodyChunks = explode("--$boundary", $bodyString);
				foreach ($bodyChunks as $chunk) {
					if (!$chunk || $chunk == '--') {
						continue;
					}

					list($chunkHeaders, $chunkData) = explode("\n\n", $chunk, 2);
					$chunkHeaders = explode("\n", $chunkHeaders);
					$param = array(
						'value' => wfWAFUtils::substr($chunkData, 0, -1),
					);
					foreach ($chunkHeaders as $chunkHeader) {
						if (wfWAFUtils::strpos($chunkHeader, ':') !== false) {
							list($chunkHeaderKey, $chunkHeaderValue) = explode(':', $chunkHeader, 2);
							$chunkHeaderKey = trim($chunkHeaderKey);
							$chunkHeaderValue = trim($chunkHeaderValue);
							switch ($chunkHeaderKey) {
								case 'Content-Disposition':
									$dataAttributes = explode(';', $chunkHeaderValue);
									foreach ($dataAttributes as $attr) {
										$attr = trim($attr);
										if (preg_match('/^name="(.*?)"$/i', $attr, $attrMatch)) {
											$param['name'] = $attrMatch[1];
											continue;
										}
										if (preg_match('/^filename="(.*?)"$/i', $attr, $attrMatch)) {
											$param['filename'] = $attrMatch[1];
											continue;
										}
									}
									break;
								case 'Content-Type':
									$param['type'] = $chunkHeaderValue;
									break;
							}
						}
					}
					if (array_key_exists('name', $param)) {
						if (array_key_exists('filename', $param)) {
							$files[$param['name']] = array(
								'name'    => $param['filename'],
								'type'    => $param['type'],
								'size'    => wfWAFUtils::strlen($param['value']),
								'content' => $param['value'],
							);
							$fileNames[$param['name']] = $param['filename'];
						} else {
							$body .= urlencode($param['name']) . '=' . urlencode($param['value']) . '&';
						}
					}
				}

				if ($body) {
					parse_str($body, $postBody);
					if (is_array($postBody)) {
						$request->setBody($postBody);
					} else {
						$request->setBody($body);
					}
				}
				if ($files) {
					$request->setFiles($files);
				}
				if ($fileNames) {
					$request->setFileNames($fileNames);
				}

			} else {
				parse_str($bodyString, $postBody);
				if (is_array($postBody)) {
					$request->setBody($postBody);
				} else {
					$request->setBody($bodyString);
				}
			}
		}

		return $request;
	}

	private static function extractFileProperty($key, $property) {
		$extracted = array();
		if (is_array($property)) {
			foreach ($property as $nestedKey => $value) {
				$nestedKey = "{$key}[" . var_export($nestedKey, true) . ']';
				foreach (self::extractFileProperty($nestedKey, $value) as $nested) {
					$extracted[] = $nested;
				}
			}
		}
		else if (is_string($property) || is_int($property)) {
			$extracted[] = array(
				$key,
				$property
			);
		}
		return $extracted;
	}

	private static function flattenFiles($files) {
		$flat = array();
		foreach ($files as $baseKey => $file) {
			foreach ($file as $property => $value) {
				foreach (self::extractFileProperty($baseKey, $value) as $extracted) {
					list($finalKey, $finalValue) = $extracted;
					if (!array_key_exists($finalKey, $flat))
						$flat[$finalKey] = array();
					$flat[$finalKey][$property] = $finalValue;
				}
			}
		}
		return $flat;
	}

	/**
	 * @param wfWAFRequest|null $request
	 * @return wfWAFRequest
	 */
	public static function createFromGlobals($request = null) {
		if ($request === null) {
			if (version_compare(phpversion(), '5.3.0') > 0) {
				$class = get_called_class();
				$request = new $class();
			} else {
				$request = new self();
			}
		}

		$request->setAuth(array());
		$request->setCookies(array());
		$request->setFileNames(array());
		$request->setFiles(array());
		$request->setHeaders(array());
		$request->setHost('');
		$request->setIP('');
		$request->setMethod('');
		$request->setPath('');
		$request->setProtocol('');
		$request->setTimestamp('');
		$request->setURI('');
		$request->setMetadata(array());

		$request->setBody(wfWAFUtils::stripMagicQuotes($_POST));
		if (defined('WFWAF_DISABLE_RAW_BODY') && WFWAF_DISABLE_RAW_BODY) {
			$request->setRawBody('');
		}
		else {
			$rawBody=wfWAFUtils::rawPOSTBody();
			$request->setRawBody($rawBody);
		}
		
		$request->setQueryString(wfWAFUtils::stripMagicQuotes($_GET));
		$request->setCookies(wfWAFUtils::stripMagicQuotes($_COOKIE));
		$request->setFiles(wfWAFUtils::stripMagicQuotes(self::flattenFiles($_FILES)));

		if (!empty($_FILES)) {
			$fileNames = array();
			foreach ($_FILES as $input => $file) {
				$fileNames[$input] = wfWAFUtils::stripMagicQuotes($file['name']);
			}
			$request->setFileNames($fileNames);
		}

		if (is_array($_SERVER)) { //All of these depend on $_SERVER being non-null and an array
			$auth = array();
			if (array_key_exists('PHP_AUTH_USER', $_SERVER)) {
				$auth['user'] = wfWAFUtils::stripMagicQuotes($_SERVER['PHP_AUTH_USER']);
			}
			if (array_key_exists('PHP_AUTH_PW', $_SERVER)) {
				$auth['password'] = wfWAFUtils::stripMagicQuotes($_SERVER['PHP_AUTH_PW']);
			}
			$request->setAuth($auth);

			if (array_key_exists('REQUEST_TIME_FLOAT', $_SERVER)) {
				$timestamp = $_SERVER['REQUEST_TIME_FLOAT'];
			} else if (array_key_exists('REQUEST_TIME', $_SERVER)) {
				$timestamp = $_SERVER['REQUEST_TIME'];
			} else {
				$timestamp = time();
			}
			$request->setTimestamp($timestamp);

			$headers = array();
			foreach ($_SERVER as $key => $value) {
				if (wfWAFUtils::strpos($key, 'HTTP_') === 0) {
					$header = wfWAFUtils::substr($key, 5);
					$header = str_replace(array(' ', '_'), array('', ' '), $header);
					$header = ucwords(wfWAFUtils::strtolower($header));
					$header = str_replace(' ', '-', $header);
					$headers[$header] = wfWAFUtils::stripMagicQuotes($value);
				}
			}
			if (array_key_exists('CONTENT_TYPE', $_SERVER)) {
				$headers['Content-Type'] = wfWAFUtils::stripMagicQuotes($_SERVER['CONTENT_TYPE']);
			}
			if (array_key_exists('CONTENT_LENGTH', $_SERVER)) {
				$headers['Content-Length'] = wfWAFUtils::stripMagicQuotes($_SERVER['CONTENT_LENGTH']);
			}
			$request->setHeaders($headers);

			$host = '';
			if (array_key_exists('Host', $headers)) {
				$host = $headers['Host'];
			} else if (array_key_exists('SERVER_NAME', $_SERVER)) {
				$host = wfWAFUtils::stripMagicQuotes($_SERVER['SERVER_NAME']);
			}
			$request->setHost($host);

			$request->setMethod(array_key_exists('REQUEST_METHOD', $_SERVER) ? wfWAFUtils::stripMagicQuotes($_SERVER['REQUEST_METHOD']) : 'GET');
			$request->setProtocol((array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
			$request->setUri(array_key_exists('REQUEST_URI', $_SERVER) ? wfWAFUtils::stripMagicQuotes($_SERVER['REQUEST_URI']) : '');

			$uri = parse_url($request->getURI());
			if (is_array($uri) && array_key_exists('path', $uri)) {
				$path = $uri['path'];
			} else {
				$path = $request->getURI();
			}
			$request->setPath($path);
		}

		return $request;
	}

	private $auth;
	private $body;
	private $rawBody;
	private $md5Body;
	private $jsonBody;
	private $jsonParsed = false;
	private $cookies;
	private $fileNames;
	private $files;
	private $headers;
	private $host;
	private $ip;
	private $method;
	private $path;
	private $protocol;
	private $queryString;
	private $md5QueryString;
	private $timestamp;
	private $uri;
	private $metadata;

	private $highlightParamFormat;
	private $highlightMatchFormat;
	private $highlightMatches;
	private $highlightMatchFilter = 'urlencode';


	protected function _arrayValueByKeys($global, $key) {
		if (is_array($global)) {
			if (is_array($key)) {
				$_key = array_shift($key);
				if (array_key_exists($_key, $global)) {
					if (count($key) > 0) {
						return $this->_arrayValueByKeys($global[$_key], $key);
					} else {
						return $global[$_key];
					}
				}
			} else {
				return array_key_exists($key, $global) ? $global[$key] : null;
			}
		}
		return null;
	}

	public function getBody() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->_arrayValueByKeys($this->body, $args);
		}
		return $this->body;
	}
	
	public function getRawBody() {
		return $this->rawBody;
	}
	
	public function getMd5Body() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->_arrayValueByKeys($this->md5Body, $args);
		}
		return $this->md5Body;
	}

	public function getJsonBody() {
		if ($this->jsonParsed === false) {
			if (defined('WFWAF_DISABLE_RAW_BODY') && WFWAF_DISABLE_RAW_BODY) {
				$this->setJsonBody(null);
			}
			else {
				$this->setJsonBody(wfWAFUtils::json_decode($this->getRawBody(), true));
			}
		}
		return $this->jsonBody;
	}

	public function getQueryString() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->_arrayValueByKeys($this->queryString, $args);
		}
		return $this->queryString;
	}
	
	public function getMd5QueryString() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->_arrayValueByKeys($this->md5QueryString, $args);
		}
		return $this->md5QueryString;
	}

	public function getHeaders() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->_arrayValueByKeys($this->headers, $args);
		}
		return $this->headers;
	}

	public function getCookies() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->_arrayValueByKeys($this->cookies, $args);
		}
		return $this->cookies;
	}

	/*
	 * Formats the provided cookie array (or $this->getCookies() if null) into a string
	 * and preserves arrays.
	 *
	 * The format is in "cookie1=value; cookie2=value, ..."
	 *
	 * @param array|null $cookies
	 * @param string|null $baseKey The base key used when recursing.
	 * @return string
	 */
	public function getCookieString($cookies = null, $baseKey = null, $preventRedaction = false, $redactor = null) {
		if ($cookies == null) {
			$cookies = $this->getCookies();
		}
		$isAssoc = (array_keys($cookies) !== range(0, count($cookies) - 1));
		$cookieString = '';
		if ($redactor === null)
			$redactor = wfCookieRedactor::load();
		foreach ($cookies as $cookieName => $cookieValue) {
			$resolvedName = $cookieName;
			if ($baseKey !== null) {
				if ($isAssoc) {
					$resolvedName = $baseKey . '[' . $cookieName . ']';
				}
				else {
					$resolvedName = $baseKey . '[]';
				}
			}

			if (is_array($cookieValue)) {
				$nestedCookies = $this->getCookieString($cookieValue, $resolvedName);
				$cookieString .= $nestedCookies;
			}
			else {
				if (!$preventRedaction)
					$redactor->redact($resolvedName, $cookieValue);
				
				$cookieString .= $resolvedName . '=' . urlencode($cookieValue) . '; ';
			}
		}
		return $cookieString;
	}

	public function getFiles() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->_arrayValueByKeys($this->files, $args);
		}
		return $this->files;
	}

	public function getFileNames() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->_arrayValueByKeys($this->fileNames, $args);
		}
		return $this->fileNames;
	}

	public function getHost() {
		return $this->host;
	}

	public function getURI() {
		return $this->uri;
	}
	
	public function getMetadata() {
		if (func_num_args() > 0) {
			$args = func_get_args();
			return $this->_arrayValueByKeys($this->metadata, $args);
		}
		return $this->metadata;
	}

	public function getPath() {
		return $this->path;
	}

	public function getIP() {
		return $this->ip;
	}

	public function getMethod() {
		return $this->method;
	}

	public function getProtocol() {
		return $this->protocol;
	}

	public function getAuth($arg1 = null) {
		if ($arg1) {
			if (is_array($this->auth) && array_key_exists($arg1, $this->auth)) {
				return $this->auth[$arg1];
			}
			return null;
		}
		return $this->auth;
	}

	public function getTimestamp() {
		return $this->timestamp;
	}

	public function __toString() {
		return $this->highlightFailedParams();
	}

	/**
	 * @param array $failedParams
	 * @param string $highlightParamFormat
	 * @param string $highlightMatchFormat
	 * @return string
	 */
	public function highlightFailedParams($failedParams = array(), $highlightParamFormat = '[param]%s[/param]',
	                                      $highlightMatchFormat = '[match]%s[/match]', $preventRedaction = false) {
		$highlights = array();

		// Cap at 47.5kb
		$maxRequestLen = 1024 * 47.5;

		$this->highlightParamFormat = $highlightParamFormat;
		$this->highlightMatchFormat = $highlightMatchFormat;

		if (is_array($failedParams)) {
			foreach ($failedParams as $paramKey => $categories) {
				foreach ($categories as $categoryKey => $failedRules) {
					foreach ($failedRules as $failedRule) {
						$rule = $failedRule['rule'];
						/** @var wfWAFRuleComparisonFailure $failedComparison */
						$failedComparison = $failedRule['failedComparison'];
						$action = $failedRule['action'];

						$paramKey = $failedComparison->getParamKey();
						if (preg_match('/request\.([a-z0-9]+)(?:\[(.*?)\](.*?))?$/i', $paramKey, $matches)) {
							$global = $matches[1];
							if (method_exists('wfWAFRequestInterface', "get" . ucfirst($global))) {
								$highlight = array(
									'match' => $failedComparison->getMatches(),
								);
								if (isset($matches[2])) {
									$highlight['param'] = "$matches[2]$matches[3]";
								}
								$highlights[$global][] = $highlight;
							}
						}
					}
				}
			}
		}

		$uri = $this->getURI();
		$queryStringPos = wfWAFUtils::strpos($uri, '?');
		if ($queryStringPos !== false) {
			$uri = wfWAFUtils::substr($uri, 0, $queryStringPos);
		}
		$queryString = $this->getQueryString();
		if ($queryString) {
			$uri .= '?' . http_build_query($queryString, '', '&');
		}
		if (!empty($highlights['queryString'])) {
			foreach ($highlights['queryString'] as $matches) {
				if (!empty($matches['param'])) {
					$this->highlightMatches = $matches['match'];
					$uri = preg_replace_callback('/(&|\?|^)(' . preg_quote(urlencode($matches['param']), '/') . ')=(.*?)(&|$)/', array(
						$this, 'highlightParam',
					), $uri);
				}
			}
		}

		if (!empty($highlights['uri'])) {
			foreach ($highlights['uri'] as $matches) {
				if ($matches) {

				}
			}
			$uri = sprintf($highlightParamFormat, $uri);
		}

		$request = "{$this->getMethod()} $uri HTTP/1.1\n";
		$hasAuth = false;
		$auth = $this->getAuth();

		if (is_array($this->getHeaders())) {
			foreach ($this->getHeaders() as $header => $value) {
				switch (wfWAFUtils::strtolower($header)) {
					case 'cookie':
						// TODO: Hook up highlights to cookies
						$request .= 'Cookie: ' . trim($this->getCookieString(null, null, $preventRedaction)) . "\n";
						break;

					case 'host':
						$request .= 'Host: ' . $this->getHost() . "\n";
						break;

					case 'authorization':
						$hasAuth = true;
						if ($auth) {
							$request .= 'Authorization: Basic ' . ($preventRedaction ? base64_encode($auth['user'] . ':' . $auth['password']) : '[redacted]') . "\n";
						}
						break;

					default:
						$request .= $header . ': ' . $value . "\n";
						break;
				}
			}
		}

		if (!$hasAuth && $auth) {
			$request .= 'Authorization: Basic ' . ($preventRedaction ? base64_encode($auth['user'] . ':' . $auth['password']) : '[redacted]') . "\n";
		}
		
		$bareRequestURI = wfWAFUtils::extractBareURI($this->getURI());
		$isAuthRequest = (strpos($bareRequestURI, '/wp-login.php') !== false);
		$isXMLRPC = (strpos($bareRequestURI, '/xmlrpc.php') !== false);
		$xmlrpcFieldMap = array(
			'wp.getUsersBlogs' => array(0, 1),
			'wp.newPost' => array(1, 2),
			'wp.editPost' => array(1, 2),
			'wp.deletePost' => array(1, 2),
			'wp.getPost' => array(1, 2),
			'wp.getPosts' => array(1, 2),
			'wp.newTerm' => array(1, 2),
			'wp.editTerm' => array(1, 2),
			'wp.deleteTerm' => array(1, 2),
			'wp.getTerm' => array(1, 2),
			'wp.getTerms' => array(1, 2),
			'wp.getTaxonomy' => array(1, 2),
			'wp.getTaxonomies' => array(1, 2),
			'wp.getUser' => array(1, 2),
			'wp.getUsers' => array(1, 2),
			'wp.getProfile' => array(1, 2),
			'wp.editProfile' => array(1, 2),
			'wp.getPage' => array(2, 3),
			'wp.getPages' => array(1, 2),
			'wp.newPage' => array(1, 2),
			'wp.deletePage' => array(1, 2),
			'wp.editPage' => array(2, 3),
			'wp.getPageList' => array(1, 2),
			'wp.getAuthors' => array(1, 2),
			'wp.getTags' => array(1, 2),
			'wp.newCategory' => array(1, 2),
			'wp.deleteCategory' => array(1, 2),
			'wp.suggestCategories' => array(1, 2),
			'wp.getComment' => array(1, 2),
			'wp.getComments' => array(1, 2),
			'wp.deleteComment' => array(1, 2),
			'wp.editComment' => array(1, 2),
			'wp.newComment' => array(1, 2),
			'wp.getCommentStatusList' => array(1, 2),
			'wp.getCommentCount' => array(1, 2),
			'wp.getPostStatusList' => array(1, 2),
			'wp.getPageStatusList' => array(1, 2),
			'wp.getPageTemplates' => array(1, 2),
			'wp.getMediaItem' => array(1, 2),
			'wp.getMediaLibrary' => array(1, 2),
			'wp.getPostFormats' => array(1, 2),
			'wp.getPostType' => array(1, 2),
			'wp.getPostTypes' => array(1, 2),
			'wp.getRevisions' => array(1, 2),
			'wp.restoreRevision' => array(1, 2),
			'blogger.getUsersBlogs' => array(1, 2),
			'blogger.getUserInfo' => array(1, 2),
			'blogger.getPost' => array(2, 3),
			'blogger.getRecentPosts' => array(2, 3),
			'blogger.newPost' => array(2, 3),
			'blogger.editPost' => array(2, 3),
			'blogger.deletePost' => array(2, 3),
			'metaWeblog.newPost' => array(1, 2),
			'metaWeblog.editPost' => array(1, 2),
			'metaWeblog.getPost' => array(1, 2),
			'metaWeblog.getRecentPosts' => array(1, 2),
			'metaWeblog.getCategories' => array(1, 2),
			'metaWeblog.newMediaObject' => array(1, 2),
			'mt.getRecentPostTitles' => array(1, 2),
			'mt.getCategoryList' => array(1, 2),
			'mt.getPostCategories' => array(1, 2),
			'mt.setPostCategories' => array(1, 2),
			'mt.publishPost' => array(1, 2),
		);

		$body = $this->getBody();
		$rawBody = $this->getRawBody();
		$contentType = $this->getHeaders('Content-Type');
		if (wfXMLRPCBody::canParse() && $isXMLRPC && is_string($rawBody) && !$preventRedaction) {
			$xml =& $rawBody;
			if ($contentType == 'application/x-www-form-urlencoded') {
				$xml = @urldecode($rawBody);
			}
			
			$xmlrpc = new wfXMLRPCBody($xml);
			if ($xmlrpc->parse() && $xmlrpc->messageType == 'methodCall') {
				if ($xmlrpc->methodName == 'system.multicall') {
					$subCalls =& $xmlrpc->params[0]['value'];
					if (is_array($subCalls)) {
						foreach ($subCalls as &$call) {
							$method = $call['value']['methodName']['value'];
							$params =& $call['value']['params']['value'];
							
							if (isset($xmlrpcFieldMap[$method])) {
								$fieldIndexes = $xmlrpcFieldMap[$method];
								foreach ($fieldIndexes as $i) {
									if (isset($params[$i])) {
										$params[$i]['value'] = '[redacted]';
									}
								}
							}
						}
					}
				}
				else {
					if (isset($xmlrpcFieldMap[$xmlrpc->methodName])) {
						$params =& $xmlrpc->params;
						$fieldIndexes = $xmlrpcFieldMap[$xmlrpc->methodName];
						foreach ($fieldIndexes as $i) {
							if (isset($params[$i])) {
								$params[$i]['value'] = '[redacted]';
							}
						}
					}
				}
				
				$xml = (string) $xmlrpc;
				if ($contentType == 'application/x-www-form-urlencoded') {
					$body = urlencode($xml);
				}
			}
		}
		else if (is_array($body)) {
			foreach ($body as $bkey => &$bvalue) {
				if (!$preventRedaction && $isAuthRequest && ($bkey == 'log' || $bkey == 'pwd' || $bkey == 'user_login' || $bkey == 'user_email' || $bkey == 'pass1' || $bkey == 'pass2' || $bkey == 'rp_key')) {
					$bvalue = '[redacted]';
				}
			}
			
			if (preg_match('/^multipart\/form\-data;(?:\s*(?!boundary)(?:[^\x00-\x20\(\)<>@,;:\\"\/\[\]\?\.=]+)=[^;]+;)*\s*boundary=([^;]*)(?:;\s*(?:[^\x00-\x20\(\)<>@,;:\\"\/\[\]\?\.=]+)=[^;]+)*$/i', (string) $contentType, $boundaryMatches)) {
				$boundary = $boundaryMatches[1];
				$bodyArray = array();
				foreach ($body as $key => $value) {
					$bodyArray = array_merge($bodyArray, $this->reduceBodyParameter($key, $value));
				}
				
				$body = '';
				foreach ($bodyArray as $param => $value) {
					if (!empty($highlights['body'])) {
						foreach ($highlights['body'] as $matches) {
							if (!empty($matches['param']) && $matches['param'] === $param) {
								$value = sprintf($this->highlightParamFormat, $value);
								if (is_array($matches['match'][0])) {
									$replace = array();
									foreach ($matches['match'][0] as $key => $match) {
										$replace[$match] = sprintf($this->highlightMatchFormat, $match);
									}
									if ($replace) {
										$value = str_replace(array_keys($replace), $replace, $value);
									}
								} else { // preg_match
									$value = str_replace($matches['match'][0], sprintf($this->highlightMatchFormat, $matches['match'][0]), $value);
								}
								break;
							}
						}
					}

					$body .= <<<FORM
--$boundary
Content-Disposition: form-data; name="$param"

$value

FORM;
				}

				foreach ($this->getFiles() as $param => $file) {
					$name = array_key_exists('name', $file) ? $file['name'] : '';
					$mime = array_key_exists('type', $file) ? $file['type'] : '';
					$value = '';
					$lenToRead = $maxRequestLen - (wfWAFUtils::strlen($request) + wfWAFUtils::strlen($body) + 1);
					if (array_key_exists('content', $file)) {
						$value = $file['content'];
					} else if ($lenToRead > 0 && file_exists($file['tmp_name'])) {
						$handle = fopen($file['tmp_name'], 'r');
						$value = fread($handle, $lenToRead);
						fclose($handle);
					}

					if (!empty($highlights['fileNames'])) {
						foreach ($highlights['fileNames'] as $matches) {
							if (!empty($matches['param']) && $matches['param'] === $param) {
								$name = sprintf($this->highlightParamFormat, $name);
								$name = str_replace($matches['match'][0], sprintf($this->highlightMatchFormat, $matches['match'][0]), $name);
								break;
							}
						}
					}

					$body .= <<<FORM
--$boundary
Content-Disposition: form-data; name="$param"; filename="$name"
Content-Type: $mime
Expires: 0

$value

FORM;
				}

				if ($body) {
					$body .= "--$boundary--\n";
				}
			}
			else { //Assume application/x-www-form-urlencoded and re-encode the body
				$body = http_build_query($body, '', '&');
				if (!empty($highlights['body'])) {
					foreach ($highlights['body'] as $matches) {
						if (!empty($matches['param'])) {
							$this->highlightMatches = $matches['match'];
							$body = preg_replace_callback('/(&|^)(' . preg_quote(urlencode($matches['param']), '/') . ')=(.*?)(&|$)/', array(
								$this, 'highlightParam',
							), $body);
						}
					}
				}
			}
		}
		
		if (!is_string($body) || empty($body)) {
			if (is_string($rawBody)) {
				$body = $rawBody;
			}
			else {
				$body = '';
			}
		}

		$request .= "\n" . $body;

		if (wfWAFUtils::strlen($request) > $maxRequestLen) {
			$request = wfWAFUtils::substr($request, 0, $maxRequestLen);
		}
		return $request;
	}

	/**
	 * @param array $matches
	 * @return string
	 */
	private function highlightParam($matches) {
		$value = '';
		if (is_array($this->highlightMatches)) {
			// preg_match_all
			if (is_array($this->highlightMatches[0])) {
				$value = $matches[3];
				$replace = array();
				foreach ($this->highlightMatches[0] as $key => $match) {
					$this->highlightMatches[0][$key] = $this->callHighlightMatchFilter($match);
					$replace[] = sprintf($this->highlightMatchFormat, $this->callHighlightMatchFilter($match));
				}
				if ($replace) {
					$value = str_replace($this->highlightMatches[0], $replace, $value);
				}

			} else { // preg_match
				$param = $this->callHighlightMatchFilter($this->highlightMatches[0]);
				$value = str_replace($param, sprintf($this->highlightMatchFormat, $param), $matches[3]);
			}
		}
		if (wfWAFUtils::strlen($value) === 0) {
			$value = sprintf($this->highlightMatchFormat, $value);
		}

		return $matches[1] . sprintf($this->highlightParamFormat, $matches[2] . '=' . $value) . $matches[4];
	}

	/**
	 * @param $match
	 * @return mixed
	 */
	private function callHighlightMatchFilter($match) {
		return is_callable($this->highlightMatchFilter) ? call_user_func($this->highlightMatchFilter, $match) : $match;
	}
	
	/**
	 * Encodes all of the keys with the MD5 hash.
	 * 
	 * @param array|string $value
	 * @return array|string
	 */
	private function md5EncodeKeys($value) {
		if (!is_array($value)) {
			return md5($value);
		}
		
		$result = array();
		foreach ($value as $k => $v) {
			$md5Key = md5($k);
			if (is_array($v)) {
				$result[$md5Key] = $this->md5EncodeKeys($v);
			}
			else {
				$result[$md5Key] = $v;
			}
		}
		return $result;
	}

	/**
	 * @param string $key
	 * @param string|array $value
	 * @return array
	 */
	private function reduceBodyParameter($key, $value) {
		if (is_array($value)) {
			$param = array();
			foreach ($value as $index => $val) {
				$param = array_merge($param, $this->reduceBodyParameter("{$key}[$index]", $val));
			}
			return $param;
		}
		return array(
			$key => $value,
		);
	}

	/**
	 * @param mixed $auth
	 */
	public function setAuth($auth) {
		$this->auth = $auth;
	}

	/**
	 * @param mixed $body
	 */
	public function setBody($body) {
		$this->body = $body;
		$this->setMd5Body($this->md5EncodeKeys($body));
	}
	
	public function setRawBody($rawBody) {
		$this->rawBody = $rawBody;
	}
	
	/**
	 * @param mixed $md5Body
	 */
	public function setMd5Body($md5Body) {
		$this->md5Body = $md5Body;
	}

	public function setJsonBody($jsonBody) {
		$this->jsonBody = $jsonBody;
		$this->jsonParsed = true;
	}

	/**
	 * @param mixed $cookies
	 */
	public function setCookies($cookies) {
		$this->cookies = $cookies;
	}

	/**
	 * @param mixed $fileNames
	 */
	public function setFileNames($fileNames) {
		$this->fileNames = $fileNames;
	}

	/**
	 * @param mixed $files
	 */
	public function setFiles($files) {
		$this->files = $files;
	}

	/**
	 * @param mixed $headers
	 */
	public function setHeaders($headers) {
		$this->headers = $headers;
	}

	/**
	 * @param mixed $host
	 */
	public function setHost($host) {
		$this->host = $host;
	}

	/**
	 * @param mixed $ip
	 */
	public function setIP($ip) {
		$this->ip = $ip;
	}

	/**
	 * @param mixed $method
	 */
	public function setMethod($method) {
		$this->method = $method;
	}

	/**
	 * @param mixed $path
	 */
	public function setPath($path) {
		$this->path = $path;
	}

	/**
	 * @param mixed $protocol
	 */
	public function setProtocol($protocol) {
		$this->protocol = $protocol;
	}

	/**
	 * @param mixed $queryString
	 */
	public function setQueryString($queryString) {
		$this->queryString = $queryString;
		$this->setMd5QueryString($this->md5EncodeKeys($queryString));
	}
	
	/**
	 * @param mixed $md5QueryString
	 */
	public function setMd5QueryString($md5QueryString) {
		$this->md5QueryString = $md5QueryString;
	}

	/**
	 * @param mixed $timestamp
	 */
	public function setTimestamp($timestamp) {
		$this->timestamp = $timestamp;
	}

	/**
	 * @param mixed $uri
	 */
	public function setUri($uri) {
		$this->uri = $uri;
	}
	
	/**
	 * @param array $metadata
	 */
	public function setMetadata($metadata) {
		$this->metadata = $metadata;
	}
}
}