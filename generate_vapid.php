<?php
putenv('OPENSSL_CONF=D:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\extras\\ssl\\openssl.cnf');

$config = [
    'curve_name'       => 'prime256v1',
    'private_key_type' => OPENSSL_KEYTYPE_EC,
    'config'           => 'D:\\laragon\\bin\\php\\php-8.3.30-Win32-vs16-x64\\extras\\ssl\\openssl.cnf',
];

$key = openssl_pkey_new($config);
if (!$key) {
    die("Error: " . openssl_error_string() . PHP_EOL);
}

$keyDetails = openssl_pkey_get_details($key);

function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$privateKey = base64UrlEncode($keyDetails['ec']['d']);
$publicKey  = base64UrlEncode("\x04" . $keyDetails['ec']['x'] . $keyDetails['ec']['y']);

echo "=============================" . PHP_EOL;
echo "VAPID_PUBLIC_KEY=" . $publicKey . PHP_EOL;
echo "VAPID_PRIVATE_KEY=" . $privateKey . PHP_EOL;
echo "VAPID_SUBJECT=mailto:admin@hiwash.com" . PHP_EOL;
echo "=============================" . PHP_EOL;
