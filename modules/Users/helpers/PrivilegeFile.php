<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

class Users_PrivilegeFile_Helper
{
    /**
     * 権限ファイルを原子的に書き換える。
     * 同じファイルを切り詰めてから書くと、その間に require したリクエストが
     * 空のユーザー情報を読み、そのまま保存してしまう（#1896）。
     * 同じディレクトリの一時ファイルに書いてから rename で置き換えるため、
     * 読み込み側は常に書き換え前か後の完全な内容を読む。
     *
     * @param string $path    書き込み先
     * @param string $content 書き込む内容
     * @return bool 成功時 true
     */
    public static function write(string $path, string $content): bool
    {
        $tmpPath = @tempnam(dirname($path), basename($path) . '.tmp');
        if ($tmpPath === false) {
            return false;
        }

        // tempnam は 0600 で作るため、Web と cron で実行ユーザーが違っても読めるよう既存の権限に揃える
        $mode = file_exists($path) ? fileperms($path) & 0777 : 0666 & ~umask();
        if (
            file_put_contents($tmpPath, $content) !== strlen($content)
            || !chmod($tmpPath, $mode)
            || !rename($tmpPath, $path)
        ) {
            @unlink($tmpPath);
            return false;
        }
        return true;
    }
}
