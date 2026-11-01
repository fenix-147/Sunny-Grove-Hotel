<?php
/**
 * Hàm dựng khối dùng chung cho các pattern (giúp mọi pattern có cùng cấu trúc, dễ bảo trì).
 *
 * @package SunnyGrove
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lấy nội dung của một pattern khác trong theme (để trang mẫu ghép từ các khối có sẵn).
 *
 * @param string $name Tên file pattern (không có .php).
 * @return string
 */
function sunny_grove_pattern( $name ) {
	$file = get_template_directory() . '/patterns/' . sanitize_file_name( $name ) . '.php';
	if ( ! is_readable( $file ) ) {
		return '';
	}
	ob_start();
	include $file;
	return ob_get_clean() . "\n";
}

/**
 * Link tới một trang chính (Phòng, Ưu đãi…) theo ngôn ngữ đang dựng nội dung.
 * Plugin Sunny Grove Core trả về link thật qua bộ lọc "sunny_grove_page_url"; nếu chưa có thì dùng "#".
 *
 * @param string $key Khóa trang: rooms, amenities, offers, explore, blog, contact, booking.
 * @return string
 */
function sunny_grove_page_url( $key ) {
	$url = apply_filters( 'sunny_grove_page_url', '', $key );
	return $url ? $url : '#';
}

/**
 * Khối "Banner video" kèm tiêu đề, mô tả và nội dung tùy chọn.
 *
 * @param array $args Tham số.
 * @return string Mã block.
 */
function sunny_grove_banner( array $args ) {
	$a = wp_parse_args(
		$args,
		array(
			'media'        => '',
			'video'        => '',
			'video_mobile' => '',
			'height'       => 'medium',
			'overlay'      => 40,
			'position'     => 'center',
			'eyebrow'      => '',
			'title'        => '',
			'title_bind'   => null,
			'level'        => 1,
			'text'         => '',
			'inner'        => '',
		)
	);

	$poster = sunny_grove_media( $a['media'] );
	$attrs  = array(
		'videoUrl'        => $a['video'] ? sunny_grove_media( $a['video'] )['url'] : '',
		'videoMobileUrl'  => $a['video_mobile'] ? sunny_grove_media( $a['video_mobile'] )['url'] : '',
		'posterId'        => (int) $poster['id'],
		'posterUrl'       => $poster['url'],
		'overlay'         => (int) $a['overlay'],
		'height'          => $a['height'],
		'contentPosition' => $a['position'],
	);

	$inner = '';
	if ( $a['eyebrow'] ) {
		$inner .= '<!-- wp:paragraph {"align":"center","className":"is-style-sg-eyebrow"} --><p class="has-text-align-center is-style-sg-eyebrow">' . esc_html( $a['eyebrow'] ) . '</p><!-- /wp:paragraph -->' . "\n";
	}
	if ( $a['title'] || $a['title_bind'] ) {
		$level  = max( 1, min( 3, (int) $a['level'] ) );
		$hattrs = array( 'textAlign' => 'center' );
		if ( 2 !== $level ) {
			$hattrs['level'] = $level;
		}
		if ( $a['title_bind'] ) {
			$hattrs['metadata'] = array( 'bindings' => array( 'content' => sunny_grove_bind( $a['title_bind'][0], $a['title_bind'][1] ) ) );
		}
		$inner .= '<!-- wp:heading ' . sunny_grove_attrs( $hattrs ) . ' --><h' . $level . ' class="wp-block-heading has-text-align-center">' . ( $a['title_bind'] ? '' : esc_html( $a['title'] ) ) . '</h' . $level . '><!-- /wp:heading -->' . "\n";
	}
	if ( $a['text'] ) {
		$inner .= '<!-- wp:paragraph {"align":"center","className":"sg-lead"} --><p class="has-text-align-center sg-lead">' . esc_html( $a['text'] ) . '</p><!-- /wp:paragraph -->' . "\n";
	}
	$inner .= $a['inner'];

	return '<!-- wp:sunny-grove/video-banner ' . sunny_grove_attrs( $attrs ) . ' -->' . "\n" . $inner . '<!-- /wp:sunny-grove/video-banner -->';
}

/**
 * Tiêu đề của một khối nội dung: dòng nhỏ + tiêu đề + mô tả.
 *
 * @param string $eyebrow Dòng nhỏ.
 * @param string $title   Tiêu đề.
 * @param string $text    Mô tả.
 * @return string
 */
function sunny_grove_section_head( $eyebrow, $title, $text = '' ) {
	$out = '';
	if ( $eyebrow ) {
		$out .= '<!-- wp:paragraph {"align":"center","className":"is-style-sg-eyebrow"} --><p class="has-text-align-center is-style-sg-eyebrow">' . esc_html( $eyebrow ) . '</p><!-- /wp:paragraph -->' . "\n";
	}
	$out .= '<!-- wp:heading {"textAlign":"center"} --><h2 class="wp-block-heading has-text-align-center">' . esc_html( $title ) . '</h2><!-- /wp:heading -->' . "\n";
	if ( $text ) {
		$out .= '<!-- wp:paragraph {"align":"center","className":"sg-lead"} --><p class="has-text-align-center sg-lead">' . esc_html( $text ) . '</p><!-- /wp:paragraph -->' . "\n";
	}
	return $out;
}

/**
 * Mở / đóng một khối nền toàn chiều rộng.
 *
 * @param string $bg    Màu nền (slug trong bảng màu) hoặc rỗng.
 * @param string $class Class thêm.
 * @return string
 */
function sunny_grove_section_open( $bg = '', $class = '' ) {
	$attrs = array(
		'align'     => 'full',
		'className' => trim( 'sg-section sg-reveal ' . $class ),
		'style'     => array(
			'spacing' => array(
				'padding' => array(
					'top'    => 'var:preset|spacing|70',
					'bottom' => 'var:preset|spacing|70',
				),
			),
		),
		'layout'    => array( 'type' => 'constrained' ),
	);
	$classes = 'wp-block-group alignfull ' . trim( 'sg-section sg-reveal ' . $class );
	if ( $bg ) {
		$attrs['backgroundColor'] = $bg;
		$classes                 .= ' has-' . $bg . '-background-color has-background';
	}
	return '<!-- wp:group ' . sunny_grove_attrs( $attrs ) . ' --><div class="' . esc_attr( $classes ) . '" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">' . "\n";
}

/**
 * Đóng khối nền.
 *
 * @return string
 */
function sunny_grove_section_close() {
	return '</div><!-- /wp:group -->' . "\n";
}

/**
 * Nút bấm (một hoặc nhiều).
 *
 * @param array  $buttons Danh sách [ text, url, outline(bool), bindings(array|null) ].
 * @param string $justify Căn: center | left.
 * @return string
 */
function sunny_grove_buttons( array $buttons, $justify = 'center' ) {
	$out = '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"' . esc_attr( $justify ) . '"}} --><div class="wp-block-buttons">';
	foreach ( $buttons as $b ) {
		$text     = $b[0] ?? '';
		$url      = $b[1] ?? '';
		$outline  = ! empty( $b[2] );
		$bindings = $b[3] ?? null;
		$attrs    = array();
		if ( $outline ) {
			$attrs['className'] = 'is-style-outline';
		}
		if ( $bindings ) {
			$attrs['metadata'] = array( 'bindings' => $bindings );
		}
		$out .= '<!-- wp:button ' . ( $attrs ? sunny_grove_attrs( $attrs ) . ' ' : '' ) . '--><div class="wp-block-button' . ( $outline ? ' is-style-outline' : '' ) . '"><a class="wp-block-button__link wp-element-button"' . ( $url ? ' href="' . esc_url( $url ) . '"' : '' ) . '>' . esc_html( $text ) . '</a></div><!-- /wp:button -->';
	}
	return $out . '</div><!-- /wp:buttons -->' . "\n";
}

/**
 * Binding nhanh.
 *
 * @param string $source Nguồn (label|hotel|meta).
 * @param string $key    Khóa.
 * @return array
 */
function sunny_grove_bind( $source, $key ) {
	return array(
		'source' => 'sunny-grove/' . $source,
		'args'   => array( 'key' => $key ),
	);
}

/**
 * Đoạn văn được nối dữ liệu.
 *
 * @param string $source Nguồn.
 * @param string $key    Khóa.
 * @param string $class  Class.
 * @return string
 */
function sunny_grove_bound_paragraph( $source, $key, $class = '' ) {
	$attrs = array( 'metadata' => array( 'bindings' => array( 'content' => sunny_grove_bind( $source, $key ) ) ) );
	if ( $class ) {
		$attrs['className'] = $class;
	}
	return '<!-- wp:paragraph ' . sunny_grove_attrs( $attrs ) . ' --><p' . ( $class ? ' class="' . esc_attr( $class ) . '"' : '' ) . '></p><!-- /wp:paragraph -->' . "\n";
}

/**
 * Mở Query Loop.
 *
 * @param string $post_type Loại nội dung.
 * @param int    $per_page  Số mục.
 * @param array  $layout    Bố cục post-template.
 * @param string $class     Class của Query.
 * @param array  $extra     Tham số query thêm.
 * @return string
 */
function sunny_grove_query_open( $post_type, $per_page, array $layout, $class = '', array $extra = array() ) {
	static $query_id = 0;
	++$query_id;
	$query = array_merge(
		array(
			'perPage'  => $per_page,
			'pages'    => 0,
			'offset'   => 0,
			'postType' => $post_type,
			'order'    => 'post' === $post_type ? 'desc' : 'asc',
			'orderBy'  => 'post' === $post_type ? 'date' : 'menu_order',
			'author'   => '',
			'search'   => '',
			'exclude'  => array(),
			'sticky'   => '',
			'inherit'  => false,
		),
		$extra
	);
	$attrs = array(
		'queryId' => $query_id,
		'query'   => $query,
		'align'   => 'wide',
	);
	if ( $class ) {
		$attrs['className'] = $class;
	}
	return '<!-- wp:query ' . sunny_grove_attrs( $attrs ) . ' --><div class="wp-block-query alignwide' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">'
		. '<!-- wp:post-template ' . sunny_grove_attrs( array( 'layout' => $layout ) ) . ' -->' . "\n";
}

/**
 * Đóng Query Loop, kèm thông báo khi không có mục nào.
 *
 * @param string $empty_label_key Khóa nhãn khi trống.
 * @param bool   $pagination      Có phân trang không.
 * @return string
 */
function sunny_grove_query_close( $empty_label_key = '', $pagination = false ) {
	$out = '<!-- /wp:post-template -->' . "\n";
	if ( $pagination ) {
		$out .= '<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} --><!-- wp:query-pagination-previous /--><!-- wp:query-pagination-numbers /--><!-- wp:query-pagination-next /--><!-- /wp:query-pagination -->' . "\n";
	}
	if ( $empty_label_key ) {
		$out .= '<!-- wp:query-no-results -->' . sunny_grove_bound_paragraph( 'label', $empty_label_key, 'has-text-align-center' ) . '<!-- /wp:query-no-results -->' . "\n";
	}
	return $out . '</div><!-- /wp:query -->' . "\n";
}

/**
 * Mở nhóm thẻ (card).
 *
 * @param string $modifier Kiểu thẻ.
 * @return string
 */
function sunny_grove_card_open( $modifier ) {
	return '<!-- wp:group {"className":"sg-card sg-card--' . esc_attr( $modifier ) . '","layout":{"type":"default"}} --><div class="wp-block-group sg-card sg-card--' . esc_attr( $modifier ) . '">' . "\n";
}

/**
 * Mở phần thân thẻ.
 *
 * @return string
 */
function sunny_grove_card_body_open() {
	return '<!-- wp:group {"className":"sg-card__body","layout":{"type":"default"}} --><div class="wp-block-group sg-card__body">' . "\n";
}

/**
 * Đóng nhóm.
 *
 * @return string
 */
function sunny_grove_group_close() {
	return '</div><!-- /wp:group -->' . "\n";
}

/**
 * Thẻ phòng (dùng trong Query Loop).
 *
 * @return string
 */
function sunny_grove_room_card() {
	return sunny_grove_card_open( 'room' )
		. '<!-- wp:sunny-grove/motion-media {"aspectRatio":"4/3","showVideo":false} /-->' . "\n"
		. sunny_grove_card_body_open()
		. '<!-- wp:sunny-grove/post-terms-list {"taxonomy":"sg_guest_type","variant":"chips"} /-->' . "\n"
		. '<!-- wp:post-title {"level":3,"isLink":true} /-->' . "\n"
		. '<!-- wp:sunny-grove/room-facts {"compact":true} /-->' . "\n"
		. '<!-- wp:sunny-grove/post-terms-list {"taxonomy":"sg_room_feature","variant":"compact","limit":4} /-->' . "\n"
		. '<!-- wp:group {"className":"sg-card__footer","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} --><div class="wp-block-group sg-card__footer">' . "\n"
		. sunny_grove_bound_paragraph( 'meta', 'price', 'sg-price' )
		. sunny_grove_buttons( array( array( '', '', true, array( 'text' => sunny_grove_bind( 'label', 'read_more' ), 'url' => sunny_grove_bind( 'meta', 'permalink' ) ) ) ), 'left' )
		. sunny_grove_group_close()
		. sunny_grove_group_close()
		. sunny_grove_group_close();
}

/**
 * Thẻ tiện ích có ảnh động.
 *
 * @return string
 */
function sunny_grove_amenity_card() {
	return sunny_grove_card_open( 'amenity' )
		. '<!-- wp:sunny-grove/motion-media {"aspectRatio":"4/5"} /-->' . "\n"
		. sunny_grove_card_body_open()
		. '<!-- wp:post-title {"level":3,"isLink":true} /-->' . "\n"
		. sunny_grove_bound_paragraph( 'meta', 'short' )
		. sunny_grove_bound_paragraph( 'meta', 'hours_line', 'sg-meta-line' )
		. sunny_grove_group_close()
		. sunny_grove_group_close();
}

/**
 * Thẻ địa điểm.
 *
 * @return string
 */
function sunny_grove_place_card() {
	return sunny_grove_card_open( 'place' )
		. '<!-- wp:sunny-grove/motion-media {"aspectRatio":"4/3"} /-->' . "\n"
		. sunny_grove_card_body_open()
		. sunny_grove_bound_paragraph( 'meta', 'compare', 'sg-compare' )
		. '<!-- wp:post-title {"level":3,"isLink":true} /-->' . "\n"
		. '<!-- wp:post-excerpt {"excerptLength":18} /-->' . "\n"
		. '<!-- wp:sunny-grove/place-facts {"showCompare":false} /-->' . "\n"
		. sunny_grove_group_close()
		. sunny_grove_group_close();
}

/**
 * Thẻ ưu đãi.
 *
 * @return string
 */
function sunny_grove_offer_card() {
	return sunny_grove_card_open( 'offer' )
		. '<!-- wp:sunny-grove/motion-media {"aspectRatio":"3/2","showVideo":false,"showBadge":true} /-->' . "\n"
		. sunny_grove_card_body_open()
		. '<!-- wp:post-title {"level":3,"isLink":true} /-->' . "\n"
		. '<!-- wp:post-excerpt {"excerptLength":20} /-->' . "\n"
		. sunny_grove_bound_paragraph( 'meta', 'valid_until', 'sg-meta-line' )
		. '<!-- wp:group {"className":"sg-card__footer","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} --><div class="wp-block-group sg-card__footer">' . "\n"
		. '<!-- wp:group {"layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"0"}}} --><div class="wp-block-group">' . "\n"
		. sunny_grove_bound_paragraph( 'meta', 'old_price', 'sg-old-price' )
		. sunny_grove_bound_paragraph( 'meta', 'offer_price', 'sg-price' )
		. sunny_grove_group_close()
		. sunny_grove_buttons( array( array( '', '', false, array( 'text' => sunny_grove_bind( 'label', 'offer_book' ), 'url' => sunny_grove_bind( 'meta', 'book_url' ) ) ) ), 'left' )
		. sunny_grove_group_close()
		. sunny_grove_group_close()
		. sunny_grove_group_close();
}

/**
 * Thẻ bài viết.
 *
 * @return string
 */
function sunny_grove_post_card() {
	return '<!-- wp:group {"className":"sg-card sg-post-card","layout":{"type":"default"}} --><div class="wp-block-group sg-card sg-post-card">' . "\n"
		. '<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2","sizeSlug":"large"} /-->' . "\n"
		. sunny_grove_card_body_open()
		. '<!-- wp:post-date {"className":"sg-meta-line"} /-->' . "\n"
		. '<!-- wp:post-title {"level":3,"isLink":true} /-->' . "\n"
		. '<!-- wp:post-excerpt {"excerptLength":22} /-->' . "\n"
		. sunny_grove_group_close()
		. sunny_grove_group_close();
}

/**
 * Tiêu đề nối nhãn (dùng cho các danh sách "khác" trong trang chi tiết).
 *
 * @param string $key Khóa nhãn.
 * @return string
 */
function sunny_grove_bound_heading( $key ) {
	$attrs = array(
		'textAlign' => 'center',
		'metadata'  => array( 'bindings' => array( 'content' => sunny_grove_bind( 'label', $key ) ) ),
	);
	return '<!-- wp:heading ' . sunny_grove_attrs( $attrs ) . ' --><h2 class="wp-block-heading has-text-align-center"></h2><!-- /wp:heading -->' . "\n";
}
