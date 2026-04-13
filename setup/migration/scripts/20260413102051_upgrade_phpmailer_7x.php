<?php
/**
 * マイグレーション: upgrade_phpmailer_7x
 *
 * PHPMailer 5.2.27（バンドル版）→ PHPMailer 7.x（Composer管理）へのアップグレード
 *
 * 問題: PHPMailer 5.2.27 では $LE = "\n" がデフォルトで、
 *       厳格なSMTPサーバ（plala.or.jp等）が CRLF 不備を拒否する。
 * 解決: PHPMailer 7.x は $LE = "\r\n" がデフォルトで、RFC 5321 準拠。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260413102051_UpgradePhpmailer7x extends FRMigrationClass {

    private const PHPMAILER_PACKAGE = 'phpmailer/phpmailer:^7.0';

    private static $composerPaths = ['/usr/local/bin/composer', '/usr/bin/composer'];

    /**
     * ソースファイル書き換え定義
     * 'autoload'  : 旧requireを置換する autoload文（nullの場合は削除のみ）
     * 'use_after' : use文を挿入する基準行のキーワード（nullの場合はuse文不要）
     */
    private $sourceFilePatches = [
        'cron/send_mail.php' => [
            'autoload'  => "require_once dirname(__DIR__) . '/vendor/autoload.php';",
            'use_after' => 'CommonUtils.php',
        ],
        'modules/Emails/mail.php' => [
            'autoload'  => "require_once 'vendor/autoload.php';",
            'use_after' => 'VTCacheUtils.php',
        ],
        'vtlib/Vtiger/Mailer.php' => [
            'autoload'  => "require_once dirname(__DIR__, 2) . '/vendor/autoload.php';",
            'use_after' => 'CommonUtils.php',
        ],
        'cron/SendReminder.service' => [
            'autoload'  => null,
            'use_after' => null,
        ],
        'modules/CustomerPortal/include.inc' => [
            'autoload'  => null,
            'use_after' => null,
        ],
    ];

    public function process() {
        $rootDir = realpath(dirname(__FILE__) . '/../../../') . '/';

        // フェーズ1: composer require + 検証
        $this->runComposerRequire($rootDir);
        $this->verifyPhpmailer($rootDir);

        // フェーズ2: ソースファイル書き換え
        $this->updateSourceFiles($rootDir);

        $this->log("PHPMailer 7.x アップグレード完了");
    }

    private function runComposerRequire($rootDir) {
        if ($this->isPhpmailerInstalled($rootDir)) {
            $this->log("composer: PHPMailer は既にインストール済み");
            return;
        }

        $composerBin = $this->findComposerBin();
        $this->log("composer require " . self::PHPMAILER_PACKAGE . " を実行中...");

        $process = proc_open(
            [$composerBin, 'require', self::PHPMAILER_PACKAGE, '--no-interaction'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes, $rootDir
        );
        if (!is_resource($process)) {
            throw new Exception("composer プロセスの起動に失敗しました");
        }

        stream_get_contents($pipes[1]); fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            throw new Exception("composer require に失敗しました（終了コード: {$returnCode}）\n{$stderr}");
        }
        $this->log("composer require 完了（composer.json/lock 更新済み）");
    }

    private function verifyPhpmailer($rootDir) {
        // composer require後にautoloaderが更新されているため、強制再読み込み
        require $rootDir . 'vendor/autoload.php';

        if (!class_exists('PHPMailer\PHPMailer\PHPMailer', true)) {
            throw new Exception("PHPMailer\\PHPMailer\\PHPMailer クラスが見つかりません");
        }
        $this->log("PHPMailer v" . \PHPMailer\PHPMailer\PHPMailer::VERSION . " [OK]");

        $ref = new \ReflectionProperty(\PHPMailer\PHPMailer\PHPMailer::class, 'LE');
        $le = $ref->getValue(new \PHPMailer\PHPMailer\PHPMailer());
        if ($le !== "\r\n") {
            throw new Exception("CRLF設定が不正: LE=0x" . bin2hex($le) . " (期待値: 0x0d0a)");
        }
        $this->log("SMTP CRLF準拠 [OK]");
    }

    private function updateSourceFiles($rootDir) {
        $useBlock = "\nuse PHPMailer\\PHPMailer\\PHPMailer;\nuse PHPMailer\\PHPMailer\\SMTP;\nuse PHPMailer\\PHPMailer\\Exception;\n";

        foreach ($this->sourceFilePatches as $relPath => $patch) {
            $file = $rootDir . $relPath;
            if (!file_exists($file)) {
                $this->log("スキップ（未存在）: {$relPath}");
                continue;
            }

            $content = file_get_contents($file);
            if (strpos($content, 'use PHPMailer\PHPMailer\PHPMailer;') !== false) {
                $this->log("スキップ（変更済み）: {$relPath}");
                continue;
            }

            $original = $content;

            // 旧require/include行をコメントアウトし、autoloadを追加
            $replaced = false;
            $content = preg_replace_callback(
                '/^((require_once|require|include_once)\s*[\(]?\s*["\'].*class\.(smtp|phpmailer)\.php["\'][\)]?\s*;)\s*\n/m',
                function ($match) use ($patch, &$replaced) {
                    $commented = '// ' . $match[1] . "\n";
                    if (!$replaced && $patch['autoload']) {
                        $replaced = true;
                        return $patch['autoload'] . "\n" . $commented;
                    }
                    return $commented;
                },
                $content
            );

            // use文の挿入
            if ($patch['use_after']) {
                $content = preg_replace(
                    '/(.+' . preg_quote($patch['use_after'], '/') . '.+\n)/',
                    "$1" . $useBlock, $content, 1
                );
            }

            if ($content !== $original) {
                file_put_contents($file, $content);
                $this->log("更新: {$relPath}");
            }
        }
    }

    private function isPhpmailerInstalled($rootDir) {
        return file_exists($rootDir . 'vendor/phpmailer/phpmailer/src/PHPMailer.php');
    }

    private function findComposerBin() {
        foreach (self::$composerPaths as $path) {
            if (is_executable($path)) return $path;
        }
        throw new Exception("composer コマンドが見つかりません（検索: " . implode(', ', self::$composerPaths) . "）");
    }
}
