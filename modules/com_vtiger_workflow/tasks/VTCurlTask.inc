<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once('modules/com_vtiger_workflow/VTEntityCache.inc');
require_once('modules/com_vtiger_workflow/VTWorkflowUtils.php');
require_once('modules/com_vtiger_workflow/VTSimpleTemplate.inc');
require_once('modules/com_vtiger_workflow/VTPlainTextTemplate.inc');
require_once('modules/com_vtiger_workflow/VTJsonTemplate.inc');
require_once('modules/com_vtiger_workflow/VTHeaderTemplate.inc');
require_once('modules/com_vtiger_workflow/VTUrlTemplate.inc');

class VTCurlTask extends VTTask {
	// Curl requests are typically short, execute immediately
	public $executeImmediately = true;

	/** タイムアウトの既定値（秒） */
	const DEFAULT_TIMEOUT = 30;

	/** タイムアウトの上限（秒）。ワークフローを長時間ブロックさせない */
	const MAX_TIMEOUT = 60;

	/** 保持するレスポンスの上限バイト数。巨大な応答でメモリを食わせない */
	const MAX_RESPONSE_BYTES = 65536;

	/** 許可するスキーム。file:// や gopher:// 等を使わせない */
	const ALLOWED_SCHEMES = array('http', 'https');

	/**
	 * Called after unserialization - normalize fields
	 */
	public function __wakeup() {
		$this->normalizeFields();
	}

	public function getFieldNames() {
		return array("url", "method", "headers", "body", "timeout");
	}

	public function doTask($entity) {
		global $adb, $log;

		$util = new VTWorkflowUtils();
		$admin = $util->adminUser();

		try {
			$entityCache = new VTEntityCache($admin);
			$entityId = $entity->getId();

			// Ensure headers and body are strings (convert from array if needed)
			$this->normalizeFields();

			// URLの差し込み値はパーセントエンコードする（& や空白でURLが変わるのを防ぐ）
			$urlTemplate = new VTUrlTemplate($this->url);
			$renderedUrl = $urlTemplate->render($entityCache, $entityId);

			// ヘッダーの差し込み値は改行を除去する（任意ヘッダー注入を防ぐ）
			$headersTemplate = new VTHeaderTemplate($this->headers);
			$renderedHeaders = $headersTemplate->render($entityCache, $entityId);

			// ボディはJSON想定のため、値をJSON文字列エスケープして注入する（"や改行でJSONが壊れるのを防ぐ）
			$bodyTemplate = new VTJsonTemplate($this->body);
			$renderedBody = $bodyTemplate->render($entityCache, $entityId);

			$method = !empty($this->method) ? strtoupper($this->method) : 'POST';
			$timeout = self::normalizeTimeout($this->timeout);

			$result = self::execute($renderedUrl, $method, $renderedHeaders, $renderedBody, $timeout);

			if ($result['success']) {
				$log->info("VTCurlTask: Successfully executed curl request to " . $renderedUrl);
			} else {
				$log->error("VTCurlTask: Failed to execute curl request: " . $result['error']);
			}
		} catch (Exception $e) {
			$log->error("VTCurlTask: Exception during curl execution: " . $e->getMessage());
		} finally {
			// 例外が出ても管理者ユーザーへの切り替えは必ず戻す
			$util->revertUser();
		}
	}

	/**
	 * Normalize fields - convert arrays to strings if needed
	 * This handles legacy data that was incorrectly saved as arrays
	 */
	private function normalizeFields() {
		// Convert headers array to string
		if (is_array($this->headers)) {
			$headerLines = array();
			foreach ($this->headers as $key => $value) {
				if (is_numeric($key)) {
					// Numeric key means it's already a line
					$headerLines[] = $value;
				} else {
					// Associative array, convert to "Key: Value" format
					$headerLines[] = $key . ': ' . $value;
				}
			}
			$this->headers = implode("\n", $headerLines);
		}

		// Convert body array to string (JSON format)
		if (is_array($this->body)) {
			$this->body = json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		}

		// null等は空文字に寄せる。empty()で判定すると "0" が消えるため is_string で見る
		if (!is_string($this->headers)) {
			$this->headers = '';
		}
		if (!is_string($this->body)) {
			$this->body = '';
		}
	}

	/**
	 * タイムアウト値を 1〜MAX_TIMEOUT 秒に正規化する。
	 * doTask とテスト送信で同じ値になるよう共通化している。
	 * @param mixed $value
	 * @return int
	 */
	public static function normalizeTimeout($value) {
		$timeout = (int) $value;
		if ($timeout <= 0) {
			return self::DEFAULT_TIMEOUT;
		}
		if ($timeout > self::MAX_TIMEOUT) {
			return self::MAX_TIMEOUT;
		}
		return $timeout;
	}

	/**
	 * URL検証 → curl実行までをまとめて行う。
	 * 検証を忘れた経路が生まれないよう、送信は必ずこのメソッドを通す。
	 *
	 * @param string $url
	 * @param string $method
	 * @param string $headers
	 * @param string $body
	 * @param int $timeout
	 * @return array success / error / http_code / response
	 */
	public static function execute($url, $method, $headers, $body, $timeout) {
		$validation = self::validateUrl($url);
		if (!$validation['valid']) {
			return array(
				'success' => false,
				'error' => $validation['error'],
			);
		}
		return self::runCurl($url, $method, $headers, $body, self::normalizeTimeout($timeout), $validation);
	}

	/**
	 * URLが送信先として妥当かを判定する（真偽値版）。
	 * @param string $url
	 * @return bool
	 */
	public static function checkUrl($url) {
		$validation = self::validateUrl($url);
		return $validation['valid'];
	}

	/**
	 * URLを検証し、名前解決結果まで含めて返す。
	 *
	 * SSRF対策として次を行う:
	 *  - スキームを http / https のみに限定
	 *  - ホスト名が解決する全アドレスを検証し、内部向けが1つでもあれば拒否
	 *  - 名前解決できない場合は拒否（fail-closed）
	 *  - IPv6リテラル・末尾ドット・IPv4射影アドレスを正規化してから判定
	 *
	 * @param string $url
	 * @return array valid / error / host / rawHost / port / ips / isLiteralIp
	 */
	public static function validateUrl($url) {
		$url = trim((string) $url);

		if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
			return self::urlError('URLの形式が不正です');
		}

		$parsedUrl = parse_url($url);
		if (!$parsedUrl || empty($parsedUrl['scheme']) || empty($parsedUrl['host'])) {
			return self::urlError('URLの形式が不正です');
		}

		$scheme = strtolower($parsedUrl['scheme']);
		if (!in_array($scheme, self::ALLOWED_SCHEMES, true)) {
			return self::urlError('http / https 以外のスキームは使用できません: ' . $scheme);
		}

		// parse_url はIPv6リテラルを [::1] の形で返す。末尾ドットも落として正規化する
		$rawHost = strtolower($parsedUrl['host']);
		$host = rtrim(trim($rawHost, '[]'), '.');
		if ($host === '') {
			return self::urlError('ホスト名が空です');
		}

		$port = isset($parsedUrl['port']) ? (int) $parsedUrl['port'] : ($scheme === 'https' ? 443 : 80);

		$ips = self::resolveHost($host);
		if (empty($ips)) {
			// 解決できないホストを許可すると、DNSが引けない環境で検証が無効化される
			return self::urlError('ホスト名を解決できませんでした: ' . $host);
		}

		foreach ($ips as $ip) {
			if (!self::isPublicIp($ip)) {
				// ホスト名がIPリテラルの場合は同じ値を二度出さない
				$detail = ($ip === $host) ? $host : $host . ' (' . $ip . ')';
				return self::urlError('内部ネットワーク宛のURLは使用できません: ' . $detail);
			}
		}

		return array(
			'valid' => true,
			'error' => '',
			'host' => $host,
			'rawHost' => trim($rawHost, '[]'),
			'port' => $port,
			'ips' => $ips,
			'isLiteralIp' => (bool) filter_var($host, FILTER_VALIDATE_IP),
		);
	}

	/**
	 * @param string $message
	 * @return array
	 */
	private static function urlError($message) {
		return array(
			'valid' => false,
			'error' => $message,
			'host' => '',
			'rawHost' => '',
			'port' => 0,
			'ips' => array(),
			'isLiteralIp' => false,
		);
	}

	/**
	 * ホスト名を全アドレス（A / AAAA）に解決する。
	 * @param string $host
	 * @return array
	 */
	private static function resolveHost($host) {
		if (filter_var($host, FILTER_VALIDATE_IP)) {
			return array($host);
		}

		$ips = array();

		$ipv4List = @gethostbynamel($host);
		if (is_array($ipv4List)) {
			$ips = $ipv4List;
		}

		if (function_exists('dns_get_record')) {
			$records = @dns_get_record($host, DNS_AAAA);
			if (is_array($records)) {
				foreach ($records as $record) {
					if (!empty($record['ipv6'])) {
						$ips[] = $record['ipv6'];
					}
				}
			}
		}

		return array_values(array_unique($ips));
	}

	/**
	 * 外部へ出しても良いアドレスかを判定する。
	 * @param string $ip
	 * @return bool
	 */
	private static function isPublicIp($ip) {
		// IPv4射影(::ffff:a.b.c.d) / NAT64 / 6to4 は埋め込まれたIPv4で判定する
		$embedded = self::embeddedIpv4($ip);
		if ($embedded !== null) {
			return self::isPublicIp($embedded);
		}

		if (!filter_var($ip, FILTER_VALIDATE_IP)) {
			return false;
		}

		// プライベート/予約範囲はPHPのフィルタで除外する
		//   IPv4: 0.0.0.0/8, 10/8, 127/8, 169.254/16, 172.16/12, 192.168/16, 240/4
		//   IPv6: ::1/128, ::/128, ::ffff:0:0/96, fc00::/7, fe80::/10
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return false;
		}

		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$ipLong = ip2long($ip);
			// 100.64.0.0/10 (CGNAT) と 224.0.0.0/4 以降 はPHPのフィルタ対象外なので個別に弾く
			if ($ipLong >= ip2long('100.64.0.0') && $ipLong <= ip2long('100.127.255.255')) {
				return false;
			}
			if ($ipLong >= ip2long('224.0.0.0')) {
				return false;
			}
		}

		return true;
	}

	/**
	 * IPv6アドレスに埋め込まれたIPv4を取り出す。
	 * ::ffff:127.0.0.1 のような表記でループバック判定を回避されるのを防ぐ。
	 * @param string $ip
	 * @return string|null
	 */
	private static function embeddedIpv4($ip) {
		$binary = @inet_pton($ip);
		if ($binary === false || strlen($binary) !== 16) {
			return null;
		}

		$hex = bin2hex($binary);
		$prefixes = array(
			'00000000000000000000ffff', // ::ffff:0:0/96  IPv4射影
			'000000000000000000000000', // ::/96          IPv4互換
			'0064ff9b0000000000000000', // 64:ff9b::/96   NAT64
		);
		foreach ($prefixes as $prefix) {
			if (strpos($hex, $prefix) === 0) {
				return long2ip((int) hexdec(substr($hex, 24, 8)));
			}
		}

		// 2002:a.b.c.d::/16 (6to4)
		if (strpos($hex, '2002') === 0) {
			return long2ip((int) hexdec(substr($hex, 4, 8)));
		}

		return null;
	}

	/**
	 * Execute curl request
	 * 検証済みのURLに対してのみ呼ぶこと。通常は execute() を使う。
	 *
	 * @param string $url
	 * @param string $method
	 * @param string $headers
	 * @param string $body
	 * @param int $timeout
	 * @param array|null $validation validateUrl() の結果。名前解決のピン留めに使う
	 * @return array
	 */
	public static function runCurl($url, $method, $headers, $body, $timeout, $validation = null) {
		global $log;

		$ch = curl_init();

		// Set URL
		curl_setopt($ch, CURLOPT_URL, $url);

		// スキームをhttp/httpsに固定する。URL検証をすり抜けた場合の保険
		if (defined('CURLPROTO_HTTP') && defined('CURLPROTO_HTTPS')) {
			curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
			curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		}

		// Set method
		switch ($method) {
			case 'GET':
				curl_setopt($ch, CURLOPT_HTTPGET, true);
				break;
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				if (!empty($body)) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				}
				break;
			case 'PUT':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				if (!empty($body)) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				}
				break;
			case 'DELETE':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
			case 'PATCH':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
				if (!empty($body)) {
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
				}
				break;
			default:
				curl_setopt($ch, CURLOPT_POST, true);
		}

		// Parse and set headers
		if (!empty($headers)) {
			$headerLines = explode("\n", $headers);
			$curlHeaders = array();
			foreach ($headerLines as $headerLine) {
				$headerLine = trim($headerLine);
				if (!empty($headerLine)) {
					$curlHeaders[] = $headerLine;
				}
			}
			if (!empty($curlHeaders)) {
				curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
			}
		}

		// Set timeout
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min($timeout, 10));

		// SSL verification (enabled for security)
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		// Disable redirect following for security
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

		// 検証時の名前解決結果に固定し、検証後に別アドレスへ差し替えられるのを防ぐ
		// (DNSリバインディング対策)
		if (is_array($validation) && empty($validation['isLiteralIp']) && !empty($validation['ips'])) {
			$pinnedIp = $validation['ips'][0];
			$pinnedIp = (strpos($pinnedIp, ':') !== false) ? '[' . $pinnedIp . ']' : $pinnedIp;
			$resolveList = array();
			// URL上の表記（末尾ドット付き等）と正規化後の両方を登録しておく
			foreach (array_unique(array($validation['host'], $validation['rawHost'])) as $pinnedHost) {
				if ($pinnedHost !== '') {
					$resolveList[] = $pinnedHost . ':' . $validation['port'] . ':' . $pinnedIp;
				}
			}
			if (!empty($resolveList)) {
				curl_setopt($ch, CURLOPT_RESOLVE, $resolveList);
			}
		}

		// レスポンスは上限バイトまでしか保持しない。
		// CURLOPT_RETURNTRANSFER の代わりに書き込みコールバックで打ち切る
		$responseBuffer = '';
		$maxBytes = self::MAX_RESPONSE_BYTES;
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curlHandle, $chunk) use (&$responseBuffer, $maxBytes) {
			$remaining = $maxBytes - strlen($responseBuffer);
			if ($remaining > 0) {
				$responseBuffer .= substr($chunk, 0, $remaining);
			}
			// 上限超過分は捨てるが、消費済みとして返し転送は継続させる
			return strlen($chunk);
		});

		// Execute request
		curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		$errno = curl_errno($ch);

		curl_close($ch);

		if ($errno) {
			return array(
				'success' => false,
				'error' => "Curl error ($errno): $error"
			);
		}

		// レスポンス本文にはトークン等が含まれ得るためログには残さない
		$log->debug(sprintf('VTCurlTask: HTTP %d, response %d bytes', $httpCode, strlen($responseBuffer)));

		return array(
			'success' => true,
			'http_code' => $httpCode,
			'response' => $responseBuffer
		);
	}
}
