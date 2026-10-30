<?php
/**
 * Nhập / xóa nội dung mẫu song ngữ (Tiếng Việt + English).
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

/**
 * Ngôn ngữ dùng khi dựng nội dung mẫu (vi/en). Đặt tạm trong lúc nhập để pattern lấy đúng link trang.
 *
 * @param string|null $set Đặt giá trị mới.
 * @return string
 */
function sgc_demo_building_lang( $set = null ) {
	static $lang = '';
	if ( null !== $set ) {
		$lang = $set;
	}
	return $lang;
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
 * Link trang chính theo ngôn ngữ cho pattern của theme.
 *
 * @param string $url Giá trị trước.
 * @param string $key Khóa trang.
 * @return string
 */
function sgc_demo_page_url( $url, $key ) {
	$pages = (array) get_option( 'sgc_pages', array() );
	$lang  = sgc_demo_building_lang() ? sgc_demo_building_lang() : sg_current_lang();
	$id    = $pages[ $key ][ $lang ] ?? ( $pages[ $key ][ sg_default_lang() ] ?? 0 );
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
			'lang'           => '',
		)
	);
	if ( ! $ids ) {
		return 0; // Không ghi nhớ kết quả "chưa có" vì ảnh có thể được nhập ngay sau đó.
	}
	$cache[ $file ] = (int) $ids[0];
	return $cache[ $file ];
}

/* -------------------------------------------------------------------------
 * Form liên hệ theo ngôn ngữ
 * ---------------------------------------------------------------------- */

/**
 * Shortcode [sunny_grove_contact_form]: hiện form Contact Form 7 đúng ngôn ngữ đang xem.
 *
 * @return string
 */
function sgc_contact_form_shortcode() {
	$forms = (array) get_option( 'sgc_contact_forms', array() );
	$id    = $forms[ sg_current_lang() ] ?? ( $forms[ sg_default_lang() ] ?? 0 );
	if ( $id && shortcode_exists( 'contact-form-7' ) ) {
		return do_shortcode( '[contact-form-7 id="' . (int) $id . '"]' );
	}
	// Chưa có form: hiện nút liên hệ nhanh thay thế.
	if ( function_exists( 'render_block' ) ) {
		return render_block(
			array(
				'blockName' => 'sunny-grove/hotel-info',
				'attrs'     => array(
					'fields' => array( 'phone', 'zalo', 'whatsapp', 'email' ),
					'layout' => 'buttons',
				),
			)
		);
	}
	return '';
}
add_shortcode( 'sunny_grove_contact_form', 'sgc_contact_form_shortcode' );

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
 * Tạo ngôn ngữ Tiếng Việt (mặc định) + English trong Polylang.
 *
 * @return bool True nếu Polylang sẵn sàng.
 */
function sgc_demo_setup_languages() {
	if ( ! function_exists( 'PLL' ) || ! PLL() || empty( PLL()->model ) ) {
		sgc_demo_log( 'Polylang chưa được kích hoạt – nội dung chỉ nhập bằng Tiếng Việt.' );
		return false;
	}
	$model    = PLL()->model;
	$existing = $model->get_languages_list( array( 'fields' => 'slug' ) );
	$wanted   = array(
		array(
			'name'       => 'Tiếng Việt',
			'slug'       => 'vi',
			'locale'     => 'vi',
			'rtl'        => false,
			'flag'       => 'vn',
			'term_group' => 0,
		),
		array(
			'name'       => 'English',
			'slug'       => 'en',
			'locale'     => 'en_US',
			'rtl'        => false,
			'flag'       => 'gb',
			'term_group' => 1,
		),
	);
	foreach ( $wanted as $lang ) {
		if ( in_array( $lang['slug'], (array) $existing, true ) ) {
			continue;
		}
		if ( isset( $model->languages ) && method_exists( $model->languages, 'add' ) ) {
			$result = $model->languages->add( $lang );
		} elseif ( method_exists( $model, 'add_language' ) ) {
			$result = $model->add_language( $lang );
		} else {
			$result = new WP_Error( 'sgc', 'Polylang API not found' );
		}
		sgc_demo_log( is_wp_error( $result ) ? 'Lỗi tạo ngôn ngữ ' . $lang['slug'] . ': ' . $result->get_error_message() : 'Đã tạo ngôn ngữ ' . $lang['name'] );
	}
	$model->clean_languages_cache();

	// Cấu hình Polylang: Tiếng Việt mặc định, không thêm /vi/ vào URL, English dùng /en/, không tự đoán ngôn ngữ trình duyệt.
	$set = array(
		'default_lang'  => 'vi',
		'hide_default'  => true,
		'force_lang'    => 1,
		'rewrite'       => true,
		'redirect_lang' => true, // Trang chủ English là /en/ thay vì /en/home/.
		'browser'       => false,
		'media_support' => false,
		'sync'          => array( 'taxonomies', 'post_meta', '_thumbnail_id', 'menu_order', 'post_parent' ),
	);
	foreach ( $set as $key => $value ) {
		if ( method_exists( PLL()->options, 'set' ) ) {
			PLL()->options->set( $key, $value );
		} else {
			PLL()->options[ $key ] = $value;
		}
	}
	if ( method_exists( PLL()->options, 'save' ) ) {
		PLL()->options->save();
	} elseif ( is_array( PLL()->options ) ) {
		update_option( 'polylang', PLL()->options );
	}

	// Đã cấu hình xong thay cho trình hướng dẫn của Polylang → ẩn thông báo "Run the Setup Wizard".
	$dismissed = (array) get_option( 'pll_dismissed_notices', array() );
	if ( ! in_array( 'wizard', $dismissed, true ) ) {
		$dismissed[] = 'wizard';
		update_option( 'pll_dismissed_notices', $dismissed );
	}

	// Gán ngôn ngữ mặc định cho nội dung có sẵn (nếu có).
	if ( method_exists( $model, 'set_language_in_mass' ) ) {
		$model->set_language_in_mass();
	}
	return true;
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

/**
 * Tạo/cập nhật một bài theo khóa mẫu và ngôn ngữ.
 *
 * @param string $type Loại nội dung.
 * @param string $key  Khóa mẫu.
 * @param string $lang Ngôn ngữ.
 * @param array  $args Tham số wp_insert_post.
 * @return int
 */
function sgc_demo_upsert_post( $type, $key, $lang, array $args ) {
	$found = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'lang'           => '',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => '_sgc_demo_key',
					'value' => $type . ':' . $key . ':' . $lang,
				),
			),
		)
	);
	$args = array_merge(
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
	update_post_meta( $id, '_sgc_demo_key', $type . ':' . $key . ':' . $lang );
	update_post_meta( $id, '_sgc_demo', 1 );
	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $id, $lang );
	}
	return (int) $id;
}

/**
 * Liên kết các bản dịch.
 *
 * @param array $ids lang => ID.
 */
function sgc_demo_link_posts( array $ids ) {
	$ids = array_filter( $ids );
	if ( count( $ids ) > 1 && function_exists( 'pll_save_post_translations' ) ) {
		pll_save_post_translations( $ids );
	}
}

/**
 * Tạo nhóm phân loại song ngữ.
 *
 * @param array $langs Ngôn ngữ đang dùng.
 * @return array taxonomy => key => lang => term_id
 */
function sgc_demo_terms( array $langs ) {
	$map = array();
	foreach ( sgc_demo_data()['terms'] as $tax => $terms ) {
		foreach ( $terms as $term ) {
			$ids = array();
			foreach ( $langs as $lang ) {
				$slug  = $term['slug'][ $lang ] ?? sanitize_title( $term[ $lang ] );
				$exist = get_term_by( 'slug', $slug, $tax );
				if ( $exist ) {
					$tid = (int) $exist->term_id;
				} else {
					$res = wp_insert_term( $term[ $lang ], $tax, array( 'slug' => $slug ) );
					if ( is_wp_error( $res ) ) {
						sgc_demo_log( 'Lỗi tạo nhóm ' . $term[ $lang ] . ': ' . $res->get_error_message() );
						continue;
					}
					$tid = (int) $res['term_id'];
				}
				update_term_meta( $tid, '_sgc_demo', 1 );
				if ( function_exists( 'pll_set_term_language' ) ) {
					pll_set_term_language( $tid, $lang );
				}
				$ids[ $lang ]                      = $tid;
				$map[ $tax ][ $term['key'] ][ $lang ] = $tid;
			}
			if ( count( $ids ) > 1 && function_exists( 'pll_save_term_translations' ) ) {
				pll_save_term_translations( $ids );
			}
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
 * @param string $lang    Ngôn ngữ.
 */
function sgc_demo_set_terms( $post_id, $tax, array $keys, array $terms, $lang ) {
	$ids = array();
	foreach ( $keys as $key ) {
		if ( isset( $terms[ $tax ][ $key ][ $lang ] ) ) {
			$ids[] = $terms[ $tax ][ $key ][ $lang ];
		}
	}
	wp_set_object_terms( $post_id, $ids, $tax );
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

	$data      = sgc_demo_data();
	$has_pll   = sgc_demo_setup_languages();
	$langs     = $has_pll ? array( 'vi', 'en' ) : array( sg_default_lang() );
	$locales   = array(
		'vi' => 'vi',
		'en' => 'en_US',
	);
	$main_lang = $langs[0];

	// Giá trị mặc định cho ngôn ngữ mới (nhãn, thông tin khách sạn, chữ trên form).
	sgc_seed_options();
	if ( function_exists( 'sgb_seed_options' ) ) {
		sgb_seed_options();
	}

	// Ảnh.
	$files = array();
	foreach ( $data['media'] as $m ) {
		$files[ $m['file'] ] = $m[ $main_lang ] ?? '';
	}
	foreach ( array( 'rooms', 'amenities', 'places', 'offers', 'posts' ) as $group ) {
		foreach ( $data[ $group ] as $item ) {
			if ( ! empty( $item['image'] ) && ! isset( $files[ $item['image'] ] ) ) {
				$files[ $item['image'] ] = $item[ $main_lang ]['title'] ?? '';
			}
			foreach ( (array) ( $item['gallery'] ?? array() ) as $g ) {
				if ( ! isset( $files[ $g ] ) ) {
					$files[ $g ] = $item[ $main_lang ]['title'] ?? '';
				}
			}
		}
	}
	foreach ( $files as $file => $alt ) {
		sgc_demo_import_image( $file, $alt );
	}
	sgc_demo_log( 'Đã nhập ' . count( $files ) . ' ảnh mẫu.' );

	$terms = sgc_demo_terms( $langs );
	$thumb = static function ( $post_id, $file ) {
		$att = sgc_demo_attachment( $file );
		if ( $att ) {
			set_post_thumbnail( $post_id, $att );
		}
	};
	$gallery = static function ( array $files ) {
		return implode( ',', array_filter( array_map( 'sgc_demo_attachment', $files ) ) );
	};

	// Phòng.
	foreach ( $data['rooms'] as $room ) {
		$ids = array();
		foreach ( $langs as $lang ) {
			$c  = $room[ $lang ];
			$id = sgc_demo_upsert_post(
				'sg_room',
				$room['key'],
				$lang,
				array(
					'post_title'   => $c['title'],
					'post_excerpt' => $c['excerpt'],
					'post_content' => sgc_demo_blocks( $c['content'] ),
					'post_name'    => sanitize_title( $c['title'] ) . ( 'vi' === $lang ? '' : '-' . $lang ),
					'menu_order'   => (int) $room['order'],
				)
			);
			if ( ! $id ) {
				continue;
			}
			foreach ( $room['meta'] as $k => $v ) {
				update_post_meta( $id, $k, $v );
			}
			foreach ( $room['i18n_meta'] as $k => $v ) {
				update_post_meta( $id, $k, $v[ $lang ] );
			}
			update_post_meta( $id, 'sg_gallery', $gallery( $room['gallery'] ) );
			$thumb( $id, $room['image'] );
			foreach ( $room['terms'] as $tax => $keys ) {
				sgc_demo_set_terms( $id, $tax, $keys, $terms, $lang );
			}
			$ids[ $lang ] = $id;
		}
		sgc_demo_link_posts( $ids );
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['rooms'] ) . ' phòng.' );

	// Tiện ích.
	foreach ( $data['amenities'] as $item ) {
		$ids = array();
		foreach ( $langs as $lang ) {
			$c  = $item[ $lang ];
			$id = sgc_demo_upsert_post(
				'sg_amenity',
				$item['key'],
				$lang,
				array(
					'post_title'   => $c['title'],
					'post_excerpt' => $c['excerpt'],
					'post_content' => sgc_demo_blocks( $c['content'] ),
					'post_name'    => sanitize_title( $c['title'] ),
					'menu_order'   => (int) $item['order'],
				)
			);
			if ( ! $id ) {
				continue;
			}
			update_post_meta( $id, 'sg_hours', $c['hours'] );
			update_post_meta( $id, 'sg_short', $c['short'] );
			update_post_meta( $id, 'sg_gallery', $gallery( $item['gallery'] ) );
			if ( $item['video'] ) {
				update_post_meta( $id, 'sg_video', $item['video'] );
			}
			$thumb( $id, $item['image'] );
			$ids[ $lang ] = $id;
		}
		sgc_demo_link_posts( $ids );
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['amenities'] ) . ' tiện ích.' );

	// Địa điểm.
	foreach ( $data['places'] as $item ) {
		$ids = array();
		foreach ( $langs as $lang ) {
			$c  = $item[ $lang ];
			$id = sgc_demo_upsert_post(
				'sg_place',
				$item['key'],
				$lang,
				array(
					'post_title'   => $c['title'],
					'post_excerpt' => $c['excerpt'],
					'post_content' => sgc_demo_blocks( $c['content'] ),
					'post_name'    => sanitize_title( $c['title'] ),
					'menu_order'   => (int) $item['order'],
				)
			);
			if ( ! $id ) {
				continue;
			}
			foreach ( $item['meta'] as $k => $v ) {
				update_post_meta( $id, $k, $v );
			}
			if ( $c['compare'] ) {
				update_post_meta( $id, 'sg_compare', $c['compare'] );
			}
			$thumb( $id, $item['image'] );
			sgc_demo_set_terms( $id, 'sg_place_tag', $item['tags'], $terms, $lang );
			$ids[ $lang ] = $id;
		}
		sgc_demo_link_posts( $ids );
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['places'] ) . ' địa điểm.' );

	// Ưu đãi.
	foreach ( $data['offers'] as $item ) {
		$ids = array();
		foreach ( $langs as $lang ) {
			$c  = $item[ $lang ];
			$id = sgc_demo_upsert_post(
				'sg_offer',
				$item['key'],
				$lang,
				array(
					'post_title'   => $c['title'],
					'post_excerpt' => $c['excerpt'],
					'post_content' => sgc_demo_blocks( $c['content'] ),
					'post_name'    => sanitize_title( $c['title'] ),
					'menu_order'   => (int) $item['order'],
				)
			);
			if ( ! $id ) {
				continue;
			}
			foreach ( array( 'price' => 'sg_price', 'old_price' => 'sg_old_price', 'badge' => 'sg_badge', 'conditions' => 'sg_conditions' ) as $src => $meta ) {
				if ( '' !== $c[ $src ] ) {
					update_post_meta( $id, $meta, $c[ $src ] );
				} else {
					delete_post_meta( $id, $meta );
				}
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
			$thumb( $id, $item['image'] );
			sgc_demo_set_terms( $id, 'sg_guest_type', $item['guest'], $terms, $lang );
			$ids[ $lang ] = $id;
		}
		sgc_demo_link_posts( $ids );
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['offers'] ) . ' ưu đãi.' );

	// Bài viết.
	foreach ( $data['posts'] as $item ) {
		$ids = array();
		foreach ( $langs as $lang ) {
			$c  = $item[ $lang ];
			$id = sgc_demo_upsert_post(
				'post',
				$item['key'],
				$lang,
				array(
					'post_title'   => $c['title'],
					'post_excerpt' => $c['excerpt'],
					'post_content' => sgc_demo_blocks( $c['content'] ),
					'post_name'    => sanitize_title( $c['title'] ),
					'post_date'    => wp_date( 'Y-m-d H:i:s', time() - (int) $item['days_ago'] * DAY_IN_SECONDS ),
				)
			);
			if ( ! $id ) {
				continue;
			}
			$thumb( $id, $item['image'] );
			sgc_demo_set_terms( $id, 'category', array( $item['category'] ), $terms, $lang );
			$ids[ $lang ] = $id;
		}
		sgc_demo_link_posts( $ids );
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['posts'] ) . ' bài viết.' );

	// Trang: lượt 1 tạo trang trống để có link, lượt 2 dựng nội dung từ pattern theo từng ngôn ngữ.
	$pages = array();
	foreach ( $data['pages'] as $page ) {
		$ids = array();
		foreach ( $langs as $lang ) {
			$ids[ $lang ] = sgc_demo_upsert_post(
				'page',
				$page['key'],
				$lang,
				array(
					'post_title' => $page[ $lang ]['title'],
					'post_name'  => $page[ $lang ]['slug'],
				)
			);
		}
		sgc_demo_link_posts( $ids );
		$pages[ $page['key'] ] = $ids;
	}
	update_option( 'sgc_pages', $pages );

	$general                 = (array) get_option( 'sgc_hotel', array() );
	$general['booking_page'] = (string) $pages['booking'][ $main_lang ];
	update_option( 'sgc_hotel', $general );
	if ( function_exists( 'sgb_general' ) ) {
		$sgb                 = (array) get_option( 'sgb_general', array() );
		$sgb['booking_page'] = (string) $pages['booking'][ $main_lang ];
		update_option( 'sgb_general', $sgb );
	}
	update_option( 'sgc_demo_imported', time() ); // Bật video mẫu cho pattern.

	foreach ( $langs as $lang ) {
		$switched = switch_to_locale( $locales[ $lang ] ?? get_locale() );
		sgc_demo_building_lang( $lang );
		foreach ( $data['pages'] as $page ) {
			wp_update_post(
				wp_slash(
					array(
						'ID'           => $pages[ $page['key'] ][ $lang ],
						'post_content' => sunny_grove_pattern( $page['pattern'] ),
					)
				)
			);
		}
		if ( $switched ) {
			restore_previous_locale();
		}
	}
	sgc_demo_building_lang( '' );
	// Lưu nội dung có thể khiến Polylang gán lại ngôn ngữ theo ngữ cảnh đang chạy (ngôn ngữ đang xem / bộ lọc
	// ngôn ngữ của quản trị) → gán lại ngôn ngữ và liên kết bản dịch cho chắc chắn.
	foreach ( $pages as $ids ) {
		foreach ( array_filter( $ids ) as $lang => $id ) {
			if ( function_exists( 'pll_set_post_language' ) ) {
				pll_set_post_language( $id, $lang );
			}
		}
		sgc_demo_link_posts( $ids );
	}
	sgc_demo_log( 'Đã tạo ' . count( $data['pages'] ) . ' trang cho mỗi ngôn ngữ.' );

	// Trang chủ tĩnh.
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $pages['home'][ $main_lang ] );
	update_option( 'page_for_posts', 0 );
	if ( $has_pll ) {
		PLL()->model->clean_languages_cache(); // Polylang lưu trang chủ của từng ngôn ngữ trong bộ nhớ đệm.
	}

	// Menu.
	sgc_demo_menus( $data['menus'], $pages, $langs );

	// Form liên hệ.
	sgc_demo_contact_forms( $data['contact_form'], $langs );

	// Thông tin chung của site.
	update_option( 'blogname', 'Sunny Grove' );
	update_option( 'blogdescription', sgc_hotel( 'slogan', $main_lang ) );
	// Dịch khẩu hiệu cho các ngôn ngữ khác (sửa được tại Ngôn ngữ → Bản dịch chuỗi).
	if ( $has_pll && class_exists( 'PLL_MO' ) ) {
		foreach ( array_slice( $langs, 1 ) as $lang_slug ) {
			$lang_obj = PLL()->model->get_language( $lang_slug );
			$slogan   = sgc_hotel( 'slogan', $lang_slug );
			if ( $lang_obj && $slogan ) {
				$mo = new PLL_MO();
				$mo->import_from_db( $lang_obj );
				$mo->add_entry( $mo->make_entry( sgc_hotel( 'slogan', $main_lang ), $slogan ) );
				$mo->export_to_db( $lang_obj );
			}
		}
	}
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

	// Xóa bộ quy tắc đường dẫn để WordPress dựng lại ở lần tải trang kế tiếp, khi Polylang đã nạp đủ
	// ngôn ngữ mới (nếu dựng ngay trong lúc nhập, đường dẫn /en/ sẽ bị thiếu).
	delete_option( 'rewrite_rules' );
	kses_init_filters();
	sgc_demo_log( 'Hoàn tất! Mở trang chủ để xem website.' );
	return $GLOBALS['sgc_demo_log'];
}

/**
 * Tạo menu theo ngôn ngữ và gán vào vị trí (Polylang).
 *
 * @param array $menus Cấu trúc menu.
 * @param array $pages key => lang => ID.
 * @param array $langs Ngôn ngữ.
 */
function sgc_demo_menus( array $menus, array $pages, array $langs ) {
	$names     = array(
		'primary' => array(
			'vi' => 'Menu chính (Tiếng Việt)',
			'en' => 'Main menu (English)',
		),
		'footer'  => array(
			'vi' => 'Menu chân trang (Tiếng Việt)',
			'en' => 'Footer menu (English)',
		),
	);
	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	$pll_menus = array();
	foreach ( $menus as $location => $keys ) {
		foreach ( $langs as $lang ) {
			$name = $names[ $location ][ $lang ] ?? $location . ' ' . $lang;
			$menu = wp_get_nav_menu_object( $name );
			if ( $menu ) {
				foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
					wp_delete_post( $item->ID, true );
				}
				$menu_id = (int) $menu->term_id;
			} else {
				$menu_id = (int) wp_create_nav_menu( $name );
			}
			foreach ( $keys as $i => $key ) {
				if ( empty( $pages[ $key ][ $lang ] ) ) {
					continue;
				}
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-object-id' => $pages[ $key ][ $lang ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
						'menu-item-position'  => $i + 1,
					)
				);
			}
			$pll_menus[ $location ][ $lang ] = $menu_id;
			if ( $lang === $langs[0] ) {
				$locations[ $location ] = $menu_id;
			}
		}
	}
	set_theme_mod( 'nav_menu_locations', $locations );

	if ( function_exists( 'PLL' ) && PLL() && isset( PLL()->options ) ) {
		$theme    = get_stylesheet();
		$current  = PLL()->options['nav_menus'] ?? array();
		$current  = is_array( $current ) ? $current : array();
		$current[ $theme ] = $pll_menus;
		if ( method_exists( PLL()->options, 'set' ) ) {
			PLL()->options->set( 'nav_menus', $current );
			if ( method_exists( PLL()->options, 'save' ) ) {
				PLL()->options->save();
			}
		} else {
			$opt              = (array) get_option( 'polylang', array() );
			$opt['nav_menus'] = $current;
			update_option( 'polylang', $opt );
		}
	}
	sgc_demo_log( 'Đã tạo menu cho mỗi ngôn ngữ.' );
}

/**
 * Tạo form Contact Form 7 cho từng ngôn ngữ (nếu plugin có cài).
 *
 * @param array $forms Mẫu form.
 * @param array $langs Ngôn ngữ.
 */
function sgc_demo_contact_forms( array $forms, array $langs ) {
	if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
		sgc_demo_log( 'Chưa cài Contact Form 7 – trang Liên hệ sẽ hiện nút liên hệ nhanh thay cho form.' );
		return;
	}
	$saved = (array) get_option( 'sgc_contact_forms', array() );
	foreach ( $langs as $lang ) {
		if ( ! empty( $saved[ $lang ] ) && get_post( $saved[ $lang ] ) ) {
			continue;
		}
		$f    = $forms[ $lang ] ?? $forms['vi'];
		$form = WPCF7_ContactForm::get_template(
			array(
				'title'  => $f['title'],
				'locale' => 'vi' === $lang ? 'vi' : 'en_US',
			)
		);
		$mail            = $form->prop( 'mail' );
		$mail['subject'] = $f['subject'];
		$form->set_properties(
			array(
				'form' => $f['form'],
				'mail' => $mail,
			)
		);
		$id = $form->save();
		if ( $id ) {
			$saved[ $lang ] = (int) $id;
			update_post_meta( $id, '_sgc_demo', 1 );
		}
	}
	update_option( 'sgc_contact_forms', $saved );
	sgc_demo_log( 'Đã tạo form liên hệ cho mỗi ngôn ngữ.' );
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
			'post_type'      => array( 'sg_room', 'sg_amenity', 'sg_place', 'sg_offer', 'post', 'page', 'attachment', 'wpcf7_contact_form' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'lang'           => '',
			'meta_key'       => '_sgc_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
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
				'lang'       => '',
			)
		);
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			wp_delete_term( $term->term_id, $tax );
		}
	}
	delete_option( 'sgc_demo_imported' );
	delete_option( 'sgc_pages' );
	delete_option( 'sgc_contact_forms' );
	return $count;
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
	echo '<p>' . esc_html__( 'Tạo sẵn trang, phòng, tiện ích, địa điểm, ưu đãi, bài viết, menu bằng Tiếng Việt và English để xem website ngay. Cần kích hoạt theme Sunny Grove và plugin Polylang trước. Có thể bấm lại nhiều lần – nội dung mẫu sẽ được cập nhật, không bị nhân đôi.', 'sunny-grove-core' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'sgc_demo' );
	echo '<input type="hidden" name="action" value="sgc_demo"><input type="hidden" name="do" value="import">';
	submit_button( __( 'Nhập nội dung mẫu', 'sunny-grove-core' ), 'primary', 'submit', false );
	echo '</form></div>';

	echo '<div class="sgc-demo-box"><h2>' . esc_html__( 'Xóa nội dung mẫu', 'sunny-grove-core' ) . '</h2>';
	echo '<p>' . esc_html__( 'Xóa mọi phòng, trang, ảnh, bài viết được tạo bởi nội dung mẫu. Nội dung bạn tự tạo không bị ảnh hưởng. Không thể hoàn tác – hãy sao lưu trước.', 'sunny-grove-core' ) . '</p>';
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
