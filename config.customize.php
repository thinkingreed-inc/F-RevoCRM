<?php
// PDF
$is_headlesschrome = false;// trueの場合：headless chromeを使用。falseの場合：TCPDFを使用。
$chromeurl = "http://localhost:30080/converthtmltopdf.php";// headlless chromeの場所またはコマンド
#$chromeurl = "google-chrome";// headlless chromeの場所（Linux）
#$chromeurl = "\"C:\Program Files\Google\Chrome\Application\chrome.exe\"";// headlless chromeの場所（Windows）
$hostfiledirectory = "/var/www/html2pdf/";//PDF作成場所（Linux）
#$hostfiledirectory = "D:/Applications/F-RevoCRM/crm/test/pdf/";//PDF作成場所（Windows）
$dokerfiledirectory = "/html2pdf/";//コマンド実行の場合はコメントアウトする
$show_subordinate_roles_list = true;// trueの場合：共有リスト欄に下位の役割が作成した全てのリストを表示。

// ドキュメントの添付ファイル上限（バイト）
// 分割アップロードで送信するため、PHPの upload_max_filesize / post_max_size に
// 依存せずこの値まで登録できる。既定は 2GB。
// 注意: アップロード中は storage/chunk_uploads に実サイズ分の一時ファイルを作り、
// 保存時に storage 本体へコピーするため、一時的に2倍の空き容量が必要。
$documents_upload_maxsize = 2147483648;// 2GB

// CSP（Content-Security-Policy）で許可する外部ドメイン
// 各ディレクティブに対して許可するURLを配列で指定する
$csp_allowed_domains = array(
	'script-src'  => array('https://maps.google.com', 'https://www.google.com', 'http://localhost:5173'),
	'style-src'   => array('http://localhost:5173'),
	'img-src'     => array('https://f-revocrm.jp'),
	'font-src'    => array(),
	'connect-src' => array('http://localhost:5173', 'ws://localhost:5173'),
	'frame-src'   => array('https://www.google.com', 'https:'),
);
