<?php
/** Read-only: confirm captcha renders on login and blocks a login attempt without it. */
$wp_path = rtrim($argv[1] ?? '', '/');
$_SERVER['HTTP_HOST'] = 'viminfo.in';
define('WP_USE_THEMES', false);
require "$wp_path/wp-load.php";
require_once ABSPATH . 'wp-admin/includes/plugin.php';
echo "captcha plugin active: " . (is_plugin_active('viminfo-captcha/viminfo-captcha.php') ? 'yes' : 'no') . "\n";
$login = wp_remote_retrieve_body(wp_remote_get(wp_login_url(), ['timeout' => 20, 'sslverify' => false]));
echo "login page shows captcha: " . (strpos($login, 'vi_captcha_answer') !== false ? 'yes' : 'no') . "\n";
if (preg_match('/Security check: what is \d \+ \d\?/', $login, $m)) echo "question: {$m[0]}\n";
// Attempt a login with a made-up user and no captcha answer: should be rejected by the captcha.
$res = wp_remote_post(wp_login_url(), ['timeout' => 20, 'sslverify' => false, 'redirection' => 0,
    'body' => ['log' => 'captcha-test-nouser', 'pwd' => 'x', 'wp-submit' => 'Log In', 'testcookie' => 1],
    'cookies' => [new WP_Http_Cookie(['name' => 'wordpress_test_cookie', 'value' => 'WP Cookie check'])]]);
$body = wp_remote_retrieve_body($res);
echo "login without captcha blocked by captcha: " . (stripos($body, 'security question') !== false ? 'yes' : 'no') . "\n";
if (function_exists('comments_open')) echo "comments default open: " . get_option('default_comment_status') . "\n";
