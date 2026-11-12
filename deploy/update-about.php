<?php
/**
 * Creates or updates the "About" page on viminfo.in from about-page.html.
 * Usage: php update-about.php /path/to/wordpress /path/to/about-page.html
 */
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

$wp_path = rtrim($argv[1] ?? '', '/');
$html_file = $argv[2] ?? __DIR__ . '/about-page.html';

if (!$wp_path || !file_exists("$wp_path/wp-load.php")) {
    fwrite(STDERR, "wp-load.php not found in '$wp_path'\n");
    exit(1);
}
if (!file_exists($html_file)) {
    fwrite(STDERR, "Content file not found: $html_file\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'viminfo.in';
define('WP_USE_THEMES', false);
require "$wp_path/wp-load.php";

// Keep block markup intact (no logged-in user in CLI, so HTML filters would otherwise apply).
kses_remove_filters();

$content = file_get_contents($html_file);
$page = get_page_by_path('about', OBJECT, 'page');

if ($page) {
    $result = wp_update_post([
        'ID'           => $page->ID,
        'post_content' => $content,
        'post_status'  => 'publish',
    ], true);
    $action = 'Updated';
} else {
    $result = wp_insert_post([
        'post_title'   => 'About',
        'post_name'    => 'about',
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_content' => $content,
    ], true);
    $action = 'Created';
}

if (is_wp_error($result)) {
    fwrite(STDERR, 'Failed: ' . $result->get_error_message() . "\n");
    exit(1);
}

echo "$action About page (ID $result): " . get_permalink($result) . "\n";
