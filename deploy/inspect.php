<?php
/** Read-only: print theme header branding code and CSS colour variables. */
$wp_path = rtrim($argv[1] ?? '', '/');
$_SERVER['HTTP_HOST'] = 'viminfo.in';
define('WP_USE_THEMES', false);
require "$wp_path/wp-load.php";
$dir = get_stylesheet_directory();
echo "theme dir: $dir\n";
foreach (glob("$dir/*.php") as $f) {
    $c = file_get_contents($f);
    if (preg_match('/site-branding.{0,800}/s', $c, $m)) echo "== " . basename($f) . "\n" . $m[0] . "\n";
}
foreach (array_merge(glob("$dir/*.css"), glob("$dir/assets/css/*.css") ?: [], glob("$dir/css/*.css") ?: []) as $f) {
    $c = file_get_contents($f);
    if (preg_match('/:root\s*{[^}]*}/s', $c, $m)) echo "== " . basename($f) . " :root\n" . $m[0] . "\n";
    if (preg_match_all('/[^}]*(site-title|site-branding)[^{]*{[^}]*}/s', $c, $mm)) echo "== " . basename($f) . " title rules\n" . implode("\n", $mm[0]) . "\n";
    if (preg_match_all('/font-family:[^;]+;/', $c, $ff)) echo "fonts: " . implode(' | ', array_unique($ff[0])) . "\n";
}
echo "mu-plugins dir: " . WPMU_PLUGIN_DIR . " exists=" . (is_dir(WPMU_PLUGIN_DIR) ? 'y' : 'n') . "\n";
