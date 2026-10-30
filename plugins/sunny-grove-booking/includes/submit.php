<?php
/**
 * Nhận, kiểm tra và lưu yêu cầu đặt phòng.
 *
 * @package SunnyGroveBooking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đăng ký REST API.
 */
function sgb_register_routes() {
	register_rest_route(
		'sunny-grove-booking/v1',
		'/token',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'sgb_rest_token',
		)
	);
	register_rest_route(
		'sunny-grove-booking/v1',
		'/request',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => 'sgb_rest_submit',
		)
	);
}
add_action( 'rest_api_init', 'sgb_register_routes' );

/**
 * Báo cho plugin cache (LiteSpeed Cache và các plugin khác) không lưu cache phản hồi này.
 */
function sgb_no_cache() {
	do_action( 'litespeed_control_set_nocache', 'sunny grove booking' ); // LiteSpeed Cache.
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true ); // WP Super Cache, W3 Total Cache…
	}
	nocache_headers();
}

/**
 * Mã bảo mật mới (form có thể nằm trên trang đã được cache, nên JS lấy mã mới ngay trước khi gửi).
 *
 * @return WP_REST_Response
 */
function sgb_rest_token() {
	sgb_no_cache();
	$response = new WP_REST_Response(
		array(
			'nonce' => wp_create_nonce( 'sgb_submit' ),
		)
	);
	$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
	return $response;
}

/**
 * Gửi yêu cầu qua REST (JavaScript).
 *
 * @param WP_REST_Request $request Yêu cầu.
 * @return WP_REST_Response
 */
function sgb_rest_submit( WP_REST_Request $request ) {
	sgb_no_cache();
	$params = $request->get_json_params();
	if ( ! is_array( $params ) ) {
		$params = $request->get_body_params();
	}
	$result = sgb_handle_submission( (array) $params );
	return new WP_REST_Response( $result, $result['ok'] ? 200 : 422 );
}

/**
 * Gửi form không có JavaScript (dự phòng).
 */
function sgb_admin_post_submit() {
	sgb_no_cache();
	$result = sgb_handle_submission( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce được kiểm tra trong sgb_handle_submission().
	$back   = isset( $_POST['sgb_source'] ) ? esc_url_raw( wp_unslash( $_POST['sgb_source'] ) ) : home_url( '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$back   = wp_validate_redirect( $back, home_url( '/' ) );
	wp_safe_redirect( add_query_arg( 'sgb', $result['ok'] ? 'ok' : 'error', $back ) . '#sgb-form' );
	exit;
}
add_action( 'admin_post_nopriv_sgb_submit', 'sgb_admin_post_submit' );
add_action( 'admin_post_sgb_submit', 'sgb_admin_post_submit' );

/**
 * Địa chỉ IP (đã băm, không lưu IP gốc).
 *
 * @return string
 */
function sgb_ip_hash() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	return wp_hash( 'sgb|' . $ip );
}

/**
 * Kiểm tra reCAPTCHA v3 (nếu đã nhập khóa).
 *
 * @param string $token Mã từ trình duyệt.
 * @return bool
 */
function sgb_verify_recaptcha( $token ) {
	$secret = sgb_general( 'recaptcha_secret' );
	if ( ! $secret || ! sgb_general( 'recaptcha_site' ) ) {
		return true;
	}
	if ( ! $token ) {
		return false;
	}
	$response = wp_remote_post(
		'https://www.google.com/recaptcha/api/siteverify',
		array(
			'timeout' => 8,
			'body'    => array(
				'secret'   => $secret,
				'response' => $token,
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return true; // Không chặn khách khi Google không phản hồi; honeypot + giới hạn tần suất vẫn hoạt động.
	}
	$body  = json_decode( wp_remote_retrieve_body( $response ), true );
	$score = (float) str_replace( ',', '.', sgb_general( 'recaptcha_score' ) );
	return ! empty( $body['success'] ) && ( ! isset( $body['score'] ) || (float) $body['score'] >= $score );
}

/**
 * Xử lý một yêu cầu: kiểm tra bảo mật, làm sạch, kiểm tra hợp lệ, lưu và gửi email.
 *
 * @param array $data Dữ liệu thô.
 * @return array { ok:bool, errors:array, message:string, html?:string, id?:int }
 */
function sgb_handle_submission( array $data ) {
	$langs   = sgb_languages();
	$lang    = isset( $data['sgb_lang'] ) ? sanitize_key( $data['sgb_lang'] ) : '';
	$lang    = isset( $langs[ $lang ] ) ? $lang : sgb_default_lang();
	$contact = sgb_contact_info( $lang );
	$fail    = static function ( $message_key, $errors = array() ) use ( $lang, $contact ) {
		return array(
			'ok'      => false,
			'errors'  => $errors,
			'message' => sgb_text( $message_key, $lang, array( 'hotline' => $contact['hotline'] ) ),
		);
	};

	// 1. Bảo mật: nonce, honeypot, thời gian điền form.
	$nonce = isset( $data['sgb_nonce'] ) ? sanitize_text_field( $data['sgb_nonce'] ) : '';
	if ( ! wp_verify_nonce( $nonce, 'sgb_submit' ) ) {
		return $fail( 'err_spam' );
	}
	if ( ! empty( $data['sgb_website'] ) ) {
		return $fail( 'err_spam' );
	}
	$ts = isset( $data['sgb_ts'] ) ? absint( $data['sgb_ts'] ) : 0;
	if ( $ts && ( time() - $ts ) < max( 0, (int) sgb_general( 'min_seconds' ) ) ) {
		return $fail( 'err_spam' );
	}

	// 2. Giới hạn tần suất theo IP.
	$limit = max( 1, (int) sgb_general( 'rate_limit' ) );
	$rkey  = 'sgb_rl_' . substr( sgb_ip_hash(), 0, 20 );
	$count = (int) get_transient( $rkey );
	if ( $count >= $limit ) {
		return $fail( 'err_rate' );
	}

	// 3. reCAPTCHA (tùy chọn).
	if ( ! sgb_verify_recaptcha( isset( $data['sgb_recaptcha'] ) ? sanitize_text_field( $data['sgb_recaptcha'] ) : '' ) ) {
		return $fail( 'err_spam' );
	}

	// 4. Làm sạch.
	$clean = array(
		'checkin'        => isset( $data['checkin'] ) ? sanitize_text_field( $data['checkin'] ) : '',
		'checkout'       => isset( $data['checkout'] ) ? sanitize_text_field( $data['checkout'] ) : '',
		'room_id'        => isset( $data['room'] ) ? absint( $data['room'] ) : 0,
		'room_text'      => isset( $data['room_text'] ) ? sanitize_text_field( $data['room_text'] ) : '',
		'adults'         => isset( $data['adults'] ) ? absint( $data['adults'] ) : 0,
		'children'       => isset( $data['children'] ) ? absint( $data['children'] ) : 0,
		'name'           => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
		'phone'          => isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '',
		'contact_method' => isset( $data['contact_method'] ) ? sanitize_key( $data['contact_method'] ) : 'phone',
		'contact_id'     => isset( $data['contact_id'] ) ? sanitize_text_field( $data['contact_id'] ) : '',
		'email'          => isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '',
		'note'           => isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : '',
		'source'         => isset( $data['sgb_source'] ) ? esc_url_raw( $data['sgb_source'] ) : '',
	);
	$clean['name']       = mb_substr( $clean['name'], 0, 100 );
	$clean['contact_id'] = mb_substr( $clean['contact_id'], 0, 40 );
	$clean['room_text']  = mb_substr( $clean['room_text'], 0, 120 );
	$clean['note']       = mb_substr( $clean['note'], 0, 1000 );
	if ( ! in_array( $clean['contact_method'], array( 'phone', 'zalo', 'whatsapp' ), true ) ) {
		$clean['contact_method'] = 'phone';
	}

	// 5. Kiểm tra hợp lệ.
	$errors = array();
	$e      = static function ( $key, $vars = array() ) use ( $lang ) {
		return sgb_text( $key, $lang, $vars );
	};
	$in     = DateTime::createFromFormat( '!Y-m-d', $clean['checkin'], wp_timezone() );
	$out    = DateTime::createFromFormat( '!Y-m-d', $clean['checkout'], wp_timezone() );
	$today  = new DateTime( 'today', wp_timezone() );
	if ( ! $in || $in->format( 'Y-m-d' ) !== $clean['checkin'] ) {
		$errors['checkin'] = $e( 'err_required' );
	} elseif ( $in < $today ) {
		$errors['checkin'] = $e( 'err_past' );
	}
	if ( ! $out || $out->format( 'Y-m-d' ) !== $clean['checkout'] ) {
		$errors['checkout'] = $e( 'err_required' );
	} elseif ( $in && $out <= $in ) {
		$errors['checkout'] = $e( 'err_dates' );
	}
	if ( $in && $out && ! isset( $errors['checkin'] ) && ! isset( $errors['checkout'] ) ) {
		$nights = sgb_nights( $clean['checkin'], $clean['checkout'] );
		$ahead  = (int) $today->diff( $in )->format( '%a' );
		if ( $nights > (int) sgb_general( 'max_nights' ) || $ahead > (int) sgb_general( 'max_days_ahead' ) ) {
			$errors['checkout'] = $e( 'err_too_long' );
		}
	}
	if ( $clean['adults'] < 1 || $clean['adults'] > (int) sgb_general( 'max_adults' ) ) {
		$errors['adults'] = $e( 'err_guests' );
	}
	if ( $clean['children'] > (int) sgb_general( 'max_children' ) ) {
		$errors['children'] = $e( 'err_guests' );
	}
	if ( $clean['room_id'] ) {
		$room = get_post( $clean['room_id'] );
		if ( ! $room || sgb_room_post_type() !== $room->post_type || 'publish' !== $room->post_status ) {
			$errors['room'] = $e( 'err_room' );
		}
	}
	if ( '' === trim( $clean['name'] ) ) {
		$errors['name'] = $e( 'err_required' );
	}
	$digits = preg_replace( '/\D+/', '', $clean['phone'] );
	if ( '' === $clean['phone'] ) {
		$errors['phone'] = $e( 'err_required' );
	} elseif ( strlen( $digits ) < 8 || strlen( $digits ) > 15 || ! preg_match( '/^[0-9+().\s-]+$/', $clean['phone'] ) ) {
		$errors['phone'] = $e( 'err_phone' );
	}
	$raw_email = isset( $data['email'] ) ? trim( (string) $data['email'] ) : '';
	if ( '' === $raw_email ) {
		if ( '1' === sgb_general( 'require_email' ) ) {
			$errors['email'] = $e( 'err_required' );
		}
	} elseif ( ! is_email( $clean['email'] ) ) {
		$errors['email'] = $e( 'err_email' );
	}

	if ( $errors ) {
		return $fail( 'err_generic', $errors );
	}

	// 6. Lưu.
	set_transient( $rkey, $count + 1, HOUR_IN_SECONDS );
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'sgb_request',
			'post_status' => 'publish',
			'post_title'  => sprintf( '%1$s · %2$s → %3$s', $clean['name'], $clean['checkin'], $clean['checkout'] ),
			'post_author' => 0,
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		return $fail( 'err_network' );
	}

	$room_title = $clean['room_id'] ? get_the_title( $clean['room_id'] ) : $clean['room_text'];
	$meta       = array(
		'checkin'        => $clean['checkin'],
		'checkout'       => $clean['checkout'],
		'room_id'        => $clean['room_id'],
		'room_title'     => $room_title,
		'adults'         => $clean['adults'],
		'children'       => $clean['children'],
		'name'           => $clean['name'],
		'phone'          => $clean['phone'],
		'contact_method' => $clean['contact_method'],
		'contact_id'     => $clean['contact_id'],
		'email'          => $clean['email'],
		'note'           => $clean['note'],
		'lang'           => $lang,
		'source'         => $clean['source'],
		'status'         => 'new',
		'ip_hash'        => substr( sgb_ip_hash(), 0, 20 ),
	);
	foreach ( $meta as $key => $value ) {
		update_post_meta( $post_id, '_sgb_' . $key, $value );
	}

	do_action( 'sgb_request_created', $post_id, $meta );

	// 7. Email (lỗi gửi email không làm mất yêu cầu – nhân viên vẫn thấy trong trang quản trị).
	sgb_send_emails( $post_id );

	$vars = array(
		'ten_khach'  => $clean['name'],
		'ma_yeu_cau' => sgb_request_code( $post_id ),
		'hotline'    => $contact['hotline'],
		'zalo'       => $contact['zalo'],
		'whatsapp'   => $contact['whatsapp'],
	);

	return array(
		'ok'      => true,
		'errors'  => array(),
		'id'      => $post_id,
		'code'    => $vars['ma_yeu_cau'],
		'message' => sgb_text( 'success_title', $lang, $vars ),
		'html'    => sgb_success_html( $lang, $vars, $contact ),
	);
}

/**
 * Khối thông báo thành công (kèm nút gọi / nhắn tin).
 *
 * @param string $lang    Ngôn ngữ.
 * @param array  $vars    Biến.
 * @param array  $contact Thông tin liên hệ.
 * @return string
 */
function sgb_success_html( $lang, $vars, $contact ) {
	$html  = '<p class="sgb-success__title">' . esc_html( sgb_text( 'success_title', $lang, $vars ) ) . '</p>';
	$html .= '<p class="sgb-success__text">' . esc_html( sgb_text( 'success_message', $lang, $vars ) ) . '</p>';
	$html .= '<div class="sgb-success__actions">';
	if ( $contact['hotline'] ) {
		$html .= '<a class="sgb-button wp-element-button" href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $contact['hotline'] ) ) . '">' . esc_html( sgb_text( 'btn_call', $lang, $vars ) ) . '</a>';
	}
	if ( $contact['zalo'] ) {
		$html .= '<a class="sgb-button sgb-button--ghost" href="https://zalo.me/' . esc_attr( preg_replace( '/\D+/', '', $contact['zalo'] ) ) . '" target="_blank" rel="noopener">' . esc_html( sgb_text( 'btn_zalo', $lang, $vars ) ) . '</a>';
	}
	if ( $contact['whatsapp'] ) {
		$wa = preg_replace( '/\D+/', '', $contact['whatsapp'] );
		$cc = preg_replace( '/\D+/', '', sgb_general( 'country_code' ) );
		$wa = ( $cc && 0 === strpos( $wa, '0' ) ) ? $cc . substr( $wa, 1 ) : $wa;
		$html .= '<a class="sgb-button sgb-button--ghost" href="https://wa.me/' . esc_attr( $wa ) . '" target="_blank" rel="noopener">' . esc_html( sgb_text( 'btn_whatsapp', $lang, $vars ) ) . '</a>';
	}
	$html .= '</div><button type="button" class="sgb-link" data-sgb-again>' . esc_html( sgb_text( 'btn_again', $lang ) ) . '</button>';
	return $html;
}
