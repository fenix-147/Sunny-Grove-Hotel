<?php
/**
 * Gửi email qua SMTP (tùy chọn) – thay plugin SMTP bên ngoài.
 *
 * Thông số đặt trong wp-config.php (mật khẩu không lưu trong database, không đưa lên GitHub):
 *   define( 'SG_SMTP_HOST', 'smtp.tenmien.vn' );
 *   define( 'SG_SMTP_PORT', 587 );            // 587 (tls) hoặc 465 (ssl)
 *   define( 'SG_SMTP_SECURE', 'tls' );        // 'tls', 'ssl' hoặc ''
 *   define( 'SG_SMTP_USER', 'booking@tenmien.vn' );
 *   define( 'SG_SMTP_PASS', 'mat-khau' );
 * Không khai báo SG_SMTP_HOST thì WordPress gửi email như bình thường (hàm mail của hosting).
 *
 * @package SunnyGroveBooking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Có cấu hình SMTP không.
 *
 * @return bool
 */
function sgb_smtp_enabled() {
	return defined( 'SG_SMTP_HOST' ) && '' !== (string) SG_SMTP_HOST;
}

/**
 * Cấu hình PHPMailer của WordPress dùng SMTP.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $mailer Đối tượng gửi thư.
 */
function sgb_smtp_configure( $mailer ) {
	if ( ! sgb_smtp_enabled() ) {
		return;
	}
	$mailer->isSMTP();
	$mailer->Host       = (string) SG_SMTP_HOST; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$mailer->Port       = defined( 'SG_SMTP_PORT' ) ? (int) SG_SMTP_PORT : 587; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$secure             = defined( 'SG_SMTP_SECURE' ) ? strtolower( (string) SG_SMTP_SECURE ) : 'tls';
	$mailer->SMTPSecure = in_array( $secure, array( 'tls', 'ssl' ), true ) ? $secure : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	if ( defined( 'SG_SMTP_USER' ) && '' !== (string) SG_SMTP_USER ) {
		$mailer->SMTPAuth = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->Username = (string) SG_SMTP_USER; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$mailer->Password = defined( 'SG_SMTP_PASS' ) ? (string) SG_SMTP_PASS : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
}
add_action( 'phpmailer_init', 'sgb_smtp_configure' );
