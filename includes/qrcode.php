<?php
/**
 * OpenFrame — Offline QR Code encoder
 * -----------------------------------
 * Byte mode, ECC level M, versions 1–16.
 * No GD, no Composer, no network. Ported from Project Nayuki (MIT / public domain).
 */

/**
 * Encode $text as a QR matrix of 0/1 modules. Index is [row][col].
 *
 * @return array<int,array<int,int>>
 */
function qrcode_matrix($text)
{
    $data = qr_encode_payload((string) $text, $version);
    $codewords = qr_add_ecc_and_interleave($data, $version);
    $size = $version * 4 + 17;

    $mod = qr_blank($size, 0);
    $fn  = qr_blank($size, false);
    qr_draw_function_patterns($mod, $fn, $version, $size);

    qr_draw_codewords($mod, $fn, $codewords, $size);

    $bestScore = PHP_INT_MAX;
    $best = $mod;
    for ($mask = 0; $mask < 8; $mask++) {
        $cand = $mod;
        qr_apply_mask($cand, $fn, $mask, $size);
        qr_draw_format_bits($cand, $fn, $mask, $size);
        $score = qr_penalty_score($cand, $size);
        if ($score < $bestScore) {
            $bestScore = $score;
            $best = $cand;
        }
    }

    return $best;
}

/**
 * Pixel-aligned SVG (integer module rects, no viewBox scaling).
 *
 * @return string
 */
function qrcode_svg($text, $modulePx = 6, $margin = 4)
{
    $matrix = qrcode_matrix($text);
    $n = count($matrix);
    $modulePx = max(3, (int) $modulePx);
    $margin = max(4, (int) $margin);
    $dim = ($n + 2 * $margin) * $modulePx;

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $dim . '" height="' . $dim
        . '" shape-rendering="crispEdges" role="img" aria-label="QR code">'
        . '<rect width="' . $dim . '" height="' . $dim . '" fill="#ffffff"/>';

    for ($r = 0; $r < $n; $r++) {
        for ($c = 0; $c < $n; $c++) {
            if (!empty($matrix[$r][$c])) {
                $x = ($c + $margin) * $modulePx;
                $y = ($r + $margin) * $modulePx;
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $modulePx
                    . '" height="' . $modulePx . '" fill="#000000"/>';
            }
        }
    }

    return $svg . '</svg>';
}

/**
 * HTML table QR — high-contrast modules for phone cameras.
 *
 * @return string
 */
function qrcode_html($text, $modulePx = 6, $margin = 4)
{
    $matrix = qrcode_matrix($text);
    $n = count($matrix);
    $modulePx = max(3, (int) $modulePx);
    $margin = max(4, (int) $margin);
    $total = $n + 2 * $margin;
    $cell = 'width:' . $modulePx . 'px;height:' . $modulePx . 'px;padding:0;margin:0;border:0;line-height:0;font-size:0;';

    $html = '<table class="otp-qr" role="img" aria-label="QR code" cellpadding="0" cellspacing="0"'
        . ' style="border-collapse:collapse;border-spacing:0;margin:0 auto;background:#ffffff;">';

    for ($r = 0; $r < $total; $r++) {
        $html .= '<tr>';
        for ($c = 0; $c < $total; $c++) {
            $mr = $r - $margin;
            $mc = $c - $margin;
            $dark = ($mr >= 0 && $mr < $n && $mc >= 0 && $mc < $n && !empty($matrix[$mr][$mc]));
            $html .= '<td style="' . $cell . 'background:' . ($dark ? '#000000' : '#ffffff') . '"></td>';
        }
        $html .= '</tr>';
    }

    return $html . '</table>';
}

function qr_blank($size, $fill)
{
    $row = array_fill(0, $size, $fill);
    $out = array();
    for ($i = 0; $i < $size; $i++) {
        $out[$i] = $row;
    }

    return $out;
}

function qr_append_bits(array &$bb, $val, $len)
{
    for ($i = $len - 1; $i >= 0; $i--) {
        $bb[] = ($val >> $i) & 1;
    }
}

function qr_ecc_per_block($version)
{
    static $t = array(
        0, 10, 16, 26, 18, 24, 16, 18, 22, 22, 26, 30, 22, 22, 24, 24, 28,
    );

    return $t[$version];
}

function qr_num_ecc_blocks($version)
{
    static $t = array(
        0, 1, 1, 1, 2, 2, 4, 4, 4, 5, 5, 5, 8, 9, 9, 10, 10,
    );

    return $t[$version];
}

function qr_num_raw_data_modules($ver)
{
    $result = (16 * $ver + 128) * $ver + 64;
    if ($ver >= 2) {
        $numAlign = intdiv($ver, 7) + 2;
        $result -= (25 * $numAlign - 10) * $numAlign - 55;
        if ($ver >= 7) {
            $result -= 36;
        }
    }

    return $result;
}

function qr_num_data_codewords($ver)
{
    return intdiv(qr_num_raw_data_modules($ver), 8)
        - qr_ecc_per_block($ver) * qr_num_ecc_blocks($ver);
}

function qr_ccbits($ver)
{
    return ($ver <= 9) ? 8 : 16;
}

function qr_encode_payload($text, &$version)
{
    $len = strlen($text);
    $version = 0;
    for ($v = 1; $v <= 16; $v++) {
        $capacity = qr_num_data_codewords($v) * 8;
        $need = 4 + qr_ccbits($v) + 8 * $len + 4;
        if ($need <= $capacity) {
            $version = $v;
            break;
        }
    }
    if ($version === 0) {
        $text = substr($text, 0, 100);
        $len = strlen($text);
        $version = 10;
    }

    $capacity = qr_num_data_codewords($version) * 8;
    $bb = array();
    qr_append_bits($bb, 0x4, 4);
    qr_append_bits($bb, $len, qr_ccbits($version));
    for ($i = 0; $i < $len; $i++) {
        qr_append_bits($bb, ord($text[$i]), 8);
    }

    $term = min(4, $capacity - count($bb));
    if ($term > 0) {
        qr_append_bits($bb, 0, $term);
    }
    while (count($bb) % 8 !== 0) {
        $bb[] = 0;
    }

    $pad = 0xEC;
    while (count($bb) < $capacity) {
        qr_append_bits($bb, $pad, 8);
        $pad ^= 0xEC ^ 0x11;
    }

    $bytes = array();
    $n = intdiv(count($bb), 8);
    for ($i = 0; $i < $n; $i++) {
        $b = 0;
        for ($j = 0; $j < 8; $j++) {
            $b = ($b << 1) | $bb[$i * 8 + $j];
        }
        $bytes[] = $b;
    }

    return $bytes;
}

function qr_rs_multiply($x, $y)
{
    $z = 0;
    for ($i = 7; $i >= 0; $i--) {
        $z = ($z << 1) ^ ((($z >> 7) & 1) * 0x11D);
        $z ^= (($y >> $i) & 1) * $x;
    }

    return $z;
}

function qr_rs_divisor($degree)
{
    $result = array_fill(0, $degree, 0);
    $result[$degree - 1] = 1;
    $root = 1;
    for ($i = 0; $i < $degree; $i++) {
        for ($j = 0; $j < $degree; $j++) {
            $result[$j] = qr_rs_multiply($result[$j], $root);
            if ($j + 1 < $degree) {
                $result[$j] ^= $result[$j + 1];
            }
        }
        $root = qr_rs_multiply($root, 0x02);
    }

    return $result;
}

function qr_rs_remainder(array $data, array $divisor)
{
    $result = array_fill(0, count($divisor), 0);
    foreach ($data as $b) {
        $factor = $b ^ $result[0];
        array_shift($result);
        $result[] = 0;
        for ($i = 0; $i < count($result); $i++) {
            $result[$i] ^= qr_rs_multiply($divisor[$i], $factor);
        }
    }

    return $result;
}

function qr_add_ecc_and_interleave(array $data, $version)
{
    $numBlocks = qr_num_ecc_blocks($version);
    $blockEccLen = qr_ecc_per_block($version);
    $rawCodewords = intdiv(qr_num_raw_data_modules($version), 8);
    $numShortBlocks = $numBlocks - ($rawCodewords % $numBlocks);
    $shortBlockLen = intdiv($rawCodewords, $numBlocks);
    $rsDiv = qr_rs_divisor($blockEccLen);

    $blocks = array();
    $k = 0;
    for ($i = 0; $i < $numBlocks; $i++) {
        $datLen = $shortBlockLen - $blockEccLen + (($i < $numShortBlocks) ? 0 : 1);
        $dat = array_slice($data, $k, $datLen);
        $k += $datLen;
        $ecc = qr_rs_remainder($dat, $rsDiv);
        if ($i < $numShortBlocks) {
            $dat[] = 0;
        }
        $blocks[] = array_merge($dat, $ecc);
    }

    $result = array();
    $blockLen = count($blocks[0]);
    $skipAt = $shortBlockLen - $blockEccLen;
    for ($i = 0; $i < $blockLen; $i++) {
        for ($j = 0; $j < $numBlocks; $j++) {
            if ($i !== $skipAt || $j >= $numShortBlocks) {
                $result[] = $blocks[$j][$i];
            }
        }
    }

    return $result;
}

function qr_alignment_positions($version)
{
    static $t = array(
        1  => array(),
        2  => array(6, 18),
        3  => array(6, 22),
        4  => array(6, 26),
        5  => array(6, 30),
        6  => array(6, 34),
        7  => array(6, 22, 38),
        8  => array(6, 24, 42),
        9  => array(6, 26, 46),
        10 => array(6, 28, 50),
        11 => array(6, 30, 54),
        12 => array(6, 32, 58),
        13 => array(6, 34, 62),
        14 => array(6, 26, 46, 66),
        15 => array(6, 26, 48, 70),
        16 => array(6, 26, 50, 74),
    );

    return $t[$version];
}

function qr_set_function(array &$mod, array &$fn, $x, $y, $dark)
{
    $mod[$y][$x] = $dark ? 1 : 0;
    $fn[$y][$x] = true;
}

function qr_draw_finder(array &$mod, array &$fn, $cx, $cy, $size)
{
    for ($dy = -4; $dy <= 4; $dy++) {
        for ($dx = -4; $dx <= 4; $dx++) {
            $x = $cx + $dx;
            $y = $cy + $dy;
            if ($x < 0 || $y < 0 || $x >= $size || $y >= $size) {
                continue;
            }
            $dist = max(abs($dx), abs($dy));
            qr_set_function($mod, $fn, $x, $y, ($dist !== 2 && $dist !== 4));
        }
    }
}

function qr_draw_alignment(array &$mod, array &$fn, $cx, $cy)
{
    for ($dy = -2; $dy <= 2; $dy++) {
        for ($dx = -2; $dx <= 2; $dx++) {
            qr_set_function($mod, $fn, $cx + $dx, $cy + $dy, max(abs($dx), abs($dy)) !== 1);
        }
    }
}

function qr_format_bits($mask)
{
    $data = (0 << 3) | ($mask & 7);
    $rem = $data;
    for ($i = 0; $i < 10; $i++) {
        $rem = ($rem << 1) ^ ((($rem >> 9) & 1) * 0x537);
    }

    return (($data << 10) | $rem) ^ 0x5412;
}

function qr_draw_format_bits(array &$mod, array &$fn, $mask, $size)
{
    $bits = qr_format_bits($mask);
    for ($i = 0; $i <= 5; $i++) {
        qr_set_function($mod, $fn, 8, $i, ($bits >> $i) & 1);
    }
    qr_set_function($mod, $fn, 8, 7, ($bits >> 6) & 1);
    qr_set_function($mod, $fn, 8, 8, ($bits >> 7) & 1);
    qr_set_function($mod, $fn, 7, 8, ($bits >> 8) & 1);
    for ($i = 9; $i <= 14; $i++) {
        qr_set_function($mod, $fn, 14 - $i, 8, ($bits >> $i) & 1);
    }
    for ($i = 0; $i <= 7; $i++) {
        qr_set_function($mod, $fn, $size - 1 - $i, 8, ($bits >> $i) & 1);
    }
    for ($i = 8; $i <= 14; $i++) {
        qr_set_function($mod, $fn, 8, $size - 15 + $i, ($bits >> $i) & 1);
    }
    qr_set_function($mod, $fn, 8, $size - 8, true);
}

function qr_draw_version(array &$mod, array &$fn, $version, $size)
{
    if ($version < 7) {
        return;
    }
    $rem = $version;
    for ($i = 0; $i < 12; $i++) {
        $rem = ($rem << 1) ^ ((($rem >> 11) & 1) * 0x1F25);
    }
    $bits = ($version << 12) | $rem;
    for ($i = 0; $i < 18; $i++) {
        $bit = ($bits >> $i) & 1;
        $a = $size - 11 + ($i % 3);
        $b = intdiv($i, 3);
        qr_set_function($mod, $fn, $a, $b, $bit);
        qr_set_function($mod, $fn, $b, $a, $bit);
    }
}

function qr_draw_function_patterns(array &$mod, array &$fn, $version, $size)
{
    for ($i = 0; $i < $size; $i++) {
        qr_set_function($mod, $fn, 6, $i, $i % 2 === 0);
        qr_set_function($mod, $fn, $i, 6, $i % 2 === 0);
    }

    qr_draw_finder($mod, $fn, 3, 3, $size);
    qr_draw_finder($mod, $fn, $size - 4, 3, $size);
    qr_draw_finder($mod, $fn, 3, $size - 4, $size);

    $pos = qr_alignment_positions($version);
    $num = count($pos);
    for ($i = 0; $i < $num; $i++) {
        for ($j = 0; $j < $num; $j++) {
            if (($i === 0 && $j === 0) || ($i === 0 && $j === $num - 1) || ($i === $num - 1 && $j === 0)) {
                continue;
            }
            qr_draw_alignment($mod, $fn, $pos[$i], $pos[$j]);
        }
    }

    qr_draw_format_bits($mod, $fn, 0, $size);
    qr_draw_version($mod, $fn, $version, $size);
}

function qr_draw_codewords(array &$mod, array &$fn, array $data, $size)
{
    $i = 0;
    $nBits = count($data) * 8;
    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right <= 6) {
            $right -= 1;
        }
        for ($vert = 0; $vert < $size; $vert++) {
            for ($j = 0; $j < 2; $j++) {
                $x = $right - $j;
                $upward = (($right + 1) & 2) === 0;
                $y = $upward ? $size - 1 - $vert : $vert;
                if (!$fn[$y][$x] && $i < $nBits) {
                    $mod[$y][$x] = ($data[$i >> 3] >> (7 - ($i & 7))) & 1;
                    $i++;
                }
            }
        }
    }
}

function qr_mask_bit($mask, $x, $y)
{
    switch ($mask) {
        case 0: return (($x + $y) % 2) === 0;
        case 1: return ($y % 2) === 0;
        case 2: return ($x % 3) === 0;
        case 3: return (($x + $y) % 3) === 0;
        case 4: return ((intdiv($x, 3) + intdiv($y, 2)) % 2) === 0;
        case 5: return (($x * $y) % 2 + ($x * $y) % 3) === 0;
        case 6: return ((($x * $y) % 2 + ($x * $y) % 3) % 2) === 0;
        default: return ((($x + $y) % 2 + ($x * $y) % 3) % 2) === 0;
    }
}

function qr_apply_mask(array &$mod, array &$fn, $mask, $size)
{
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            if ($fn[$y][$x]) {
                continue;
            }
            if (qr_mask_bit($mask, $x, $y)) {
                $mod[$y][$x] ^= 1;
            }
        }
    }
}

function qr_penalty_score(array $mod, $size)
{
    $score = 0;

    for ($y = 0; $y < $size; $y++) {
        $run = 1;
        for ($x = 1; $x < $size; $x++) {
            if ($mod[$y][$x] === $mod[$y][$x - 1]) {
                $run++;
            } else {
                if ($run >= 5) {
                    $score += 3 + ($run - 5);
                }
                $run = 1;
            }
        }
        if ($run >= 5) {
            $score += 3 + ($run - 5);
        }
    }
    for ($x = 0; $x < $size; $x++) {
        $run = 1;
        for ($y = 1; $y < $size; $y++) {
            if ($mod[$y][$x] === $mod[$y - 1][$x]) {
                $run++;
            } else {
                if ($run >= 5) {
                    $score += 3 + ($run - 5);
                }
                $run = 1;
            }
        }
        if ($run >= 5) {
            $score += 3 + ($run - 5);
        }
    }

    for ($y = 0; $y < $size - 1; $y++) {
        for ($x = 0; $x < $size - 1; $x++) {
            $v = $mod[$y][$x];
            if ($v === $mod[$y][$x + 1] && $v === $mod[$y + 1][$x] && $v === $mod[$y + 1][$x + 1]) {
                $score += 3;
            }
        }
    }

    $dark = 0;
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            if ($mod[$y][$x]) {
                $dark++;
            }
        }
    }
    $percent = (int) (100 * $dark / ($size * $size));
    $score += 10 * (int) (abs($percent - 50) / 5);

    return $score;
}
