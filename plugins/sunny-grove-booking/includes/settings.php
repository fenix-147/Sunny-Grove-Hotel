<?php
/**
 * Cài đặt & chuỗi hiển thị song ngữ (mọi nội dung đều sửa được trong trang quản trị).
 *
 * @package SunnyGroveBooking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đọc file mặc định trong thư mục defaults/.
 *
 * @param string $name Tên file.
 * @return array
 */
function sgb_defaults( $name ) {
	static $cache = array();
	if ( ! isset( $cache[ $name ] ) ) {
		$file           = SGB_DIR . 'defaults/' . sanitize_file_name( $name ) . '.json';
		$json           = is_readable( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$cache[ $name ] = $json ? (array) json_decode( $json, true ) : array();
	}
	return $cache[ $name ];
}

/**
 * Ngôn ngữ của site (Polylang nếu có).
 *
 * @return array slug => [ name, locale ]
 */
function sgb_languages() {
	if ( function_exists( 'pll_languages_list' ) ) {
		$slugs = pll_languages_list( array( 'fields' => 'slug' ) );
		if ( $slugs ) {
			$names   = pll_languages_list( array( 'fields' => 'name' ) );
			$locales = pll_languages_list( array( 'fields' => 'locale' ) );
			$out     = array();
			foreach ( $slugs as $i => $slug ) {
				$out[ $slug ] = array(
					'name'   => $names[ $i ],
					'locale' => $locales[ $i ],
				);
			}
			return $out;
		}
	}
	$locale = get_locale();
	return array(
		strtolower( substr( $locale, 0, 2 ) ) => array(
			'name'   => $locale,
			'locale' => $locale,
		),
	);
}

/**
 * Ngôn ngữ mặc định.
 *
 * @return string
 */
function sgb_default_lang() {
	if ( function_exists( 'pll_default_language' ) && pll_default_language() ) {
		return pll_default_language();
	}
	return (string) array_key_first( sgb_languages() );
}

/**
 * Ngôn ngữ khách đang xem.
 *
 * @return string
 */
function sgb_current_lang() {
	if ( function_exists( 'pll_current_language' ) && pll_current_language() ) {
		return pll_current_language();
	}
	return sgb_default_lang();
}

/**
 * Giá trị mặc định theo ngôn ngữ.
 *
 * @param array  $field Định nghĩa.
 * @param string $lang  Ngôn ngữ.
 * @return string
 */
function sgb_default_text( $field, $lang ) {
	if ( isset( $field[ $lang ] ) ) {
		return $field[ $lang ];
	}
	return $field['en'] ?? ( $field['vi'] ?? '' );
}

/**
 * Nạp giá trị mặc định cho các ô còn trống.
 */
function sgb_seed_options() {
	$general = (array) get_option( 'sgb_general', array() );
	foreach ( sgb_defaults( 'general' ) as $key => $field ) {
		if ( ! isset( $general[ $key ] ) ) {
			$general[ $key ] = $field['value'];
		}
	}
	update_option( 'sgb_general', $general );

	$texts  = (array) get_option( 'sgb_texts', array() );
	$schema = sgb_defaults( 'texts' );
	foreach ( array_keys( sgb_languages() ) as $lang ) {
		foreach ( $schema['fields'] as $key => $field ) {
			if ( ! isset( $texts[ $lang ][ $key ] ) ) {
				$texts[ $lang ][ $key ] = sgb_default_text( $field, $lang );
			}
		}
	}
	update_option( 'sgb_texts', $texts );
}
add_action( 'pll_add_language', 'sgb_seed_options', 20 );

/**
 * Cài đặt chung.
 *
 * @param string $key Khóa.
 * @return string
 */
function sgb_general( $key ) {
	$general = (array) get_option( 'sgb_general', array() );
	if ( isset( $general[ $key ] ) ) {
		return (string) $general[ $key ];
	}
	$defaults = sgb_defaults( 'general' );
	return (string) ( $defaults[ $key ]['value'] ?? '' );
}

/**
 * Chuỗi hiển thị theo ngôn ngữ, có thay biến {ten}.
 *
 * @param string $key  Khóa.
 * @param string $lang Ngôn ngữ.
 * @param array  $vars Biến.
 * @return string
 */
function sgb_text( $key, $lang = '', $vars = array() ) {
	$lang  = $lang ? $lang : sgb_current_lang();
	$texts = (array) get_option( 'sgb_texts', array() );
	if ( isset( $texts[ $lang ][ $key ] ) && '' !== $texts[ $lang ][ $key ] ) {
		$text = $texts[ $lang ][ $key ];
	} else {
		$schema = sgb_defaults( 'texts' );
		$text   = isset( $schema['fields'][ $key ] ) ? sgb_default_text( $schema['fields'][ $key ], $lang ) : '';
	}
	if ( ! $vars ) {
		return $text;
	}
	$map = array();
	foreach ( $vars as $k => $v ) {
		$map[ '{' . $k . '}' ] = (string) $v;
	}
	return strtr( $text, $map );
}

/**
 * Thông tin liên hệ hiển thị sau khi gửi. Ô trống có thể được plugin khác (Sunny Grove Core) bổ sung qua bộ lọc.
 *
 * @param string $lang Ngôn ngữ.
 * @return array
 */
function sgb_contact_info( $lang = '' ) {
	$info = array(
		'hotline'    => sgb_general( 'hotline' ),
		'zalo'       => sgb_general( 'zalo' ),
		'whatsapp'   => sgb_general( 'whatsapp' ),
		'hotel_name' => sgb_general( 'from_name' ),
	);
	return (array) apply_filters( 'sgb_contact_info', $info, $lang );
}

/**
 * Link trang đặt phòng theo ngôn ngữ.
 *
 * @param string $lang Ngôn ngữ.
 * @return string
 */
function sgb_booking_page_url( $lang = '' ) {
	$page_id = (int) sgb_general( 'booking_page' );
	if ( $page_id && function_exists( 'pll_get_post' ) ) {
		$translated = pll_get_post( $page_id, $lang ? $lang : sgb_current_lang() );
		$page_id    = $translated ? $translated : $page_id;
	}
	return $page_id ? (string) get_permalink( $page_id ) : '';
}
