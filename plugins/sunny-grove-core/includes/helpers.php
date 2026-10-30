<?php
/**
 * Hàm tiện ích dùng chung.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đọc một file JSON trong thư mục data/ (giá trị mặc định, không phải nội dung hiển thị cứng).
 *
 * @param string $name Tên file không có đuôi.
 * @return array
 */
function sgc_data( $name ) {
	static $cache = array();
	if ( ! isset( $cache[ $name ] ) ) {
		$file           = SGC_DIR . 'data/' . sanitize_file_name( $name ) . '.json';
		$json           = is_readable( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$cache[ $name ] = $json ? (array) json_decode( $json, true ) : array();
	}
	return $cache[ $name ];
}

/**
 * Danh sách ngôn ngữ của site: slug => [ name, locale ].
 * Lấy từ Polylang nếu có, nếu không dùng ngôn ngữ của WordPress.
 *
 * @return array
 */
function sgc_languages() {
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
		sgc_locale_to_slug( $locale ) => array(
			'name'   => $locale,
			'locale' => $locale,
		),
	);
}

/**
 * Chuyển locale (vi, en_US) thành mã ngắn (vi, en).
 *
 * @param string $locale Locale.
 * @return string
 */
function sgc_locale_to_slug( $locale ) {
	return strtolower( substr( (string) $locale, 0, 2 ) );
}

/**
 * Ngôn ngữ mặc định.
 *
 * @return string
 */
function sgc_default_lang() {
	if ( function_exists( 'pll_default_language' ) ) {
		$lang = pll_default_language();
		if ( $lang ) {
			return $lang;
		}
	}
	$langs = array_keys( sgc_languages() );
	return $langs[0];
}

/**
 * Ngôn ngữ khách đang xem.
 *
 * @return string
 */
function sgc_current_lang() {
	if ( ! empty( $GLOBALS['sgc_lang_override'] ) ) {
		return $GLOBALS['sgc_lang_override'];
	}
	if ( function_exists( 'pll_current_language' ) ) {
		$lang = pll_current_language();
		if ( $lang ) {
			return $lang;
		}
	}
	return sgc_default_lang();
}

/**
 * Chạy một hàm như thể đang xem bằng ngôn ngữ $lang (nhãn + định dạng số/ngày theo ngôn ngữ đó).
 * Dùng cho các request của trình soạn thảo, nơi Polylang không biết ngôn ngữ đang sửa.
 *
 * @param string   $lang     Mã ngôn ngữ.
 * @param callable $callback Hàm cần chạy.
 * @return mixed
 */
function sgc_with_lang( $lang, $callback ) {
	$langs = sgc_languages();
	if ( ! $lang || ! isset( $langs[ $lang ] ) ) {
		return $callback();
	}
	$previous                    = $GLOBALS['sgc_lang_override'] ?? '';
	$GLOBALS['sgc_lang_override'] = $lang;
	$switched                    = switch_to_locale( $langs[ $lang ]['locale'] );
	try {
		return $callback();
	} finally {
		if ( $switched ) {
			restore_previous_locale();
		}
		$GLOBALS['sgc_lang_override'] = $previous;
	}
}

/**
 * Ngôn ngữ của một bài (mặc định nếu chưa gán).
 *
 * @param int $post_id ID bài.
 * @return string
 */
function sgc_post_lang( $post_id ) {
	$lang = function_exists( 'pll_get_post_language' ) ? pll_get_post_language( (int) $post_id ) : '';
	return $lang ? $lang : sgc_default_lang();
}

/**
 * Lấy ID bản dịch của một bài/trang theo ngôn ngữ đang xem.
 *
 * @param int    $post_id ID gốc.
 * @param string $lang    Ngôn ngữ (mặc định: đang xem).
 * @return int
 */
function sgc_translated_id( $post_id, $lang = '' ) {
	$post_id = (int) $post_id;
	if ( $post_id && function_exists( 'pll_get_post' ) ) {
		$translated = pll_get_post( $post_id, $lang ? $lang : sgc_current_lang() );
		if ( $translated ) {
			return (int) $translated;
		}
	}
	return $post_id;
}

/**
 * Thay các biến dạng {ten} trong chuỗi.
 *
 * @param string $text Chuỗi.
 * @param array  $vars Biến.
 * @return string
 */
function sgc_fill( $text, array $vars ) {
	$map = array();
	foreach ( $vars as $key => $value ) {
		$map[ '{' . $key . '}' ] = (string) $value;
	}
	return strtr( (string) $text, $map );
}

/**
 * Lấy ID bài viết từ ngữ cảnh block (dùng trong Query Loop) hoặc bài hiện tại.
 *
 * @param WP_Block|null $block Block.
 * @return int
 */
function sgc_context_post_id( $block = null ) {
	if ( $block instanceof WP_Block && ! empty( $block->context['postId'] ) ) {
		return (int) $block->context['postId'];
	}
	return (int) get_the_ID();
}

/**
 * Chuẩn hóa số điện thoại thành dạng chỉ có chữ số (giữ dấu + đầu).
 *
 * @param string $phone Số điện thoại.
 * @return string
 */
function sgc_phone_digits( $phone ) {
	$phone = trim( (string) $phone );
	$plus  = 0 === strpos( $phone, '+' ) ? '+' : '';
	return $plus . preg_replace( '/\D+/', '', $phone );
}

/**
 * Số điện thoại dạng quốc tế không có dấu + (dùng cho WhatsApp): 0901... => 84901...
 *
 * @param string $phone Số điện thoại.
 * @return string
 */
function sgc_phone_international( $phone ) {
	$digits = ltrim( sgc_phone_digits( $phone ), '+' );
	$code   = preg_replace( '/\D+/', '', (string) sgc_hotel( 'country_code' ) );
	if ( $code && 0 === strpos( $digits, '0' ) ) {
		$digits = $code . substr( $digits, 1 );
	}
	return $digits;
}
