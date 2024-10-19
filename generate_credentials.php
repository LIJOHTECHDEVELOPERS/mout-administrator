<?php
// Path to the downloaded M-Pesa certificate
$cert_path = 'ProductionCertificate.cer';

// Your initiator password
$initiator_password = 'Mout@2024';

// Read the certificate
$cert = file_get_contents($cert_path);

// Extract the public key from the certificate
$pubkey = openssl_pkey_get_public($cert);

if ($pubkey === false) {
    die('Error loading public key');
}

// Encrypt the password
$encrypted = '';
if (!openssl_public_encrypt($initiator_password, $encrypted, $pubkey, OPENSSL_PKCS1_PADDING)) {
    die('Error encrypting data');
}

// Base64 encode the encrypted password
$security_credential = base64_encode($encrypted);

echo "Your security credential is: " . $security_credential;

// Clean up
openssl_pkey_free($pubkey); // Updated to use openssl_pkey_free
?>
