<?php
$wp_path = rtrim($argv[1] ?? '', '/');
$_SERVER['HTTP_HOST'] = 'viminfo.in';
define('WP_USE_THEMES', false);
require "$wp_path/wp-load.php";
echo "login url: " . wp_login_url() . "\n";
$r = wp_remote_get(wp_login_url(), ['timeout' => 20, 'sslverify' => false, 'redirection' => 0]);
echo "GET status: " . wp_remote_retrieve_response_code($r) . " location: " . wp_remote_retrieve_header($r, 'location') . " len: " . strlen(wp_remote_retrieve_body($r)) . "\n";
$b = wp_remote_retrieve_body($r);
echo "GET has loginform: " . (strpos($b, 'loginform') !== false ? 'yes' : 'no') . ", captcha: " . (strpos($b, 'vi_captcha_answer') !== false ? 'yes' : 'no') . "\n";
if (is_wp_error($r)) echo "error: " . $r->get_error_message() . "\n";
echo "snippet: " . substr(preg_replace('/\s+/', ' ', strip_tags($b)), 0, 300) . "\n";
$res = wp_remote_post(wp_login_url(), ['timeout' => 20, 'sslverify' => false, 'redirection' => 0,
    'body' => ['log' => 'captcha-test-nouser', 'pwd' => 'x', 'wp-submit' => 'Log In', 'testcookie' => 1],
    'cookies' => [new WP_Http_Cookie(['name' => 'wordpress_test_cookie', 'value' => 'WP Cookie check'])]]);
$pb = wp_remote_retrieve_body($res);
echo "POST status: " . wp_remote_retrieve_response_code($res) . ", re-shown form has captcha: " . (strpos($pb, 'vi_captcha_answer') !== false ? 'yes' : 'no') . "\n";
// Also render the login_form action output directly.
ob_start(); do_action('login_form'); $o = ob_get_clean();
echo "login_form hook outputs captcha: " . (strpos($o, 'vi_captcha_answer') !== false ? 'yes' : 'no') . "\n";
