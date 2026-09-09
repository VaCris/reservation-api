<?php
$config = [
    'private_key_bits' => 4096,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

$res = openssl_pkey_new($config);
openssl_pkey_export($res, $privKey);
$pubKey = openssl_pkey_get_details($res);

file_put_contents(__DIR__ . '/private.pem', $privKey);
file_put_contents(__DIR__ . '/public.pem', $pubKey['key']);

echo "Claves generadas\n";
?>