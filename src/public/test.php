<?php
echo "<h1>PHP Info:</h1>";
phpinfo(); // PHPの設定情報
echo "<h1>Server Variables:</h1>";
echo "<pre>";
print_r($_SERVER); // 環境変数、特に SCRIPT_FILENAME, DOCUMENT_ROOT, REQUEST_URI, PATH_INFO を確認
echo "</pre>";
echo "<h1>Laravel Application Path:</h1>";
echo "<pre>";
echo "Base Path: " . base_path() . "\n";
echo "Public Path: " . public_path() . "\n";
echo "Storage Path: " . storage_path() . "\n";
echo "Resources Path: " . resource_path() . "\n";
echo "</pre>";