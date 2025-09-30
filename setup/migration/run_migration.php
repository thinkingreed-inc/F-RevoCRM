<?php
/**
 * F-RevoCRM マイグレーション実行ツール
 * 
 * 使用方法: 
 *   php setup/migration/run_migration.php setup/migration/scripts/マイグレーションファイル.php    - 特定のマイグレーションを実行
 *   php setup/migration/run_migration.php setup/migration/scripts/マイグレーションファイル.php -d - 特定のマイグレーションの実行記録を削除
 *   php setup/migration/run_migration.php --all                                                   - すべての未実行マイグレーションを実行
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
    
    // setup/migration/scripts/ で始まるパスのみ許容
    if (strpos($migrationFile, 'setup/migration/scripts/') !== 0) {
        echo "エラー: マイグレーションファイルは 'setup/migration/scripts/' で始まる必要があります: {$migrationFile}\n";
        echo "正しい形式: setup/migration/scripts/YYYYMMDDHHmmss_migration_name.php\n";
        return false;
    }
    
    // setup/migration/scripts/ を scripts/ に変換してパスを構築
    $scriptsRelativePath = substr($migrationFile, strlen('setup/migration/'));
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
        $result = runSingleMigration('setup/migration/scripts/' . $file);
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

function deleteMigrationRecord($migrationFile) {
    global $scriptDir;
    
    // setup/migration/scripts/ で始まるパスのみ許容
    if (strpos($migrationFile, 'setup/migration/scripts/') !== 0) {
        echo "エラー: マイグレーションファイルは 'setup/migration/scripts/' で始まる必要があります: {$migrationFile}\n";
        echo "正しい形式: setup/migration/scripts/YYYYMMDDHHmmss_migration_name.php\n";
        return false;
    }
    
    // setup/migration/scripts/ を scripts/ に変換してパスを構築
    $scriptsRelativePath = substr($migrationFile, strlen('setup/migration/'));
    $migrationPath = $scriptDir . '/' . $scriptsRelativePath;
    
    if (!file_exists($migrationPath)) {
        echo "エラー: マイグレーションファイルが見つかりません: {$migrationFile}\n";
        return false;
    }
    
    // ファイル名からクラス名を抽出
    $className = extractClassNameFromFile($migrationPath);
    
    if (!$className) {
        echo "エラー: ファイルからマイグレーションクラスが見つかりません: {$migrationFile}\n";
        return false;
    }
    
    try {
        // データベース接続を取得
        global $adb;
        if (!$adb) {
            $adb = PearDatabase::getInstance();
        }
        
        // マイグレーション履歴テーブルの存在確認
        $checkTableSql = "SHOW TABLES LIKE 'com_vtiger_migrations'";
        $checkResult = $adb->query($checkTableSql);
        
        if ($adb->num_rows($checkResult) === 0) {
            echo "マイグレーション履歴テーブルが存在しません。削除する記録がありません。\n";
            return false;
        }
        
        // 実行済み記録の確認
        $checkSql = "SELECT migration_name FROM com_vtiger_migrations WHERE migration_name = ?";
        $checkResult = $adb->pquery($checkSql, array($className));
        
        if ($adb->num_rows($checkResult) === 0) {
            echo "マイグレーション '{$className}' の実行記録が見つかりません。\n";
            return false;
        }
        
        // 実行済み記録を削除
        $deleteSql = "DELETE FROM com_vtiger_migrations WHERE migration_name = ?";
        $adb->pquery($deleteSql, array($className));
        
        echo "マイグレーション '{$className}' の実行記録を削除しました。\n";
        return true;
        
    } catch (Exception $e) {
        echo "マイグレーション記録削除エラー {$migrationFile}: " . $e->getMessage() . "\n";
        return false;
    }
}

// メイン実行部分
if ($argc < 2) {
    echo "使用方法:\n";
    echo "  php setup/migration/run_migration.php setup/migration/scripts/マイグレーションファイル.php    - 特定のマイグレーションを実行\n";
    echo "  php setup/migration/run_migration.php setup/migration/scripts/マイグレーションファイル.php -d - 特定のマイグレーションの実行記録を削除\n";
    echo "  php setup/migration/run_migration.php --all                                                   - すべての未実行マイグレーションを実行\n";
    exit(1);
}

$argument = $argv[1];
$deleteMode = false;

// -dオプションの確認
if ($argc >= 3 && $argv[2] === '-d') {
    $deleteMode = true;
} elseif ($argc >= 3 && $argv[1] === '-d') {
    $deleteMode = true;
    $argument = $argv[2];
}

// --allオプションと-dオプションの併用チェック
if ($argument === '--all' && $deleteMode) {
    echo "エラー: --allオプションと-dオプションは併用できません。\n";
    exit(1);
}

echo "F-RevoCRM マイグレーション実行ツール\n";
echo "==================================\n\n";

if ($argument === '--all') {
    runAllMigrations();
} else {
    if ($deleteMode) {
        echo "マイグレーション記録を削除中: {$argument}\n";
        deleteMigrationRecord($argument);
    } else {
        echo "マイグレーションを実行中: {$argument}\n";
        runSingleMigration($argument);
    }
}
