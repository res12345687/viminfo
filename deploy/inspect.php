<?php
$wp_path = rtrim($argv[1] ?? '', '/');
$_SERVER['HTTP_HOST'] = 'viminfo.in';
define('WP_USE_THEMES', false);
require "$wp_path/wp-load.php";
$r = wp_remote_get(wp_login_url(), ['timeout' => 20, 'sslverify' => false]);
$b = wp_remote_retrieve_body($r);
preg_match('/<title>(.*?)<\/title>/s', $b, $t);
echo "login title: " . ($t[1] ?? '?') . "\n";
echo "login page shows captcha: " . (strpos($b, 'vi_captcha_answer') !== false ? 'yes' : 'no') . "\n";
foreach (['cache-control', 'x-litespeed-cache', 'x-cache', 'cf-cache-status', 'x-proxy-cache', 'age', 'server'] as $h) {
    $v = wp_remote_retrieve_header($r, $h); if ($v) echo "header $h: " . (is_array($v) ? implode(',', $v) : $v) . "\n";
}
$a = wp_remote_retrieve_body(wp_remote_get(admin_url('admin-ajax.php?action=vi_captcha'), ['timeout' => 20, 'sslverify' => false]));
echo "ajax challenge: " . substr($a, 0, 80) . "\n";
