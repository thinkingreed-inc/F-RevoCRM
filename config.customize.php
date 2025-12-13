<?php
// PDF
$is_headlesschrome = false;// trueの場合：headless chromeを使用。falseの場合：TCPDFを使用。
$chromeurl = "http://localhost:30080/converthtmltopdf.php";// headlless chromeの場所またはコマンド
#$chromeurl = "google-chrome";// headlless chromeの場所（Linux）
#$chromeurl = "\"C:\Program Files\Google\Chrome\Application\chrome.exe\"";// headlless chromeの場所（Windows）
$hostfiledirectory = "/var/www/html2pdf/";//PDF作成場所（Linux）
#$hostfiledirectory = "D:/Applications/F-RevoCRM/crm/test/pdf/";//PDF作成場所（Windows）
$dokerfiledirectory = "/html2pdf/";//コマンド実行の場合はコメントアウトする
$show_subordinate_roles_list = true;// trueの場合：共有リスト欄に下位の役割が作成した全てのリストを表示。
