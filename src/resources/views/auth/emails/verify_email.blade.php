<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title></title>
</head>

<body>
  <h1>会員本登録のご案内</h1>
  <p>{{ config('app.name')}}への仮登録ありがとうございます。</p>
  <p>以下のURLから、会員本登録を完了させてください。</p>
  <a href="{{ $verificationUrl }}">{{ $verificationUrl }}</a>
  <p>※有効期限は60分です。</p>
</body>

</html>