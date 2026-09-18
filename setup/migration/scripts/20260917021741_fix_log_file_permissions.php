<?php

/**
 * マイグレーション: fix_log_file_permissions
 * 生成日時: 20260917021741
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260917021741_FixLogFilePermissions extends FRMigrationClass
{
    /** ログファイルに与えるパーミッション */
    public const LOG_FILE_PERMISSION = 0666;

    /**
     * Issue #1850: ログファイルが読み取り不可のパーミッション（8進数 1232）で
     * 作成されていた問題への対応。
     * ロガー側は修正済みだが、書き込みが発生しない過去日のログは古い権限のまま
     * 残るため、logs/ 直下の読めなくなったログを 0666 に直す。
     * ログの出力先は log4php.properties の appender 設定に従うため、既定の
     * logs/ 以外へ向けている環境は対象外。
     */
    public function process(): void
    {
        $logDir = dirname(__DIR__, 3) . '/logs';
        $this->applyTo($logDir);
    }

    /**
     * 指定ディレクトリ直下の .log のうち、所有者に読み権限が無いものを 0666 に直す。
     *
     * logs/ には Logger 以外が書くファイル（fileMissing.log など）や運用者が置いた
     * ファイルも混ざるため、所有者が読める権限（0600 など）は運用者の意図とみなし変更しない。
     * 対象はディレクトリ直下の通常ファイルのみで、logs/cron/ のようなサブディレクトリと
     * シンボリックリンクは見ない。
     * chmod はファイル所有者と実行ユーザーが異なると失敗するが、
     * ログの権限修正のためにマイグレーション全体を止める必要は無いため、
     * 失敗件数を記録するだけで例外は投げない。
     *
     * @param string $logDir ログディレクトリの絶対パス
     */
    public function applyTo(string $logDir): void
    {
        if (!is_dir($logDir)) {
            $this->log("ログディレクトリが存在しないためスキップ: {$logDir}");
            return;
        }

        // glob() はパス側の [ ] * ? もパターンとして解釈し、該当すると黙って 0 件になる。
        // ログディレクトリのパスは環境依存のため scandir で列挙する。
        $entries = scandir($logDir);
        if ($entries === false) {
            $this->log("ログディレクトリの読み込みに失敗: {$logDir}");
            return;
        }

        $files = [];
        foreach ($entries as $entry) {
            if (substr($entry, -4) !== '.log') {
                continue;
            }
            $files[] = $logDir . '/' . $entry;
        }

        $changedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($files as $path) {
            // chmod はリンク先に作用するため、シンボリックリンクは対象にしない
            if (!is_file($path) || is_link($path)) {
                continue;
            }

            clearstatcache(true, $path);
            $perms = fileperms($path);
            if ($perms === false) {
                $this->log("パーミッションの取得に失敗: " . basename($path));
                $failedCount++;
                continue;
            }

            // 所有者が読めるものは運用者が意図して設定した権限とみなし、触れない。
            // 直すのは #1850 のバグで作られた「所有者すら読めない」ファイルだけ。
            if (($perms & 0400) !== 0) {
                $skippedCount++;
                continue;
            }

            if (@chmod($path, self::LOG_FILE_PERMISSION)) {
                $this->log("パーミッション変更: " . basename($path));
                $changedCount++;
            } else {
                $this->log("パーミッション変更に失敗: " . basename($path));
                $failedCount++;
            }
        }

        $this->log("ログのパーミッション修正完了 - 変更: {$changedCount}, スキップ: {$skippedCount}, 失敗: {$failedCount}");

        if ($failedCount > 0) {
            $this->log("失敗したファイルは所有者が異なる可能性があります。`chmod 0644 {$logDir}/*.log` を手動で実行してください。");
        }
    }
}
