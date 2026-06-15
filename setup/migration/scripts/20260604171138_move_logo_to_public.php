<?php
/**
 * マイグレーション: move_logo_to_public
 * 生成日時: 20260604171138
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260604171138_MoveLogoToPublic extends FRMigrationClass {
    
    /**
     * Issue #1637: publicディレクトリ移行前にアップロードされたロゴファイルが
     * test/logo/ に残り表示されなくなる問題への対応。
     * test/logo/ 配下のファイルを public/logo/ へ移動する。
     * 移動先に同名ファイルが既に存在する場合は上書きせずスキップする。
     */
    public function process() {
        $rootDir = realpath(dirname(__FILE__) . '/../../../');
        $srcDir  = $rootDir . '/test/logo';
        $destDir = $rootDir . '/public/logo';

        if (!is_dir($srcDir)) {
            $this->log("test/logo/ が存在しないためスキップ");
            return;
        }

        if (!is_dir($destDir)) {
            if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
                $this->log("public/logo/ の作成に失敗: {$destDir}");
                return;
            }
        }

        $movedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        $files = glob($srcDir . '/*');
        if ($files === false) {
            $this->log("test/logo/ の読み込みに失敗");
            return;
        }

        foreach ($files as $srcPath) {
            if (!is_file($srcPath)) {
                continue;
            }
            $basename = basename($srcPath);
            $destPath = $destDir . '/' . $basename;

            if (file_exists($destPath)) {
                $this->log("移動先に同名ファイル存在のためスキップ: {$basename}");
                $skippedCount++;
                continue;
            }

            if (@rename($srcPath, $destPath)) {
                $this->log("移動完了: {$basename}");
                $movedCount++;
            } else {
                $this->log("移動失敗: {$basename}");
                $failedCount++;
            }
        }

        $this->log("ロゴファイル移動完了 - 移動: {$movedCount}, スキップ: {$skippedCount}, 失敗: {$failedCount}");

        if ($failedCount > 0) {
            throw new Exception("ロゴファイル移動に {$failedCount} 件失敗しました。test/logo/ および public/logo/ の権限を確認のうえ、マイグレーションを再実行してください。");
        }
    }
}