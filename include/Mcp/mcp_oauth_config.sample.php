<?php
/**
 * MCP OAuth 環境設定サンプル。
 * このファイルを同じディレクトリに mcp_oauth_config.php としてコピーし、環境に合わせて編集してください。
 * （mcp_oauth_config.php は .gitignore 済み＝環境固有のためGitに含めない）
 */
return [
	/**
	 * issuer: authorization server metadata を配信するベースURL。
	 *
	 *  ''(空)        : 自ドメインを使用（標準環境）。
	 *                  メタデータを {自ドメイン}/.well-known/oauth-authorization-server で
	 *                  配信できるよう、docroot の /.well-known/ を mcp-oauth/well-known-*.php に
	 *                  ルーティングしてください（README参照）。
	 *
	 *  'https://...' : ドメインルートの /.well-known/ が使えない環境
	 *                  （例: 一部の共有ホスティングのサブドメイン）向け。
	 *                  .well-known が使える別ドメインを指定し、そのドメインの
	 *                  /.well-known/oauth-authorization-server にメタデータを置きます（README参照）。
	 *                  認可エンドポイント(authorize/token/register)は本MCPのドメインのままで構いません。
	 */
	'issuer' => '',
];
