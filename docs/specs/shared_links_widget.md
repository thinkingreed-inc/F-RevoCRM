# 共有リンクウィジェット 仕様書

- 対応 Issue: [#1963 ダッシュボードに共有のリンクを付けられるようにしてほしい](https://github.com/thinkingreed-inc/F-RevoCRM/issues/1963)
- ブランチ: `feature/1963-shared-link-widget`
- 作成日: 2026-11-06

## 1. 目的

社内システムなど、全社員がよく使う URL を CRM のダッシュボードから開けるようにする。

- リンクは**システム管理者が設定画面で一元管理**する（各ユーザーが個別に登録するものではない）。
- リンクごとに**公開範囲**（役割・グループ・ユーザー）を設定でき、対象のユーザーにだけ表示する。
- 管理者は**ウィジェット（リンクのまとまり）を複数作れる**（例:「社内システム」「申請・手続き」）。見出しはウィジェットごとに付ける。
- 各ユーザーは、ダッシュボードの「ウィジェットの追加」から必要なウィジェットを置いて、管理者が登録したリンクを使う。
- リンク先へのリクエストは GET / POST を選べる。リクエストパラメータを指定でき、値には**ログインユーザーの情報を差し込める**（例: 社員番号やメールアドレスを連携先へ渡す）。

## 2. 対象外（今回は実装しない）

| 項目 | 理由・方針 |
|---|---|
| ブックマークモジュールとの統合 | ブックマークは個人単位のデータ。今回は管理者が一元管理するため、別のマスタとして持つ |
| ウィジェットを全ユーザーのダッシュボードへ自動で配置 | 既存ウィジェットと同じく、各ユーザーが「ウィジェットを追加」から配置する |
| 一般ユーザーによるリンクの追加・編集 | 管理者のみ |
| リンク先の死活監視・プレビュー | なし |

## 3. 画面仕様

### 3.1 管理画面（設定 > 共有リンク）

- 場所: 設定メニューの「システム構成」ブロック（`LBL_CONFIGURATION`）に「共有リンク」を追加する（休祝日マスタと同じブロック）。
- URL: `index.php?module=SharedLinks&parent=Settings&view=List`
- アクセスできるのはシステム管理者のみ（`Settings_Vtiger_Index_View` の既定の判定）。
- 画面本体は Web コンポーネント `<shared-links-page>`（React）で描画する。休祝日マスタ（`Settings/Holidays`）と同じ構成。

#### ウィジェット（リンクのまとまり）

- 画面の上部にウィジェットをタブで並べ（作った順）、それぞれにリンクの件数を出す。タブを選ぶと、そのウィジェットのリンクの一覧に切り替わる。
- 「＋ ウィジェットを作る」で見出しを入力して作る。作ったウィジェットのタブを選んだ状態にする。
- 選んだウィジェットの見出しを変えられる（変えるまで「保存」は押せない）。見出しは「ウィジェットの追加」メニューと、各ユーザーのダッシュボードのウィジェットの見出しになる。
- 「このウィジェットを削除」で、中のリンクごと削除する。確認ダイアログに見出しとリンクの件数を出す。各ユーザーのダッシュボードに置いたウィジェットと、「ウィジェットの追加」の項目も消える。
- 見出しの制約: 必須・30 文字まで・`< > & " '` は使えない（vtiger の「ウィジェットの追加」メニューと見出しの title 属性はエスケープせずに出力するため）・ほかのダッシュボードウィジェット（「ノート」「Mini List」や、共有リンクのほかのウィジェット）と同じ名前は付けられない（大文字小文字を区別しない）。
- ウィジェットが 1 つも無いときは「ウィジェットを作る」への案内を出し、リンクは追加できない。
- タブの並び替えはしない（作った順）。

#### 一覧（選んだウィジェットのリンク）

| 列 | 内容 |
|---|---|
| 並び順 | 上下ボタンで入れ替える。ウィジェットでの表示順になる |
| タイトル | |
| URL | 長い場合は省略表示 |
| メソッド | GET / POST |
| 公開範囲 | 「全員」、または対象の名前を並べて表示（多い場合は先頭の数件と「ほか n 件」） |
| 状態 | 有効 / 無効 |
| 操作 | 編集・削除 |

- 「リンクを追加」ボタンで編集モーダルを開く。
- 削除する前に確認ダイアログを出す。

#### 編集モーダル

| 項目 | 必須 | 入力 | 制約 |
|---|---|---|---|
| ウィジェット | ○ | 選択 | 新規は今開いているウィジェット。選び直すとそのウィジェットへ移す |
| タイトル | ○ | テキスト | 1〜100 文字 |
| URL | ○ | テキスト | `http` / `https` のみ。最大 2000 文字。クエリ文字列を含んでもよい |
| 説明 | | テキストエリア | 最大 500 文字。ウィジェットでタイトルの下に表示する |
| メソッド | ○ | GET / POST | 既定は GET |
| リクエストパラメータ | | 行を追加・削除できる（下記） | 最大 20 行 |
| 開き方 | ○ | 「別タブ」/「別ウィンドウ」 | 既定は「別タブ」。詳細は 3.3 |
| ウィンドウの幅・高さ | ○（別ウィンドウのとき） | 数値（px） | 開き方が「別ウィンドウ」のときだけ出す。幅・高さとも 200〜4000。既定は幅 1024・高さ 768 |
| 公開範囲 | ○ | 「全員」/「指定した対象のみ」 | 既定は「全員」。詳細は 3.4 |
| リファラーを送らない | | チェックボックス | 既定はオフ（5 章） |
| 有効 | | チェックボックス | 既定はオン。オフにするとウィジェットに表示しない |


#### リクエストパラメータの行

1 行に次の 3 つを並べる。記法を手で書く必要はなく、選ぶだけで設定できる。

| 欄 | 入力 | 制約 |
|---|---|---|
| 名前 | テキスト | 1〜100 文字で必須 |
| 値の種類 | 「固定値」/「ユーザー情報」の切り替え | 既定は「固定値」 |
| 値 | 種類が「固定値」ならテキスト、「ユーザー情報」なら項目のプルダウン | 固定値は最大 1000 文字（空でもよい）。ユーザー情報は項目の選択が必須 |

```
名前 [employee_id ]  値の種類 (○固定値 ●ユーザー情報)  値 [ユーザー名        ▼]   [×]
名前 [mail        ]  値の種類 (○固定値 ●ユーザー情報)  値 [メールアドレス    ▼]   [×]
名前 [from        ]  値の種類 (●固定値 ○ユーザー情報)  値 [crm               ]   [×]
[＋ パラメータを追加]
```

- 値の種類を切り替えたときは、値の欄を空に戻す。
- プルダウンは項目の表示ラベル（例: 「メールアドレス」）で並べ、名前で絞り込める。差し込める項目は 4 章。
- 一覧画面・編集モーダルでも、ユーザー情報の値は項目の表示ラベルで見せる。内部の項目名は見せない。
- 保存時のエラーは、該当する項目の下に表示する。

### 3.2 ダッシュボードのウィジェット

- ホームのダッシュボードの「ウィジェットの追加」メニューに、作ったウィジェットがそれぞれの見出しで出る。同じウィジェットは 1 つのタブに 1 個まで置ける（ほかのウィジェットは引き続き選べる）。
- 見出しは管理画面で付けたもの。
- 中身は Web コンポーネント `<shared-links-widget>`（React）で描画する。サーバーで公開範囲の判定とユーザー情報の差し込みを済ませ、見ているユーザーに表示できるリンクだけを属性で渡す（公開範囲外のリンクは URL も HTML に出ない）。
- そのウィジェットの有効なリンクのうち、**見ているユーザーが公開範囲に含まれるもの**を並び順のとおりに表示する（3.4）。
- 1 件 = 1 行で、行全体がリンク。左にタイトルの頭文字の目印（リンクごとに決まる色）、中央にタイトル（1 行で省略）と説明（2 行まで）、右に開き方の印（別タブ ↗ / 別ウィンドウ ⧉）を出す。行に乗ると背景と右の印の色が変わる。
- 件数が多いときは中身をスクロールする。ウィジェットの大きさを変えたら、中身の高さも合わせる（vtiger の共通の処理は高さを固定値で書き込むだけで、大きくしてもスクロールバーが残るため、ウィジェット用の JS で合わせ直す）。
- リンクを押すと、リンクごとに設定した開き方（別タブ / 別ウィンドウ）で開く。CRM の画面そのものが遷移することはない。詳細は 3.3。
- 表示できるリンクが 1 件も無いときは「共有リンクが登録されていません」と表示する。管理者が見ているときは、設定画面へのボタンも表示する。
- 1 つ置いても、ほかの共有リンクのウィジェットは「ウィジェットの追加」から消えない（vtiger の共通の処理は、追加したウィジェットと同じ名前の項目をすべて隠すため、押した項目以外を表示し直す）。
- vtiger の共通 CSS（`public/resources/styles.css`）に `.group`（margin 付き）があるため、Tailwind の `group` は名前付き（`group/link`）で使う。
- ウィジェットの更新ボタンを押すと最新の内容を再取得する（既存ウィジェットと同じ動き）。

### 3.3 開き方

リンクごとに「別タブ」か「別ウィンドウ」を選ぶ。

| 開き方 | GET | POST |
|---|---|---|
| 別タブ | `<a href="…" target="_blank" rel="noopener">` で開く | 押したときに JS でフォームを作り、`target="_blank"` で送信してからフォームを消す |
| 別ウィンドウ | 押したときに `window.open(URL, 名前, "width=…,height=…,left=…,top=…,noopener")` で開く | 先に `window.open("about:blank", 名前, "width=…,height=…,left=…,top=…")` で空のウィンドウを開き、**そのウィンドウの文書の中で**フォームを作って送信する。送信した直後に、開いたウィンドウの `opener` を `null` にする |

- どちらの場合も、GET の `<a>` の href にはパラメータを付けた最終的な URL を入れる。別ウィンドウのリンクでも、中クリック・Ctrl＋クリック・URL のコピーはブラウザの通常の動き（別タブで開く）になる。通常のクリックだけを JS で受けて別ウィンドウで開く。
- POST のフォームは HTML に書き出さない（6.2）。
- POST・別ウィンドウで、CRM の画面から `target=名前` で送らないのは、フォームに `rel="noopener"` / `noreferrer` を付けるとブラウザが名前付きのウィンドウを探さず、新しいタブで送信して空のウィンドウが残るため（E2E で確認）。空のウィンドウは CRM と同じオリジンの about:blank なので、中の文書を操作できる。中を操作できない場合（同じ名前のウィンドウが別のサイトを開いたまま残っている）は、`rel` を付けずに `target=名前` で送る。
- POST の送り先が CSP の `form-action` で許可されていないと、ブラウザが送信を止める（6.5）。その場合はウィンドウを開かず、「このリンクの送り先は許可されていないため開けません。システム管理者に連絡してください」と通知する。
- 別ウィンドウは、今のブラウザのウィンドウの中央に来るように `left` / `top` を計算する。指定したサイズが画面より大きいときは、画面に収まるサイズに縮める。
- ウィンドウの名前はリンクごとに固定する（`frSharedLink_<リンク ID>`）。同じリンクを 2 回押したときは、同じウィンドウを再利用する（開くたびにウィンドウが増えない）。
  - ただし GET で `noopener` を付けたときは、ブラウザによっては再利用されず、新しいウィンドウが開く。
- ポップアップがブロックされて `window.open` が `null` を返したとき（POST の場合）は、「ポップアップがブロックされました。ブラウザの設定で、このサイトのポップアップを許可してください」と通知する。GET は `noopener` を付けると成功しても `null` が返るので、判定しない。
- ブラウザやユーザーの設定によっては、別ウィンドウを指定しても別タブで開くことがある（ブラウザ側の仕様のため対応しない）。

### 3.4 公開範囲

リンクごとに、ウィジェットに表示するユーザーを限定できる。

#### 設定のしかた（編集モーダル）

- 「全員」を選ぶと、すべてのユーザーに表示する。
- 「指定した対象のみ」を選ぶと、対象を追加する欄が出る。対象は 1 件以上必要。
  1. 種類を選ぶ: 役割 / グループ / ユーザー
  2. 対象を選ぶ（名前で絞り込める選択欄）。役割は階層がわかるように字下げして並べる
  3. 種類が役割のときだけ「下位の役割も含む」のチェックボックスを出す（既定はオフ）
  4. 「追加」を押すと、選んだ対象がチップで並ぶ。チップの × で外せる
- チップの表示: `役割: 営業部長`、`役割と下位: 営業部長`、`グループ: 営業チーム`、`ユーザー: 山田 太郎`
- 同じ対象（種類と ID が同じもの）は重ねて追加できない。「役割: A」と「役割と下位: A」は別の対象として両方追加できる（意味は後者に含まれるが、エラーにはしない）。
- 選択欄に出すユーザーは有効（Active）なユーザーだけ。

#### 表示するかどうかの判定

見ているユーザーが次のどれか 1 つに当てはまれば表示する（OR）。

| 種類 | 当てはまる条件 |
|---|---|
| ユーザー | 見ているユーザー本人 |
| グループ | 見ているユーザーがそのグループに属している。グループの中のグループ・役割経由で属している場合も含む（共有ルールと同じ `GetUserGroups` で DB から求める） |
| 役割 | 見ているユーザーの役割がその役割と同じ |
| 役割と下位 | 見ているユーザーの役割がその役割と同じか、その下位（ユーザーの `parent_role_seq` にその役割 ID が含まれる） |

- 判定はウィジェットを表示するたびにサーバ側で行う。役割とグループは毎回 DB（`vtiger_user2role` / `vtiger_role` / `GetUserGroups`）から求めるので、変更はすぐに反映される。
- ユーザーの権限ファイル（`user_privileges_<id>.php`）は使わない。管理者の権限ファイルは役割の経路とグループを持たず、管理者で「役割と下位」「グループ」が当てはまらなくなるため（テストで確認）。
- **管理者も同じ判定に従う**（管理者だからといって全リンクを表示しない）。管理者は管理画面で全リンクを確認できる。
- 対象の役割・グループ・ユーザーが削除されたときは、その対象は判定で無視する。管理画面では `（削除済み）` と表示し、管理者が外せるようにする。削除された対象を 1 件でも含むリンクには、管理画面の一覧で警告アイコンを出す（対象がすべて削除されると、そのリンクは誰にも表示されない）。

## 4. ユーザー情報の差し込み

### 4.1 動き

- 値の種類が「ユーザー情報」のパラメータは、ウィジェットを表示するときに、**そのとき見ているユーザー**の選んだ項目の値を送る。
- 1 つのパラメータに入れられるのは 1 項目だけ。固定の文字列との組み合わせ（例: `crm-<ユーザー ID>`）や、複数項目の連結はできない。姓名を続けて送りたい場合のために、「氏名（姓 名）」を項目として用意する（4.2）。
- ユーザー情報を入れられるのは**パラメータの値だけ**。URL 本体・パラメータ名には入れられない（URL の構造が値によって変わるのを防ぐため）。
- 値は表示用の形式（ピックリストは翻訳後のラベル、日付はユーザーの日付形式）ではなく、**DB に保存されている値**を送る。連携先が機械的に扱えるようにするため。
- 保存時、選んだ項目が差し込める項目の一覧に無ければエラーにする。
- 保存した後に項目が削除された・差し込めなくなったときは、空文字を送る。管理画面では値を「（削除された項目）」と表示し、一覧に警告アイコンを出す。

### 4.2 差し込める項目

- ユーザーモジュールの項目のうち、次の**除外対象以外**すべて（カスタム項目も含む）。
  - パスワード系: `user_password`, `confirm_password`
  - 認証・内部用: `accesskey`, `user_hash`, `crypt_type`, `user_preferences`
  - 画像・署名などのテキストに向かない項目: `imagename`, `signature`
  - 上記以外でも uitype が 99（パスワード）の項目
- 上記のほかに、次の 2 つを用意する。
  - 「ユーザー ID」（内部の項目名 `id`）
  - 「氏名（姓 名）」（内部の項目名 `full_name`）。`last_name` と `first_name` を半角スペースでつないだもの。どちらかが空なら、空でないほうだけ
- 差し込める項目の一覧（内部の項目名と表示ラベル）は、管理画面の API が返す。

## 5. リファラー（Referer）の扱い

Issue では「リンク先の CSRF 対策で開けない問題を避けるため、Referer を付けないほうがよいかもしれないが、マナーとして良いのか疑問」とされている。

- **リンクごとの設定**にする。既定はオフ（ブラウザの既定の動きで Referer を送る）。
  - 補足: 最近のブラウザの既定のポリシー（`strict-origin-when-cross-origin`）では、別のサイトへ送られる Referer は CRM のオリジン（`https://crm.example.com/`）だけで、パスやクエリは送られない。
- オンにしたリンクは、Referer を送らない。
  - 別タブ・GET: `<a>` に `rel="noopener noreferrer"` を付ける
  - 別ウィンドウ・GET: `window.open` の features に `noreferrer` を付ける
  - POST・別タブ: 送信するフォームに `rel="noopener noreferrer"` を付ける。フォームの `rel` に対応していない古いブラウザでは Referer が送られることがある
  - POST・別ウィンドウ: 開いた空のウィンドウに `<meta name="referrer" content="no-referrer">` を入れてから送る（`rel` を付けると名前付きのウィンドウへ送れないため）
- オンにするのは「連携先が外部からの Referer を拒否していて開けない」ときに限る、と画面の説明文に書く。

## 6. セキュリティ

### 6.1 入力の検証（保存時・サーバ側）

- URL は `parse_url` で scheme と host を取り出せること、かつ scheme が `http` / `https` であること。`javascript:` などは保存しない。
- メソッドは `GET` / `POST` 以外、開き方は `tab` / `window` 以外を受け付けない。`window` のときは幅・高さが 200〜4000 の整数であること。`tab` のときは幅・高さを NULL で保存する。
- 文字数と件数の上限（3.1）をサーバ側でも検証する。
- DB へは prepared statement（`pquery`）で保存する。

### 6.2 CRM の CSRF トークンを外部へ送らない

F-RevoCRM の csrf-magic は、出力される HTML の `<form method="post">` へ CRM の CSRF トークン（`__vtrftk`）を自動で埋め込む（`libraries/csrf-magic/csrf-magic.php` の出力バッファ処理と、`csrf-magic.js` のページ読み込み時の処理）。そのため、POST のフォームを HTML に書き出すと、トークンがリンク先へ送られてしまう。

- ウィジェットの HTML には `<form>` を書き出さない。POST の内容（URL とパラメータ）は `<shared-links-widget>` の属性（JSON）で渡し、押したときに JS でフォームを作って送信する。
- 送信する直前に、フォームに含まれる入力が登録したパラメータだけであることを確認する（`__vtrftk` と同じ名前のパラメータは送らない）。

### 6.5 CSP の form-action（POST の送り先）

CRM の画面は CSP（`includes/http/Response.php`）で `form-action 'self'` を指定しており、そのままでは外部へのフォーム送信をブラウザが止める（別タブ・別ウィンドウとも。空のウィンドウは作成元の CSP を引き継ぐ）。

- `config.customize.php` の `$csp_allowed_domains['form-action']` に送り先を登録できるようにする（他のディレクティブと同じ仕組み。コアの `buildCSPHeader()` を変更）。既定は空（`'self'` だけ）。
- 例: `'form-action' => array('https://kintai.example.com')`
- 管理画面では、POST の送り先が許可されていないリンクに印（⛔）を出し、編集モーダルで POST を選ぶと登録が必要なことを注記する。判定は CSP のソース表現の主なもの（`*`・`https:` のようなスキームだけの指定・ホスト・先頭の `*.`・ポート・パス）に対応する（`Settings_SharedLinks_Rules_Model::isFormActionAllowed`）。
- ウィジェットでは、許可されていない POST のリンクを押してもウィンドウを開かず、通知する。
- GET は CSP の `form-action` に関係しない（リンクの遷移のため）。

### 6.3 出力のエスケープ

- タイトル・説明・URL・パラメータは、`<shared-links-widget>` の属性（JSON）として HTML エスケープして渡す。React は文字として描画する（HTML として解釈しない）。
- 差し込むユーザー情報は、見ている本人の情報だけ。他のユーザーの情報は差し込まない。

### 6.4 権限

| 操作 | 権限 |
|---|---|
| 管理画面の表示、リンクの追加・編集・削除・並び替え | システム管理者のみ（API でも `isAdminUser()` を確認する） |
| ウィジェットの追加・表示 | ホーム（ダッシュボード）にアクセスできるユーザー |
| ウィジェットに出るリンク | 公開範囲（3.4）に含まれるユーザーだけ。公開範囲外のリンクは、URL やパラメータも HTML に出さない |

## 7. データ構造

文字セットと照合順序は `DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci` を明示する（CLAUDE.md の規約）。

### 7.0 `vtiger_shared_link_sets`（ウィジェット = リンクのまとまり）

| 列 | 型 | 内容 |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `title` | VARCHAR(30) NOT NULL | 見出し（`vtiger_links.linklabel` と同じ値にそろえる） |
| `linkid` | INT NOT NULL UNIQUE | `vtiger_links.linkid`（ダッシュボードのウィジェットの登録） |
| `sequence` | INT NOT NULL DEFAULT 0 | 並び順（作った順） |
| `createdtime` / `modifiedtime` | DATETIME NOT NULL | |

- ウィジェット 1 つにつき、`vtiger_links` に `DASHBOARDWIDGET` を 1 行登録する（`tabid` = Home、`linkurl` = `index.php?module=Home&view=ShowWidget&name=SharedLinks`）。どのウィジェットかは、ShowWidget が付ける `linkid` で見分ける。
- 見出しを変えるときは `linklabel` も変える（`linkid` は変えないので、各ユーザーのダッシュボードに置いたウィジェットは残る）。
- 削除するときは、中のリンク（パラメータ・対象を含む）・`vtiger_links` の行・各ユーザーの配置（`vtiger_module_dashboard_widgets`）も消す。
- 登録するときは、採番テーブル `vtiger_links_seq` が `vtiger_links` の最大 ID より遅れていれば先に追いつかせる（過去のマイグレーション（IFrame ウィジェットの追加）が `MAX(linkid)+1` で直接 INSERT していて、採番が遅れている環境があるため）。

### 7.1 `vtiger_shared_links`（リンク）

| 列 | 型 | 内容 |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `setid` | INT NOT NULL | `vtiger_shared_link_sets.id`（どのウィジェットに出すか。`(setid, sequence)` に INDEX） |
| `title` | VARCHAR(100) NOT NULL | タイトル |
| `url` | VARCHAR(2000) NOT NULL | URL |
| `description` | TEXT NULL | 説明 |
| `method` | VARCHAR(4) NOT NULL DEFAULT 'GET' | `GET` / `POST` |
| `noreferrer` | TINYINT(1) NOT NULL DEFAULT 0 | リファラーを送らない |
| `open_mode` | VARCHAR(10) NOT NULL DEFAULT 'tab' | `tab`（別タブ）/ `window`（別ウィンドウ） |
| `window_width` | INT NULL | 別ウィンドウの幅（px）。`tab` のときは NULL |
| `window_height` | INT NULL | 別ウィンドウの高さ（px）。`tab` のときは NULL |
| `visibility` | VARCHAR(10) NOT NULL DEFAULT 'all' | `all`（全員）/ `targets`（指定した対象のみ） |
| `sequence` | INT NOT NULL DEFAULT 0 | 並び順 |
| `active` | TINYINT(1) NOT NULL DEFAULT 1 | 有効 |
| `createdtime` | DATETIME NOT NULL | |
| `modifiedtime` | DATETIME NOT NULL | |
| `modifiedby` | INT NOT NULL | 最後に更新した管理者のユーザー ID |

### 7.2 `vtiger_shared_link_params`（リクエストパラメータ）

| 列 | 型 | 内容 |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `linkid` | INT NOT NULL | `vtiger_shared_links.id`（INDEX。リンクを削除したら一緒に削除する） |
| `name` | VARCHAR(100) NOT NULL | パラメータ名 |
| `value_type` | VARCHAR(10) NOT NULL DEFAULT 'fixed' | `fixed`（固定値）/ `user`（ユーザー情報） |
| `value` | VARCHAR(1000) NOT NULL DEFAULT '' | `fixed` のときは固定値、`user` のときはユーザーの項目名 |
| `sequence` | INT NOT NULL DEFAULT 0 | 並び順（送信する順） |

- 同じ名前のパラメータを複数行登録してもよい（`a=1&a=2` のような送り方に対応するため）。
- GET で、URL に既にあるクエリ文字列と同じ名前のパラメータを登録したときは、両方とも送る（上書きしない）。

### 7.3 `vtiger_shared_link_targets`（公開範囲の対象）

| 列 | 型 | 内容 |
|---|---|---|
| `id` | INT AUTO_INCREMENT PK | |
| `linkid` | INT NOT NULL | `vtiger_shared_links.id`（INDEX。リンクを削除したら一緒に削除する） |
| `target_type` | VARCHAR(20) NOT NULL | `Users` / `Groups` / `Roles` / `RoleAndSubordinates` |
| `target_id` | VARCHAR(30) NOT NULL | ユーザー ID・グループ ID・役割 ID（`H2` など） |

- `(linkid, target_type, target_id)` に UNIQUE をかける。
- 種類の名前は、vtiger の共有ルール（`Settings/SharingAccess`）と同じものにする。
- `visibility` が `all` のときは、このテーブルに行を持たない（保存時に消す）。

### 7.4 マイグレーション

`setup/migration/` のコマンドで 1 本作る。何度実行しても結果が変わらないようにする。

1. 上記 4 テーブルを作る（無ければ）。
2. ウィジェットが 1 つも無ければ、最初のウィジェット「共有リンク」を作り、`vtiger_links` に登録する（7.0）。
3. `vtiger_settings_field` に設定メニューを登録する（無ければ）。
   - ブロック: `LBL_CONFIGURATION`、`name` = `LBL_SHARED_LINKS`
   - `linkto` = `index.php?module=SharedLinks&parent=Settings&view=List`

## 8. 実装の構成

コア（`vtlib/`・`includes/`・`modules/Vtiger/`）は変更しない。ただし CSP の `form-action` だけは、POST の送り先を許可するために `includes/http/Response.php` を変更する（6.5。方針は確認済み）。ウィジェットのクラスは `Vtiger_Loader::getComponentClassName('Dashboard', …)` がモジュール側（Home）を先に探すので、`modules/Home/dashboards/` に置けばよい。また、tabid = 3 で登録した `DASHBOARDWIDGET` は、`Vtiger_DashBoard_Model::getSelectableDashboard()` によって「ウィジェットを追加」メニューに自動で出る。

| 種別 | ファイル | 内容 |
|---|---|---|
| Model | `modules/Settings/SharedLinks/models/Module.php` | DB の読み書き（ウィジェットの作成・見出しの変更・削除、リンクの取得・保存・削除・並び替え）、差し込める項目・公開範囲の選択肢の一覧、ウィジェットに出すリンクの取得、ユーザーの役割とグループ（DB から求める） |
| Model | `modules/Settings/SharedLinks/models/Rules.php` | DB を使わない判定（入力の検証・公開範囲の判定・パラメータの値の解決・GET の URL の組み立て・役割の並び順）。単体テストの対象 |
| View | `modules/Settings/SharedLinks/views/List.php` | 管理画面 |
| API | `modules/Settings/SharedLinks/apis/SharedLinkAPI.php` | 管理画面用の JSON API（8.1）。管理者のみ |
| テンプレート | `layouts/v7/modules/Settings/SharedLinks/SharedLinksList.tpl` | `<shared-links-page>` を置くだけ |
| Dashboard | `modules/Home/dashboards/SharedLinks.php` | `Home_SharedLinks_Dashboard`。`linkid` からウィジェットを引き、見ているユーザーに表示できるリンク（差し込み済み）と文言を JSON にして渡す |
| テンプレート | `layouts/v7/modules/Home/dashboards/SharedLinks.tpl` / `SharedLinksContents.tpl` | ウィジェットの枠と、`<shared-links-widget>` を置く中身 |
| コア | `includes/http/Response.php`、`config.customize.php` | CSP の `form-action` を `$csp_allowed_domains['form-action']` で足せるようにする（6.5） |
| JS / CSS | `public/layouts/v7/modules/Home/resources/SharedLinksWidget.js` / `.css` | ダッシュボードの枠の調整だけ（中身の高さをウィジェットの大きさに合わせる `Home_SharedLinks_Widget_Js`、「ウィジェットの追加」メニューで他の共有リンクを隠さない、中身の余白とスクロール）。`Home_DashBoard_View`（`modules/Home/views/DashBoard.php`）で読み込む |
| React | `assets/react-web-components/src/components/SharedLinks/` | 管理画面（`SharedLinksPage`・`SharedLinkSets`・`SharedLinkEditModal`）とウィジェット（`SharedLinksWidget`）。リンクを開く処理は `utils/openSharedLink.ts`。型は `src/types/sharedLinks.ts` |
| 言語 | `languages/{ja_jp,en_us}/Settings/SharedLinks.php`、`languages/{ja_jp,en_us}/Home.php`、`languages/{ja_jp,en_us}/Settings/Vtiger.php` | 管理画面・ウィジェットの文言、設定メニューの名前 |
| マイグレーション | `setup/migration/scripts/20261106093622_add_shared_links_widget.php` | 7.4 |

### 8.1 API（`?module=SharedLinks&parent=Settings&api=SharedLinkAPI`）

| mode | メソッド | 内容 |
|---|---|---|
| `info` | GET | 差し込める項目の一覧、公開範囲の選択肢（役割（階層つき）・グループ・有効なユーザー）、画面の文言 |
| `list` | GET | ウィジェットの一覧（`sets`）と、すべてのリンク（無効も含む。`set_id` 付き）とパラメータ・公開範囲の対象（表示名と削除済みかどうかを含む） |
| `save_set` | POST | ウィジェットを作る（`title`）。`id` があれば見出しを変える。見出しに誤りがあれば `{"saved": false, "errors": {"title": …}}` |
| `delete_set` | POST | ウィジェットを削除（中のリンク・`vtiger_links` の行・各ユーザーの配置も消す） |
| `save` | POST | リンクの追加・更新（`set_id` で出すウィジェットを指定。`id` があれば更新）。パラメータと公開範囲の対象はまとめて置き換える。値の種類が 2 種類以外・差し込めない項目のときはエラー。対象の種類が 4 種類以外・存在しない ID・`targets` で 0 件のときはエラー |
| `delete` | POST | 削除（パラメータも削除） |
| `reorder` | POST | `ids` の順に `sequence` を振り直す |

返す JSON は既存の `Vtiger_Api_Controller` の形式に合わせる。

- `save` の入力に誤りがあるときは、保存せずに `{"saved": false, "errors": {"<項目>": "<文言>"}}` を返す（HTTP 200）。項目のキーは `title`・`url`・`params.0.value`・`targets.0` などで、画面はこれを該当する項目の下に出す。保存できたら `{"saved": true, "id": <ID>}`。
- 管理者以外は 403、存在しないリンク・ウィジェット（`set_id` を含む）の更新・削除は 404、`link` が JSON でない・`ids` に数字以外があるときは 400。
- 更新系（`save` / `delete` / `reorder` / `save_set` / `delete_set`）は POST と CSRF トークンを必須にする。
- `link` は JSON で受け取り、`Vtiger_Request::getRaw()` で読む（`get()` は値をエンティティ化するため、URL の `&` などが変わってしまう）。

## 9. テスト

テスト仕様書（デシジョン表・テストケース・実行結果）は [docs/tests/SharedLinks/](../tests/SharedLinks/README.md)。
