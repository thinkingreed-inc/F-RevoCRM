<?php
/**
 * マイグレーション: fix_error_reporting_for_php85
 * 生成日時: 20260518161627
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260518161627_FixErrorReportingForPhp85 extends FRMigrationClass {

    /**
     * マイグレーションを実行する
     *
     * PHP 8.4 以降で E_STRICT 定数が deprecated になったため、
     * config.inc.php の error_reporting から E_STRICT を除去し、
     * PHP_VERSION_ID による分岐に置き換える。
     * PHP 7.4 との互換性を維持する。
     */
    public function process() {
        $filename = 'config.inc.php';

        if (!file_exists($filename)) {
            $this->log("config.inc.php が見つかりません。スキップします。");
            return;
        }

        $contents = file_get_contents($filename);
        if ($contents === false) {
            throw new Exception("config.inc.php の読み込みに失敗しました。");
        }

        // E_STRICT を含まない場合、または既に修正済みの場合はスキップ
        if (strpos($contents, 'E_STRICT') === false || strpos($contents, '$_e_strict') !== false) {
            $this->log("config.inc.php の error_reporting は修正不要です。");
            return;
        }

        $lines = explode("\n", $contents);
        $newLines = array();
        $definitionInserted = false;

        foreach ($lines as $line) {
            // version_compare 付きの行を単純な error_reporting に変換
            if (strpos($line, 'version_compare') !== false && strpos($line, 'E_STRICT') !== false) {
                $trimmed = ltrim($line);
                if (strpos($trimmed, '//') === 0) {
                    // コメント行: version_compare を除去して後半の error_reporting のみ残す
                    if (preg_match('/error_reporting\([^)]*E_STRICT[^)]*\)/', $line, $matches)) {
                        $replacement = str_replace('E_STRICT', '$_e_strict', $matches[0]);
                        $suffix = '';
                        if (preg_match('/(\/\/\s*(?:DEBUGGING|PRODUCTION).*)$/', $line, $suffixMatch)) {
                            $suffix = '   '.$suffixMatch[1];
                        }
                        $line = '//ini_set(\'display_errors\',\'on\'); '.$replacement.';'.$suffix;
                    }
                } else {
                    // 非コメント行: 後半の error_reporting のみ残す
                    if (preg_match('/error_reporting\([^)]*E_STRICT[^)]*\)/', $line, $matches, 0, strrpos($line, 'error_reporting'))) {
                        $replacement = str_replace('E_STRICT', '$_e_strict', $matches[0]);
                        $suffix = '';
                        if (preg_match('/(\/\/\s*(?:DEBUGGING|PRODUCTION).*)$/', $line, $suffixMatch)) {
                            $suffix = ' '.$suffixMatch[1];
                        }
                        $line = $replacement.';'.$suffix;
                    }
                }
            }

            // E_STRICT を含む error_reporting 行を変換（version_compare なし）
            if (strpos($line, 'E_STRICT') !== false && strpos($line, 'error_reporting') !== false) {
                $line = str_replace('E_STRICT', '$_e_strict', $line);
            }

            // $_e_strict を使う最初の非コメント行の前に変数定義を挿入
            if (!$definitionInserted && strpos($line, '$_e_strict') !== false) {
                $trimmed = ltrim($line);
                if (strpos($trimmed, '//') !== 0) {
                    $indent = substr($line, 0, strlen($line) - strlen($trimmed));
                    $newLines[] = $indent.'// E_STRICT is deprecated since PHP 8.4';
                    $newLines[] = $indent.'$_e_strict = (PHP_VERSION_ID < 80400) ? E_STRICT : 0;';
                    $definitionInserted = true;
                }
            }

            $newLines[] = $line;
        }

        $newContents = implode("\n", $newLines);

        if (file_put_contents($filename, $newContents) === false) {
            throw new Exception("config.inc.php の書き込みに失敗しました。手動で E_STRICT を修正してください。");
        }

        $this->log("config.inc.php の error_reporting を PHP 8.4+ 互換に更新しました。");
    }
}
