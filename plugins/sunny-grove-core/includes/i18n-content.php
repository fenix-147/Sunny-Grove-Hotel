<?php
/**
 * Nội dung song ngữ trong một bài:
 * - Block chữ lưu bản tiếng Anh trong thuộc tính "sgEn" của chính block đó: { "<trường>": "...", "h": "<mã băm chữ VI>" }.
 * - Tiêu đề / tóm tắt / slug tiếng Anh: meta _sg_en_title, _sg_en_excerpt, _sg_en_slug.
 * - Trường chữ của Phòng/Tiện ích/Địa điểm/Ưu đãi: meta "<khóa>_en" (ví dụ sg_bed_en).
 * Khi xem bản tiếng Anh, chữ tiếng Việt được thay bằng chữ tiếng Anh; chữ chưa dịch giữ tiếng Việt.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block có chữ cần dịch và các trường của nó.
 *
 * - html: trường lấy từ HTML đã lưu. "tag" = nội dung bên trong thẻ; "tag@attr" = thuộc tính của thẻ.
 * - cells: các ô của bảng (theo thứ tự).
 * - attrs: trường lưu trong thuộc tính block (block động).
 * - text: các trường là chữ (dùng để tính mã băm, đếm "đã dịch"); trường còn lại là link.
 *
 * @return array
 */
function sg_i18n_block_spec() {
	$spec = array(
		'core/paragraph'                => array( 'html' => array( 'content' => 'p' ) ),
		'core/heading'                  => array( 'html' => array( 'content' => 'h[1-6]' ) ),
		'core/list-item'                => array( 'html' => array( 'content' => 'li' ) ),
		'core/button'                   => array(
			'html' => array(
				'text' => 'a|button',
				'url'  => 'a@href',
			),
			'text' => array( 'text' ),
		),
		'core/image'                    => array(
			'html' => array(
				'alt'     => 'img@alt',
				'caption' => 'figcaption',
			),
		),
		'core/cover'                    => array( 'html' => array( 'alt' => 'img@alt' ) ),
		'core/media-text'               => array( 'html' => array( 'alt' => 'img@alt' ) ),
		'core/quote'                    => array( 'html' => array( 'citation' => 'cite' ) ),
		'core/pullquote'                => array(
			'html' => array(
				'value'    => 'p',
				'citation' => 'cite',
			),
		),
		'core/details'                  => array( 'html' => array( 'summary' => 'summary' ) ),
		'core/verse'                    => array( 'html' => array( 'content' => 'pre' ) ),
		'core/table'                    => array(
			'html'  => array( 'caption' => 'figcaption' ),
			'cells' => true,
		),
		'core/search'                   => array( 'attrs' => array( 'label', 'placeholder', 'buttonText' ) ),
		'core/query-pagination-next'    => array( 'attrs' => array( 'label' ) ),
		'core/query-pagination-previous' => array( 'attrs' => array( 'label' ) ),
		'core/post-excerpt'             => array( 'attrs' => array( 'moreText' ) ),
	);
	return apply_filters( 'sg_i18n_block_spec', $spec );
}

/**
 * Các trường chữ (không phải link) của một block.
 *
 * @param array $spec Định nghĩa block.
 * @return string[]
 */
function sg_i18n_text_fields( $spec ) {
	if ( isset( $spec['text'] ) ) {
		$fields = $spec['text'];
	} else {
		$fields = array_merge( array_keys( $spec['html'] ?? array() ), $spec['attrs'] ?? array() );
	}
	if ( ! empty( $spec['cells'] ) ) {
		$fields[] = 'cells';
	}
	return $fields;
}

/**
 * Chuẩn hóa chữ để so sánh/băm: bỏ thẻ, giải mã ký tự, gộp khoảng trắng.
 * (Cùng thuật toán với sgHash trong assets/editor-i18n.js.)
 *
 * @param string $html HTML.
 * @return string
 */
function sg_i18n_normalize( $html ) {
	$text = html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	return trim( preg_replace( '/[\s\x{00A0}]+/u', ' ', $text ) );
}

/**
 * Mã băm FNV-1a 32 bit (hex) của chữ tiếng Việt – để biết bản EN đã cũ khi chữ VI bị sửa.
 *
 * @param array $values Giá trị các trường chữ (chuỗi hoặc mảng chuỗi).
 * @return string
 */
function sg_i18n_hash( array $values ) {
	$parts = array();
	foreach ( $values as $value ) {
		foreach ( (array) $value as $piece ) {
			$parts[] = sg_i18n_normalize( $piece );
		}
	}
	$data = implode( "\x1f", $parts );
	$hash = 0x811c9dc5;
	$len  = strlen( $data );
	for ( $i = 0; $i < $len; $i++ ) {
		$hash ^= ord( $data[ $i ] );
		$hash  = ( $hash * 0x01000193 ) & 0xffffffff;
	}
	return str_pad( dechex( $hash ), 8, '0', STR_PAD_LEFT );
}

/**
 * Biểu thức tìm thẻ đầu tiên trong một đoạn HTML.
 *
 * @param string $tags Tên thẻ (có thể dạng "a|button" hoặc "h[1-6]").
 * @return string
 */
function sg_i18n_tag_regex( $tags ) {
	return '~(<(' . $tags . ')\b[^>]*>)(.*?)(</\2>|\z)~s';
}

/**
 * Lấy giá trị các trường dịch được của một block.
 *
 * @param array $block Block (parse_blocks).
 * @return array|null Trường => giá trị; null nếu block không thuộc loại dịch được.
 */
function sg_i18n_extract( array $block ) {
	$spec = sg_i18n_block_spec()[ $block['blockName'] ?? '' ] ?? null;
	if ( ! $spec ) {
		return null;
	}
	$out   = array();
	$html  = implode( '', array_filter( $block['innerContent'] ?? array(), 'is_string' ) );
	$first = (string) ( $block['innerContent'][0] ?? $html );
	foreach ( $spec['html'] ?? array() as $field => $selector ) {
		if ( false !== strpos( $selector, '@' ) ) {
			list( $tag, $attr ) = explode( '@', $selector );
			$proc               = new WP_HTML_Tag_Processor( $html );
			$value              = '';
			if ( $proc->next_tag( array( 'tag_name' => $tag ) ) ) {
				$value = (string) $proc->get_attribute( $attr );
			}
			$out[ $field ] = $value;
		} else {
			// Nội dung thẻ: tìm trong đoạn đầu (list-item có danh sách con nằm sau).
			$source        = preg_match( sg_i18n_tag_regex( $selector ), $first ) ? $first : $html;
			$out[ $field ] = preg_match( sg_i18n_tag_regex( $selector ), $source, $m ) ? trim( $m[3] ) : '';
		}
	}
	if ( ! empty( $spec['cells'] ) ) {
		preg_match_all( '~<(td|th)\b[^>]*>(.*?)</\1>~s', $html, $m );
		$out['cells'] = array_map( 'trim', $m[2] );
	}
	foreach ( $spec['attrs'] ?? array() as $attr ) {
		$out[ $attr ] = (string) ( $block['attrs'][ $attr ] ?? '' );
	}
	// Trường được nối dữ liệu (Block Bindings) đã tự đổi theo ngôn ngữ → không dịch tay.
	foreach ( array_keys( $block['attrs']['metadata']['bindings'] ?? array() ) as $bound ) {
		unset( $out[ $bound ] );
	}
	return $out;
}

/**
 * Block có chữ cần dịch (ít nhất một trường chữ khác rỗng)?
 *
 * @param array $block Block.
 * @return bool
 */
function sg_i18n_block_has_text( array $block ) {
	$values = sg_i18n_extract( $block );
	if ( ! $values ) {
		return false;
	}
	$spec = sg_i18n_block_spec()[ $block['blockName'] ];
	foreach ( sg_i18n_text_fields( $spec ) as $field ) {
		if ( isset( $values[ $field ] ) && '' !== sg_i18n_normalize( implode( ' ', (array) $values[ $field ] ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Mã băm chữ VI hiện tại của block.
 *
 * @param array $block Block.
 * @return string
 */
function sg_i18n_block_hash( array $block ) {
	$values = (array) sg_i18n_extract( $block );
	$spec   = sg_i18n_block_spec()[ $block['blockName'] ] ?? array();
	$texts  = array();
	foreach ( sg_i18n_text_fields( $spec ) as $field ) {
		if ( isset( $values[ $field ] ) ) {
			$texts[ $field ] = $values[ $field ];
		}
	}
	return sg_i18n_hash( $texts );
}

/**
 * Trạng thái dịch của một block: none (không cần dịch) | missing | outdated | done.
 *
 * @param array  $block Block.
 * @param string $lang  Ngôn ngữ.
 * @return string
 */
function sg_i18n_block_status( array $block, $lang = 'en' ) {
	if ( ! sg_i18n_block_has_text( $block ) ) {
		return 'none';
	}
	$en = sg_i18n_block_translation( $block, $lang );
	if ( ! $en ) {
		return 'missing';
	}
	return ( $en['h'] ?? '' ) === sg_i18n_block_hash( $block ) ? 'done' : 'outdated';
}

/**
 * Bản dịch lưu trong block (thuộc tính sgEn).
 *
 * @param array  $block Block.
 * @param string $lang  Ngôn ngữ.
 * @return array
 */
function sg_i18n_block_translation( array $block, $lang = 'en' ) {
	$attr = 'sg' . ucfirst( $lang );
	$en   = $block['attrs'][ $attr ] ?? array();
	if ( ! is_array( $en ) ) {
		return array();
	}
	$spec = sg_i18n_block_spec()[ $block['blockName'] ?? '' ] ?? array();
	foreach ( sg_i18n_text_fields( $spec ) as $field ) {
		if ( isset( $en[ $field ] ) && '' !== sg_i18n_normalize( implode( ' ', (array) $en[ $field ] ) ) ) {
			return $en;
		}
	}
	return array();
}

/**
 * Duyệt cây block theo thứ tự đọc; trả các block dịch được kèm đường đi (chỉ số) trong cây.
 *
 * @param array $blocks Cây block.
 * @param array $path   Đường đi hiện tại.
 * @return array[] [ 'path' => int[], 'block' => array ]
 */
function sg_i18n_flatten( array $blocks, array $path = array() ) {
	$out = array();
	foreach ( $blocks as $i => $block ) {
		if ( empty( $block['blockName'] ) ) {
			continue;
		}
		$here = array_merge( $path, array( $i ) );
		if ( sg_i18n_block_has_text( $block ) ) {
			$out[] = array(
				'path'  => $here,
				'block' => $block,
			);
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$out = array_merge( $out, sg_i18n_flatten( $block['innerBlocks'], $here ) );
		}
	}
	return $out;
}

/**
 * Đặt thuộc tính cho block theo đường đi trong cây.
 *
 * @param array  $blocks Cây block (tham chiếu).
 * @param int[]  $path   Đường đi.
 * @param string $attr   Thuộc tính.
 * @param mixed  $value  Giá trị.
 */
function sg_i18n_set_attr( array &$blocks, array $path, $attr, $value ) {
	$index = array_shift( $path );
	if ( ! isset( $blocks[ $index ] ) ) {
		return;
	}
	if ( ! $path ) {
		if ( ! is_array( $blocks[ $index ]['attrs'] ?? null ) ) {
			$blocks[ $index ]['attrs'] = array();
		}
		$blocks[ $index ]['attrs'][ $attr ] = $value;
		return;
	}
	sg_i18n_set_attr( $blocks[ $index ]['innerBlocks'], $path, $attr, $value );
}

/**
 * Ghép bản dịch: lấy chữ của từng block trong nội dung tiếng Anh, gắn vào block tương ứng của nội dung tiếng Việt.
 * Ghép theo thứ tự đọc và loại block (thuật toán dãy con chung dài nhất – chịu được block thừa/thiếu ở một bên).
 *
 * @param string $vi_content Nội dung tiếng Việt.
 * @param string $en_content Nội dung tiếng Anh.
 * @param string $lang       Ngôn ngữ của bản dịch.
 * @return array{content:string,matched:int,vi_only:array,en_only:array,mismatch:array}
 */
function sg_i18n_merge_content( $vi_content, $en_content, $lang = 'en' ) {
	$vi_tree = parse_blocks( $vi_content );
	$vi      = sg_i18n_flatten( $vi_tree );
	$en      = sg_i18n_flatten( parse_blocks( $en_content ) );
	$n       = count( $vi );
	$m       = count( $en );

	// Bảng LCS theo tên block.
	$lcs = array_fill( 0, $n + 1, array_fill( 0, $m + 1, 0 ) );
	for ( $i = $n - 1; $i >= 0; $i-- ) {
		for ( $j = $m - 1; $j >= 0; $j-- ) {
			$lcs[ $i ][ $j ] = $vi[ $i ]['block']['blockName'] === $en[ $j ]['block']['blockName']
				? $lcs[ $i + 1 ][ $j + 1 ] + 1
				: max( $lcs[ $i + 1 ][ $j ], $lcs[ $i ][ $j + 1 ] );
		}
	}

	$result = array(
		'matched'  => 0,
		'vi_only'  => array(),
		'en_only'  => array(),
		'mismatch' => array(),
	);
	$attr   = 'sg' . ucfirst( $lang );
	$i      = 0;
	$j      = 0;
	while ( $i < $n && $j < $m ) {
		if ( $vi[ $i ]['block']['blockName'] === $en[ $j ]['block']['blockName'] ) {
			$vi_block = $vi[ $i ]['block'];
			$values   = (array) sg_i18n_extract( $en[ $j ]['block'] );
			$vi_vals  = (array) sg_i18n_extract( $vi_block );
			if ( isset( $values['cells'], $vi_vals['cells'] ) && count( $values['cells'] ) !== count( $vi_vals['cells'] ) ) {
				$result['mismatch'][] = sg_i18n_describe( $vi_block ) . ' ≠ ' . sg_i18n_describe( $en[ $j ]['block'] );
			} else {
				$values = sg_i18n_clean_translation( $values, $vi_vals, $lang );
				if ( $values ) {
					$values['h'] = sg_i18n_block_hash( $vi_block );
					sg_i18n_set_attr( $vi_tree, $vi[ $i ]['path'], $attr, $values );
				}
				++$result['matched'];
			}
			++$i;
			++$j;
		} elseif ( $lcs[ $i + 1 ][ $j ] >= $lcs[ $i ][ $j + 1 ] ) {
			$result['vi_only'][] = sg_i18n_describe( $vi[ $i ]['block'] );
			++$i;
		} else {
			$result['en_only'][] = sg_i18n_describe( $en[ $j ]['block'] );
			++$j;
		}
	}
	for ( ; $i < $n; $i++ ) {
		$result['vi_only'][] = sg_i18n_describe( $vi[ $i ]['block'] );
	}
	for ( ; $j < $m; $j++ ) {
		$result['en_only'][] = sg_i18n_describe( $en[ $j ]['block'] );
	}
	$result['content'] = serialize_blocks( $vi_tree );
	return $result;
}

/**
 * Bỏ các trường không cần lưu: link nội bộ (tự đổi sang /en/), trường trùng hệt bản VI nếu là link.
 *
 * @param array  $values  Giá trị EN.
 * @param array  $vi_vals Giá trị VI.
 * @param string $lang    Ngôn ngữ.
 * @return array
 */
function sg_i18n_clean_translation( array $values, array $vi_vals, $lang ) {
	$home = untrailingslashit( get_option( 'home' ) );
	foreach ( array( 'url' ) as $link ) {
		if ( ! isset( $values[ $link ] ) ) {
			continue;
		}
		$en_url = (string) $values[ $link ];
		$vi_url = (string) ( $vi_vals[ $link ] ?? '' );
		// Link nội bộ hoặc giống bản VI → không lưu, website tự đổi sang ngôn ngữ đang xem.
		if ( '' === $en_url || $en_url === $vi_url || 0 === strpos( $en_url, $home ) || 0 === strpos( $en_url, '/' ) || 0 === strpos( $en_url, '#' ) ) {
			unset( $values[ $link ] );
		}
	}
	foreach ( $values as $key => $value ) {
		if ( '' === $value || array() === $value ) {
			unset( $values[ $key ] );
		}
	}
	return $values;
}

/**
 * Mô tả ngắn một block cho báo cáo.
 *
 * @param array $block Block.
 * @return string
 */
function sg_i18n_describe( array $block ) {
	$flat = array();
	foreach ( (array) sg_i18n_extract( $block ) as $value ) {
		foreach ( (array) $value as $piece ) {
			$flat[] = $piece;
		}
	}
	$text = sg_i18n_normalize( implode( ' | ', $flat ) );
	$name = str_replace( 'core/', '', (string) $block['blockName'] );
	return $name . ' “' . mb_substr( $text, 0, 60 ) . ( mb_strlen( $text ) > 60 ? '…' : '' ) . '”';
}

/* -------------------------------------------------------------------------
 * Xuất bản dịch ra website
 * ---------------------------------------------------------------------- */

/**
 * Thay chữ VI trong HTML của block bằng chữ ngôn ngữ đang xem.
 *
 * @param string $content HTML đã render.
 * @param array  $block   Block.
 * @return string
 */
function sg_i18n_render_block( $content, $block ) {
	$lang = sg_current_lang();
	if ( sg_i18n_legacy_active() || $lang === sg_default_lang() || is_admin() || empty( $block['blockName'] ) ) {
		return $content;
	}
	$spec = sg_i18n_block_spec()[ $block['blockName'] ] ?? null;
	$tr   = $spec ? sg_i18n_block_translation( $block, $lang ) : array();
	if ( ! $spec || ! $tr ) {
		return $content;
	}
	foreach ( $spec['html'] ?? array() as $field => $selector ) {
		if ( ! isset( $tr[ $field ] ) || '' === $tr[ $field ] ) {
			continue;
		}
		$value = (string) $tr[ $field ];
		if ( false !== strpos( $selector, '@' ) ) {
			list( $tag, $attr ) = explode( '@', $selector );
			$proc               = new WP_HTML_Tag_Processor( $content );
			if ( $proc->next_tag( array( 'tag_name' => $tag ) ) ) {
				$proc->set_attribute( $attr, 'href' === $attr ? esc_url( $value ) : $value );
				$content = $proc->get_updated_html();
			}
			continue;
		}
		// Chỉ thay nội dung thẻ đầu tiên (danh sách con của list-item nằm sau, không bị ảnh hưởng).
		$content = preg_replace_callback(
			'~(<(' . $selector . ')\b[^>]*>)(.*?)(?=</\2>|<ul\b|<ol\b|\z)~s',
			static function ( $m ) use ( $value ) {
				return $m[1] . wp_kses_post( $value );
			},
			$content,
			1
		);
	}
	if ( ! empty( $spec['cells'] ) && ! empty( $tr['cells'] ) && is_array( $tr['cells'] ) ) {
		$cells   = array_values( $tr['cells'] );
		$index   = 0;
		$content = preg_replace_callback(
			'~(<(td|th)\b[^>]*>)(.*?)(</\2>)~s',
			static function ( $m ) use ( $cells, &$index ) {
				$value = $cells[ $index ] ?? '';
				++$index;
				return '' !== (string) $value ? $m[1] . wp_kses_post( $value ) . $m[4] : $m[0];
			},
			$content
		);
	}
	return $content;
}
add_filter( 'render_block', 'sg_i18n_render_block', 5, 2 );

/**
 * Block động: đổi thuộc tính chữ trước khi render.
 *
 * @param array $block Block.
 * @return array
 */
function sg_i18n_render_block_data( $block ) {
	$lang = sg_current_lang();
	if ( sg_i18n_legacy_active() || $lang === sg_default_lang() || is_admin() || empty( $block['blockName'] ) ) {
		return $block;
	}
	$spec = sg_i18n_block_spec()[ $block['blockName'] ] ?? null;
	if ( empty( $spec['attrs'] ) ) {
		return $block;
	}
	$tr = sg_i18n_block_translation( $block, $lang );
	foreach ( $spec['attrs'] as $attr ) {
		if ( ! empty( $tr[ $attr ] ) ) {
			$block['attrs'][ $attr ] = $tr[ $attr ];
		}
	}
	return $block;
}
add_filter( 'render_block_data', 'sg_i18n_render_block_data', 5 );

/**
 * Bản dịch của một trường bài (title | excerpt) – rỗng nếu chưa dịch.
 *
 * @param int    $post_id ID.
 * @param string $field   title | excerpt.
 * @param string $lang    Ngôn ngữ.
 * @return string
 */
function sg_post_field_tr( $post_id, $field, $lang ) {
	return (string) get_post_meta( (int) $post_id, sg_en_key( $field, $lang ), true );
}

/**
 * Tiêu đề theo ngôn ngữ đang xem (ngoài website).
 *
 * @param string $title   Tiêu đề.
 * @param int    $post_id ID.
 * @return string
 */
function sg_i18n_the_title( $title, $post_id = 0 ) {
	$lang = sg_current_lang();
	if ( sg_i18n_legacy_active() || ! $post_id || $lang === sg_default_lang() || ( is_admin() && empty( $GLOBALS['sg_lang_override'] ) ) ) {
		return $title;
	}
	$tr = sg_post_field_tr( $post_id, 'title', $lang );
	return '' !== $tr ? esc_html( $tr ) : $title;
}
add_filter( 'the_title', 'sg_i18n_the_title', 5, 2 );

/**
 * Tiêu đề trang đơn trong thẻ <title>.
 *
 * @param string  $title Tiêu đề.
 * @param WP_Post $post  Bài.
 * @return string
 */
function sg_i18n_single_post_title( $title, $post ) {
	return $post instanceof WP_Post ? wp_strip_all_tags( sg_i18n_the_title( $title, $post->ID ) ) : $title;
}
add_filter( 'single_post_title', 'sg_i18n_single_post_title', 5, 2 );

/**
 * Tóm tắt theo ngôn ngữ đang xem.
 *
 * @param string  $excerpt Tóm tắt.
 * @param WP_Post $post    Bài.
 * @return string
 */
function sg_i18n_excerpt( $excerpt, $post = null ) {
	$lang = sg_current_lang();
	if ( sg_i18n_legacy_active() || ! $post || $lang === sg_default_lang() || ( is_admin() && empty( $GLOBALS['sg_lang_override'] ) ) ) {
		return $excerpt;
	}
	$tr = sg_post_field_tr( $post->ID, 'excerpt', $lang );
	return '' !== $tr ? $tr : $excerpt;
}
add_filter( 'get_the_excerpt', 'sg_i18n_excerpt', 5, 2 );

/**
 * Các trường chữ của Phòng/Tiện ích/Địa điểm/Ưu đãi có bản tiếng Anh riêng.
 *
 * @return string[]
 */
function sg_i18n_text_meta_keys() {
	$keys = array();
	foreach ( sgc_meta_fields() as $fields ) {
		foreach ( $fields as $key => $field ) {
			if ( in_array( $field['type'], array( 'text', 'textarea' ), true ) ) {
				$keys[] = $key;
			}
		}
	}
	return array_values( array_unique( $keys ) );
}

/**
 * Giá trị trường chữ theo ngôn ngữ (chưa dịch thì dùng tiếng Việt).
 *
 * @param int    $post_id ID.
 * @param string $key     Khóa meta gốc.
 * @param string $lang    Ngôn ngữ (mặc định: đang xem).
 * @return string
 */
function sg_meta_tr( $post_id, $key, $lang = '' ) {
	$lang  = $lang ? $lang : sg_current_lang();
	$value = get_post_meta( (int) $post_id, $key, true );
	if ( $lang !== sg_default_lang() && in_array( $key, sg_i18n_text_meta_keys(), true ) ) {
		$tr = (string) get_post_meta( (int) $post_id, $key . '_' . $lang, true );
		if ( '' !== $tr ) {
			return $tr;
		}
	}
	return $value;
}

/**
 * Tên danh mục/nhãn theo ngôn ngữ đang xem (term meta _sg_en_name).
 *
 * @param WP_Term $term Term.
 * @return WP_Term
 */
function sg_i18n_term( $term ) {
	$lang = sg_current_lang();
	if ( sg_i18n_legacy_active() || ! $term instanceof WP_Term || $lang === sg_default_lang() || ( is_admin() && empty( $GLOBALS['sg_lang_override'] ) ) ) {
		return $term;
	}
	$name = (string) get_term_meta( $term->term_id, sg_en_key( 'name', $lang ), true );
	if ( '' !== $name ) {
		$term       = clone $term;
		$term->name = $name;
	}
	return $term;
}
add_filter( 'get_term', 'sg_i18n_term', 5 );

/**
 * Áp dụng tên tiếng Anh cho danh sách term.
 *
 * @param array $terms Danh sách.
 * @return array
 */
function sg_i18n_terms( $terms ) {
	return is_array( $terms ) ? array_map( static function ( $t ) {
		return $t instanceof WP_Term ? sg_i18n_term( $t ) : $t;
	}, $terms ) : $terms;
}
add_filter( 'get_terms', 'sg_i18n_terms', 5 );
add_filter( 'get_the_terms', 'sg_i18n_terms', 5 );

/**
 * Mục menu: tên tiếng Anh (meta _sg_en_title) và link giữ đúng ngôn ngữ.
 *
 * @param WP_Post $item Mục menu.
 * @return WP_Post
 */
function sg_i18n_menu_item( $item ) {
	$lang = sg_current_lang();
	if ( sg_i18n_legacy_active() || is_admin() || $lang === sg_default_lang() || empty( $item->ID ) ) {
		return $item;
	}
	$title = (string) get_post_meta( $item->ID, sg_en_key( 'title', $lang ), true );
	if ( '' !== $title ) {
		$item->title = $title;
	} elseif ( 'post_type' === ( $item->type ?? '' ) && ! empty( $item->object_id ) && '' === (string) $item->post_title ) {
		$tr = sg_post_field_tr( $item->object_id, 'title', $lang );
		if ( '' !== $tr ) {
			$item->title = $tr;
		}
	}
	if ( 'custom' === ( $item->type ?? '' ) && ! empty( $item->url ) ) {
		$item->url = sg_url( $item->url, $lang );
	}
	return $item;
}
add_filter( 'wp_setup_nav_menu_item', 'sg_i18n_menu_item', 20 );
