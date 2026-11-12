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

/**
 * File CSS/JS dùng ngoài website: bản thu gọn (.min) nếu có VÀ mới hơn hoặc bằng file gốc; còn lại dùng file gốc
 * (sửa file gốc mà chưa tạo lại bản .min thì website tự dùng file gốc – không bao giờ mất thay đổi).
 * Bật SCRIPT_DEBUG thì luôn dùng file gốc. Số phiên bản (?ver=) theo thời gian sửa file để trình duyệt không giữ bản cũ.
 *
 * @param string $rel Đường dẫn trong plugin, vd. 'assets/blocks.css'.
 * @return array { url, ver, path }
 */
function sgc_asset( $rel ) {
	$use = $rel;
	if ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
		$min      = preg_replace( '/\.(css|js)$/', '.min.$1', $rel );
		$src_time = @filemtime( SGC_DIR . $rel ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$min_time = @filemtime( SGC_DIR . $min ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( $min_time && $src_time && $min_time >= $src_time ) {
			$use = $min;
		}
	}
	$time = @filemtime( SGC_DIR . $use ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	return array(
		'url'  => SGC_URL . $use,
		'ver'  => $time ? (string) $time : SGC_VERSION,
		'path' => SGC_DIR . $use,
	);
}
