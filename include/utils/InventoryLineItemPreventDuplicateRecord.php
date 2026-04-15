<?php

class InventoryLineItemPreventDuplicateRecord 
{
    // 最大の待機時間
    const EXPIRED_SECONDS = 180;
    // 次のチェックまでの間隔
    const CHECKING_INTERVAL_SECONDS = 3;
    
    /**
     * 更新中のInventoryレコードの情報が存在するか確認する
     *
     * @param int $parentRecordId The ID of the parent record.
     * @return bool True if duplicate exists, False otherwise.
     */ 
    protected static function checkExecutingRecords($parentRecordId) 
    {
        if(empty($parentRecordId)) {
            return false;
        }
        
        $db = PearDatabase::getInstance();
        $query = 'SELECT COUNT(*) AS count 
                  FROM vtiger_execute_lineitem 
                  WHERE parent_id = ?';
        $result = $db->pquery($query, [$parentRecordId]);
        
        $row = $db->fetchByAssoc($result);
        return $row && $row['count'] >= 1;
    }
    
    /**
     * 更新中のInventoryレコードの情報を追加する
     *
     * @param int $parentRecordId The ID of the parent record.
     * @return bool True if lock acquired, False otherwise.
     */
    protected static function addExecutingRecord($parentRecordId)
    {
        $db = PearDatabase::getInstance();
        $currentTime = date('Y-m-d H:i:s');
        $query = 'INSERT IGNORE INTO vtiger_execute_lineitem (parent_id, executed_at)
                  VALUES (?, ?)';
        $result = $db->pquery($query, [$parentRecordId, $currentTime]);
        // INSERT IGNOREで重複時は挿入されない → affected_rows=0 → 他プロセスが先にロック取得済み
        if ($db->getAffectedRowCount($result) === 0) {
            return false;
        }
        return true;
    }
    
    /**
     * 更新が完了したInventoryレコードの情報を削除する
     * 
     * @param int $parentRecordId The ID of the parent record.
     * @return void
     */
    protected static function removeExecutingRecord($parentRecordId) 
    {
        $db = PearDatabase::getInstance();
        $query = 'DELETE FROM vtiger_execute_lineitem 
                  WHERE parent_id = ?';
        $db->pquery($query, [$parentRecordId]);
    }
    
    /**
     * 有効期限切れのInventoryレコードの情報を削除する
     * 
     * @return void
     */
    protected static function removeExpiredExecutingRecord() 
    {
        $db = PearDatabase::getInstance();
        $expiredTime = date(
            'Y-m-d H:i:s', 
            strtotime('-'.self::EXPIRED_SECONDS.' seconds')
        );
        $query = 'DELETE FROM vtiger_execute_lineitem
                  WHERE executed_at <= ?';
        $db->pquery($query, [$expiredTime]);
    }
    
    /**
     * 指定の間隔で現在更新中のInventoryレコードの処理が完了したかチェックする
     *
     * @param int $parentRecordId The ID of the parent record.
     * @return bool True if timeout reached, False otherwise.
     */
    protected static function periodicExecutingCheck($parentRecordId) 
    {
        $elapsedTime = 0;
        while ($elapsedTime < self::EXPIRED_SECONDS) {
            if (!self::checkExecutingRecords($parentRecordId)) {
                return true;
            }
            sleep(self::CHECKING_INTERVAL_SECONDS);
            $elapsedTime += self::CHECKING_INTERVAL_SECONDS;
        }
        return false;
    }
    
    /**
     * Inventoryレコードの明細レコード（子）が重複登録されないように制御を開始する
     *
     * @param int $parentRecordId The ID of the parent record.
     * @return void
     */
    public static function startPreventDuplicate($parentRecordId)
    {
        if(empty($parentRecordId)) {
            return;
        }

        self::removeExpiredExecutingRecord();
        if(self::periodicExecutingCheck($parentRecordId)) {
            if(!self::addExecutingRecord($parentRecordId)) {
                // INSERT IGNOREで失敗 = 他プロセスが先にロック取得 → 再度待機
                if(self::periodicExecutingCheck($parentRecordId)) {
                    if(!self::addExecutingRecord($parentRecordId)) {
                        throw new Exception('明細レコードの重複登録を防止するための待機時間が超過しました。しばらく経ってから再度お試しください。');
                    }
                } else {
                    throw new Exception('明細レコードの重複登録を防止するための待機時間が超過しました。しばらく経ってから再度お試しください。');
                }
            }
        } else {
            throw new Exception('明細レコードの重複登録を防止するための待機時間が超過しました。しばらく経ってから再度お試しください。');
        }
    }
    
    /**
     * Inventoryレコードの明細レコード（子）の重複登録制御を終了する
     *
     * @param int $parentRecordId The ID of the parent record.
     * @return void
     */
    public static function endPreventDuplicate($parentRecordId)
    {
        self::removeExecutingRecord($parentRecordId);
        self::removeExpiredExecutingRecord();
    }
}
