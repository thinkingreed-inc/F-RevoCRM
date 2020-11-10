--
-- カスタマイズを含む全テーブルの削除
--
delimiter //
DROP PROCEDURE IF EXISTS drop_all_table//
CREATE PROCEDURE drop_all_table()
BEGIN
  DECLARE done INT;
  DECLARE _tableName VARCHAR(100);
  DECLARE cur CURSOR FOR
    select TABLE_NAME from information_schema.tables where TABLE_SCHEMA = (select database()) order by TABLE_NAME;
  DECLARE EXIT HANDLER FOR NOT FOUND SET done = 0;


  SET done = 1;
  OPEN cur;
  WHILE done DO
    FETCH cur INTO _tableName;

    SET @s = CONCAT('DROP TABLE IF EXISTS ' , _tableName);
    PREPARE stmt from @s;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END WHILE;
END
//

delimiter ;

SET FOREIGN_KEY_CHECKS=0;

call drop_all_table();
drop PROCEDURE drop_all_table;
