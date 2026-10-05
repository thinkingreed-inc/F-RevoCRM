<?php
/**
 * OAuth 2.1 認可エンドポイント
 * GET/POST /mcp-oauth/authorize.php
 *
 * フロー:
 *   1. GET: OAuthパラメータ検証 → ログインフォーム表示
 *   2. POST action=login: F-revo認証 → 同意画面表示
 *   3. POST action=consent, decision=allow: 認可コード発行 → redirect_uriにリダイレクト
 *   4. POST action=consent, decision=deny: エラーリダイレクト
 *
 * F-revoログイン連携:
 *   Users::doLogin() でパスワード検証（PHASH/crypt_type対応）
 *   Users::retrieve_user_id() でユーザーID取得
 */

// F-revoブートで warning 等が出力されると header('Location') が "headers already sent" で
// 失敗する(リダイレクトされず「反応なし」になる)ため、出力バッファリングで吸収する。
ob_start();
// F-revoブート
chdir(dirname(__DIR__, 2));
require_once 'config.inc.php';
if (file_exists('config_override.php')) {
	include_once 'config_override.php';
}
require_once 'vendor/autoload.php';
require_once 'include/utils/CommonUtils.php';
vimport('includes.runtime.EntryPoint');

require_once 'include/Mcp/OAuthHelper.php';
require_once 'include/Mcp/OAuthStorage.php';
require_once 'include/Mcp/RateLimiter.php';
require_once 'include/Mcp/AuditLogger.php';
require_once 'modules/Users/Users.php';

// エラー表示抑制
ini_set('display_errors', '0');
ini_set('html_errors', '0');

// セッション開始（ログイン状態・CSRFトークン・OAuthパラメータ保持）
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// ================================================================
//  GET: パラメータ検証 → ログインフォーム表示
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$responseType       = $_GET['response_type'] ?? '';
	$clientId           = $_GET['client_id'] ?? '';
	$redirectUri        = $_GET['redirect_uri'] ?? '';
	$codeChallenge      = $_GET['code_challenge'] ?? '';
	$codeChallengeMethod = $_GET['code_challenge_method'] ?? '';
	$state              = $_GET['state'] ?? '';
	$scope              = $_GET['scope'] ?? 'mcp';

	// ── client_id 検証（失敗時はリダイレクトせずエラーページ表示） ──
	if ($clientId === '') {
		renderErrorPage('client_id が指定されていません。');
		exit;
	}
	$client = Mcp_OAuthStorage::getClient($clientId);
	if ($client === null) {
		renderErrorPage('無効な client_id です。クライアントが登録されていません。');
		exit;
	}

	// ── redirect_uri 厳格一致検証（失敗時はリダイレクトせずエラーページ表示） ──
	if ($redirectUri === '') {
		renderErrorPage('redirect_uri が指定されていません。');
		exit;
	}
	if (!in_array($redirectUri, $client['redirect_uris_array'], true)) {
		renderErrorPage('redirect_uri が登録されたクライアントのものと一致しません。');
		exit;
	}

	// ── ここからはredirect_uriが検証済みなのでエラーはリダイレクトで返す ──

	// response_type 検証
	if ($responseType !== 'code') {
		redirectWithError($redirectUri, 'unsupported_response_type', 'response_type must be "code"', $state);
		exit;
	}

	// PKCE 必須検証（OAuth 2.1）
	if ($codeChallenge === '') {
		redirectWithError($redirectUri, 'invalid_request', 'code_challenge is required (PKCE S256)', $state);
		exit;
	}
	if ($codeChallengeMethod !== 'S256') {
		redirectWithError($redirectUri, 'invalid_request', 'code_challenge_method must be "S256"', $state);
		exit;
	}

	// セッションにOAuthパラメータ保存
	$_SESSION['mcp_oauth_params'] = [
		'client_id'      => $clientId,
		'client_name'    => $client['client_name'],
		'redirect_uri'   => $redirectUri,
		'code_challenge' => $codeChallenge,
		'state'          => $state,
		'scope'          => $scope,
	];
	$_SESSION['mcp_oauth_csrf'] = bin2hex(random_bytes(32));

	// ログインフォーム表示
	renderLoginForm('', $_SESSION['mcp_oauth_csrf'], $client['client_name']);
	exit;
}

// ================================================================
//  POST: ログインまたは同意処理
// ================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action    = $_POST['action'] ?? '';
	$csrfToken = $_POST['csrf_token'] ?? '';

	// セッション有効性チェック
	if (!isset($_SESSION['mcp_oauth_params']) || !isset($_SESSION['mcp_oauth_csrf'])) {
		renderErrorPage('セッションが無効です。認可フローの最初からやり直してください。');
		exit;
	}

	$oauthParams = $_SESSION['mcp_oauth_params'];

	// CSRF トークン検証
	if (!hash_equals($_SESSION['mcp_oauth_csrf'], $csrfToken)) {
		renderErrorPage('CSRF検証に失敗しました。ページを再読み込みしてやり直してください。');
		exit;
	}

	// ────────────────────────────────────────
	//  action=login: F-revoユーザー認証
	// ────────────────────────────────────────
	if ($action === 'login') {
		$username = trim($_POST['username'] ?? '');
		$password = $_POST['password'] ?? '';

		// ブルートフォース対策: IP単位のレート制限(60秒で8回まで)＋失敗監査ログ
		global $root_directory;
		$mcpBaseDir = !empty($root_directory) ? rtrim($root_directory, '/\\') : dirname(__DIR__, 2);
		$loginIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		$loginLimiter = new Mcp_RateLimiter($mcpBaseDir . '/cache', 60, 8);
		$loginAudit = new Mcp_AuditLogger($mcpBaseDir . '/logs');
		if (!$loginLimiter->check('oauth_login:' . $loginIp)) {
			$loginAudit->logAuth($loginIp, false, 'oauth_login_rate_limited');
			$_SESSION['mcp_oauth_csrf'] = bin2hex(random_bytes(32));
			renderLoginForm('ログイン試行が多すぎます。しばらく待ってから再度お試しください。', $_SESSION['mcp_oauth_csrf'], $oauthParams['client_name']);
			exit;
		}

		if ($username === '' || $password === '') {
			$_SESSION['mcp_oauth_csrf'] = bin2hex(random_bytes(32));
			renderLoginForm('ユーザー名とパスワードを入力してください。', $_SESSION['mcp_oauth_csrf'], $oauthParams['client_name']);
			exit;
		}

		// F-revo Users 認証（doLogin: PHASH/crypt_type自動判別）
		$userObj = new Users();
		$userObj->column_fields['user_name'] = $username;
		$loginOk = $userObj->doLogin($password);

		if (!$loginOk) {
			$loginAudit->logAuth($loginIp, false, 'oauth_login_failed');
			$_SESSION['mcp_oauth_csrf'] = bin2hex(random_bytes(32));
			renderLoginForm('ユーザー名またはパスワードが正しくありません。', $_SESSION['mcp_oauth_csrf'], $oauthParams['client_name']);
			exit;
		}

		// ユーザーID取得
		$userId = $userObj->retrieve_user_id($username);
		if (empty($userId)) {
			$_SESSION['mcp_oauth_csrf'] = bin2hex(random_bytes(32));
			renderLoginForm('ユーザーが見つかりません（削除済みの可能性があります）。', $_SESSION['mcp_oauth_csrf'], $oauthParams['client_name']);
			exit;
		}

		// セッションにユーザー情報保存
		$_SESSION['mcp_oauth_userid']   = (int) $userId;
		$_SESSION['mcp_oauth_username'] = $username;
		$_SESSION['mcp_oauth_csrf']     = bin2hex(random_bytes(32));

		// 同意画面表示
		renderConsentForm($username, $oauthParams['client_name'], $oauthParams['scope'], $_SESSION['mcp_oauth_csrf']);
		exit;
	}

	// ────────────────────────────────────────
	//  action=consent: 許可 or 拒否
	// ────────────────────────────────────────
	if ($action === 'consent') {
		$decision = $_POST['decision'] ?? '';

		if (!isset($_SESSION['mcp_oauth_userid'])) {
			renderErrorPage('ログインセッションが無効です。最初からやり直してください。');
			exit;
		}

		$userId      = (int) $_SESSION['mcp_oauth_userid'];
		$redirectUri = $oauthParams['redirect_uri'];
		$state       = $oauthParams['state'];

		if ($decision === 'deny') {
			// ── 拒否: redirect_uri にエラーリダイレクト ──
			cleanupOAuthSession();
			redirectWithError($redirectUri, 'access_denied', 'The user denied the authorization request', $state);
			exit;
		}

		if ($decision === 'allow') {
			// ── 許可: 認可コード発行 → リダイレクト ──
			$code     = mcp_oauth_generate_token();
			$codeHash = hash('sha256', $code);
			$expiresAt = date('Y-m-d H:i:s', time() + 600); // 10分後

			try {
				Mcp_OAuthStorage::createAuthCode(
					$codeHash,
					$oauthParams['client_id'],
					$userId,
					$oauthParams['code_challenge'],
					$redirectUri,
					$oauthParams['scope'],
					$expiresAt
				);
			} catch (\Exception $e) {
				error_log('[MCP OAuth Authorize] DB error: ' . $e->getMessage());
				renderErrorPage('認可コードの発行に失敗しました。しばらくしてからやり直してください。');
				exit;
			}

			// セッションクリーンアップ
			cleanupOAuthSession();

			// redirect_uri にリダイレクト（code + state）
			$params = ['code' => $code];
			if ($state !== '') {
				$params['state'] = $state;
			}
			$separator = (strpos($redirectUri, '?') === false) ? '?' : '&';
			$location = $redirectUri . $separator . http_build_query($params);
			while (ob_get_level()) { ob_end_clean(); }
			if (!headers_sent()) {
				header('Location: ' . $location, true, 302);
			}
			// ヘッダーが効かない場合でも確実にブラウザを移動させる JS/meta フォールバック
			$locEsc = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
			echo '<!DOCTYPE html><html><head><meta charset="utf-8">'
				. '<meta http-equiv="refresh" content="0;url=' . $locEsc . '">'
				. '<script>location.replace(' . json_encode($location) . ');</script></head>'
				. '<body style="font-family:sans-serif;text-align:center;padding:40px">'
				. 'リダイレクトしています…<br><a href="' . $locEsc . '">移動しない場合はこちら</a></body></html>';
			exit;
		}

		renderErrorPage('無効な操作です。');
		exit;
	}

	renderErrorPage('無効なアクションです。');
	exit;
}

// GETとPOST以外
http_response_code(405);
exit;

// ================================================================
//  ヘルパー関数
// ================================================================

/**
 * OAuthセッション変数をクリーンアップ
 */
function cleanupOAuthSession(): void
{
	unset(
		$_SESSION['mcp_oauth_params'],
		$_SESSION['mcp_oauth_csrf'],
		$_SESSION['mcp_oauth_userid'],
		$_SESSION['mcp_oauth_username']
	);
}

/**
 * redirect_uri にOAuthエラーリダイレクト
 */
function redirectWithError(string $redirectUri, string $error, string $description, string $state): void
{
	$params = [
		'error'             => $error,
		'error_description' => $description,
	];
	if ($state !== '') {
		$params['state'] = $state;
	}
	$separator = (strpos($redirectUri, '?') === false) ? '?' : '&';
	$location = $redirectUri . $separator . http_build_query($params);
	while (ob_get_level()) { ob_end_clean(); } header('Location: ' . $location);
}

/**
 * エラーページ表示（redirect_uriが検証できない場合用）
 */
function renderErrorPage(string $message): void
{
	http_response_code(400);
	echo getHtmlHeader('エラー - F-revo CRM');
	echo '<div class="container">';
	echo '<div class="card">';
	echo '<h1>F-revo CRM</h1>';
	echo '<div class="alert error-box">';
	echo '<strong>エラー</strong><br>';
	echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
	echo '</div>';
	echo '<p class="hint">ブラウザを閉じて、接続元のアプリケーションからやり直してください。</p>';
	echo '</div></div>';
	echo getHtmlFooter();
}

/**
 * ログインフォーム表示
 */
function renderLoginForm(string $error, string $csrf, string $clientName): void
{
	$esc = function (string $s): string {
		return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
	};
	echo getHtmlHeader('ログイン - F-revo CRM');
	echo '<div class="container">';
	echo '<div class="card">';
	echo '<div class="logo">F-revo CRM</div>';
	echo '<p class="subtitle"><strong>' . $esc($clientName ?: 'MCP Client') . '</strong> が F-revo CRM へのアクセスを要求しています</p>';
	if ($error !== '') {
		echo '<div class="alert">' . $esc($error) . '</div>';
	}
	echo '<form method="POST" autocomplete="on">';
	echo '<input type="hidden" name="csrf_token" value="' . $esc($csrf) . '">';
	echo '<input type="hidden" name="action" value="login">';
	echo '<div class="field">';
	echo '<label for="username">ユーザー名</label>';
	echo '<input type="text" id="username" name="username" required autofocus autocomplete="username">';
	echo '</div>';
	echo '<div class="field">';
	echo '<label for="password">パスワード</label>';
	echo '<input type="password" id="password" name="password" required autocomplete="current-password">';
	echo '</div>';
	echo '<button type="submit" class="btn primary">ログイン</button>';
	echo '</form>';
	echo '</div></div>';
	echo getHtmlFooter();
}

/**
 * 同意画面表示
 */
function renderConsentForm(string $username, string $clientName, string $scope, string $csrf): void
{
	$esc = function (string $s): string {
		return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
	};
	echo getHtmlHeader('アクセス許可 - F-revo CRM');
	echo '<div class="container">';
	echo '<div class="card">';
	echo '<div class="logo">F-revo CRM</div>';
	echo '<h2>アクセス許可の確認</h2>';
	echo '<div class="info-box">';
	echo '<p><strong>' . $esc($clientName ?: 'MCP Client') . '</strong> が以下の操作を要求しています：</p>';
	echo '</div>';
	echo '<div class="user-badge">ログインユーザー: <strong>' . $esc($username) . '</strong></div>';
	echo '<ul class="permissions">';
	echo '<li>CRM データの読み取り・検索</li>';
	echo '<li>CRM データの作成・更新・削除</li>';
	echo '<li>あなたの権限の範囲内での操作のみ</li>';
	echo '</ul>';
	echo '<form method="POST" onsubmit="var w=document.getElementById(\'mcpWait\'); if(w){w.style.display=\'flex\';}">';
	echo '<input type="hidden" name="csrf_token" value="' . $esc($csrf) . '">';
	echo '<input type="hidden" name="action" value="consent">';
	echo '<div class="btn-group">';
	echo '<button type="submit" name="decision" value="allow" class="btn primary">許可する</button>';
	echo '<button type="submit" name="decision" value="deny" class="btn secondary">拒否する</button>';
	echo '</div>';
	echo '</form>';
	echo '<p class="hint">許可すると、このアプリケーションがあなたのアカウントで F-revo CRM を操作できるようになります。</p>';
	echo '</div></div>';
	// 「許可/拒否」押下時の処理中オーバーレイ（F-revoブートで数秒かかるため即フィードバック）
	echo '<div id="mcpWait" style="display:none;position:fixed;inset:0;background:rgba(13,44,84,.6);color:#fff;flex-direction:column;align-items:center;justify-content:center;z-index:9999;font-size:1.1rem">';
	echo '<div style="width:44px;height:44px;border:5px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:mcpspin 1s linear infinite;margin-bottom:16px"></div>';
	echo '処理中… Claude に戻ります。少々お待ちください';
	echo '</div>';
	echo '<style>@keyframes mcpspin{to{transform:rotate(360deg)}}</style>';
	echo getHtmlFooter();
}

/**
 * HTML ヘッダー（共通CSS付き）
 */
function getHtmlHeader(string $title): string
{
	$t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
	return <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$t}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Hiragino Kaku Gothic ProN","Yu Gothic",Meiryo,sans-serif;
     background:linear-gradient(135deg,#e8eef6 0%,#f0f2f5 100%);
     min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.container{width:100%;max-width:440px}
.card{background:#fff;border-radius:14px;padding:36px 32px;
      box-shadow:0 4px 24px rgba(13,44,84,.1)}
.logo{color:#0d2c54;font-size:1.5rem;font-weight:800;text-align:center;
      margin-bottom:8px;letter-spacing:.02em}
h2{color:#333;font-size:1.05rem;margin:16px 0 12px;text-align:center;font-weight:600}
.subtitle{color:#6b7280;text-align:center;margin-bottom:22px;font-size:.88rem;line-height:1.5}
.alert{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;
       border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:.86rem;line-height:1.5}
.error-box{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;
            border-radius:8px;padding:14px;margin-bottom:12px;font-size:.9rem}
.info-box{background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;
           padding:12px 16px;margin:8px 0 12px;font-size:.9rem}
.user-badge{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;
             padding:10px 14px;margin:12px 0;font-size:.88rem;text-align:center}
.field{margin-bottom:16px}
.field label{display:block;color:#374151;font-size:.86rem;font-weight:600;margin-bottom:5px}
.field input{width:100%;padding:11px 14px;border:1px solid #d1d5db;border-radius:8px;
             font-size:.95rem;transition:border-color .2s,box-shadow .2s}
.field input:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.12)}
.btn{display:inline-block;padding:11px 24px;border:none;border-radius:8px;
     font-size:.95rem;font-weight:600;cursor:pointer;transition:all .2s;text-align:center}
.btn.primary{background:#0d2c54;color:#fff;width:100%}
.btn.primary:hover{background:#162f5a;box-shadow:0 2px 8px rgba(13,44,84,.2)}
.btn.secondary{background:#e5e7eb;color:#374151}
.btn.secondary:hover{background:#d1d5db}
.btn-group{display:flex;gap:12px;margin-top:22px}
.btn-group .btn{flex:1}
.permissions{margin:8px 0 4px 22px;color:#374151;font-size:.88rem;line-height:1.7}
.permissions li{margin-bottom:4px}
.hint{color:#9ca3af;font-size:.78rem;text-align:center;margin-top:18px;line-height:1.5}
</style>
</head>
<body>

HTML;
}

/**
 * HTML フッター
 */
function getHtmlFooter(): string
{
	return '</body></html>';
}
