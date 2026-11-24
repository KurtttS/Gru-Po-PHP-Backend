<?php

echo "PHP Version: " . PHP_VERSION . "<br>";
echo "PHP SAPI: " . PHP_SAPI . "<br>";
echo "Loaded php.ini: " . php_ini_loaded_file() . "<br>";
echo "Extension directory: " . ini_get('extension_dir') . "<br>";
echo "cURL DLL exists: " .
    (file_exists(ini_get('extension_dir') . DIRECTORY_SEPARATOR . 'php_curl.dll') ? 'YES' : 'NO') . "<br>";
echo "cURL loaded: " .
    (extension_loaded('curl') ? 'YES' : 'NO') . "<br>";
echo "curl_init exists: " .
    (function_exists('curl_init') ? 'YES' : 'NO');

?>