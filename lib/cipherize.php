<?php
// Mode kunci salah: mengganti setiap teks di area konten dengan potongan ciphertext asli dari database.
// Tag HTML dan atribut tetap utuh, hanya teks (dan placeholder) yang diganti.
function cipherize(string $html): string {
    global $db;
    $pool = (string)setting('check_data');
    foreach ($db->query('SELECT data FROM buku LIMIT 5')->fetchAll(PDO::FETCH_COLUMN) as $c) $pool .= $c;
    if ($pool === '') $pool = 'x7Kq9ZpL2mVd8aWt';
    $big = str_repeat($pool, (int)ceil(500 / max(1, strlen($pool))));
    $n = strlen($big); $i = 0; $inCode = false;

    $parts = preg_split('/(<script.*?<\/script>|<style.*?<\/style>|<[^>]*>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($parts as $k => $p) {
        if ($p === '') continue;
        if ($p[0] === '<') {   // tag HTML: dibiarkan, kecuali atribut placeholder
            if (preg_match('/^<code\b/i', $p)) $inCode = true;
            elseif (preg_match('/^<\/code/i', $p)) $inCode = false;
            $parts[$k] = preg_replace_callback(
                '/placeholder="[^"]*"/i',
                function ($m) use ($big, &$i) { return 'placeholder="' . substr($big, (++$i * 7) % 40, 22) . '"'; },
                $p
            );
            continue;
        }
        if (!preg_match('/^(\s*)(.*?)(\s*)$/s', $p, $m) || $m[2] === '') continue;   // spasi saja: lewati
        $len = min(strlen(html_entity_decode($m[2])), $inCode ? 90 : 30);
        $parts[$k] = $m[1] . substr($big, (++$i * 11) % ($n - 100), max(1, $len)) . $m[3];
    }
    return implode('', $parts);
}
