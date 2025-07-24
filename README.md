# Social-Networking-Service

![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?logo=javascript&logoColor=black)
![PHP](https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)

## URL
https://sns.yua-tech.com


## アプリケーション概要

このアプリケーションはSNS(Social Networking Service)です。  
テキスト、画像の投稿、リアルタイムチャットやユーザー間のフォローなどの機能があります。  
このアプリケーションはWebアプリケーション開発の学習を目的として、作成しました。  
ここでのWebアプリケーション開発の学習とは、単に問題なく動作するWebアプリケーションを開発することではなく、  
Webアプリケーション開発における様々な要素をより深く理解することに重きを置いています。  
そのため、ライブラリやフレームワークは極力使用せず、独自のマイクロフレームワークを構築しています。

| 機能一覧 |
| ------------- |
| ユーザー登録 |
| ユーザーログイン |
| パスワードリセット |
| ユーザープロフィール設定 |
| 投稿 |
| 予約投稿 |
| リプライ |
| 投稿（リプライ）削除 |
| いいね |
| タイムライン（トレンド, フォロー） |
| リアルタイムチャット |
| 通知（フォロー, リプライ, いいね, チャットメッセージ受信） |

## 使用方法

### ログイン画面

<img src="docs/captures/login.png" style="width: 70%;">

### ユーザー登録・認証

登録時にはメールアドレス検証(制限時間付き)が必要です。

<img src="docs/captures/register.png" style="width: 70%;">
<img src="docs/captures/mail.png" style="width: 70%;">

### パスワードリセット

パスワードを忘れた場合はリセットすることも可能です。

<img src="docs/captures/forget.png" style="width: 70%;">

### タイムライン

タイムラインはトレンドとフォローをタブで切り替えることができます。  
トレンドはいいね数が多い順で表示します。  
フォローは自分とフォロワーの投稿を作成日順で表示します。

<img src="docs/captures/trend.png" style="width: 70%;">
<img src="docs/captures/follow.png" style="width: 70%;">

### 投稿

サイドメニューの「投稿」をクリックすると、ポスト作成用のモーダルが開きます。  
「予約する」をオンにし、日時を指定(YYYY/MM/DD HH:MM)することで、指定した時間に投稿することできます。  

<img src="docs/captures/post.png" style="width: 70%;">

### リプライ

投稿の左下のリプライアイコンをクリックすることで、リプライを作成することができます。  
リプライでは予約投稿はできません。

<img src="docs/captures/reply.png" style="width: 70%;">

### 詳細

投稿をクリックすると、詳細画面に遷移し、当該投稿とそれ紐づくリプライを表示します。

<img src="docs/captures/post-detail.png" style="width: 70%;">

### ユーザープロフィール

サイドメニューの「プロフィール」またはユーザーのアイコンやユーザー名をクリックすることで  
ユーザープロフィール画面に遷移します。  
プロフィール画面ではユーザー情報とそのユーザーの「投稿」「リプライ」「いいね」が表示されます

<img src="docs/captures/profile.png" style="width: 70%;">

自分のプロフィール画面ではプロフィール編集ボタンが表示され、プロフィールを編集・更新することができます。  

<img src="docs/captures/profile-edit.png" style="width: 70%;">

フォローやフォロワーのリンクを押すとそれぞれの一覧が表示されます。  

<img src="docs/captures/followee.png" style="width: 70%;">

### メッセージ

ユーザー同士でリアルタイムチャット(テキストのみ)を行えます。  
ユーザープロフィール画面にある✉アイコンからチャットページに遷移します。  

<img src="docs/captures/chat-icon.png" style="width: 70%;">
<img src="docs/captures/chat.png" style="width: 70%;">

サイドメニューの「メッセージ」をクリックすると、すでにチャットを行っているユーザーの一覧が表示されます。

<img src="docs/captures/chat-user.png" style="width: 70%;">

### 通知 

サイドメニューの通知をクリックすると、自分以外のユーザーからの
「いいね」「リプライ」「フォロー」「メッセージ」通知を確認することができます。  
通知をクリックすると、通知に対応する画面に遷移します。  
また、未確認の通知は背景色が水色になり、サイドメニューにも未確認の通知数が表示されます。  

<img src="docs/captures/notification.png" style="width: 70%;">

### スマホ版

<img src="docs/captures/trend-sp.png" style="width: 30%; margin-right: 5px">
<img src="docs/captures/profile-sp.png" style="width: 30%;>