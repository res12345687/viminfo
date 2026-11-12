<?php
/**
 * Plugin Name: VimInfo Brand Wordmark
 * Description: Replaces the plain site title in the header with the styled "VimInfo and Systems" wordmark. Managed from GitHub (res12345687/viminfo).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The theme prints the header title with bloginfo('name'). Swap in the wordmark
 * only for the first name lookup after <head> is done, so the page <title>,
 * feeds and meta tags keep the plain text name.
 */
add_action('wp_head', function () {
    $GLOBALS['viminfo_brand_pending'] = true;
}, PHP_INT_MAX);

add_filter('bloginfo', function ($output, $show) {
    if ($show !== 'name' || empty($GLOBALS['viminfo_brand_pending']) || is_admin() || is_feed()) {
        return $output;
    }
    $GLOBALS['viminfo_brand_pending'] = false;

    return '<span class="vi-brand__mark" aria-hidden="true">V</span>'
        . '<span class="vi-brand__text">'
        . '<span class="vi-brand__name">Vim<span>Info</span></span>'
        . '<span class="vi-brand__sub">and Systems</span>'
        . '</span>'
        . '<span class="screen-reader-text">VimInfo and Systems</span>';
}, PHP_INT_MAX, 2);

add_action('wp_head', function () {
    ?>
<style id="viminfo-brand">
.site-title a{display:inline-flex;align-items:center;gap:11px;text-decoration:none;color:var(--th-navy,#0b1f3a)}
.vi-brand__mark{flex:none;display:grid;place-items:center;width:40px;height:40px;border-radius:11px;background:linear-gradient(135deg,var(--th-navy,#0b1f3a) 0%,var(--th-navy-light,#12294f) 45%,var(--th-teal,#16b8a6) 100%);color:#fff;font-size:1.25rem;font-weight:800;letter-spacing:-.03em;box-shadow:0 6px 16px rgba(11,31,58,.22),inset 0 1px 0 rgba(255,255,255,.18);transition:transform .25s ease,box-shadow .25s ease}
.site-title a:hover .vi-brand__mark{transform:rotate(-6deg) scale(1.05);box-shadow:0 10px 22px rgba(22,184,166,.35)}
.vi-brand__text{display:flex;flex-direction:column;line-height:1}
.vi-brand__name{font-size:1.4rem;font-weight:800;letter-spacing:-.025em;color:var(--th-navy,#0b1f3a)}
.vi-brand__name span{background:linear-gradient(90deg,var(--th-teal,#16b8a6),var(--th-teal-dark,#0f9184));-webkit-background-clip:text;background-clip:text;color:transparent}
.vi-brand__sub{margin-top:5px;font-size:.6rem;font-weight:600;letter-spacing:.34em;text-transform:uppercase;color:var(--th-muted,#5c6b7a)}
.site-title .screen-reader-text{position:absolute!important;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap}
@media (max-width:600px){.vi-brand__mark{width:34px;height:34px;font-size:1.05rem;border-radius:9px}.vi-brand__name{font-size:1.2rem}.vi-brand__sub{font-size:.52rem;letter-spacing:.28em}}
</style>
    <?php
}, 20);
