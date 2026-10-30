<?php
/**
 * Nhập / xóa nội dung mẫu song ngữ – mỗi trang/phòng/bài là MỘT bản chứa cả Tiếng Việt và English.
 *
 * Chạy từ: Sunny Grove → Nội dung mẫu (nút bấm) hoặc WP-CLI: wp sunny-grove demo import
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dữ liệu mẫu.
 *
 * @return array
 */
function sgc_demo_data() {
	static $data = null;
	if ( null === $data ) {
		$json = file_get_contents( SGC_DIR . 'demo/content.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$data = (array) json_decode( (string) $json, true );
	}
	return $data;
}

/* -------------------------------------------------------------------------
 * Theme hỏi ảnh/video và link trang cho pattern
 * ---------------------------------------------------------------------- */

/**
 * Trả ảnh/video mẫu (nếu đã nhập) cho pattern của theme.
 *
 * @param mixed  $media Giá trị trước.
 * @param string $key   Khóa.
 * @return mixed
 */
function sgc_demo_pattern_media( $media, $key ) {
	$data = sgc_demo_data();
	if ( 0 === strpos( $key, 'video:' ) ) {
		return isset( $data['videos'][ $key ] ) && get_option( 'sgc_demo_imported' ) ? array( 'url' => $data['videos'][ $key ] ) : $media;
	}
	$file = $data['media'][ $key ]['file'] ?? $key;
	$id   = sgc_demo_attachment( $file );
	if ( $id ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
		return array(
			'id'  => $id,
			'url' => $src ? $src[0] : wp_get_attachment_url( $id ),
		);
	}
	return $media;
}
add_filter( 'sunny_grove_media', 'sgc_demo_pattern_media', 10, 2 );

/**
 * Link trang chính cho pattern của theme (link nội bộ tự đổi sang /en/ khi xem bản tiếng Anh).
 *
 * @param string $url Giá trị trước.
 * @param string $key Khóa trang.
 * @return string
 */
function sgc_demo_page_url( $url, $key ) {
	$pages = (array) get_option( 'sgc_pages', array() );
	$id    = $pages[ $key ][ sg_default_lang() ] ?? 0;
	return $id ? (string) get_permalink( $id ) : $url;
}
add_filter( 'sunny_grove_page_url', 'sgc_demo_page_url', 10, 2 );

/**
 * ID tệp mẫu đã nhập theo tên file.
 *
 * @param string $file Tên file (không đuôi).
 * @return int
 */
function sgc_demo_attachment( $file ) {
	static $cache = array();
	if ( isset( $cache[ $file ] ) ) {
		return $cache[ $file ];
	}
	$ids            = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_sgc_demo_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $file, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( ! $ids ) {
		return 0; // Không ghi nhớ kết quả "chưa có" vì ảnh có thể được nhập ngay sau đó.
	}
	$cache[ $file ] = (int) $ids[0];
	return $cache[ $file ];
}

/* -------------------------------------------------------------------------
 * Nhập nội dung
 * ---------------------------------------------------------------------- */

/**
 * Ghi log tiến trình.
 *
 * @param string $msg Nội dung.
 */
function sgc_demo_log( $msg ) {
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		WP_CLI::log( $msg );
	}
	$GLOBALS['sgc_demo_log'][] = $msg;
}

/**
 * Tạo/cập nhật một bài theo khóa mẫu (một bài chứa cả hai ngôn ngữ).
 * Nhận cả khóa cũ dạng "loại:khóa:vi" (từ thời dùng Polylang) để không tạo trùng khi nhập lại.
 *
 * @param string $type Loại nội dung.
 * @param string $key  Khóa mẫu.
 * @param array  $args Tham số wp_insert_post.
 * @return int
 */
function sgc_demo_upsert_post( $type, $key, array $args ) {
	$found = get_posts(
		array(
			'post_type'         => $type,
			'post_status'       => 'any',
			'posts_per_page'    => 1,
			'fields'            => 'ids',
			'sg_include_merged' => false,
			'meta_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_sgc_demo_key',
					'value'   => array( $type . ':' . $key, $type . ':' . $key . ':' . sg_default_lang() ),
					'compare' => 'IN',
				),
			),
		)
	);
	$args  = array_merge(
		array(
			'post_type'   => $type,
			'post_status' => 'publish',
		),
		$args
	);
	if ( $found ) {
		$args['ID'] = $found[0];
		$id         = wp_update_post( wp_slash( $args ), true );
	} else {
		$id = wp_insert_post( wp_slash( $args ), true );
	}
	if ( is_wp_error( $id ) ) {
		sgc_demo_log( 'Lỗi tạo ' . $type . ' ' . $key . ': ' . $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_sgc_demo_key', $type . ':' . $key );
	update_post_meta( $id, '_sgc_demo', 1 );
	return (int) $id;
}

/**
 * Lưu phần tiếng Anh của một bài: tiêu đề, tóm tắt, slug (kèm mã băm chữ VI để theo dõi bản dịch cũ).
 *
 * @param int   $id ID bài.
 * @param array $en Dữ liệu tiếng Anh: title, excerpt.
 */
function sgc_demo_save_english( $id, array $en ) {
	foreach ( sg_secondary_langs() as $lang ) {
		$post = get_post( $id );
		update_post_meta( $id, sg_en_key( 'title', $lang ), $en['title'] );
		update_post_meta( $id, sg_en_key( 'title_h', $lang ), sg_i18n_hash( array( $post->post_title ) ) );
		if ( ! empty( $en['excerpt'] ) ) {
			update_post_meta( $id, sg_en_key( 'excerpt', $lang ), $en['excerpt'] );
			update_post_meta( $id, sg_en_key( 'excerpt_h', $lang ), sg_i18n_hash( array( $post->post_excerpt ) ) );
		}
		$slug = ! empty( $en['slug'] ) ? sanitize_title( $en['slug'] ) : sanitize_title( $en['title'] );
		if ( $slug && $slug !== $post->post_name && (int) get_option( 'page_on_front' ) !== (int) $id ) {
			update_post_meta( $id, sg_en_key( 'slug', $lang ), $slug );
		} else {
			delete_post_meta( $id, sg_en_key( 'slug', $lang ) );
		}
	}
}

/**
 * Nội dung block có bản dịch: dựng block từ chữ VI, gắn chữ EN vào từng block (thuộc tính sgEn).
 *
 * @param string $vi Nội dung block tiếng Việt.
 * @param string $en Nội dung block tiếng Anh (cùng cấu trúc).
 * @return string
 */
function sgc_demo_bilingual_content( $vi, $en ) {
	$merged = sg_i18n_merge_content( $vi, $en, sg_secondary_langs()[0] ?? 'en' );
	if ( $merged['vi_only'] || $merged['en_only'] || $merged['mismatch'] ) {
		sgc_demo_log( 'Lưu ý: có block chưa ghép được bản dịch (' . count( $merged['vi_only'] ) . '/' . count( $merged['en_only'] ) . ').' );
	}
	return $merged['content'];
}

/**
 * Tạo danh mục/nhãn (tên VI, tên EN lưu trong term meta).
 *
 * @return array taxonomy => key => term_id
 */
function sgc_demo_terms() {
	$map = array();
	foreach ( sgc_demo_data()['terms'] as $tax => $terms ) {
		foreach ( $terms as $term ) {
			$slug  = $term['slug']['vi'] ?? sanitize_title( $term['vi'] );
			$exist = get_term_by( 'slug', $slug, $tax );
			if ( $exist ) {
				$tid = (int) $exist->term_id;
			} else {
				$res = wp_insert_term( $term['vi'], $tax, array( 'slug' => $slug ) );
				if ( is_wp_error( $res ) ) {
					sgc_demo_log( 'Lỗi tạo nhóm ' . $term['vi'] . ': ' . $res->get_error_message() );
					continue;
				}
				$tid = (int) $res['term_id'];
			}
			update_term_meta( $tid, '_sgc_demo', 1 );
			foreach ( sg_secondary_langs() as $lang ) {
				if ( ! empty( $term[ $lang ] ) ) {
					update_term_meta( $tid, sg_en_key( 'name', $lang ), $term[ $lang ] );
				}
			}
			$map[ $tax ][ $term['key'] ] = $tid;
		}
	}
	return $map;
}

/**
 * Gán nhóm phân loại cho bài.
 *
 * @param int    $post_id ID.
 * @param string $tax     Phân loại.
 * @param array  $keys    Khóa nhóm.
 * @param array  $terms   Bảng tra từ sgc_demo_terms().
 */
function sgc_demo_set_terms( $post_id, $tax, array $keys, array $terms ) {
	$ids = array();
	foreach ( $keys as $key ) {
		if ( isset( $terms[ $tax ][ $key ] ) ) {
			$ids[] = $terms[ $tax ][ $key ];
		}
	}
	wp_set_object_terms( $post_id, $ids, $tax );
}

/**
 * Ghi một trường chữ song ngữ: bản VI vào khóa gốc, bản EN vào "<khóa>_en".
 *
 * @param int    $id  ID bài.
 * @param string $key Khóa meta.
 * @param string $vi  Chữ VI.
 * @param string $en  Chữ EN.
 */
function sgc_demo_meta_tr( $id, $key, $vi, $en ) {
	foreach ( array( $key => $vi, $key . '_' . ( sg_secondary_langs()[0] ?? 'en' ) => $en ) as $k => $v ) {
		if ( '' !== (string) $v ) {
			update_post_meta( $id, $k, $v );
		} else {
			delete_post_meta( $id, $k );
		}
	}
}

/**
 * Tạo một bài nội dung (phòng, tiện ích, địa điểm, ưu đãi, bài viết) với cả hai ngôn ngữ.
 *
 * @param string $type  Loại nội dung.
 * @param array  $item  Dữ liệu mẫu.
 * @param array  $extra Tham số wp_insert_post bổ sung.
 * @return int
 */
function sgc_demo_item( $type, array $item, array $extra = array() ) {
	$vi = $item['vi'];
	$en = $item['en'] ?? $vi;
	$id = sgc_demo_upsert_post(
		$type,
		$item['key'],
		array_merge(
			array(
				'post_title'   => $vi['title'],
				'post_excerpt' => $vi['excerpt'] ?? '',
				'post_content' => sgc_demo_bilingual_content( sgc_demo_blocks( (array) ( $vi['content'] ?? array() ) ), sgc_demo_blocks( (array) ( $en['content'] ?? array() ) ) ),
				'post_name'    => sanitize_title( $vi['title'] ),
				'menu_order'   => (int) ( $item['order'] ?? 0 ),
			),
			$extra
		)
	);
	if ( $id ) {
		sgc_demo_save_english( $id, $en );
		if ( ! empty( $item['image'] ) ) {
			$att = sgc_demo_attachment( $item['image'] );
			if ( $att ) {
				set_post_thumbnail( $id, $att );
			}
		}
		if ( ! empty( $item['gallery'] ) ) {
			update_post_meta( $id, 'sg_gallery', implode( ',', array_filter( array_map( 'sgc_demo_attachment', (array) $item['gallery'] ) ) ) );
		}
	}
	return $id;
}

/**
 * Nhập toàn bộ nội dung mẫu.
 *
 * @return array Log.
 */
function sgc_demo_import() {
	$GLOBALS['sgc_demo_log'] = array();
	if ( ! function_exists( 'sunny_grove_pattern' ) ) {
		sgc_demo_log( 'Hãy kích hoạt theme Sunny Grove trước khi nhập nội dung mẫu.' );
		return $GLOBALS['sgc_demo_log'];
	}
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 600 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
	}
	kses_remove_filters(); // Nội dung mẫu là block của theme, không cần lọc.
	$data = sgc_demo_data();
	$en   = sg_secondary_langs()[0] ?? 'en';

	sgc_seed_options();
	if ( function_exists( 'sgb_seed_options' ) ) {
		sgb_seed_options();
	}

	// Ảnh.
	$files = array();
	foreach ( $data['media'] as $m ) {
		$files[ $m['file'] ] = $m['vi'] ?? '';
	}
	foreach ( array( 'rooms', 'amenities', 'places', 'offers', 'posts' ) as $group ) {
		foreach ( $data[ $group ] as $item ) {
			foreach ( array_merge( array( $item['image'] ?? '' ), (array) ( $item['gallery'] ?? array() ) ) as $file ) {
				if ( $file && ! isset( $files[ $file ] ) ) {
					$files[ $file ] = $item['vi']['title'] ?? '';
				}
			}
		}
	}
	foreach ( $files as $file => $alt ) {
		sgc_demo_import_image( $file, $alt );
	}
	sgc_demo_log( 'Đã nhập ' . count( $files ) . ' ảnh mẫu.' );

	$terms = sgc_demo_terms();

	foreach ( $data['rooms'] as $room ) {
		$id = sgc_demo_item( 'sg_room', $room );
		if ( ! $id ) {
			continue;
		}
		foreach ( $room['meta'] as $k => $v ) {
			update_post_meta( $id, $k, $v );
		}
		foreach ( $room['i18n_meta'] as $k => $v ) {
			sgc_demo_meta_tr( $id, $k, $v['vi'] ?? '', $v[ $en ] ?? '' );
		}
		foreach ( $room['terms'] as $tax => $keys ) {
			sgc_demo_set_terms( $id, $tax, $keys, $terms );
		}
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['rooms'] ) . ' phòng.' );

	foreach ( $data['amenities'] as $item ) {
		$id = sgc_demo_item( 'sg_amenity', $item );
		if ( ! $id ) {
			continue;
		}
		sgc_demo_meta_tr( $id, 'sg_hours', $item['vi']['hours'], $item[ $en ]['hours'] ?? '' );
		sgc_demo_meta_tr( $id, 'sg_short', $item['vi']['short'], $item[ $en ]['short'] ?? '' );
		if ( $item['video'] ) {
			update_post_meta( $id, 'sg_video', $item['video'] );
		}
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['amenities'] ) . ' tiện ích.' );

	foreach ( $data['places'] as $item ) {
		$id = sgc_demo_item( 'sg_place', $item );
		if ( ! $id ) {
			continue;
		}
		foreach ( $item['meta'] as $k => $v ) {
			update_post_meta( $id, $k, $v );
		}
		sgc_demo_meta_tr( $id, 'sg_compare', $item['vi']['compare'], $item[ $en ]['compare'] ?? '' );
		sgc_demo_set_terms( $id, 'sg_place_tag', $item['tags'], $terms );
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['places'] ) . ' địa điểm.' );

	foreach ( $data['offers'] as $item ) {
		$id = sgc_demo_item( 'sg_offer', $item );
		if ( ! $id ) {
			continue;
		}
		foreach ( array( 'price' => 'sg_price', 'old_price' => 'sg_old_price', 'badge' => 'sg_badge', 'conditions' => 'sg_conditions' ) as $src => $meta ) {
			sgc_demo_meta_tr( $id, $meta, $item['vi'][ $src ], $item[ $en ][ $src ] ?? '' );
		}
		foreach ( array( 'valid_from' => 'sg_valid_from', 'valid_to' => 'sg_valid_to' ) as $src => $meta ) {
			$value = $item[ $src ];
			if ( '' === $value ) {
				delete_post_meta( $id, $meta );
				continue;
			}
			$time = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? strtotime( $value ) : strtotime( $value, current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			update_post_meta( $id, $meta, gmdate( 'Y-m-d', $time ) );
		}
		sgc_demo_set_terms( $id, 'sg_guest_type', $item['guest'], $terms );
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['offers'] ) . ' ưu đãi.' );

	foreach ( $data['posts'] as $item ) {
		$id = sgc_demo_item( 'post', $item, array( 'post_date' => wp_date( 'Y-m-d H:i:s', time() - (int) $item['days_ago'] * DAY_IN_SECONDS ) ) );
		if ( $id ) {
			sgc_demo_set_terms( $id, 'category', array( $item['category'] ), $terms );
		}
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['posts'] ) . ' bài viết.' );

	foreach ( (array) ( $data['reviews'] ?? array() ) as $item ) {
		$id = sgc_demo_upsert_post(
			'sg_review',
			$item['key'],
			array(
				'post_title' => $item['name'],
				'menu_order' => (int) $item['order'],
				'post_date'  => wp_date( 'Y-m-d H:i:s', time() - (int) $item['days_ago'] * DAY_IN_SECONDS ),
			)
		);
		if ( $id ) {
			update_post_meta( $id, 'sg_rating', (string) $item['rating'] );
			update_post_meta( $id, 'sg_source', $item['source'] );
			update_post_meta( $id, 'sg_review_date', wp_date( 'Y-m-d', time() - (int) $item['days_ago'] * DAY_IN_SECONDS ) );
			sgc_demo_meta_tr( $id, 'sg_review_text', $item['vi']['text'], $item[ $en ]['text'] ?? '' );
			sgc_demo_meta_tr( $id, 'sg_review_from', $item['vi']['from'], $item[ $en ]['from'] ?? '' );
		}
	}
	sgc_demo_log( 'Đã tạo ' . count( (array) ( $data['reviews'] ?? array() ) ) . ' đánh giá.' );

	// Trang: lượt 1 tạo trang trống để có link, lượt 2 dựng nội dung từ pattern bằng từng ngôn ngữ rồi ghép.
	$pages = array();
	foreach ( $data['pages'] as $page ) {
		$id = sgc_demo_upsert_post(
			'page',
			$page['key'],
			array(
				'post_title' => $page['vi']['title'],
				'post_name'  => $page['vi']['slug'],
			)
		);
		$pages[ $page['key'] ] = array( sg_default_lang() => $id );
	}
	update_option( 'sgc_pages', $pages );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $pages['home'][ sg_default_lang() ] );
	update_option( 'page_for_posts', 0 );

	$general                 = (array) get_option( 'sgc_hotel', array() );
	$general['booking_page'] = (string) $pages['booking'][ sg_default_lang() ];
	update_option( 'sgc_hotel', $general );
	if ( function_exists( 'sgb_general' ) ) {
		$sgb                 = (array) get_option( 'sgb_general', array() );
		$sgb['booking_page'] = (string) $pages['booking'][ sg_default_lang() ];
		update_option( 'sgb_general', $sgb );
	}
	update_option( 'sgc_demo_imported', time() ); // Bật video mẫu cho pattern.

	foreach ( $data['pages'] as $page ) {
		$id = $pages[ $page['key'] ][ sg_default_lang() ];
		if ( ! $id ) {
			continue;
		}
		$render = static function () use ( $page ) {
			return sunny_grove_pattern( $page['pattern'] );
		};
		$vi     = sg_with_lang( sg_default_lang(), $render );
		$en_txt = sg_with_lang( $en, $render );
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $id,
					'post_content' => sgc_demo_bilingual_content( $vi, $en_txt ),
				)
			)
		);
		sgc_demo_save_english( $id, $page[ $en ] ?? $page['vi'] );
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['pages'] ) . ' trang (mỗi trang chứa cả hai ngôn ngữ).' );

	sgc_demo_menus( $data['menus'], $pages );

	// Tên và mô tả website.
	update_option( 'blogname', 'Sunny Grove' );
	update_option( 'blogdescription', sgc_hotel( 'slogan', sg_default_lang() ) );
	$strings = (array) get_option( 'sg_i18n_strings', array() );
	foreach ( sg_secondary_langs() as $lang ) {
		$strings[ $lang ]['blogname']        = 'Sunny Grove';
		$strings[ $lang ]['blogdescription'] = sgc_hotel( 'slogan', $lang );
	}
	update_option( 'sg_i18n_strings', $strings );
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	// Xóa bài/trang mặc định của WordPress nếu chưa bị sửa.
	foreach ( array( 1, 2 ) as $default_id ) {
		$p = get_post( $default_id );
		if ( $p && ! get_post_meta( $default_id, '_sgc_demo', true ) && $p->post_date === $p->post_modified && in_array( $p->post_name, array( 'hello-world', 'sample-page', 'xin-chao-the-gioi', 'trang-mau' ), true ) ) {
			wp_delete_post( $default_id, true );
		}
	}

	// Trạng thái dịch cho cột "EN" trong danh sách.
	$all = get_posts(
		array(
			'post_type'      => sg_i18n_post_types(),
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_sgc_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	);
	foreach ( $all as $post_id ) {
		sg_i18n_store_status( $post_id );
	}

	// Dựng lại luật đường dẫn ở lần tải trang kế tiếp (có thêm slug tiếng Anh của loại nội dung).
	delete_option( 'rewrite_rules' );
	kses_init_filters();
	sgc_demo_log( 'Hoàn tất! Mở trang chủ để xem website (bản tiếng Anh ở /en/).' );
	return $GLOBALS['sgc_demo_log'];
}

/**
 * Tạo menu và gán vào vị trí. Mục menu trỏ tới trang → tên tiếng Anh tự lấy theo tiêu đề tiếng Anh của trang.
 *
 * @param array $menus Cấu trúc menu.
 * @param array $pages key => lang => ID.
 */
function sgc_demo_menus( array $menus, array $pages ) {
	$names     = array(
		'primary' => __( 'Menu chính', 'sunny-grove-core' ),
		'footer'  => __( 'Menu chân trang', 'sunny-grove-core' ),
	);
	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	foreach ( $menus as $location => $keys ) {
		$name    = $names[ $location ] ?? $location;
		$menu_id = ! empty( $locations[ $location ] ) && wp_get_nav_menu_object( $locations[ $location ] ) ? (int) $locations[ $location ] : 0;
		if ( ! $menu_id ) {
			$menu    = wp_get_nav_menu_object( $name );
			$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );
		}
		foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
			wp_delete_post( $item->ID, true );
		}
		foreach ( $keys as $i => $key ) {
			$page_id = $pages[ $key ][ sg_default_lang() ] ?? 0;
			if ( ! $page_id ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-object-id' => $page_id,
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $i + 1,
				)
			);
		}
		$locations[ $location ] = $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
	sgc_demo_log( 'Đã tạo menu.' );
}

/**
 * Xóa toàn bộ nội dung mẫu (bài, trang, ảnh, nhóm, menu) – dùng trước khi chạy thật.
 *
 * @return int Số mục đã xóa.
 */
function sgc_demo_remove() {
	$count = 0;
	$posts = get_posts(
		array(
			'post_type'         => array( 'sg_room', 'sg_amenity', 'sg_place', 'sg_offer', 'sg_review', 'post', 'page', 'attachment' ),
			'post_status'       => 'any',
			'posts_per_page'    => -1,
			'fields'            => 'ids',
			'sg_include_merged' => true,
			'meta_key'          => '_sgc_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	);
	foreach ( $posts as $id ) {
		if ( 'attachment' === get_post_type( $id ) ) {
			wp_delete_attachment( $id, true );
		} else {
			wp_delete_post( $id, true );
		}
		++$count;
	}
	foreach ( array( 'sg_guest_type', 'sg_room_feature', 'sg_place_tag', 'category' ) as $tax ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => false,
				'meta_key'   => '_sgc_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		);
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			wp_delete_term( $term->term_id, $tax );
		}
	}
	delete_option( 'sgc_demo_imported' );
	delete_option( 'sgc_pages' );
	return $count;
}

/**
 * Nhập một ảnh từ thư mục demo/media vào Thư viện Media.
 *
 * @param string $file Tên file (không đuôi).
 * @param string $alt  Mô tả ảnh.
 * @return int
 */
function sgc_demo_import_image( $file, $alt = '' ) {
	$existing = sgc_demo_attachment( $file );
	if ( $existing ) {
		return $existing;
	}
	$path = SGC_DIR . 'demo/media/' . sanitize_file_name( $file ) . '.webp';
	if ( ! is_readable( $path ) ) {
		sgc_demo_log( 'Không tìm thấy ảnh mẫu: ' . $file );
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = wp_tempnam( $file . '.webp' );
	copy( $path, $tmp );
	$id = media_handle_sideload(
		array(
			'name'     => 'sunny-grove-' . $file . '.webp',
			'tmp_name' => $tmp,
		),
		0,
		$alt
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		sgc_demo_log( 'Lỗi nhập ảnh ' . $file . ': ' . $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_sgc_demo_file', $file );
	update_post_meta( $id, '_sgc_demo', 1 );
	if ( $alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	}
	wp_update_post(
		array(
			'ID'           => $id,
			'post_excerpt' => 'Ảnh minh họa – CẦN THAY bằng ảnh thật / Illustrative photo – TO REPLACE',
		)
	);
	return (int) $id;
}

/**
 * Chuyển mảng dòng chữ thành block (đoạn văn, tiêu đề "## ", danh sách "- ").
 *
 * @param array $lines Dòng.
 * @return string
 */
function sgc_demo_blocks( array $lines ) {
	$out  = '';
	$list = array();
	$flush = static function () use ( &$list, &$out ) {
		if ( ! $list ) {
			return;
		}
		$out .= '<!-- wp:list --><ul class="wp-block-list">';
		foreach ( $list as $item ) {
			$out .= '<!-- wp:list-item --><li>' . esc_html( $item ) . '</li><!-- /wp:list-item -->';
		}
		$out .= '</ul><!-- /wp:list -->' . "\n\n";
		$list = array();
	};
	foreach ( $lines as $line ) {
		if ( 0 === strpos( $line, '- ' ) ) {
			$list[] = substr( $line, 2 );
			continue;
		}
		$flush();
		if ( 0 === strpos( $line, '## ' ) ) {
			$out .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( substr( $line, 3 ) ) . '</h2><!-- /wp:heading -->' . "\n\n";
		} else {
			$out .= '<!-- wp:paragraph --><p>' . esc_html( $line ) . '</p><!-- /wp:paragraph -->' . "\n\n";
		}
	}
	$flush();
	return $out;
}

/* -------------------------------------------------------------------------
 * Trang quản trị "Nội dung mẫu"
 * ---------------------------------------------------------------------- */

/**
 * Thêm trang con.
 */
function sgc_demo_menu() {
	add_submenu_page( 'sgc-hotel', __( 'Nội dung mẫu', 'sunny-grove-core' ), __( 'Nội dung mẫu', 'sunny-grove-core' ), 'manage_options', 'sgc-demo', 'sgc_demo_page' );
}
add_action( 'admin_menu', 'sgc_demo_menu', 20 );

/**
 * Hiển thị trang.
 */
function sgc_demo_page() {
	$log = get_transient( 'sgc_demo_log' );
	delete_transient( 'sgc_demo_log' );
	echo '<div class="wrap sgc-settings"><h1>' . esc_html__( 'Nội dung mẫu', 'sunny-grove-core' ) . '</h1>';
	if ( $log ) {
		echo '<div class="notice notice-info"><ul>';
		foreach ( (array) $log as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul></div>';
	}
	echo '<div class="sgc-demo-box"><h2>' . esc_html__( 'Nhập nội dung mẫu song ngữ', 'sunny-grove-core' ) . '</h2>';
	echo '<p>' . esc_html__( 'Tạo sẵn trang, phòng, tiện ích, địa điểm, ưu đãi, bài viết, đánh giá, menu – mỗi mục chứa cả Tiếng Việt và English. Cần kích hoạt theme Sunny Grove trước. Có thể bấm lại nhiều lần – nội dung mẫu sẽ được cập nhật, không bị nhân đôi.', 'sunny-grove-core' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'sgc_demo' );
	echo '<input type="hidden" name="action" value="sgc_demo"><input type="hidden" name="do" value="import">';
	submit_button( __( 'Nhập nội dung mẫu', 'sunny-grove-core' ), 'primary', 'submit', false );
	echo '</form></div>';

	echo '<div class="sgc-demo-box"><h2>' . esc_html__( 'Xóa nội dung mẫu', 'sunny-grove-core' ) . '</h2>';
	echo '<p>' . esc_html__( 'Xóa mọi phòng, trang, ảnh, bài viết, đánh giá được tạo bởi nội dung mẫu. Nội dung bạn tự tạo không bị ảnh hưởng. Không thể hoàn tác – hãy sao lưu trước.', 'sunny-grove-core' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'' . esc_js( __( 'Xóa toàn bộ nội dung mẫu?', 'sunny-grove-core' ) ) . '\');">';
	wp_nonce_field( 'sgc_demo' );
	echo '<input type="hidden" name="action" value="sgc_demo"><input type="hidden" name="do" value="remove">';
	submit_button( __( 'Xóa nội dung mẫu', 'sunny-grove-core' ), 'delete', 'submit', false );
	echo '</form></div></div>';
}

/**
 * Xử lý nút bấm.
 */
function sgc_demo_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'sunny-grove-core' ) );
	}
	check_admin_referer( 'sgc_demo' );
	$do = isset( $_POST['do'] ) ? sanitize_key( $_POST['do'] ) : '';
	if ( 'remove' === $do ) {
		/* translators: %d: số mục */
		$log = array( sprintf( __( 'Đã xóa %d mục nội dung mẫu.', 'sunny-grove-core' ), sgc_demo_remove() ) );
	} else {
		$log = sgc_demo_import();
	}
	set_transient( 'sgc_demo_log', $log, 300 );
	wp_safe_redirect( admin_url( 'admin.php?page=sgc-demo' ) );
	exit;
}
add_action( 'admin_post_sgc_demo', 'sgc_demo_action' );
