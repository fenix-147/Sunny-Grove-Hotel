<?php
/**
 * Bảo mật cơ bản (không cần plugin ngoài): giới hạn đăng nhập sai, tắt XML-RPC, ẩn phiên bản WordPress,
 * header bảo mật, chặn dò tên tài khoản qua REST. Cài đặt chỉ quản trị viên sửa được.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Giá trị mặc định của cài đặt bảo mật.
 *
 * @return array
 */
function sg_security_defaults() {
	return array(
		'login_limit'       => '1',
		'login_attempts'    => '5',
		'lockout_minutes'   => '15',
		'xmlrpc_off'        => '1',
		'hide_version'      => '1',
		'headers'           => '1',
		'block_user_enum'   => '1',
		'require_2fa_admin' => '0',
	);
}

/**
 * Đọc một cài đặt bảo mật.
 *
 * @param string $key Khóa.
 * @return string
 */
function sg_security_option( $key ) {
	$opts = array_merge( sg_security_defaults(), (array) get_option( 'sg_security', array() ) );
	return (string) ( $opts[ $key ] ?? '' );
}

/* -------------------------------------------------------------------------
 * Giới hạn số lần đăng nhập sai
 * ---------------------------------------------------------------------- */

/**
 * Mã định danh IP (đã băm, không lưu IP gốc).
 *
 * @return string
 */
function sg_security_ip_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return substr( wp_hash( 'sg-login|' . $ip ), 0, 20 );
}

/**
 * Chặn đăng nhập khi IP đang bị khóa tạm.
 *
 * @param WP_User|WP_Error|null $user Kết quả xác thực.
 * @return WP_User|WP_Error|null
 */
function sg_security_check_lock( $user ) {
	if ( '1' !== sg_security_option( 'login_limit' ) ) {
		return $user;
	}
	$until = (int) get_transient( 'sg_login_lock_' . sg_security_ip_key() );
	if ( $until > time() ) {
		$minutes = max( 1, (int) ceil( ( $until - time() ) / MINUTE_IN_SECONDS ) );
		return new WP_Error(
			'sg_locked',
			sprintf(
				/* translators: %d: số phút */
				_n( 'Đăng nhập sai quá nhiều lần. Vui lòng thử lại sau %d phút.', 'Đăng nhập sai quá nhiều lần. Vui lòng thử lại sau %d phút.', $minutes, 'sunny-grove-core' ),
				$minutes
			)
		);
	}
	return $user;
}
add_filter( 'authenticate', 'sg_security_check_lock', 99 );

/**
 * Đếm lần đăng nhập sai; quá giới hạn thì khóa tạm.
 */
function sg_security_login_failed() {
	if ( '1' !== sg_security_option( 'login_limit' ) ) {
		return;
	}
	$key      = sg_security_ip_key();
	$minutes  = max( 1, (int) sg_security_option( 'lockout_minutes' ) );
	$attempts = (int) get_transient( 'sg_login_fail_' . $key ) + 1;
	set_transient( 'sg_login_fail_' . $key, $attempts, $minutes * MINUTE_IN_SECONDS );
	if ( $attempts >= max( 1, (int) sg_security_option( 'login_attempts' ) ) ) {
		set_transient( 'sg_login_lock_' . $key, time() + $minutes * MINUTE_IN_SECONDS, $minutes * MINUTE_IN_SECONDS );
		delete_transient( 'sg_login_fail_' . $key );
	}
}
add_action( 'wp_login_failed', 'sg_security_login_failed' );

/**
 * Đăng nhập đúng: xóa bộ đếm.
 *
 * Bỏ qua khi đang chạy wp-cron: lúc đó không phải người dùng đăng nhập (vd. môi trường thử
 * tự đăng nhập trong lần gọi cron), nếu xóa sẽ làm mất các lần sai đã đếm.
 */
function sg_security_login_ok() {
	if ( wp_doing_cron() ) {
		return;
	}
	delete_transient( 'sg_login_fail_' . sg_security_ip_key() );
}
add_action( 'wp_login', 'sg_security_login_ok' );

/* -------------------------------------------------------------------------
 * XML-RPC, phiên bản, header, dò tài khoản
 * ---------------------------------------------------------------------- */

/**
 * Áp dụng các tùy chọn bảo mật.
 */
function sg_security_setup() {
	if ( '1' === sg_security_option( 'xmlrpc_off' ) ) {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', '__return_empty_array' );
		remove_action( 'wp_head', 'rsd_link' );
		add_filter(
			'wp_headers',
			static function ( $headers ) {
				unset( $headers['X-Pingback'] );
				return $headers;
			}
		);
	}
	if ( '1' === sg_security_option( 'hide_version' ) ) {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'style_loader_src', 'sg_security_strip_version', 20 );
		add_filter( 'script_loader_src', 'sg_security_strip_version', 20 );
	}
	if ( '1' === sg_security_option( 'block_user_enum' ) ) {
		add_filter( 'rest_endpoints', 'sg_security_rest_users' );
		add_action( 'template_redirect', 'sg_security_author_scan', 1 );
	}
}
add_action( 'init', 'sg_security_setup', 1 );

/**
 * Bỏ "?ver=<phiên bản WordPress>" khỏi file CSS/JS (không ảnh hưởng phiên bản riêng của theme/plugin).
 *
 * @param string $src Đường dẫn.
 * @return string
 */
function sg_security_strip_version( $src ) {
	$version = get_bloginfo( 'version' );
	if ( $src && false !== strpos( $src, 'ver=' . $version ) ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}

/**
 * Header bảo mật cơ bản cho website và trang đăng nhập.
 */
function sg_security_headers() {
	if ( '1' !== sg_security_option( 'headers' ) || headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'X-Frame-Options: SAMEORIGIN' );
}
add_action( 'send_headers', 'sg_security_headers' );
add_action( 'login_init', 'sg_security_headers' );
add_action( 'admin_init', 'sg_security_headers' );

/**
 * Khách chưa đăng nhập không xem được danh sách tài khoản qua REST.
 *
 * @param array $endpoints Endpoint.
 * @return array
 */
function sg_security_rest_users( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
}

/**
 * Chặn dò tên tài khoản bằng ?author=1.
 */
function sg_security_author_scan() {
	if ( ! is_user_logged_in() && isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}

/* -------------------------------------------------------------------------
 * Trang cài đặt Bảo mật
 * ---------------------------------------------------------------------- */

/**
 * Menu.
 */
function sg_security_admin_menu() {
	add_submenu_page( 'sgc-hotel', __( 'Bảo mật', 'sunny-grove-core' ), __( 'Bảo mật', 'sunny-grove-core' ), 'manage_options', 'sg-security', 'sg_security_render' );
}
add_action( 'admin_menu', 'sg_security_admin_menu', 21 );

/**
 * Nội dung trang cài đặt.
 */
function sg_security_render() {
	$checks = array(
		'login_limit'       => __( 'Giới hạn số lần đăng nhập sai', 'sunny-grove-core' ),
		'xmlrpc_off'        => __( 'Tắt XML-RPC (cổng cũ hay bị dò mật khẩu)', 'sunny-grove-core' ),
		'hide_version'      => __( 'Ẩn phiên bản WordPress', 'sunny-grove-core' ),
		'headers'           => __( 'Gửi header bảo mật (X-Content-Type-Options, Referrer-Policy, X-Frame-Options)', 'sunny-grove-core' ),
		'block_user_enum'   => __( 'Chặn dò tên tài khoản', 'sunny-grove-core' ),
		'require_2fa_admin' => __( 'Bắt buộc quản trị viên bật xác thực 2 lớp', 'sunny-grove-core' ),
	);
	echo '<div class="wrap sgc-settings"><h1>' . esc_html__( 'Bảo mật', 'sunny-grove-core' ) . '</h1>';
	sgc_settings_notice();
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'sg_security_settings' );
	echo '<input type="hidden" name="action" value="sg_security_settings"><table class="form-table" role="presentation">';
	foreach ( $checks as $key => $label ) {
		printf(
			'<tr><th scope="row">%1$s</th><td><input type="hidden" name="sg_security[%2$s]" value="0"><label><input type="checkbox" name="sg_security[%2$s]" value="1" %3$s> %4$s</label></td></tr>',
			esc_html( $label ),
			esc_attr( $key ),
			checked( sg_security_option( $key ), '1', false ),
			esc_html__( 'Bật', 'sunny-grove-core' )
		);
		if ( 'login_limit' === $key ) {
			printf(
				'<tr><th scope="row">%1$s</th><td><input type="number" min="1" max="20" name="sg_security[login_attempts]" value="%2$s" class="small-text"> %3$s <input type="number" min="1" max="1440" name="sg_security[lockout_minutes]" value="%4$s" class="small-text"> %5$s</td></tr>',
				esc_html__( 'Khóa tạm sau', 'sunny-grove-core' ),
				esc_attr( sg_security_option( 'login_attempts' ) ),
				esc_html__( 'lần sai, trong', 'sunny-grove-core' ),
				esc_attr( sg_security_option( 'lockout_minutes' ) ),
				esc_html__( 'phút', 'sunny-grove-core' )
			);
		}
	}
	echo '</table>';
	echo '<h2>' . esc_html__( 'Tắt sửa file trong trang quản trị', 'sunny-grove-core' ) . '</h2><p>';
	if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
		echo '✓ ' . esc_html__( 'Đã tắt (DISALLOW_FILE_EDIT đang bật).', 'sunny-grove-core' );
	} else {
		echo esc_html__( 'Chưa tắt. Nhờ đơn vị hosting (hoặc tự mở File Manager) thêm dòng sau vào file wp-config.php, ngay trên dòng "That\'s all, stop editing!":', 'sunny-grove-core' ) . '<br><code>define( \'DISALLOW_FILE_EDIT\', true );</code>';
	}
	echo '</p>';
	submit_button( __( 'Lưu thay đổi', 'sunny-grove-core' ) );
	echo '</form></div>';
}

/**
 * Lưu cài đặt.
 */
function sg_security_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'sunny-grove-core' ) );
	}
	check_admin_referer( 'sg_security_settings' );
	$in  = isset( $_POST['sg_security'] ) && is_array( $_POST['sg_security'] ) ? wp_unslash( $_POST['sg_security'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$out = array();
	foreach ( sg_security_defaults() as $key => $default ) {
		if ( in_array( $key, array( 'login_attempts', 'lockout_minutes' ), true ) ) {
			$out[ $key ] = (string) max( 1, absint( $in[ $key ] ?? $default ) );
		} else {
			$out[ $key ] = '1' === (string) ( $in[ $key ] ?? '0' ) ? '1' : '0';
		}
	}
	update_option( 'sg_security', $out );
	wp_safe_redirect( admin_url( 'admin.php?page=sg-security&sgc_saved=1' ) );
	exit;
}
add_action( 'admin_post_sg_security_settings', 'sg_security_save' );
