<?php
/**
 * Plugin Name: VimInfo Captcha
 * Description: Self-hosted math captcha and honeypot for login, registration, lost password and comment forms. No third-party service. Managed from GitHub (res12345687/viminfo).
 */

if (!defined('ABSPATH')) {
    exit;
}

const VIMINFO_CAPTCHA_TTL = 1800; // seconds a challenge stays valid

/** Signature for an answer/time/nonce triple. */
function viminfo_captcha_sign(int $answer, int $ts, string $nonce): string
{
    return hash_hmac('sha256', $answer . '|' . $ts . '|' . $nonce, wp_salt('nonce'));
}

/** Print the captcha fields. */
function viminfo_captcha_field(string $context = 'login'): void
{
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $ts = time();
    $nonce = wp_generate_password(16, false);
    $token = $ts . '|' . $nonce . '|' . viminfo_captcha_sign($a + $b, $ts, $nonce);
    $id = 'vi-captcha-' . $context;
    ?>
    <p class="vi-captcha">
        <label for="<?php echo esc_attr($id); ?>">
            <?php echo esc_html(sprintf('Security check: what is %d + %d?', $a, $b)); ?>
            <span class="required" aria-hidden="true">*</span>
        </label>
        <input type="text" name="vi_captcha_answer" id="<?php echo esc_attr($id); ?>" class="input" inputmode="numeric" pattern="[0-9]*" maxlength="2" autocomplete="off" required>
        <input type="hidden" name="vi_captcha_token" value="<?php echo esc_attr($token); ?>">
        <span class="vi-captcha-hp" aria-hidden="true">
            <label>Leave this field empty <input type="text" name="vi_captcha_website" value="" tabindex="-1" autocomplete="off"></label>
        </span>
    </p>
    <style>.vi-captcha-hp{position:absolute!important;left:-9999px!important;width:1px;height:1px;overflow:hidden}.vi-captcha input[name=vi_captcha_answer]{max-width:120px}</style>
    <?php
}

/** True if the submitted captcha is valid (and marks the challenge as used). */
function viminfo_captcha_passes(): bool
{
    if (!empty($_POST['vi_captcha_website'])) {
        return false; // honeypot filled: bot
    }
    $answer = isset($_POST['vi_captcha_answer']) ? trim(wp_unslash((string) $_POST['vi_captcha_answer'])) : '';
    $token = isset($_POST['vi_captcha_token']) ? wp_unslash((string) $_POST['vi_captcha_token']) : '';
    if ($answer === '' || !ctype_digit($answer) || substr_count($token, '|') !== 2) {
        return false;
    }
    [$ts, $nonce, $sig] = explode('|', $token);
    $ts = (int) $ts;
    $age = time() - $ts;
    if ($age < 0 || $age > VIMINFO_CAPTCHA_TTL || !preg_match('/^[A-Za-z0-9]{16}$/', $nonce)) {
        return false;
    }
    if (!hash_equals(viminfo_captcha_sign((int) $answer, $ts, $nonce), $sig)) {
        return false;
    }
    $key = 'vi_cap_' . md5($nonce);
    if (get_transient($key)) {
        return false; // already used
    }
    set_transient($key, 1, VIMINFO_CAPTCHA_TTL);
    return true;
}

/** Only browser form posts to wp-login.php are checked; app passwords, XML-RPC, REST and WP-CLI are untouched. */
function viminfo_captcha_is_login_form_post(): bool
{
    if ((defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) || (defined('REST_REQUEST') && REST_REQUEST) || (defined('WP_CLI') && WP_CLI) || wp_doing_ajax()) {
        return false;
    }
    return ($GLOBALS['pagenow'] ?? '') === 'wp-login.php' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

const VIMINFO_CAPTCHA_ERROR = '<strong>Error:</strong> Please answer the security question correctly.';

// Login.
add_action('login_form', function () {
    viminfo_captcha_field('login');
});
add_filter('authenticate', function ($user, $username) {
    if ($username === '' || $username === null || !viminfo_captcha_is_login_form_post()) {
        return $user;
    }
    if (!viminfo_captcha_passes()) {
        return new WP_Error('vi_captcha', VIMINFO_CAPTCHA_ERROR);
    }
    return $user;
}, 30, 2);

// Registration.
add_action('register_form', function () {
    viminfo_captcha_field('register');
});
add_filter('registration_errors', function ($errors) {
    if (viminfo_captcha_is_login_form_post() && !viminfo_captcha_passes()) {
        $errors->add('vi_captcha', VIMINFO_CAPTCHA_ERROR);
    }
    return $errors;
}, 10, 1);

// Lost password.
add_action('lostpassword_form', function () {
    viminfo_captcha_field('lostpassword');
});
add_action('lostpassword_post', function ($errors) {
    if (viminfo_captcha_is_login_form_post() && !viminfo_captcha_passes() && $errors instanceof WP_Error) {
        $errors->add('vi_captcha', VIMINFO_CAPTCHA_ERROR);
    }
}, 10, 1);

// Comments from visitors who aren't logged in.
add_action('comment_form_after_fields', function () {
    viminfo_captcha_field('comment');
});
add_filter('preprocess_comment', function ($comment) {
    if (is_user_logged_in() || is_admin() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return $comment;
    }
    $type = $comment['comment_type'] ?? '';
    if ($type === 'pingback' || $type === 'trackback') {
        return $comment;
    }
    if (!viminfo_captcha_passes()) {
        wp_die(
            esc_html__('Please go back and answer the security question correctly.'),
            esc_html__('Security check failed'),
            ['response' => 403, 'back_link' => true]
        );
    }
    return $comment;
}, 1);
