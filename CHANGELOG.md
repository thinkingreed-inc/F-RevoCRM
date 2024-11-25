# 更新履歴

## パッチ適用方法
 - ファイル、DBのバックアップを確実に取得してください
 - 差分ファイルを上書き更新してください
 - 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`
※ドメインやディレクトリはお使いのF-RevoCRMに合わせてください。

* 機能改善
  - #800 [要望]一覧画面で表示される関連項目のtooltipの表記を項目名ではなくデータの詳細を表記してほしい
  - #870 [要望]一覧表示のテーブルを今時の使いやすいものにしてほしい
  - #906 [要望]チケットのクイック作成時に詳細内容を入力する際、改行コードを入れなくても改行できるようにしてほしい
  - #942 [要望]ドキュメントファイルアップロードにおいて、外部サイトのファイルURL指定時、入力欄で255文字以上入力できるようにしてほしい
  - #963 [要望]ワークフローなどでメール通知をしたものが、顧客企業モジュールの「概要」タブ活動の欄で HTMLタグが表示されるのでタグを除外してほしい。
  - #988 [要望]ログイン履歴にログインエラーとポータルのログイン履歴を持つようにしたい
  - #997 [要望]個人カレンダーに表示する他モジュールを全員分出したい
  - #1010 [要望]日報のリストで「提出先」を「自分」に指定できるようにしたい
  - #1028 [要望]一覧画面の列幅可変機能が使いづらい
  - #1050 [要望]カレンダーの表示が遅い
  - #1092 [要望]カレンダーで活動をマウスオーバーした時に、作成者を表示させるように修正
  - #1125 [要望]モジュールの一覧画面から、レコードの複製ができるようにしてほしい。
  - #1143 [要望]複数選択が可能な項目で選択する度に選択肢の位置が最初に戻らないようにしてほしい

* 不具合修正
  - #564 [不具合]カレンダー画面にてTODOを編集した場合でも「レコードを作成しました」のメッセージが表示される
  - #644 [不具合]スケジュールワークフローの設定最大数を最初から設定したい。
  - #712 [不具合]マージインポートをすると、重複するレコードが自動で削除されてしまう
  - #881 [不具合]Androidで検索ができない
  - #905 [不具合]ブックマークの一覧画面から削除が実施できない
  - #933 [不具合]一覧画面上でのダブルクリック編集時にチェックボックス項目について既存の値が「はい」だったとしても「いいえ」として編集状態になる。
  - #954 [不具合]項目設定でカレンダー、活動がモジュール一覧から出てこなくなった
  - #958 [不具合]複数のプロファイルを役割に付けた際に「操作」の権限が無効にできないケースがある
  - #968 [不具合]資産・レンタル管理を新規作成するとFatal Errorが発生する
  - #980 [不具合]リードの電話番号（予備）の形式がメールアドレス
  - #983 [不具合]PDFテンプレートモジュールにモジュール無効化やプロファイル設定で制限をかけてもアクセスができる
  - #987 [不具合]Contactsモジュールに住所項目を持っていない場合でもAccountsの住所を上書きするかのダイアログが表示される
  - #994 [不具合]ToDoを日またぎで登録した際にカレンダーで最終日が表示されない
  - #999 [不具合]個人カレンダーのカレンダー種別の設定がPHP8対応されていない
  - #1003 [不具合]カレンダーで月をまたいで3か月以上の終日活動を登録すると、3か月先からカレンダーに活動が表示されない
  - #1006 [不具合]レポートのCSVエクスポートにてInternalServerErrorが発生する
  - #1008 [不具合]レポート 明細のあるモジュールと明細の元となるモジュールを関連モジュールにすると余計に結合される
  - #1012 [不具合]ワークフロー 定期的に実行 を使用する際に 日付項目に対して 空ではない 条件を指定すると正しく動作しない
  - #1015 [不具合]ディスプレイサイズが推奨の解像度の範囲内にも関わらず文字が見切れる
  - #1021 [不具合]クイック作成から詳細の画面に遷移する際にエラーになることがある
  - #1024 [不具合]一覧画面において?（半角ハテナ）を検索するとSQLエラーが発生する
  - #1026 [不具合]チケットの項目「解決方法」の更新ができない
  - #1031 [不具合]ドキュメントモジュールより、Tableを使って入力・登録すると、登録した表が上下に重複して表示される。 なおかつ、最後に 「/>」が表示される。
  - #1033 [不具合]ユーザー情報の項目「メールの署名」が更新できない
  - #1042 繰り返しスケジュールを保存すると、エラーになる不具合の修正
  - #1046 [不具合]クイック作成→詳細入力時に複数選択項目の値が引き継がれない
  - #1052 [不具合]追加されたモジュールにて主要項目が一つも設定されていない場合Fatal errorが発生する
  - #1056 [不具合]PHP8で関連レコードを登録した後にエラーが発生する
  - #1063 [不具合]顧客企業の活動一覧にデータが表示されない
  - #1066 [不具合]ユーザーにフォローされているデータを編集できない
  - #1071 [不具合]モジュールに一つも「主要項目」が存在しないと概要表示でエラーが出る。
  - #1073 [不具合]活動のクイック作成の「参加者」が太字になっていない
  - #1075 [不具合]活動のクイック作成の参加者をセット後、詳細入力に遷移すると参加者が消える
  - #1080 [不具合]案件の概要の活動の仕様改善
  - #1082 [不具合]活動の参加者がマウスオーバーで表示されない
  - #1085 [不具合]PHP8の環境でパスワードの再設定ができない
  - #1087 [不具合]リストでドキュメントのフォルダを条件に指定すると動かない
  - #1091 [不具合]重複の検出後に出てくるリストの一番上の「マージ」ボタンの判定がおかしい
  - #1093 [不具合]REST APIが、PHP 8以上の環境で正常に動作しない
  - #1095 [不具合]cronによるスケジュールインポートでスキップ等が発生すると、スケジュールインポートが完了しない
  - #1106 [不具合]共有リストをデフォルト表示できない
  - #1108 [不具合]ダッシュボードでリードのウィジェットが正しく動作していない
  - #1110 [不具合]リードの昇格ができなくなっている
  - #1112 [不具合]ユーザーの並び替えなどで応答がなくなる
  - #1115 [不具合]リードのウィジェットで条件を指定するとデータが表示されない
  - #1117 [不具合]一覧画面でEnterによる検索実行結果が正しくない
  - #1120 [不具合]ウィジェット関係のSQLが劣化し、正常に表示されないものが存在する
  - #1123 [不具合]ウィジェットの削除・更新ボタンの表示が重なっている
  - #1127 [不具合]活動保存時にICSファイルを生成する処理を通るとエラー落ちする
  - #1134 [不具合]一覧画面でスクロールを行うとヘッダーの項目名部分と検索ボックス部分の隙間からスクロールしたコンテンツが見える。
  - #1136 [不具合]並び替えのためのリストの表示順が保持されない
  - #1140 [不具合] ユーザーの作成/更新時に user_priviileges を2回作成する

* 翻訳修正
  - #719 [翻訳]レポート画面の翻訳漏れ

## Contributors
Thank you for contributing!
<div>
<a href="https://github.com/amino1205"><img src="https://github.com/amino1205.png" title="amino1205" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/hasesho28"><img src="https://github.com/hasesho28.png" title="hasesho28" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/junmt"><img src="https://github.com/junmt.png" title="junmt" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/kento-yn"><img src="https://github.com/kento-yn.png" title="kento-yn" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/kkouta"><img src="https://github.com/kkouta.png" title="kkouta" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/K-Haruto"><img src="https://github.com/K-Haruto.png" title="K-Haruto" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/MiyamotoKoki"><img src="https://github.com/MiyamotoKoki.png" title="MiyamotoKoki" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/Remicck"><img src="https://github.com/Remicck.png" title="Remicck" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/Ryon1211"><img src="https://github.com/Ryon1211.png" title="Ryon1211" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/sanototsuka"><img src="https://github.com/sanototsuka.png" title="sanototsuka" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/sei-sato-5069"><img src="https://github.com/sei-sato-5069.png" title="sei-sato-5069" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/taishoiwamoto"><img src="https://github.com/taishoiwamoto.png" title="taishoiwamoto" width="40" height="40" style="border-radius: 50%;"></a>
</div>

# F-RevoCRM7.4.0
## パッチ適用方法
- ファイル、DBのバックアップを確実に取得してください
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

* 機能改善
  - #872 [要望]共有カレンダーの全チェックON/OFF機能の追加
  - #897 [要望]関連のコメントを開いた際に子コメント全てが表示された状態にしてほしい
  - #570 [要望]同名のレポートが存在した場合の警告文を変更
  - #887 [要望]適格請求書のテンプレートを追加
  - #892 [要望]日報モジュールの概要画面の「活動」部分が読みづらい
  - #856 [要望]PHP8.x対応

* 不具合修正
  - #894 [不具合]子コメントのついた親コメントを削除すると、コメント件数がおかしくなる箇所の修正
  - #591 [不具合]活動のインポートでの不具合を修正
  - #728 [不具合]作成するタグが他タグと同名でも、別IDになるように修正
  - #489 [不具合]一部罫線のスタイルの崩れを修正
  - #877 [不具合]プロファイルの権限は「複製」の作成を制御するものではなく、「重複の検出」を制御するための権限であるため、翻訳を修正
  - #878 [不具合]ユーザー名の変更モーダルの「新しいユーザー名」にinputElementクラスが当たっていなかったので追加
  - #879 [不具合]選択肢の入力画面で空にも関わらず「オプションの選択」になるケースがある

## Contributors
Thank you for contributing!
<div>
<a href="https://github.com/K-Haruto"><img src="https://github.com/K-Haruto.png" title="K-Haruto" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/kkouta"><img src="https://github.com/kkouta.png" title="kkouta" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/kento-yn"><img src="https://github.com/kento-yn.png" title="kento-yn" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/Remicck"><img src="https://github.com/Remicck.png" title="Remicck" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/Ryon1211"><img src="https://github.com/Ryon1211.png" title="Ryon1211" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/junmt"><img src="https://github.com/junmt.png" title="junmt" width="40" height="40" style="border-radius: 50%;"></a>
</div>

# F-RevoCRM7.3.9
## パッチ適用方法
- ファイル、DBのバックアップを確実に取得してください
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

* 機能改善
  - #847 [要望]リストで数値を範囲検索できるようにしてほしい
  - #742 [要望]カレンダー上の予定から、顧客企業などの関連リンクに飛べる機能を追加
  - #767 [要望]翻訳がかかっている選択肢項目のエクスポート、インポートについての修正
  - #766 [要望]活動モジュール項目の並べ替えができるように修正
  - #743 [要望]カレンダーをドラッグで指定した範囲の予定を作成できる機能を追加
* 不具合修正
  - #873 [不具合]コマンドインジェクションの脆弱性
  - #874 [不具合]ラベル項目に対するXSSの脆弱性
  - #774 [不具合]日またぎの終日の活動の期間が計算されていない問題を修正
  - #574 [不具合]共有カレンダーにて、予定をマウス移動すると非表示にしていたユーザーの予定も表示される
  - #549 [不具合]共有カレンダーにて、現在のログインユーザーの予定が参加者の予定と一緒に移動しない
  - #325 [不具合]カレンダーのスケジュール削除時に共同参加者も削除するように修正
  - #854 [不具合]参照フィールドを編集した際に送信されるメールにて編集後の値が表示されるように修正
  - #854 [不具合]メールタイトルの日本語翻訳を修正
  - #840 [不具合]閲覧権限のないレポートを非表示にするように修正
  - #829 [不具合]レポートの保存ボタンが表示されるように修正
  - #835 [不具合]見積の新規作成画面にて控除された税金のキャンセルが効くように修正
  - #826 [不具合]ダッシュボードのセールスファネルのグラフの数値がずれている問題を修正
  - #793 [不具合]選択肢エディタで色を設定すると項目名が英語になる不具合を修正
  - #539 [不具合]繰り返し予定の担当者を変更時、「以降の～」「全て」を選択すると一日だけ変更前・変更後の担当者が参加者に残る
  - #610 [不具合]契約からチケットへ関連のラベル名修正
  - #744 [不具合]マイグループのチェックボックスが変化しないよう修正
  - #746 [不具合]活動を作成している途中で活動タイプを変更しても終了側の日付が変更されないように修正
  - #894 [不具合]子コメントのついた親コメントを削除すると、コメント件数がおかしくなる箇所の修正

* その他
  - Readmeに「.htaccess」に関する注意書きを追記

## Contributors
Thank you for contributing!
<div>
<a href="https://github.com/K-Haruto"><img src="https://github.com/K-Haruto.png" title="K-Haruto" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/kkouta"><img src="https://github.com/kkouta.png" title="kkouta" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/pavish69"><img src="https://github.com/pavish69.png" title="pavish69" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/kento-yn"><img src="https://github.com/kento-yn.png" title="kento-yn" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/Remicck"><img src="https://github.com/Remicck.png" title="Remicck" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/Sota-Miyamoto"><img src="https://github.com/Sota-Miyamoto.png" title="Sota-Miyamoto" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/junmt"><img src="https://github.com/junmt.png" title="junmt" width="40" height="40" style="border-radius: 50%;"></a>
</div>

# F-RevoCRM7.3.8
## パッチ適用方法
- ファイル、DBのバックアップを確実に取得してください
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

* 機能改善
  - #830 [要望]リストが自動で共有されてしまい、大きい組織で使いづらい
* 不具合修正
  - #838 [不具合]php Dockerイメージ作成時 pecl install xdebugにてエラーとなる件の対処
* その他
  - Readmeに公式サイトへのURLを追加
  - インストール時のアンケートに利用用途とプライバシーポリシーのリンクを追加

## Contributors
Thank you for contributing!

<a href="https://github.com/kazuihitoshi"><img src="https://github.com/kazuihitoshi.png" title="kazuihitoshi" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/kento-yn"><img src="https://github.com/kento-yn.png" title="pavish69" width="40" height="40" style="border-radius: 50%;"></a>

# F-RevoCRM7.3.7
## パッチ適用方法
- ファイル、DBのバックアップを確実に取得してください
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

* 機能改善
  - #694 [要望]レポートの副モジュールに関連項目で紐づくモジュールを選択できるように拡張
  - #687 [要望]スケジュールワークフローの実行件数に関するコンフィグが、config.inc.phpにて生成されない
  - #662 [要望]カレンダーのデフォルトの活動周期の文言を統一
  - #620 [要望]消費税計算の標準税率と軽減税率がまざった請求書等を発行できるように改善
  - #536 [要望]顧客担当者概要画面からチケット作成を行った際に、担当者に紐づく顧客企業を自動的に登録されるように改善
  - #453 [要望]画像をアップロードした際にプレビューが表示されるように改善
  - #495 [要望]リスト作成後に項目名を変更した項目もリストの編集・複製時に引き継がれるように改善
  - #354 [要望]TODOとプロジェクトタスクを、カレンダー上のポップアップから完了にできるように改善
  - #319 [要望]生年月日を入力することにより、現在の年齢を表示できるように改善
  - #280 [要望]TODO管理のユーザ選択で候補の一番上に自分自身を表示するように改善
  - #92 [要望]Webフォームの「フォームを表示する」の表示を改善
  - #70 [要望]日付の項目を作成する際、デフォルト値にその日の日付を設定できるように改善
  - #692 [要望]タグの所有者をわかるように修正
  - #558 [要望]WebAPIの最大取得件数を増加
  - #556 [要望]レポートの項目選択の最大数を拡張
  - #555 [要望]レポートの副モジュールの最大選択数を拡張
  - #512 [要望]活動を作成するワークフローを設定する際に該当モジュール項目の内容を反映できるようにしてほしい
* 不具合修正
  - #573 [不具合]ログイントップ画面のでRSS取得処理に失敗してログインできない問題を修正
  - #699 [翻訳]メール作成画面のAddCCとAddBcc
  - #691 [翻訳]レポート作成・編集画面での翻訳漏れ
  - #679 [不具合]活動をワークフローで「値の更新」を行うと、招待者がいる場合にエラーになる問題を修正
  - #678 [不具合]ドキュメントのクイック作成で非表示の項目まで入力できてしまう問題を修正
  - #667 [不具合]一覧画面から複数のユーザーにメール送信を行う画面を開くと、JSエラーが発生して正常に動作しない問題を修正
  - #653 [不具合]関連のユーザーをエクスポートする際、異なるデータがエクスポートされる問題を修正
  - #652 [不具合]参照権限のみのユーザーでCSVエクスポートができない問題を修正
  - #646 [不具合] ワークフローの複数選択肢条件が機能するように修正
  - #632 [不具合]見積・受注・請求モジュールの編集画面上だけ「在庫が不足しています 最大値：0」が消えない問題を修正
  - #631 [不具合]カレンダー画面にて活動のポップアップを表示した際、非公開として登録した予定の内容が表示されている問題を修正
  - #629 [不具合]メールの「クリック数」のカウントが動作するように修正
  - #625 [不具合]消費税が「個別」の請求書を出力すると、値がおかしい問題を修正
  - #609 [不具合]管理者以外のユーザーでログインするとインポートする際に「2 重複レコードの処理方法」で「選択可能な項目」に一覧が表示されるように修正
  - #605 #603 「活動の追加」をする際に「顧客企業」選択後「ご担当者様名」の検索が正常にできない問題の修正
  - #600 [不具合]dockerで環境構築時にdbコンテナのビルドに失敗する問題を修正
  - #597 [不具合]ユーザー一覧画面から「ユーザー名の変更」ができない問題を修正
  - #579 [不具合]カレンダー設定を開いたときに、画面の幅が狭いとカレンダー共有の表示が崩れる問題を修正
  - #578 [不具合]Excelファイルをアップロードした場合、vtiger_notesのfiletypeの桁数が足りていない問題を修正
  - #577 [不具合]PDFテンプレートに画像をコピー＆ペーストで貼り付けると、PDFが出力されない問題を修正
  - #569 [不具合]レポートで画面サイズを小さくすると、表示がずれてしまう問題を修正
  - #565 #538 活動を複製するときにEnterキーを連打すると活動が複数作成される問題を修正
  - #562 [不具合]カスタム項目の関連として「カレンダー」もしくは「活動」を追加するとテキストとして保存される問題を修正
  - #560 [不具合]WebAPI取得されるモジュール名等が文言変更機能を通ってない問題を修正
  - #559 [不具合]WebAPIで非表示の項目の必須入力チェックがされないように修正
  - #552 [不具合]インポートでマージを選択した際に必須入力チェックを外してほしい
  - #551 [不具合]リスト（フィルタ）、レポートの条件で「担当」項目に対して「自分」が設定可能にしてほしい
  - #539 [不具合]日報の概要画面から活動追加すると関連が空になる
  - #527 [不具合]見積の住所情報の関連選択時に出るエラーメッセージの内容が仕様と合っていない
  - #525 [不具合]日報の更新履歴が画面サイズ縮小により見ずらくなる
  - #517 [不具合]ダッシュボードのデフォルトの「案件（ステージ別）」が表示されない
  - #514 [不具合]契約モジュールをAPI経由で作成すると遅い
  - #508 [不具合]活動の担当者を変更すると、参加者に変更前の担当も入ってしまう
  - #507 [不具合]スマートフォンの活動クイック編集画面の表示が崩れてしまう
  - #501 [不具合]カレンダーの繰り返しの日付を選択する画面の翻訳漏れ
  - #492 [不具合]インポート成功時の警告が未翻訳
  - #490 [不具合]文言変更で全モジュール共通の設定を作成すると、リストの内容が翻訳されてしまう
  - #489 [不具合]文言変更の編集・削除を行うとリストのスクロールバーが現れる
  - #486 [不具合]複数選択肢の項目を編集するとデフォルト値が削除される
  - #477 [不具合]vtiger_fieldテーブル内のタイムゾーン、日付形式のデフォルト値が空である
  - #461 [不具合]メニュー設定においてのモジュールアイコンの表示位置がおかしい
  - #459 [不具合]カレンダーのリストビューで一部検索欄のスタイルがおかしい
  - #458 [不具合]メニュー設定における表示バグ
  - #456 [不具合]完了済みTODOの｢次の予定を登録する｣アイコンから新規作成すると｢完了｣状態のTODOが作成される
  - #455 [不具合]画面左上のモジュール表示がずれている
  - #450 [不具合]ワークフローにてTODOとして作成したのにカレンダーと表示される
  - #327 [不具合]チケットページに紐づけられている資産のリストが表示されない
  - #281 [不具合]一括編集にて複数選択肢の編集が反映されない
  - #215 [不具合]リマインダー機能の通知が表示されないケースが多い
  - #195 [不具合]繰り返し予定を詳細入力から編集すると「編集が保存されない可能性がある」と警告される
  - #148 fixed #119 ダッシュボードでレポートのウィジェットを出すと、凡例が外に飛び出してしまう不具合の修正
  - #146 [不具合] Webフォームの一部項目に「×」が表示されない
  - #138 [不具合] 画面幅が狭いときレポート作成画面の表示が崩れる
  - #119 [不具合]レポートで作成したグラフをダッシュボードに表示すると凡例が表示されない
  - #106 [不具合]選択肢項目を開き、選択肢をホバーした時に選択肢の文字が白になる
  - #90 [不具合]元の製造業者名が残ったまま登録されている
* その他
  - PHP5.6を推奨環境から除外（未検証のため）

## Contributors
Thank you for contributing!

<a href="https://github.com/K-Haruto"><img src="https://github.com/K-Haruto.png" title="K-Haruto" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/kkouta"><img src="https://github.com/kkouta.png" title="kkouta" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/pavish69"><img src="https://github.com/pavish69.png" title="pavish69" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/Remicck"><img src="https://github.com/Remicck.png" title="Remicck" width="40" height="40" style="border-radius: 50%;"></a>

## F-RevoCRM7.3.6
### パッチ適用方法
- ファイル、DBのバックアップを確実に取得してください
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`


* 機能改善
  - [#503](https://github.com/thinkingreed-inc/F-RevoCRM/issues/503) [要望]リスト作成時の項目選択で関連モジュール名を表示して欲しい
  - [#424](https://github.com/thinkingreed-inc/F-RevoCRM/issues/424) [要望]カスタムフィールドを追加する上限を設定してほしい
  - [#391](https://github.com/thinkingreed-inc/F-RevoCRM/issues/391) [要望]ポータルの初期パスワードが簡単な英数で生成される
  - [#388](https://github.com/thinkingreed-inc/F-RevoCRM/issues/388) [要望]CHANGELOG.mdを作る
  - [#384](https://github.com/thinkingreed-inc/F-RevoCRM/issues/384) [要望]「すべて」というリストが先頭にならないケースが有る
  - [#373](https://github.com/thinkingreed-inc/F-RevoCRM/issues/373) [要望]契約モジュールのテーブルにprimary keyがない
  - [#371](https://github.com/thinkingreed-inc/F-RevoCRM/issues/371) [要望]マイグレーションでINDEXが効いていないuitype10(関連項目)にINDEXを追加したい
  - [#364](https://github.com/thinkingreed-inc/F-RevoCRM/issues/364) [要望]関連項目を追加した際にINDEXを付与して欲しい
  - [#358](https://github.com/thinkingreed-inc/F-RevoCRM/issues/358) [要望]活動を完了で作成した際、親レコードの最終活動日の更新条件を変えてほしい
  - [#328](https://github.com/thinkingreed-inc/F-RevoCRM/issues/328) [要望]コメント削除機能が欲しい
  - [#315](https://github.com/thinkingreed-inc/F-RevoCRM/issues/315) [要望]項目の並び順変更時の挙動を変えたい
  - [#297](https://github.com/thinkingreed-inc/F-RevoCRM/issues/297) [要望] インポートのマッピングで自動生成番号の項目を選択できるようにしてほしい
  - [#289](https://github.com/thinkingreed-inc/F-RevoCRM/issues/289) [要望]ユーザーの管理項目をシステム設定の画面上から変更できるようにしてほしい
* 不具合修正
  - [#518](https://github.com/thinkingreed-inc/F-RevoCRM/issues/518) [不具合]見積印刷時に下部に線が入ってしまう
  - [#515](https://github.com/thinkingreed-inc/F-RevoCRM/issues/515) [質問]WEBフォームから取り込まれた複数選択肢の区切り記号について
  - [#498](https://github.com/thinkingreed-inc/F-RevoCRM/issues/498) [不具合] 登録/変更画面からuitype10の詳細登録を経由すると入力中のデータが消える
  - [#480](https://github.com/thinkingreed-inc/F-RevoCRM/issues/480) [不具合] 同じファイルを選択した際の警告の翻訳漏れ
  - [#474](https://github.com/thinkingreed-inc/F-RevoCRM/issues/474) [不具合]ユーザーをインポートするとカレンダー設定のタイムゾーンがSamoaになる
  - [#472](https://github.com/thinkingreed-inc/F-RevoCRM/issues/472) [不具合]メールアドレス項目が途中で切れて保存される
  - [#469](https://github.com/thinkingreed-inc/F-RevoCRM/issues/469) [不具合]docker composeからセットアップができない
  - [#452](https://github.com/thinkingreed-inc/F-RevoCRM/issues/452) [不具合]F-RevoCRMのワークフロー機能にて送信されるメールにて、TEXT形式部分のマルチバイト文字が欠落している。
  - [#440](https://github.com/thinkingreed-inc/F-RevoCRM/issues/440) [不具合]概要画面で非公開の活動の「詳細内容」が確認できてしまう
  - [#421](https://github.com/thinkingreed-inc/F-RevoCRM/issues/421) [不具合]タグを複数設定し、データ編集するとタグが増えてしまう
  - [#420](https://github.com/thinkingreed-inc/F-RevoCRM/issues/420) [不具合] タグを設定すると、編集内容が反映されない
  - [#414](https://github.com/thinkingreed-inc/F-RevoCRM/issues/414) [不具合]価格表モジュールに関連フィールドを追加するとレポートが表示されない
  - [#411](https://github.com/thinkingreed-inc/F-RevoCRM/issues/411) [不具合]大文字の拡張子となっているCSVファイルをインポートしようとするとExeptionが発生する
  - [#410](https://github.com/thinkingreed-inc/F-RevoCRM/issues/410) [不具合]送信メールサーバーを設定した際に送られるメールの本文が英語になっている
  - [#404](https://github.com/thinkingreed-inc/F-RevoCRM/issues/404) [不具合]サービスモジュールに関連フィールドを追加するとレポートが表示されない
  - [#401](https://github.com/thinkingreed-inc/F-RevoCRM/issues/401) [不具合]活動のインポート時、管理画面から追加した項目があるとインポートに失敗する
  - [#393](https://github.com/thinkingreed-inc/F-RevoCRM/issues/393) [不具合]カスタム項目「選択肢(単数)」のデフォルト値表示がおかしい
  - [#389](https://github.com/thinkingreed-inc/F-RevoCRM/issues/389) [不具合]一覧画面で一部の区切り線が消える
  - [#387](https://github.com/thinkingreed-inc/F-RevoCRM/issues/387) [不具合]カレンダー上から詳細入力を行ったあとの戻り先が遷移元のカレンダーにならない
  - [#385](https://github.com/thinkingreed-inc/F-RevoCRM/issues/385) [不具合]Contactsのportalをonとした際に送られてくるメールがすべて英語。
  - [#370](https://github.com/thinkingreed-inc/F-RevoCRM/issues/370) [不具合]ワークフロー新規作成・保存後の画面遷移で、フィルタが解除されて「全て　ワークフロー」になってしまう
  - [#363](https://github.com/thinkingreed-inc/F-RevoCRM/issues/363) [不具合]ユーザー一覧の罫線が消えることがある
  - [#362](https://github.com/thinkingreed-inc/F-RevoCRM/issues/362) [不具合]活動のリストにToDoのステータスを表示していないと活動の完了アイコンが次の予定登録アイコンに切り替わらない
  - [#353](https://github.com/thinkingreed-inc/F-RevoCRM/issues/353) [不具合]ドキュメントのリスト表示の翻訳漏れ
  - [#350](https://github.com/thinkingreed-inc/F-RevoCRM/issues/350) [不具合]項目追加時に項目タイプを何回か切り替えると、デフォルト値の表示がおかしくなる
  - [#312](https://github.com/thinkingreed-inc/F-RevoCRM/issues/312) [不具合]ワークフローで値の更新を行う際に、更新先がメールアドレスタイプの際に、フィールド名をrawテキストと認識してしまう
  - [#307](https://github.com/thinkingreed-inc/F-RevoCRM/issues/307) [不具合] カレンダーの詳細編集画面で一部表示が乱れる箇所がある
  - [#287](https://github.com/thinkingreed-inc/F-RevoCRM/issues/287) [不具合] フォーム入力画面にて変更不可のデフォルト値が変更できてしまう
  - [#249](https://github.com/thinkingreed-inc/F-RevoCRM/issues/249) [不具合]案件をエクスポートした際に関連項目（ユーザー）の値が正しくない（ver7.3.3）
  - [#149](https://github.com/thinkingreed-inc/F-RevoCRM/issues/149) [不具合]半角で数字0を入力し保存した場合、編集画面で0の値が表示されずに空となっている。

### Contributors
Thank you for contributing!

<a href="https://github.com/Sota-Miyamoto"><img src="https://github.com/Sota-Miyamoto.png" title="Sota-Miyamoto" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/K-Haruto"><img src="https://github.com/K-Haruto.png" title="K-Haruto" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/kkouta"><img src="https://github.com/kkouta.png" title="kkouta" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/pavish69"><img src="https://github.com/pavish69.png" title="pavish69" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/Remicck"><img src="https://github.com/Remicck.png" title="Remicck" width="40" height="40" style="border-radius: 50%;"></a>

## F-RevoCRM7.3.5.1
前回提供したv7.3.5に不具合がありましたので、一部機能を無効化して不具合を修正しました。  
タグをご利用のお客様はこちらのパッチの適用をお願いします。

### パッチ適用方法
- ファイル、DBのバックアップを確実に取得してください
- 差分ファイルを上書き更新してください

### 主な変更点
- タグが付与されているレコードが保存できない不具合の修正
- revert #285 [不具合]タグのインポートができない

## F-RevoCRM7.3.5
### パッチ適用方法
- ファイル、DBのバックアップを確実に取得してください
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

### 主な変更点

* 機能改善
  - [#346](https://github.com/thinkingreed-inc/F-RevoCRM/issues/346) [要望]通貨項目にマイナス値を入力できるようにしてほしい
  - [#338](https://github.com/thinkingreed-inc/F-RevoCRM/issues/338) [要望]PDFテンプレートによるPDFエクスポートにて、複数レコードで一括エクスポートができるようにしてほしい
  - [#335](https://github.com/thinkingreed-inc/F-RevoCRM/issues/335) [要望]PDFテンプレートモジュールにて、各明細の行番号を表示したい。
  - [#303](https://github.com/thinkingreed-inc/F-RevoCRM/issues/303) [要望]見積もりなどの商品複数追加するUIでの、備考欄の横幅を全体に合わあせて広くする
  - [#285](https://github.com/thinkingreed-inc/F-RevoCRM/issues/285) [不具合]タグのインポートができない
  - [#237](https://github.com/thinkingreed-inc/F-RevoCRM/issues/237) [要望]見積書、発注書などに単位を表示したい
  - [#228](https://github.com/thinkingreed-inc/F-RevoCRM/issues/228) [要望] 概要画面の活動に担当者を表示してほしい

* 不具合
  - [#348](https://github.com/thinkingreed-inc/F-RevoCRM/issues/348) [不具合]顧客企業の概要画面が開くのが遅い
  - [#337](https://github.com/thinkingreed-inc/F-RevoCRM/issues/337) [不具合]文字列項目が多数含まれているモジュールにインポートした場合、インポートに失敗する。
  - [#336](https://github.com/thinkingreed-inc/F-RevoCRM/issues/336) [不具合]WebAPIのQuery処理で受注モジュールの明細行が最終行以外取得できない。
  - [#304](https://github.com/thinkingreed-inc/F-RevoCRM/issues/304) [不具合]タイトルの長いレコードを、別モジュールの関連から表示した際、「詳細」や「×」ボタンが表示されない。
  - [#295](https://github.com/thinkingreed-inc/F-RevoCRM/issues/295) [不具合]詳細画面のアイコンの背景色が消えている
  - [#290](https://github.com/thinkingreed-inc/F-RevoCRM/issues/290) [不具合]ユーザー一覧の”名前とメールアドレス ”の列がズレて表示される
  - [#284](https://github.com/thinkingreed-inc/F-RevoCRM/issues/284) [不具合]パスワード登録、変更の文字数制限が正常に機能していない
  - [#261](https://github.com/thinkingreed-inc/F-RevoCRM/issues/261) [不具合]ワークフローで値を更新すると最終更新者がシステム管理者になる
  - [#238](https://github.com/thinkingreed-inc/F-RevoCRM/issues/238) [不具合]見積書で入力した調整金額がPDF出力すると合計金額に反映されない。
  - [#153](https://github.com/thinkingreed-inc/F-RevoCRM/issues/153) [不具合]ブロック内のテキストエリアしかない場合の表示崩れ
  - [#94](https://github.com/thinkingreed-inc/F-RevoCRM/issues/94) [不具合]私の予定表で「繰り返しの予定の変更/削除」の「×」ボタンを押すと保存できなくなる
  - [#62](https://github.com/thinkingreed-inc/F-RevoCRM/issues/62) [不具合]ユーザー管理のユーザーの画面にて、パスワードの変更等の表示が埋もれている
  - [#381](https://github.com/thinkingreed-inc/F-RevoCRM/issues/381) [不具合]価格表モジュールでレポートを出力する際に、新規作成フィールドを扱うとエラーになる不具合の修正

* 翻訳
  - [#260](https://github.com/thinkingreed-inc/F-RevoCRM/issues/260) [不具合]レポートモジュールにおける日本語翻訳漏れ
  - [#259](https://github.com/thinkingreed-inc/F-RevoCRM/issues/259) [不具合]パスワードを日本語翻訳漏れ
  - [#253](https://github.com/thinkingreed-inc/F-RevoCRM/issues/253) [不具合]グループを作成時の日本語未翻訳箇所
  - [#311](https://github.com/thinkingreed-inc/F-RevoCRM/issues/311) [不具合]他の人がインポート中のときにインポートしようとする時に表示される文言の翻訳漏れ
  - [#203](https://github.com/thinkingreed-inc/F-RevoCRM/issues/203) [不具合]リスト作成時のエラーメッセージが未翻訳
  - [#88](https://github.com/thinkingreed-inc/F-RevoCRM/issues/88) [不具合]日本語と英語が混在した文章がある

* 環境
  - [#323](https://github.com/thinkingreed-inc/F-RevoCRM/issues/323) MySQLのDockerfileにnkfが含まれているため削除

* ドキュメンテーション
  - [#171](https://github.com/thinkingreed-inc/F-RevoCRM/issues/171) [要望]マイグレーションをコマンドライン側から叩きたい
  - [#34](https://github.com/thinkingreed-inc/F-RevoCRM/issues/34) [不具合]getTranslatedString関数だと、LanguageConverterで指定した変換がされない

### Contributors
Thank you for contributing!

<a href="https://github.com/Sota-Miyamoto"><img src="https://github.com/Sota-Miyamoto.png" title="Sota-Miyamoto" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/K-Haruto"><img src="https://github.com/K-Haruto.png" title="K-Haruto" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/pavish69"><img src="https://github.com/pavish69.png" title="pavish69" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/junmt"><img src="https://github.com/junmt.png" title="junmt" width="40" height="40" style="border-radius: 50%;"></a>
<a href="https://github.com/Remicck"><img src="https://github.com/Remicck.png" title="Remicck" width="40" height="40" style="border-radius: 50%;"></a>


## F-RevoCRM7.3.4
### パッチ適用方法
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

### 主な変更点

* 機能改善
  - #79 プロジェクトタスク「終了日」のクイック作成をデフォルトで有効にしてほしい
  - #194 プロジェクトの関連>マイルストーンで新規追加する際、プロジェクトを自動セットしてほしい
  - #155 Migration後の画面が英語表示
  - #98 日付条件の翻訳を改善

* 不具合修正
  - #244 見積の「項目の詳細」から送料を設定しても反映されない
  - #234 顧客企業の活動が二重表示されてしまう
  - #220 カスタマーポータルからファイルをダウンロードすると、空のファイルが送られる
  - #216 関連を開くとPHPのエラーが発生する
  - #205 TODO管理において、ユーザ選択およびステータスが保持されない
  - #201 00:00開始の予定を作成しようとすると終日にチェックが入ってしまう
  - #200 カレンダーの設定を12時間表記にすると、週次カレンダー上部の「終日エリア」から予定を作成するときに、終日フラグが入らない
  - #199 リストの複製をすると、項目と並び順の選択に余計な項目が設定される
  - #192 レポートで日付項目に対して「空である」などの条件を指定するとSQLエラーが発生する
  - #183 「ユーザー名の変更」モーダルの新ユーザー名が翻訳されていない
  - #181 「ユーザー名の変更」がパスワードがエラーで保存出来ない
  - #179 関連画面のコメントで編集を行うと改行が削除されたように見える
  - #173 削除した顧客担当者が関連の活動に残る
  - #172 コメントに自身が設定しているサムネイル画像（プロフィール画像）が表示されない
  - #169 作成した見積の合計金額と出力したPDFファイルの合計金額が1円違う
  - #161 システム設定画面でヘッダーが一部ずれる
  - #159 エクスポートした見積PDFの値引き額（貴社特別値引き）に反映されない
  - #130 F-RevoCRM6.Xから7.Xにマイグレーションした後、F-RevoCRM6.Xにて作成していた活動を削除すると、全活動が削除される
  - #123 項目タイプ関連、モジュール活動の項目で参照ボタンを押したら「権限がありません」と表示される。
  - #78 フィルターのデフォルト設定の不具合
  - #67 モジュールの詳細画面にて、コメントのアイコンサイズが小さい
  - #158 画面幅が狭いときドキュメントのアップロードモーダルが崩れる
  - #134 画面幅が狭い際にドキュメントとレポートでヘッダーがずれる
  - #213 ユーザーとユーザで表記ゆれがある

* その他修正
  - typo関連の修正
  - Migration時に実行時間の上限がなくなるように修正
  - #180 コミットメッセージの表記ゆれの改善

## F-RevoCRM7.3.3
### パッチ適用方法
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

### 主な変更点

* 機能改善
  - 選択されているリストの色を、見やすくなるように濃く変更（#139）
  - カレンダー通知用ポップアップの処理速度を改善（#99）
  - Webフォーム参照フィールドの値を、cf_xxx形式で表示するように改善（#91）
  - 関連項目として「ユーザー」を設定できるように改善（#32）
  - 初期インストールされるワークフローのワークフロー名を日本語に変更
  - 初期インストールされるレポートのレポート名を日本語に変更

* 不具合修正
  - カレンダーの招待ユーザーに送付されるicsファイルのタイムゾーンを、受信するユーザーに合わせるように修正（#121）
  - 上部検索エリアが適切に動かないケースの修正（#85, #80）
  - インライン編集を行い、キャンセルを行った後に再度編集を行い保存すると正常な値が保存されない不具合の修正（#95）
  - 顧客ポータルのURLが長い場合、枠をはみ出してしまう不具合の修正（#64, #61）
  - 繰り返し予定や招待予定を作成した場合、終日フラグが外れてしまう不具合の修正（#96）
  - ダッシュボードウィジェットのノートにて、URLに？が含まれている場合に切り取られて保存されてしまう不具合の修正（#48）
  - 活動に顧客担当者を複数名登録した後、詳細入力へ遷移すると顧客担当者が消えてしまう不具合の修正（#10）
  - 終日の予定を時間予定に変更した際に、終日フラグがはずれない不具合の修正
  - PDFを一括出力した場合に、顧客企業名をファイル名に含むように修正
  - デザイン調整（#114, #140, #125, #116, #83, #97, #71, #37, #33）、その他

* その他修正
  - 復数の日本語訳を追加（#72） 
  - DockerコンテナのタイムゾーンをJSTに変更（#154）
  - Docker環境でのインストール時の入力を簡易化するように修正
  - Docker環境下で必要なフォルダが生成されない不具合の修正
  - Docker環境にxdebug3をインストール
  - Docker環境を再起動時に自動で立ち上がるように修正
  - Pull Requestのテンプレートを作成
  - F-RevoCRMのIE11対応を、2022年4月以降非推奨とする文言の追加


## F-RevoCRM7.3.2
### パッチ適用方法
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

### 主な変更点

* 機能改善
  - ユーザーの初回ログイン時に、共有カレンダーのマイグループに所属するユーザーを、自身のみになるように改善
  - 製品モジュールのエクスポート画面の日本語和訳を追加(#31)
  - 項目編集の｢項目を表示｣の日本語和訳を追加(#43)
  - ドキュメントモジュールで「内部」としてURLを保存した際に、新しいタブでページが開くように修正
  - その他、ドキュメントでURLを指定したときの動作を改善
  - 見積、受注、発注、請求モジュールの編集画面にて、手数料が数値以外の場合は0円を設定するように修正
  - カレンダー表示画面にて、マウスオーバー時に表示される活動画面に「活動コピー」機能を追加

* 不具合修正
  - README.mdのアップデート手順に誤りがあったため修正
  - 概要欄や関連から活動を追加する際に、招待者が正常に登録されない不具合を修正
  - スマートフォン表示時に詳細画面で項目タイトルが右寄せになる不具合の修正(#2)
  - ドキュメントの詳細画面でURLを内部で保存するとハイパーリンクにならない問題を修正
  - 見積、受注、発注、請求モジュールの編集画面にて、課税対象地域を変更すると金額がNaNとなる不具合を修正
  - リストの関連リンクから別モジュールへ遷移した場合、左サイドバーのメニューがMARKETINGのものになる不具合の修正(#38)
  - 編集画面においてシステム管理者でないユーザーの場合、詳細情報の配置が崩れる問題を修正
  - Docker環境にてドキュメント保存用フォルダが無いことによってファイルアップロードができない不具合の修正(#5)
  - Docker環境にてJpegファイルがアップロードできない不具合の修正(#24) @zeroadster

* その他修正
  - README.mdに記載されているサンプルURLをexample.comに置換
  - インストーラーにて、環境変数を見て自動でDB設定が入るように修正

## F-RevoCRM7.3.1
### パッチ適用方法
- 差分ファイルを上書き更新してください
- 以下のURLにアクセスし、マイグレーションを実施してください。  
`https://example.com/frevocrm/index.php?module=Migration&view=Index&mode=step1`

### 主な変更点
* 機能改善
  - 日本語翻訳を複数追加
  - サイドバーアイコン部分の表示を改善
  - 個人アイコンを設定した際のアスペクト比を維持するように改善
  - チケットモジュール、FAQモジュールにて、画像を挿入した場合の表示を改善
  - チケットモジュール・FAQモジュールにて、画像が保存できないケースを改善
  - チケットモジュール・FAQモジュールにて、「イメージ」ボタンのプレビュー欄の表示を改善
  - 日報モジュールの「コメント」項目の文字数制限が250文字だった為、TEXT型に変更
  - コメント欄にて、PDFファイルをアップロードした時のプレビューの挙動を改善
  - プロジェクトモジュールの「チャート」画面にて、タスク名表示を改善
  - 活動登録時に時間の選択肢をキーボードで選択した場合の動作を改善
  - モバイル：日報モジュールの概要画面の更新履歴エリアにて、更新日時の表示を改善

* 不具合修正
  - 各モジュールの概要画面に表示される活動の曜日が全て木曜日となる不具合の修正
  - 一覧画面にて、最終更新者のユーザーを選択できない不具合の修正
  - メール送信可能な一覧からメール送信対象をチェックを付けてメール送信を行った際に、ランダムに1通のみメールが送信される不具合の修正
  - リード画面にて、関連メニューのメール部分に件数が表示されない不具合の修正
  - 一般ユーザーで、レポート表示時にエラーが発生する不具合の修正
  - 管理機能「企業の詳細」画面にて、画像がアップロードできないケースがある不具合の修正
  - 初期セットアップ時に日報が追加されない不具合の修正 ※本パッチ適用時に日報モジュールが追加されます

## F-RevoCRM7.3
### 主な変更点

* 機能追加
  - 見積、受注、請求、発注のPDFテンプレートを作成・編集できる機能を追加
  - システム設定に文言変更機能を追加
  - プロジェクトタスクのガントチャートを追加
  - グラフのダッシュボード表示を追加
  - デフォルトのダッシュボードを追加
  - ユーザー一覧に検索機能を追加
  - ユーザーのCSVインポート機能を追加
  - 関連データに対しての簡易検索機能を追加
  - Webフォーム取り込みに添付ファイルの取り込み機能を追加
  - 一覧画面にクイック表示の機能を追加
  - 案件からプロジェクトにコンバートできる機能を追加
  - メールコンバーターのメール自動紐付け機能を追加
  - フォロー機能を追加
  - RSS、ブックマーク機能を追加（復活）

* 機能改善
  - 画面デザインを刷新
  - 各文言を一般的な用語に変更
  - ユーザーのパスワードを8文字以上、アルファベット英数記号を含めるように制限
  - 一覧のスクロール時にヘッダ行が固定されるように改善（一覧画面のみ）
  - チケット（旧サポート依頼）、FAQ（旧回答事例）のテキストエリアの入力欄をリッチテキストに変更
  - 項目の種類に「関連」を追加（他モジュールを紐付ける項目）
  - リスト（旧フィルタ）の複製できるように改善
  - 共有リスト（旧フィルタ）の共有先の設定できるように改善
  - 「登録/編集」権限を「登録」と「編集」の権限に分離
  - 活動のCSVインポート機能を追加
  - 複数のダッシュボード管理に対応
  - ダッシュボードのウェジェットの表示サイズ変更を追加
  - 主要項目（旧概要）と関連一覧の表示設定の柔軟性を強化
  - 課税計算の設定を強化
  - タグ機能（旧タグクラウド）を強化
  - 関連するコメントをすべて表示できるように改善
  - 各レコードの入力元の表示を追加
  - 活動の繰り返し登録をした際に一括で削除や変更ができるように改善
  - ワークフローのレコードの登録、レコードの更新のアクションを強化
  - 初期表示のカレンダー表示（個人、共有、リスト）が選択できるように改善

以上

