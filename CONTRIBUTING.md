# Contribution Guide

F-RevoCRMへのコントリビュート方法についてガイドです。

## Issues

次のIssueを受け付けています。

- 不具合報告（Bug report） => [こちらからバグ報告できます](https://github.com/thinkingreed-inc/F-RevoCRM/issues/new?assignees=&labels=&template=------bug-report-.md&title=%5B%E4%B8%8D%E5%85%B7%E5%90%88%5D)
- 要望（Feature request） => [こちらから提案できます](https://github.com/thinkingreed-inc/F-RevoCRM/issues/new?assignees=&labels=&template=---feature-request-.md&title=%5B%E8%A6%81%E6%9C%9B%5D)
- 質問（How to use question） => [こちらから質問できます](https://github.com/thinkingreed-inc/F-RevoCRM/issues/new?assignees=&labels=&template=---how-to-use-question-.md&title=%5B%E8%B3%AA%E5%95%8F%5D)

## Pull Request

Pull Requestはいつでも歓迎です。

**受け入れるPull Request**

次の種類のPull Requestを受け付けています。
基本的なPull Request（特に細かいもの）は、Issueを立てずにPull Requestを送ってもらって問題ありません。

「このような修正/改善はどうでしょう？」という疑問がある場合は、Issueを立てて相談してください。

- 不具合修正
- 機能追加
- 誤字の修正
- 翻訳の追加

**受け入れていないPull Request**

- [CODE OF CONDUCT](./.github/CODE_OF_CONDUCT.md)に反する内容を含むもの

## コミットメッセージについて
コミットメッセージは以下の形式にしてください。

`#{issue num} {コミットメッセージ}`  
例： 「 fixed #12 ○○の日本語翻訳を修正 」


参考：[js\-primer/CONTRIBUTING\.md at master · asciidwango/js\-primer](https://github.com/asciidwango/js-primer/blob/master/CONTRIBUTING.md)

### モジュール名等の表記ゆれについて
本レポジトリは現状日本語話者が開発を行うケースが多い為、以下のような規則でコミットメッセージやコメントの記入をお願いします。

#### モジュール名
モジュール名は基本的に日本語で記入してください。  
例）Potentials → 案件
#### 正式名称の利用
docker → Docker  
javascript → JavaScript  
のように、一般的な名称はなるべくその正式名称に合わせるようにお願いします。

#### F-RevoCRMの画面について
以下の文言に合わせて利用してください。
|Viewの名前|名称|備考|
|-|-|-|
|List|一覧画面||
|Detail|詳細画面・概要画面|使い分けをお願いします|
|QuickCreate|クイッククリエイト|右上の＋ボタンからレコード作成できる画面全般です|
|EditView|作成画面・編集画面||
||クイック編集|詳細画面や一覧画面で利用可能な、編集画面を経由せずに変更する機能を指します|
|RelatedList|関連リスト|例えば顧客企業モジュール内の、顧客担当者一覧などは、関連リストとなります|
|CustomView|リスト|一覧画面の左側に出る、検索条件を保存することが可能な機能です|
|Header|ヘッダーメニュー|上部に表示されるモジュール一覧やレポートへのリンクがあるエリアを指します|
|Sidebar|サイドメニュー||
|Settings|システム設定||
