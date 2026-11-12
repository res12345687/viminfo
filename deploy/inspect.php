<?php
/** Read-only: print the rendered homepage <title> and header. */
$wp_path = rtrim($argv[1] ?? '', '/');
$_SERVER['HTTP_HOST'] = 'viminfo.in';
define('WP_USE_THEMES', false);
require "$wp_path/wp-load.php";
$html = wp_remote_retrieve_body(wp_remote_get(add_query_arg('nocache', time(), home_url('/')), ['timeout' => 20, 'sslverify' => false]));
if (preg_match('/<title>.*?<\/title>/is', $html, $m)) echo $m[0] . "\n";
if (preg_match('/<header.*?<\/nav>/is', $html, $m)) echo substr(preg_replace('/\s+/', ' ', $m[0]), 0, 900) . "\n";
echo "brand css present: " . (strpos($html, 'id="viminfo-brand"') !== false ? 'yes' : 'no') . "\n";
echo "wordmark count: " . substr_count($html, 'vi-brand__mark') . "\n";
