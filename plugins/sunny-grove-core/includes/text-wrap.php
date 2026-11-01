<?php
/**
 * Xuống dòng đẹp cho chữ tiếng Việt:
 * - "Giữ liền cụm từ": các cụm trong ô Thông tin khách sạn → Giữ liền cụm từ không bị tách khi xuống dòng
 *   (dùng dấu cách không ngắt). Áp dụng cho mọi chữ hiển thị, kể cả chữ lấy từ cài đặt (Block Bindings).
 * - Dấu gạch "–" luôn đứng cuối dòng trước, không mở đầu dòng mới.
 * - Tiêu đề lớn của banner (H1) có dấu " – ": mỗi vế một dòng (VD: "Nắng đảo, vườn xanh –" / "kỳ nghỉ trọn vẹn cho cả nhà").
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Các cụm từ giữ liền của một ngôn ngữ (dài trước, để cụm dài được ưu tiên).
 *
 * @param string $lang Ngôn ngữ.
 * @return string[]
 */
function sgc_keep_phrases( $lang = '' ) {
	static $cache = array();
	$lang = $lang ? $lang : sg_current_lang();
	if ( ! isset( $cache[ $lang ] ) ) {
		$lines = preg_split( '/\R/u', (string) sgc_hotel( 'keep_phrases', $lang ) );
		$lines = array_values( array_unique( array_filter( array_map( 'trim', $lines ), static function ( $p ) {
			return false !== strpos( $p, ' ' ); // Cụm 1 chữ không cần giữ.
		} ) ) );
		usort(
			$lines,
			static function ( $a, $b ) {
				return mb_strlen( $b ) - mb_strlen( $a );
			}
		);
		$cache[ $lang ] = $lines;
	}
	return $cache[ $lang ];
}

/**
 * Biểu thức tìm mọi cụm từ giữ liền (khoảng trắng giữa các chữ có thể là dấu cách thường hoặc không ngắt).
 *
 * @param string $lang Ngôn ngữ.
 * @return string Rỗng nếu không có cụm nào.
 */
function sgc_keep_phrases_regex( $lang ) {
	static $cache = array();
	if ( ! array_key_exists( $lang, $cache ) ) {
		$parts = array();
		foreach ( sgc_keep_phrases( $lang ) as $phrase ) {
			$words   = preg_split( '/\s+/u', $phrase );
			$parts[] = implode( '(?:\s|&nbsp;|\x{00A0})+', array_map( static function ( $w ) {
				return preg_quote( $w, '/' );
			}, $words ) );
		}
		$cache[ $lang ] = $parts ? '/(?<![\p{L}\p{N}])(?:' . implode( '|', $parts ) . ')(?![\p{L}\p{N}])/iu' : '';
	}
	return $cache[ $lang ];
}

/**
 * Áp dụng giữ liền cụm từ cho một đoạn HTML (chỉ đổi phần chữ, không đụng thẻ, thuộc tính, script, style).
 *
 * @param string $html HTML.
 * @param string $lang Ngôn ngữ (mặc định: ngôn ngữ đang xem).
 * @return string
 */
function sgc_keep_together( $html, $lang = '' ) {
	$html = (string) $html;
	if ( '' === trim( $html ) ) {
		return $html;
	}
	$lang  = $lang ? $lang : sg_current_lang();
	$regex = sgc_keep_phrases_regex( $lang );
	$parts = preg_split( '~(<script\b.*?</script>|<style\b.*?</style>|<textarea\b.*?</textarea>|<!--.*?-->|<[^>]+>)~is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( false === $parts ) {
		return $html;
	}
	foreach ( $parts as $i => $part ) {
		if ( '' === $part || '<' === $part[0] ) {
			continue;
		}
		if ( $regex ) {
			$part = preg_replace_callback(
				$regex,
				static function ( $m ) {
					return preg_replace( '/(?:\s|&nbsp;)+/u', "\u{00A0}", $m[0] );
				},
				$part
			);
		}
		// Dấu gạch "–" / "—" dính với chữ đứng trước: không bao giờ mở đầu một dòng.
		$part        = preg_replace( '/[ \t]+([–—])(?=\s)/u', "\u{00A0}$1", $part );
		$parts[ $i ] = null === $part ? $parts[ $i ] : $part;
	}
	return implode( '', $parts );
}

/**
 * Block được xử lý: block chữ của WordPress và mọi block của Sunny Grove (gồm form đặt phòng).
 *
 * @param string $name Tên block.
 * @return bool
 */
function sgc_keep_together_block( $name ) {
	$core = array( 'core/heading', 'core/paragraph', 'core/list-item', 'core/button', 'core/post-title', 'core/post-excerpt', 'core/site-tagline', 'core/quote', 'core/pullquote', 'core/details', 'core/table', 'core/media-text', 'core/cover' );
	return in_array( $name, $core, true ) || 0 === strpos( $name, 'sunny-grove/' ) || 0 === strpos( $name, 'sunny-grove-booking/' );
}

/**
 * Đánh dấu tiêu đề H1 nằm trong banner (tiêu đề hero) để tách vế quanh dấu " – ".
 *
 * @param array         $block  Block.
 * @param array         $source Block gốc.
 * @param WP_Block|null $parent Block cha.
 * @return array
 */
function sgc_hero_title_mark( $block, $source = array(), $parent = null ) {
	if ( 'core/heading' === ( $block['blockName'] ?? '' ) && 1 === (int) ( $block['attrs']['level'] ?? 2 )
		&& $parent instanceof WP_Block && 'sunny-grove/video-banner' === $parent->name ) {
		$block['sgcHeroTitle'] = true;
	}
	return $block;
}
add_filter( 'render_block_data', 'sgc_hero_title_mark', 10, 3 );

/**
 * Tiêu đề hero: "Vế 1 – Vế 2" → hai dòng, mỗi vế một dòng (vế dài vẫn xuống dòng bên trong nhưng giữ liền cụm từ).
 *
 * @param string $html HTML của tiêu đề.
 * @return string
 */
function sgc_hero_title_split( $html ) {
	if ( ! preg_match( '~^(\s*<h1\b[^>]*>)(.*)(</h1>\s*)$~s', $html, $m ) || false !== strpos( $m[2], '<' ) ) {
		return $html;
	}
	$segments = preg_split( '/(?<=[–—])(?:\s|&nbsp;)+/u', trim( $m[2] ), 2 );
	if ( count( $segments ) !== 2 || '' === trim( $segments[1] ) || ! preg_match( '/(?:\s|&nbsp;|\x{00A0})[–—]$/u', $segments[0] ) ) {
		return $html;
	}
	$proc = new WP_HTML_Tag_Processor( $m[1] );
	$proc->next_tag();
	$proc->add_class( 'sg-title-split' );
	return $proc->get_updated_html() . '<span class="sg-title-line">' . $segments[0] . '</span> <span class="sg-title-line">' . $segments[1] . '</span>' . $m[3];
}

/**
 * Áp dụng khi hiển thị (sau khi đã thay chữ theo ngôn ngữ và lấy dữ liệu nối).
 *
 * @param string $content HTML.
 * @param array  $block   Block.
 * @return string
 */
function sgc_keep_together_render( $content, $block ) {
	if ( is_admin() || empty( $block['blockName'] ) || ! sgc_keep_together_block( $block['blockName'] ) ) {
		return $content;
	}
	if ( ! empty( $block['sgcHeroTitle'] ) ) {
		$content = sgc_hero_title_split( $content );
	}
	return sgc_keep_together( $content );
}
add_filter( 'render_block', 'sgc_keep_together_render', 30, 2 );
