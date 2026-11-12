<?php
/** Read-only report on how the site header/front page is built. Usage: php inspect.php /path/to/wordpress */
$wp_path = rtrim($argv[1] ?? '', '/');
$_SERVER['HTTP_HOST'] = 'viminfo.in';
define('WP_USE_THEMES', false);
require "$wp_path/wp-load.php";

$theme = wp_get_theme();
echo "theme: " . $theme->get('Name') . " (" . get_stylesheet() . ", parent " . get_template() . ")\n";
echo "block theme: " . (function_exists('wp_is_block_theme') && wp_is_block_theme() ? 'yes' : 'no') . "\n";
echo "blogname: " . get_option('blogname') . "\n";
echo "blogdescription: " . get_option('blogdescription') . "\n";
echo "show_on_front: " . get_option('show_on_front') . ", page_on_front: " . get_option('page_on_front') . ", page_for_posts: " . get_option('page_for_posts') . "\n";
echo "custom_logo: " . (int) get_theme_mod('custom_logo') . "\n";
echo "display_header_text: " . var_export(get_theme_mod('header_text', true), true) . "\n";

$front = (int) get_option('page_on_front');
if ($front) {
    $p = get_post($front);
    echo "front page: {$p->post_title} ({$p->post_name}), template: " . get_page_template_slug($front) . "\n";
    foreach (preg_split('/\n/', $p->post_content) as $i => $line) {
        if (stripos($line, 'viminfo') !== false) echo "  front content line $i: " . substr(trim($line), 0, 300) . "\n";
    }
}

// Search options, theme mods, template parts and widgets for the literal text.
global $wpdb;
$rows = $wpdb->get_results("SELECT option_name, LEFT(option_value, 300) v FROM {$wpdb->options} WHERE option_value LIKE '%viminfo.in%' AND option_name NOT IN ('siteurl','home') LIMIT 20");
foreach ($rows as $r) echo "option {$r->option_name}: {$r->v}\n";
$rows = $wpdb->get_results("SELECT ID, post_type, post_name, post_status FROM {$wpdb->posts} WHERE post_content LIKE '%viminfo.in%' AND post_status IN ('publish','private','draft') AND post_type NOT IN ('revision') LIMIT 30");
foreach ($rows as $r) echo "post {$r->ID} {$r->post_type} {$r->post_name} {$r->post_status}\n";

// Theme files mentioning it.
foreach ([get_stylesheet_directory(), get_template_directory()] as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!preg_match('/\.(php|html|json)$/', $f)) continue;
        $c = @file_get_contents($f);
        if ($c !== false && stripos($c, 'viminfo.in') !== false) echo "theme file: $f\n";
    }
}

// Rendered homepage header.
$html = wp_remote_retrieve_body(wp_remote_get(home_url('/'), ['timeout' => 20, 'sslverify' => false]));
if ($html) {
    if (preg_match('/<header.*?<\/header>/is', $html, $m)) echo "rendered header:\n" . substr(preg_replace('/\s+/', ' ', $m[0]), 0, 3000) . "\n";
    preg_match_all('/.{0,200}viminfo\.in.{0,100}/i', $html, $mm);
    foreach (array_slice($mm[0], 0, 10) as $s) echo "html: " . preg_replace('/\s+/', ' ', $s) . "\n";
} else {
    echo "could not fetch homepage\n";
}
