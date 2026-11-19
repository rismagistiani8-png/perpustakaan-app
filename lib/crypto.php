<?php
// Skema hybrid dua-lapis: butuh KUNCI SIMETRIS server + KUNCI ASIMETRIS (RSA private key admin)
// 1) plaintext  --AES-256-GCM(DEK acak)-->  data
// 2) DEK        --RSA-OAEP(public key)-->   wrapped
// 3) wrapped    --AES-256-GCM(KEK)------>   lock   (KEK = kunci simetris server, storage/master.key.php)

function gcm_enc(string $key, string $pt): string {
    $iv = random_bytes(12);
    $ct = openssl_encrypt($pt, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ct === false) throw new RuntimeException('Enkripsi gagal');
    return $iv . $tag . $ct;
}

function gcm_dec(string $key, string $blob): ?string {
    if (strlen($blob) < 28) return null;
    $r = openssl_decrypt(substr($blob, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($blob, 0, 12), substr($blob, 12, 16));
    return $r === false ? null : $r;
}

function seal(string $pt, string $pubPem, string $kek): array {
    $dek  = random_bytes(32);
    $data = gcm_enc($dek, $pt);
    if (!openssl_public_encrypt($dek, $wrapped, $pubPem, OPENSSL_PKCS1_OAEP_PADDING))
        throw new RuntimeException('RSA gagal');
    return [base64_encode($data), base64_encode(gcm_enc($kek, $wrapped))];
}

function unseal(string $data, string $lock, string $privPem, string $kek): ?string {
    $wrapped = gcm_dec($kek, base64_decode($lock, true) ?: '');
    if ($wrapped === null) return null;                                                                  // kunci simetris salah
    if (!openssl_private_decrypt($wrapped, $dek, $privPem, OPENSSL_PKCS1_OAEP_PADDING)) return null;    // kunci asimetris salah
    return gcm_dec($dek, base64_decode($data, true) ?: '');
}
