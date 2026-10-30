<?php
/**
 * Xác thực 2 lớp (TOTP – dùng được với Google Authenticator, Microsoft Authenticator…), tự viết theo RFC 6238.
 * Không gọi dịch vụ bên ngoài: mã QR được vẽ ngay trong trình duyệt (assets/qr.js).
 *
 * - Bật ở trang Hồ sơ: quét mã QR, nhập mã 6 số để xác nhận; nhận 10 mã khôi phục dự phòng (dùng 1 lần).
 * - Đăng nhập: sau mật khẩu đúng sẽ hỏi mã 6 số (hoặc mã khôi phục).
 * - Mất điện thoại: dùng mã khôi phục, hoặc quản trị viên khác tắt giúp, hoặc thêm
 *   define( 'SG_DISABLE_2FA', true ); vào wp-config.php để tạm tắt toàn bộ.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Xác thực 2 lớp đang bị tắt khẩn cấp?
 *
 * @return bool
 */
function sg_2fa_disabled() {
	return defined( 'SG_DISABLE_2FA' ) && SG_DISABLE_2FA;
}

/**
 * Người dùng đã bật 2FA?
 *
 * @param int $user_id ID.
 * @return bool
 */
function sg_2fa_enabled( $user_id ) {
	return '1' === get_user_meta( $user_id, '_sg_2fa_enabled', true ) && '' !== (string) get_user_meta( $user_id, '_sg_2fa_secret', true );
}

/* -------------------------------------------------------------------------
 * TOTP (RFC 6238) & Base32 (RFC 4648)
 * ---------------------------------------------------------------------- */

/**
 * Mã hóa Base32.
 *
 * @param string $bin Dữ liệu nhị phân.
 * @return string
 */
function sg_2fa_base32_encode( $bin ) {
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$bits     = '';
	foreach ( str_split( $bin ) as $char ) {
		$bits .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
	}
	$out = '';
	foreach ( str_split( $bits, 5 ) as $chunk ) {
		$out .= $alphabet[ bindec( str_pad( $chunk, 5, '0' ) ) ];
	}
	return $out;
}

/**
 * Giải mã Base32.
 *
 * @param string $text Chuỗi Base32.
 * @return string
 */
function sg_2fa_base32_decode( $text ) {
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$text     = strtoupper( preg_replace( '/[^A-Za-z2-7]/', '', (string) $text ) );
	$bits     = '';
	foreach ( str_split( $text ) as $char ) {
		$bits .= str_pad( decbin( strpos( $alphabet, $char ) ), 5, '0', STR_PAD_LEFT );
	}
	$out = '';
	foreach ( str_split( $bits, 8 ) as $byte ) {
		if ( 8 === strlen( $byte ) ) {
			$out .= chr( bindec( $byte ) );
		}
	}
	return $out;
}

/**
 * Mã TOTP 6 số tại một bước thời gian.
 *
 * @param string $secret Khóa Base32.
 * @param int    $step   Bước thời gian (30 giây).
 * @return string
 */
function sg_2fa_code( $secret, $step ) {
	$key  = sg_2fa_base32_decode( $secret );
	$hash = hash_hmac( 'sha1', pack( 'N2', ( $step >> 32 ) & 0xffffffff, $step & 0xffffffff ), $key, true );
	$off  = ord( $hash[19] ) & 0x0f;
	$num  = ( ( ord( $hash[ $off ] ) & 0x7f ) << 24 ) | ( ord( $hash[ $off + 1 ] ) << 16 ) | ( ord( $hash[ $off + 2 ] ) << 8 ) | ord( $hash[ $off + 3 ] );
	return str_pad( (string) ( $num % 1000000 ), 6, '0', STR_PAD_LEFT );
}

/**
 * Kiểm tra mã (cho phép lệch ±30 giây; không dùng lại mã cũ).
 *
 * @param string $secret  Khóa.
 * @param string $code    Mã người dùng nhập.
 * @param int    $user_id ID (để chống dùng lại); 0 = không ghi nhớ.
 * @return bool
 */
function sg_2fa_verify( $secret, $code, $user_id = 0 ) {
	$code = preg_replace( '/\D/', '', (string) $code );
	if ( 6 !== strlen( $code ) || ! $secret ) {
		return false;
	}
	$now  = (int) floor( time() / 30 );
	$last = $user_id ? (int) get_user_meta( $user_id, '_sg_2fa_last', true ) : 0;
	for ( $i = -1; $i <= 1; $i++ ) {
		$step = $now + $i;
		if ( $step > $last && hash_equals( sg_2fa_code( $secret, $step ), $code ) ) {
			if ( $user_id ) {
				update_user_meta( $user_id, '_sg_2fa_last', $step );
			}
			return true;
		}
	}
	return false;
}

/**
 * Tạo 10 mã khôi phục mới (trả về mã gốc để hiện 1 lần; chỉ lưu bản băm).
 *
 * @param int $user_id ID.
 * @return string[]
 */
function sg_2fa_new_recovery_codes( $user_id ) {
	$codes  = array();
	$hashes = array();
	for ( $i = 0; $i < 10; $i++ ) {
		$code     = strtolower( substr( sg_2fa_base32_encode( random_bytes( 8 ) ), 0, 10 ) );
		$code     = substr( $code, 0, 5 ) . '-' . substr( $code, 5 );
		$codes[]  = $code;
		$hashes[] = wp_hash_password( $code );
	}
	update_user_meta( $user_id, '_sg_2fa_recovery', $hashes );
	return $codes;
}

/**
 * Dùng một mã khôi phục (xóa sau khi dùng).
 *
 * @param int    $user_id ID.
 * @param string $code    Mã.
 * @return bool
 */
function sg_2fa_use_recovery( $user_id, $code ) {
	$code   = strtolower( trim( (string) $code ) );
	$hashes = (array) get_user_meta( $user_id, '_sg_2fa_recovery', true );
	foreach ( $hashes as $i => $hash ) {
		if ( $hash && wp_check_password( $code, $hash ) ) {
			unset( $hashes[ $i ] );
			update_user_meta( $user_id, '_sg_2fa_recovery', array_values( $hashes ) );
			return true;
		}
	}
	return false;
}

/**
 * Đường dẫn otpauth:// cho ứng dụng xác thực.
 *
 * @param WP_User $user   Người dùng.
 * @param string  $secret Khóa.
 * @return string
 */
function sg_2fa_uri( $user, $secret ) {
	$issuer = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	return 'otpauth://totp/' . rawurlencode( $issuer . ':' . $user->user_login ) . '?secret=' . $secret . '&issuer=' . rawurlencode( $issuer ) . '&digits=6&period=30';
}

/* -------------------------------------------------------------------------
 * Trang Hồ sơ: bật / tắt
 * ---------------------------------------------------------------------- */

/**
 * Phần "Xác thực 2 lớp" trong trang Hồ sơ.
 *
 * @param WP_User $user Người dùng đang xem.
 */
function sg_2fa_profile( $user ) {
	$self = get_current_user_id() === $user->ID;
	if ( ! $self && ! current_user_can( 'manage_options' ) ) {
		return;
	}
	wp_nonce_field( 'sg_2fa_profile', 'sg_2fa_nonce' );
	echo '<h2 id="sg-2fa">' . esc_html__( 'Xác thực 2 lớp', 'sunny-grove-core' ) . '</h2><table class="form-table" role="presentation"><tr><th>' . esc_html__( 'Trạng thái', 'sunny-grove-core' ) . '</th><td>';

	$fresh = get_transient( 'sg_2fa_codes_' . $user->ID );
	if ( $fresh && $self ) {
		delete_transient( 'sg_2fa_codes_' . $user->ID );
		echo '<div class="notice notice-success inline"><p><strong>' . esc_html__( 'Mã khôi phục – hãy in hoặc lưu lại ngay, chỉ hiện một lần:', 'sunny-grove-core' ) . '</strong></p><p><code style="display:inline-block;line-height:2">' . implode( '</code> <code style="display:inline-block;line-height:2">', array_map( 'esc_html', (array) $fresh ) ) . '</code></p><p>' . esc_html__( 'Mỗi mã dùng được một lần khi không có điện thoại.', 'sunny-grove-core' ) . '</p></div>';
	}

	if ( sg_2fa_enabled( $user->ID ) ) {
		$left = count( array_filter( (array) get_user_meta( $user->ID, '_sg_2fa_recovery', true ) ) );
		echo '<p><strong style="color:#00753a">✓ ' . esc_html__( 'Đang bật', 'sunny-grove-core' ) . '</strong> · ' . esc_html( sprintf( /* translators: %d: số mã */ __( 'Còn %d mã khôi phục', 'sunny-grove-core' ), $left ) ) . '</p>';
		if ( $self ) {
			echo '<p><label><input type="checkbox" name="sg_2fa_new_codes" value="1"> ' . esc_html__( 'Tạo bộ mã khôi phục mới (bộ cũ hết hiệu lực)', 'sunny-grove-core' ) . '</label></p>';
		}
		echo '<p><label><input type="checkbox" name="sg_2fa_disable" value="1"> ' . esc_html__( 'Tắt xác thực 2 lớp', 'sunny-grove-core' ) . '</label></p>';
		if ( $self ) {
			echo '<p><label>' . esc_html__( 'Nhập mã 6 số hiện tại để xác nhận thay đổi:', 'sunny-grove-core' ) . ' <input type="text" name="sg_2fa_confirm" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="small-text" style="width:7em"></label></p>';
		}
	} elseif ( $self ) {
		$secret = (string) get_user_meta( $user->ID, '_sg_2fa_pending', true );
		if ( ! $secret ) {
			$secret = sg_2fa_base32_encode( random_bytes( 20 ) );
			update_user_meta( $user->ID, '_sg_2fa_pending', $secret );
		}
		wp_enqueue_script( 'sg-qr', SGC_URL . 'assets/qr.js', array(), SGC_VERSION, true );
		wp_add_inline_script( 'sg-qr', 'document.addEventListener("DOMContentLoaded",function(){var b=document.getElementById("sg-2fa-qr");if(b&&window.sgQr){b.innerHTML=window.sgQr.svg(b.getAttribute("data-uri"),4);}});' );
		echo '<p>' . esc_html__( 'Chưa bật. Cách bật:', 'sunny-grove-core' ) . '</p><ol>';
		echo '<li>' . esc_html__( 'Cài ứng dụng Google Authenticator (hoặc Microsoft Authenticator) trên điện thoại.', 'sunny-grove-core' ) . '</li>';
		echo '<li>' . esc_html__( 'Trong ứng dụng bấm dấu + → Quét mã QR, rồi quét mã dưới đây:', 'sunny-grove-core' ) . '</li></ol>';
		printf( '<div id="sg-2fa-qr" data-uri="%s" style="margin:8px 0"></div>', esc_attr( sg_2fa_uri( $user, $secret ) ) );
		echo '<p class="description">' . esc_html__( 'Không quét được? Chọn "Nhập khóa thiết lập" và gõ:', 'sunny-grove-core' ) . ' <code>' . esc_html( trim( chunk_split( $secret, 4, ' ' ) ) ) . '</code></p>';
		echo '<p><label>' . esc_html__( '3. Nhập mã 6 số đang hiện trong ứng dụng:', 'sunny-grove-core' ) . ' <input type="text" name="sg_2fa_enable_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="small-text" style="width:7em"></label> ' . esc_html__( 'rồi bấm "Cập nhật hồ sơ".', 'sunny-grove-core' ) . '</p>';
	} else {
		echo '<p>' . esc_html__( 'Chưa bật.', 'sunny-grove-core' ) . '</p>';
	}
	echo '</td></tr></table>';
}
add_action( 'show_user_profile', 'sg_2fa_profile' );
add_action( 'edit_user_profile', 'sg_2fa_profile' );

/**
 * Lưu thay đổi xác thực 2 lớp ở trang Hồ sơ.
 *
 * @param int $user_id ID.
 */
function sg_2fa_profile_save( $user_id ) {
	if ( ! isset( $_POST['sg_2fa_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sg_2fa_nonce'] ), 'sg_2fa_profile' ) ) {
		return;
	}
	$self = get_current_user_id() === (int) $user_id;
	if ( ! $self && ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$code = isset( $_POST['sg_2fa_enable_code'] ) ? sanitize_text_field( wp_unslash( $_POST['sg_2fa_enable_code'] ) ) : '';
	if ( $self && '' !== $code && ! sg_2fa_enabled( $user_id ) ) {
		$secret = (string) get_user_meta( $user_id, '_sg_2fa_pending', true );
		if ( sg_2fa_verify( $secret, $code, $user_id ) ) {
			update_user_meta( $user_id, '_sg_2fa_secret', $secret );
			update_user_meta( $user_id, '_sg_2fa_enabled', '1' );
			delete_user_meta( $user_id, '_sg_2fa_pending' );
			set_transient( 'sg_2fa_codes_' . $user_id, sg_2fa_new_recovery_codes( $user_id ), 10 * MINUTE_IN_SECONDS );
		} else {
			set_transient( 'sg_2fa_error_' . $user_id, __( 'Mã không đúng – xác thực 2 lớp chưa được bật. Hãy thử lại với mã mới nhất trong ứng dụng.', 'sunny-grove-core' ), MINUTE_IN_SECONDS );
		}
		return;
	}
	if ( ! sg_2fa_enabled( $user_id ) ) {
		return;
	}
	$disable   = ! empty( $_POST['sg_2fa_disable'] );
	$new_codes = ! empty( $_POST['sg_2fa_new_codes'] );
	if ( ! $disable && ! $new_codes ) {
		return;
	}
	// Tự thay đổi của mình phải nhập mã hiện tại; quản trị viên tắt giúp người khác thì không cần.
	if ( $self ) {
		$confirm = isset( $_POST['sg_2fa_confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['sg_2fa_confirm'] ) ) : '';
		if ( ! sg_2fa_verify( (string) get_user_meta( $user_id, '_sg_2fa_secret', true ), $confirm, $user_id ) ) {
			set_transient( 'sg_2fa_error_' . $user_id, __( 'Mã xác nhận không đúng – chưa thay đổi gì.', 'sunny-grove-core' ), MINUTE_IN_SECONDS );
			return;
		}
	}
	if ( $disable ) {
		foreach ( array( '_sg_2fa_enabled', '_sg_2fa_secret', '_sg_2fa_recovery', '_sg_2fa_last', '_sg_2fa_pending' ) as $key ) {
			delete_user_meta( $user_id, $key );
		}
		return;
	}
	set_transient( 'sg_2fa_codes_' . $user_id, sg_2fa_new_recovery_codes( $user_id ), 10 * MINUTE_IN_SECONDS );
}
add_action( 'personal_options_update', 'sg_2fa_profile_save' );
add_action( 'edit_user_profile_update', 'sg_2fa_profile_save' );

/**
 * Thông báo lỗi ở trang Hồ sơ.
 */
function sg_2fa_profile_notice() {
	$user_id = get_current_user_id();
	$error   = get_transient( 'sg_2fa_error_' . $user_id );
	if ( $error ) {
		delete_transient( 'sg_2fa_error_' . $user_id );
		echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
	}
	if ( sg_2fa_required_missing( $user_id ) ) {
		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Tài khoản quản trị viên cần bật xác thực 2 lớp trước khi dùng các trang quản trị khác.', 'sunny-grove-core' ) . '</strong> <a href="#sg-2fa">' . esc_html__( 'Bật ngay', 'sunny-grove-core' ) . '</a></p></div>';
	}
}
add_action( 'admin_notices', 'sg_2fa_profile_notice' );

/**
 * Quản trị viên bắt buộc 2FA nhưng chưa bật?
 *
 * @param int $user_id ID.
 * @return bool
 */
function sg_2fa_required_missing( $user_id ) {
	return ! sg_2fa_disabled() && '1' === sg_security_option( 'require_2fa_admin' ) && user_can( $user_id, 'manage_options' ) && ! sg_2fa_enabled( $user_id );
}

/**
 * Bắt buộc 2FA: chuyển quản trị viên chưa bật về trang Hồ sơ.
 */
function sg_2fa_enforce() {
	if ( wp_doing_ajax() || ! sg_2fa_required_missing( get_current_user_id() ) ) {
		return;
	}
	global $pagenow;
	if ( 'profile.php' !== $pagenow ) {
		wp_safe_redirect( admin_url( 'profile.php#sg-2fa' ) );
		exit;
	}
}
add_action( 'admin_init', 'sg_2fa_enforce' );

/* -------------------------------------------------------------------------
 * Đăng nhập: hỏi mã sau khi nhập đúng mật khẩu
 * ---------------------------------------------------------------------- */

/**
 * Mật khẩu đúng + đã bật 2FA → hủy phiên vừa tạo, chuyển sang bước nhập mã.
 *
 * @param string  $login Tên đăng nhập.
 * @param WP_User $user  Người dùng.
 */
function sg_2fa_after_password( $login, $user ) {
	if ( sg_2fa_disabled() || ! $user instanceof WP_User || ! sg_2fa_enabled( $user->ID ) ) {
		return;
	}
	if ( defined( 'XMLRPC_REQUEST' ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	wp_clear_auth_cookie();
	$token = wp_generate_password( 32, false );
	set_transient(
		'sg_2fa_login_' . hash( 'sha256', $token ),
		array(
			'user'     => $user->ID,
			'remember' => ! empty( $_POST['rememberme'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'tries'    => 0,
		),
		10 * MINUTE_IN_SECONDS
	);
	$redirect = isset( $_REQUEST['redirect_to'] ) ? wp_unslash( $_REQUEST['redirect_to'] ) : admin_url(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	wp_safe_redirect(
		add_query_arg(
			array(
				'action'      => 'sg_2fa',
				'sg_token'    => $token,
				'redirect_to' => rawurlencode( wp_validate_redirect( $redirect, admin_url() ) ),
			),
			wp_login_url()
		)
	);
	exit;
}
add_action( 'wp_login', 'sg_2fa_after_password', 5, 2 );

/**
 * Màn hình nhập mã 6 số.
 */
function sg_2fa_login_screen() {
	$token = isset( $_REQUEST['sg_token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sg_token'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$key   = 'sg_2fa_login_' . hash( 'sha256', $token );
	$state = $token ? get_transient( $key ) : false;
	if ( ! $state ) {
		wp_safe_redirect( wp_login_url() );
		exit;
	}
	$redirect = isset( $_REQUEST['redirect_to'] ) ? wp_validate_redirect( wp_unslash( $_REQUEST['redirect_to'] ), admin_url() ) : admin_url(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$error    = '';
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		check_admin_referer( 'sg_2fa_login' );
		$code = isset( $_POST['sg_code'] ) ? sanitize_text_field( wp_unslash( $_POST['sg_code'] ) ) : '';
		$user = (int) $state['user'];
		$ok   = sg_2fa_verify( (string) get_user_meta( $user, '_sg_2fa_secret', true ), $code, $user ) || sg_2fa_use_recovery( $user, $code );
		if ( $ok ) {
			delete_transient( $key );
			wp_set_auth_cookie( $user, ! empty( $state['remember'] ) );
			wp_safe_redirect( $redirect );
			exit;
		}
		++$state['tries'];
		if ( $state['tries'] >= 5 ) {
			delete_transient( $key );
			do_action( 'wp_login_failed', get_userdata( $user )->user_login, new WP_Error( 'sg_2fa', 'too many' ) );
			wp_safe_redirect( wp_login_url() );
			exit;
		}
		set_transient( $key, $state, 10 * MINUTE_IN_SECONDS );
		$error = new WP_Error( 'sg_2fa', __( 'Mã không đúng. Hãy nhập mã mới nhất trong ứng dụng, hoặc một mã khôi phục.', 'sunny-grove-core' ) );
	}
	login_header( __( 'Xác thực 2 lớp', 'sunny-grove-core' ), '', $error ? $error : null );
	?>
	<form name="sg2faform" id="loginform" method="post" action="<?php echo esc_url( add_query_arg( array( 'action' => 'sg_2fa', 'sg_token' => $token, 'redirect_to' => rawurlencode( $redirect ) ), wp_login_url() ) ); ?>">
		<?php wp_nonce_field( 'sg_2fa_login' ); ?>
		<p><?php esc_html_e( 'Mở ứng dụng xác thực trên điện thoại và nhập mã 6 số. Mất điện thoại? Nhập một mã khôi phục.', 'sunny-grove-core' ); ?></p>
		<p>
			<label for="sg_code"><?php esc_html_e( 'Mã xác thực', 'sunny-grove-core' ); ?></label>
			<input type="text" name="sg_code" id="sg_code" class="input" autocomplete="one-time-code" inputmode="numeric" autofocus required>
		</p>
		<p class="submit"><input type="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Xác nhận', 'sunny-grove-core' ); ?>"></p>
	</form>
	<?php
	login_footer( 'sg_code' );
	exit;
}
add_action( 'login_form_sg_2fa', 'sg_2fa_login_screen' );
