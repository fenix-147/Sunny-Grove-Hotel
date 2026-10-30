<?php
/**
 * Email song ngữ: xác nhận "đã nhận yêu cầu" cho khách (theo ngôn ngữ khách xem) và thông báo cho khách sạn.
 *
 * @package SunnyGroveBooking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh sách email nhận thông báo.
 *
 * @return string[]
 */
function sgb_notify_emails() {
	$list = preg_split( '/[\s,;]+/', sgb_general( 'notify_emails' ) );
	$list = array_filter( array_map( 'sanitize_email', (array) $list ), 'is_email' );
	if ( ! $list ) {
		$list = array( get_option( 'admin_email' ) );
	}
	return array_values( array_unique( $list ) );
}

/**
 * Biến dùng trong email cho một yêu cầu, định dạng theo ngôn ngữ.
 *
 * @param int    $post_id ID yêu cầu.
 * @param string $lang    Ngôn ngữ hiển thị.
 * @return array
 */
function sgb_email_vars( $post_id, $lang ) {
	$langs   = sgb_languages();
	$contact = sgb_contact_info( $lang );
	$empty   = sgb_text( 'empty_value', $lang );
	$format  = sgb_text( 'date_format', $lang );
	$date    = static function ( $ymd ) use ( $format ) {
		$time = strtotime( $ymd . ' 12:00:00' );
		return $time ? date_i18n( $format, $time ) : $ymd;
	};
	$method  = sgb_get( $post_id, 'contact_method' );
	$nights  = sgb_nights( sgb_get( $post_id, 'checkin' ), sgb_get( $post_id, 'checkout' ) );
	$guest   = sgb_get( $post_id, 'lang' );
	$value   = static function ( $v ) use ( $empty ) {
		return '' === trim( (string) $v ) ? $empty : $v;
	};

	return array(
		'ma_yeu_cau'    => sgb_request_code( $post_id ),
		'ten_khach'     => sgb_get( $post_id, 'name' ),
		'so_dien_thoai' => sgb_get( $post_id, 'phone' ),
		'email'         => $value( sgb_get( $post_id, 'email' ) ),
		'lien_he_qua'   => sgb_text( 'method_' . ( $method ? $method : 'phone' ), $lang ),
		'so_lien_he'    => sgb_get( $post_id, 'contact_id' ),
		'ngay_nhan'     => $date( sgb_get( $post_id, 'checkin' ) ),
		'ngay_tra'      => $date( sgb_get( $post_id, 'checkout' ) ),
		'so_dem'        => sgb_text( 'nights', $lang, array( 'n' => $nights ) ),
		'phong'         => sgb_room_label( $post_id, $lang ),
		'nguoi_lon'     => sgb_get( $post_id, 'adults' ),
		'tre_em'        => sgb_get( $post_id, 'children' ),
		'ghi_chu'       => $value( sgb_get( $post_id, 'note' ) ),
		'ngon_ngu'      => isset( $langs[ $guest ] ) ? $langs[ $guest ]['name'] : $guest,
		'ten_khach_san' => $contact['hotel_name'],
		'hotline'       => $contact['hotline'],
		'zalo'          => $contact['zalo'],
		'whatsapp'      => $contact['whatsapp'],
		'link_quan_tri' => admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' ),
	);
}

/**
 * Bọc nội dung email bằng khung HTML đơn giản, dùng màu chính của theme (nếu có).
 *
 * @param string $text  Nội dung dạng chữ.
 * @param string $title Tiêu đề hiển thị trên khung.
 * @return string
 */
function sgb_email_html( $text, $title ) {
	$primary = '#1FA3A3';
	if ( function_exists( 'wp_get_global_settings' ) ) {
		foreach ( (array) wp_get_global_settings( array( 'color', 'palette', 'theme' ) ) as $color ) {
			if ( isset( $color['slug'], $color['color'] ) && 'primary' === $color['slug'] ) {
				$primary = $color['color'];
			}
		}
	}
	$primary = sanitize_hex_color( $primary ) ? $primary : '#1FA3A3';
	$body    = make_clickable( nl2br( esc_html( $text ) ) );

	return '<!doctype html><html><body style="margin:0;padding:24px;background:#f7f1e6;font-family:Arial,Helvetica,sans-serif;color:#1e2a2b;">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;">'
		. '<tr><td style="background:' . esc_attr( $primary ) . ';color:#ffffff;padding:20px 28px;font-size:18px;font-weight:bold;">' . esc_html( $title ) . '</td></tr>'
		. '<tr><td style="padding:28px;font-size:15px;line-height:1.65;">' . $body . '</td></tr>'
		. '</table></body></html>';
}

/**
 * Gửi một email HTML và ghi nhận kết quả.
 *
 * @param string|array $to       Người nhận.
 * @param string       $subject  Tiêu đề.
 * @param string       $text     Nội dung.
 * @param string       $reply_to Email trả lời.
 * @return bool
 */
function sgb_mail( $to, $subject, $text, $reply_to = '' ) {
	$from_name  = sgb_general( 'from_name' );
	$from_email = sgb_general( 'from_email' );
	$headers    = array( 'Content-Type: text/html; charset=UTF-8' );
	if ( $from_email && is_email( $from_email ) ) {
		$headers[] = sprintf( 'From: %s <%s>', wp_specialchars_decode( $from_name, ENT_QUOTES ), $from_email );
	}
	if ( $reply_to && is_email( $reply_to ) ) {
		$headers[] = 'Reply-To: ' . $reply_to;
	}
	$title = $from_name ? $from_name : get_bloginfo( 'name' );
	return (bool) wp_mail( $to, wp_specialchars_decode( $subject, ENT_QUOTES ), sgb_email_html( $text, $title ), $headers );
}

/**
 * Gửi cả hai email cho một yêu cầu.
 *
 * @param int $post_id ID yêu cầu.
 */
function sgb_send_emails( $post_id ) {
	$langs      = sgb_languages();
	$guest_lang = sgb_get( $post_id, 'lang' );
	$guest_lang = isset( $langs[ $guest_lang ] ) ? $guest_lang : sgb_default_lang();
	$admin_lang = sgb_default_lang();
	$notify     = sgb_notify_emails();

	$errors = array();
	$catch  = static function ( $error ) use ( &$errors ) {
		$errors[] = $error instanceof WP_Error ? $error->get_error_message() : '';
	};
	add_action( 'wp_mail_failed', $catch );

	// Email cho khách – đúng ngôn ngữ khách đã xem.
	$guest_email = sgb_get( $post_id, 'email' );
	if ( $guest_email && is_email( $guest_email ) ) {
		$switched = isset( $langs[ $guest_lang ] ) ? switch_to_locale( $langs[ $guest_lang ]['locale'] ) : false;
		$vars     = sgb_email_vars( $post_id, $guest_lang );
		$sent     = sgb_mail( $guest_email, sgb_text( 'guest_subject', $guest_lang, $vars ), sgb_text( 'guest_body', $guest_lang, $vars ), $notify[0] );
		if ( $switched ) {
			restore_previous_locale();
		}
		update_post_meta( $post_id, '_sgb_mail_guest', $sent ? 'sent' : 'failed' );
	} else {
		update_post_meta( $post_id, '_sgb_mail_guest', 'skipped' );
	}

	// Email cho khách sạn – ngôn ngữ mặc định của site.
	$switched = isset( $langs[ $admin_lang ] ) ? switch_to_locale( $langs[ $admin_lang ]['locale'] ) : false;
	$vars     = sgb_email_vars( $post_id, $admin_lang );
	$sent     = sgb_mail( $notify, sgb_text( 'admin_subject', $admin_lang, $vars ), sgb_text( 'admin_body', $admin_lang, $vars ), $guest_email );
	if ( $switched ) {
		restore_previous_locale();
	}
	update_post_meta( $post_id, '_sgb_mail_admin', $sent ? 'sent' : 'failed' );

	remove_action( 'wp_mail_failed', $catch );
	if ( $errors ) {
		update_post_meta( $post_id, '_sgb_mail_error', sanitize_text_field( implode( ' | ', array_filter( $errors ) ) ) );
	}
}
