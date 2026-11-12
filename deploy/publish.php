<?php
/**
 * Publishes the pages and posts listed in content/manifest.json to WordPress.
 * Creates each item if missing, otherwise updates it (matched by slug).
 * Usage: php publish.php /path/to/wordpress /path/to/content
 */
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

$wp_path = rtrim($argv[1] ?? '', '/');
$content_dir = rtrim($argv[2] ?? dirname(__DIR__) . '/content', '/');

if (!$wp_path || !file_exists("$wp_path/wp-load.php")) {
    fwrite(STDERR, "wp-load.php not found in '$wp_path'\n");
    exit(1);
}
$manifest = json_decode((string) @file_get_contents("$content_dir/manifest.json"), true);
if (!is_array($manifest)) {
    fwrite(STDERR, "Could not read $content_dir/manifest.json\n");
    exit(1);
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'viminfo.in';
define('WP_USE_THEMES', false);
require "$wp_path/wp-load.php";

// Keep block markup intact (no logged-in user in CLI, so HTML filters would otherwise apply).
kses_remove_filters();

// Attribute content to the first administrator.
$admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID']);
$author_id = $admins ? $admins[0]->ID : 0;

$failed = false;

function vim_publish(array $item, string $type, string $content_dir, int $author_id): bool
{
    $file = "$content_dir/{$item['file']}";
    if (!file_exists($file)) {
        fwrite(STDERR, "Missing file: $file\n");
        return false;
    }

    $data = [
        'post_type'    => $type,
        'post_status'  => 'publish',
        'post_title'   => $item['title'],
        'post_name'    => $item['slug'],
        'post_content' => file_get_contents($file),
        'post_author'  => $author_id,
    ];
    if (!empty($item['excerpt'])) {
        $data['post_excerpt'] = $item['excerpt'];
    }
    if (!empty($item['date'])) {
        $data['post_date'] = $item['date'];
        $data['post_date_gmt'] = get_gmt_from_date($item['date']);
        $data['edit_date'] = true;
    }

    $existing = get_page_by_path($item['slug'], OBJECT, $type);
    if ($existing) {
        $data['ID'] = $existing->ID;
        $result = wp_update_post($data, true);
        $action = 'Updated';
    } else {
        $result = wp_insert_post($data, true);
        $action = 'Created';
    }

    if (is_wp_error($result)) {
        fwrite(STDERR, "Failed {$item['slug']}: " . $result->get_error_message() . "\n");
        return false;
    }

    if ($type === 'post' && !empty($item['category'])) {
        $term = term_exists($item['category'], 'category') ?: wp_insert_term($item['category'], 'category');
        if (!is_wp_error($term)) {
            wp_set_post_categories($result, [(int) $term['term_id']]);
        }
    }

    echo "$action $type: " . get_permalink($result) . "\n";
    return true;
}

foreach ($manifest['pages'] ?? [] as $item) {
    $failed = !vim_publish($item, 'page', $content_dir, $author_id) || $failed;
}
foreach ($manifest['posts'] ?? [] as $item) {
    $failed = !vim_publish($item, 'post', $content_dir, $author_id) || $failed;
}

exit($failed ? 1 : 0);
