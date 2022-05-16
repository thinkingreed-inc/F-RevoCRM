<?php

$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

global $adb;

$sql = "UPDATE vtiger_emailtemplates SET body=?, subject=? WHERE templateid = 10";
$body = <<<EOF
    <html>
        <head>
                <title></title>
        </head>
        <body class="scayt-enabled"><!-- <center> -->
        <table border="0" cellpadding="0" cellspacing="0" class="borderGrey" style="margin-left:0px;" width="600px">
                <tbody>
                        <tr>
                                <td colspan="6"><!-- Begin Pre header --><!-- // End Pre header \ --></td>
                        </tr>
                        <tr style="height:50px;">
                                <td colspan="6" style="
                        font-family: Helvetica,Verdana,sans-serif">
                                <div style="margin-bottom:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);"><br />
                                \$contact_name$ 様へ</div>

                                <div style="margin-top:20px;margin-bottom:20px; color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">いつも弊社サービスをご利用いただきありがとうございます。<br />
                                <br />
                                本メールは、弊社カスタマーポータルへの招待メールとなります<br />
                                カスタマーポータルでは、お問い合わせ管理や、過去のFAQと回答をご確認いただけます。<br />                        
                                ポータルへのアクセスは本メールに記載のURL、ID、パスワードを使いログインしてください。</div>
                                <div style="margin-top:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">\$portal_URL$ </div>

                                <div style="margin-top:20px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">Your Username: \$login_name_portal$</div>

                                <div style="margin-bottom:20px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">Your Password: \$pw_portalmail$</div>

                                <div class="gmail_extra" style="margin-top:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);"><br />
                                </div>
                                </td>
                        </tr>
                        <tr>
                                <td colspan="6" style="font-family: Helvetica,Verdana,sans-serif;font-size: 11px;color: #666666;">
                                <table border="0" cellpadding="4" cellspacing="0" width="100%">
                                        <tbody><!--copy right data-->
                                                <tr>
                                                        <td style="
                                    padding-left: 0px;
                                    padding-right: 0px;
                                    width:350px" valign="top">
                                                        <div style="margin-top:20px;"><em>Powered By <a href="f-revocrm.jp">F-RevoCRM</a></em>
                                                        <div></div>
                                                        </div>
                                                        </td>
                                                </tr>
                                                <!--subscribers links-->
                                        </tbody>
                                </table>
                                </td>
                        </tr>
                </tbody>
        </table>
        <!-- </center> --></body>
    </html>
EOF;

$params = array($body, '顧客ポータルのログイン情報のお知らせ');

$adb->pquery($sql, $params);