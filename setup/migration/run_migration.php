<?php
/**
 * F-RevoCRM マイグレーション実行ツール
 * 
 * 使用方法: 
 *   php setup/migration/run_migration.php migration/scripts/マイグレーションファイル.php    - 特定のマイグレーションを実行
 *   php setup/migration/run_migration.php --all                                           - すべての未実行マイグレーションを実行
 */

// このスクリプトファイルのパスを基準にして適切なパスを取得
$scriptDir = dirname(__FILE__);
$rootDir = dirname(dirname($scriptDir));

// 適切なincludeを確保するためルートディレクトリに移動
chdir($rootDir);

// vtigerの設定をinclude
require_once 'config.inc.php';
require_once 'include/database/PearDatabase.php';

function runSingleMigration($migrationFile) {
    global $scriptDir;
    
    // migration/scripts/ で始まるパスのみ許容
    if (strpos($migrationFile, 'migration/scripts/') !== 0) {
        echo "エラー: マイグレーションファイルは 'migration/scripts/' で始まる必要があります: {$migrationFile}\n";
        echo "正しい形式: migration/scripts/YYYYMMDDHHmmss_migration_name.php\n";
        return false;
    }
    
    // migration/scripts/ を scripts/ に変換してパスを構築
    $scriptsRelativePath = substr($migrationFile, strlen('migration/'));
    $migrationPath = $scriptDir . '/' . $scriptsRelativePath;
    
    if (!file_exists($migrationPath)) {
        echo "エラー: マイグレーションファイルが見つかりません: {$migrationFile}\n";
        return false;
    }
    
    // マイグレーションファイルをinclude
    require_once $migrationPath;
    
    // ファイル名からクラス名を抽出
    $className = extractClassNameFromFile($migrationPath);
    
    if (!$className || !class_exists($className)) {
        echo "エラー: ファイルからマイグレーションクラスが見つかりません: {$migrationFile}\n";
        return false;
    }
    
    try {
        $migration = new $className();
        return $migration->execute();
    } catch (Exception $e) {
        echo "マイグレーション実行エラー {$migrationFile}: " . $e->getMessage() . "\n";
        // エラー発生時はnullを返してメイン処理で停止させる
        return null;
    }
}

function runAllMigrations() {
    global $scriptDir;
    $scriptsDir = $scriptDir . '/scripts';
    
    if (!is_dir($scriptsDir)) {
        echo "scriptsディレクトリが見つかりません: {$scriptsDir}\n";
        return;
    }
    
    $files = scandir($scriptsDir);
    
    $migrationFiles = array();
    foreach ($files as $file) {
        // タイムスタンプパターンのマイグレーションファイルにマッチ
        if (preg_match('/^\d{14}_.*\.php$/', $file)) {
            $migrationFiles[] = $file;
        }
    }
    
    // 時系列順に実行するためファイルをソート
    sort($migrationFiles);
    
    if (empty($migrationFiles)) {
        echo "scriptsディレクトリにマイグレーションファイルが見つかりません。\n";
        return;
    }
    
    echo "scriptsディレクトリで " . count($migrationFiles) . " 個のマイグレーションが見つかりました。\n";
    
    $successCount = 0;
    $skipCount = 0;
    
    foreach ($migrationFiles as $file) {
        echo "\n" . str_repeat('-', 50) . "\n";
        $result = runSingleMigration('migration/scripts/' . $file);
        if ($result === true) {
            $successCount++;
        } elseif ($result === false) {
            $skipCount++;
        } else {
            // エラーが発生した場合は処理を停止
            echo "\nエラー: {$file} でエラーが発生したため処理を停止します。\n";
            exit(1);
        }
    }
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "マイグレーション実行結果:\n";
    echo "- 実行済み: {$successCount}\n";
    echo "- スキップ: {$skipCount}\n";
    echo "- 合計: " . count($migrationFiles) . "\n";
}

function extractClassNameFromFile($filePath) {
    $content = file_get_contents($filePath);
    
    // クラス定義を検索（クラス名はMigrationで始まる）
    if (preg_match('/class\s+(Migration[a-zA-Z0-9_]+)\s+extends\s+FRMigrationClass/', $content, $matches)) {
        return $matches[1];
    }
    
    return null;
}

// メイン実行部分
if ($argc < 2) {
    echo "使用方法:\n";
    echo "  php setup/migration/run_migration.php migration/scripts/マイグレーションファイル.php    - 特定のマイグレーションを実行\n";
    echo "  php setup/migration/run_migration.php --all                                           - すべての未実行マイグレーションを実行\n";
    exit(1);
}

$argument = $argv[1];

echo "F-RevoCRM マイグレーション実行ツール\n";
echo "==================================\n\n";

if ($argument === '--all') {
    runAllMigrations();
} else {
    echo "マイグレーションを実行中: {$argument}\n";
    runSingleMigration($argument);
}
