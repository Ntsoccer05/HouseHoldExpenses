<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- CSRF Token -->
  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- Lambda環境変数経由の日本語文字列はBrefランタイム側でgetenv()時に文字化けするため、config('app.name')に依存せずハードコードする --}}
  <title>カケポン|家計簿アプリ</title>
  <link rel="shortcut icon" href="{{ asset('/favicon.ico') }}">

  <!-- Fonts -->
  <link rel="dns-prefetch" href="//fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
  <link rel="preload" as="image" href="{{ asset('/logo/スマカケ.webp') }}">
</head>

<body>
</body>

</html>