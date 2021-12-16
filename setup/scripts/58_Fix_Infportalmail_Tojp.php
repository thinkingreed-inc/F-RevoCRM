<?php

$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');


$sql = "UPDATE vtiger_emailtemplates SET body=? WHERE templateid = 10";
$params = array(
    "
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
                                Dear $contacts-firstname$ $contacts-lastname$,</div>

                                <div style="margin-top:20px;margin-bottom:20px; color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">私たちからお客様へ優れた サポートを提供することを保証いたします。<br />
                                この機会に、貴重なお客様のために設置したポータルをご紹介させていただきたいと思います。<br />
                                このポータルでは、質問や問題を提出したり、過去の問題や回答を確認したりすることができます。<br />                        また、当社のデータベースや過去にお客様と共有したドキュメントにア クセスすることができます。</div>
                                <div style="margin-top:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">$URL$ to login to the portal, with the credentials below.</div>

                                <div style="margin-top:20px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">Your Username: $login_name$</div>

                                <div style="margin-bottom:20px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">Your Password: $password$</div>

                                <div class="gmail_extra" style="margin-top:10px;color: rgb(34, 34, 34); font-family: arial, sans-serif; font-size: 14px; background-color: rgb(255, 255, 255);">Thank you,<br />
                                $contacts-smownerid$</div>
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
    "
);
$db->pquery($sql, $params);