<?php
/**
 * Hiệu năng phía máy chủ: bộ nhớ đệm nhỏ (tự xóa khi nội dung thay đổi), nạp sẵn dữ liệu theo lô thay vì từng mục.
 *
 * Không đổi giao diện hay tính năng: mọi hàm ở đây chỉ trả lại đúng dữ liệu như trước, nhưng ít truy vấn hơn.
 *
 * Bộ nhớ đệm lưu trong bảng options (tự nạp cùng các tùy chọn khác → 0 truy vấn khi đọc). Có Redis/Memcached
 * thì WordPress tự giữ bảng tùy chọn trong đó. Mọi mục bị xóa khi: lưu/xóa bài (mọi loại), sửa meta, sửa/xóa
 * danh mục, lưu menu, sửa cài đặt Sunny Grove hoặc cài đặt WordPress liên quan, đổi theme/plugin. Đổi cấu hình
 * ngôn ngữ (data/languages.json) hay cập nhật plugin thì "dấu vân tay" đổi nên mục cũ tự bị bỏ qua.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Bộ nhớ đệm
 * ---------------------------------------------------------------------- */

const SGC_CACHE_PREFIX = 'sgc_cache_';

/**
 * Dấu vân tay: đổi khi cập nhật plugin hoặc đổi cấu hình ngôn ngữ → mục cũ không còn dùng.
 *
 * @return string
 */
function sgc_cache_fingerprint() {
	static $fp = null;
	if ( null === $fp ) {
		$fp = substr( md5( SGC_VERSION . '|' . wp_json_encode( sg_i18n_config() ) ), 0, 10 );
	}
	return $fp;
}

/**
 * Có dùng bộ nhớ đệm trong request này không (xem trước bài, Customizer, đang cài đặt → không).
 *
 * @return bool
 */
function sgc_cache_enabled() {
	if ( wp_installing() || ( defined( 'SGC_NO_CACHE' ) && SGC_NO_CACHE ) || ! empty( $GLOBALS['sgc_cache_dirty'] ) ) {
		return false;
	}
	if ( did_action( 'parse_query' ) && ( is_preview() || is_customize_preview() ) ) {
		return false;
	}
	return (bool) apply_filters( 'sgc_cache_enabled', true );
}

/**
 * Lấy giá trị đã lưu, hoặc tính bằng $build rồi lưu lại.
 *
 * @param string   $key   Khóa (chữ thường, số, gạch dưới).
 * @param callable $build Hàm tính giá trị (phải trả về dữ liệu thuần: mảng/chuỗi/số).
 * @param int      $ttl   Thời gian sống tối đa (giây) – lưới an toàn, bình thường mục bị xóa sớm hơn khi nội dung đổi.
 * @return mixed
 */
function sgc_cache_get( $key, $build, $ttl = DAY_IN_SECONDS ) {
	$found = false;
	$value = sgc_cache_peek( $key, $found, $ttl );
	if ( $found ) {
		return $value;
	}
	$value = call_user_func( $build );
	sgc_cache_set( $key, $value );
	return $value;
}

/**
 * Đọc một mục (không tính lại).
 *
 * @param string $key   Khóa.
 * @param bool   $found Gán true nếu có.
 * @param int    $ttl   Thời gian sống tối đa (giây).
 * @return mixed
 */
function sgc_cache_peek( $key, &$found = false, $ttl = DAY_IN_SECONDS ) {
	$found = false;
	if ( ! sgc_cache_enabled() ) {
		return null;
	}
	$key = sanitize_key( $key );
	$mem = &sgc_cache_mem();
	if ( array_key_exists( $key, $mem ) ) {
		$found = true;
		return $mem[ $key ];
	}
	$row = get_option( SGC_CACHE_PREFIX . $key );
	if ( is_array( $row ) && isset( $row['f'], $row['t'] ) && array_key_exists( 'v', $row ) && sgc_cache_fingerprint() === $row['f'] && (int) $row['t'] > time() - $ttl ) {
		$found       = true;
		$mem[ $key ] = $row['v'];
		return $row['v'];
	}
	return null;
}

/**
 * Ghi một mục.
 *
 * @param string $key   Khóa.
 * @param mixed  $value Giá trị (dữ liệu thuần).
 */
function sgc_cache_set( $key, $value ) {
	if ( ! sgc_cache_enabled() ) {
		return;
	}
	$key         = sanitize_key( $key );
	$mem         = &sgc_cache_mem();
	$mem[ $key ] = $value;
	update_option(
		SGC_CACHE_PREFIX . $key,
		array(
			'f' => sgc_cache_fingerprint(),
			't' => time(),
			'v' => $value,
		),
		true
	);
}

/**
 * Bản sao trong bộ nhớ của request hiện tại.
 *
 * @return array
 */
function &sgc_cache_mem() {
	static $mem = array();
	return $mem;
}

/**
 * Xóa toàn bộ bộ nhớ đệm của Sunny Grove.
 */
function sgc_cache_flush() {
	global $wpdb;
	$mem = &sgc_cache_mem();
	$mem = array();
	$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( SGC_CACHE_PREFIX ) . '%' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	foreach ( $names as $name ) {
		delete_option( $name );
	}
	do_action( 'sgc_cache_flushed' );
}

/**
 * Dọn hằng ngày (WP-Cron): danh sách ưu đãi có ngày hôm nay trong khóa nên mỗi ngày sinh khóa mới – xóa hết mỗi ngày
 * để các mục cũ không tích lại trong bảng tùy chọn. Mục nào cần sẽ được tạo lại ở lần mở trang kế tiếp.
 */
function sgc_cache_schedule() {
	if ( ! wp_next_scheduled( 'sgc_cache_daily_flush' ) ) {
		wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'sgc_cache_daily_flush' );
	}
}
add_action( 'init', 'sgc_cache_schedule' );
add_action( 'sgc_cache_daily_flush', 'sgc_cache_flush' );

/**
 * Nội dung vừa thay đổi: xóa ngay, không lưu thêm gì trong phần còn lại của request,
 * và xóa lần nữa lúc kết thúc (phòng request khác lưu bản cũ trong lúc đang lưu).
 */
function sgc_cache_invalidate() {
	if ( ! empty( $GLOBALS['sgc_cache_dirty'] ) ) {
		return;
	}
	$GLOBALS['sgc_cache_dirty'] = true;
	sgc_cache_flush();
	add_action( 'shutdown', 'sgc_cache_flush', 0 );
}

/**
 * Loại bài không ảnh hưởng tới dữ liệu đã lưu.
 *
 * @param int $post_id ID.
 * @return bool
 */
function sgc_cache_ignored_post( $post_id ) {
	$type = get_post_type( $post_id );
	// Yêu cầu đặt phòng / tin nhắn liên hệ do khách gửi không hiện ở trang nào → không xóa bộ nhớ đệm mỗi lần khách gửi form.
	return ! $type || in_array( $type, (array) apply_filters( 'sgc_cache_ignored_post_types', array( 'revision', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_global_styles', 'sgb_request', 'sgb_message' ) ), true );
}

/**
 * Lưu/xóa/đổi trạng thái bài.
 *
 * @param int $post_id ID.
 */
function sgc_cache_on_post( $post_id ) {
	if ( ! sgc_cache_ignored_post( $post_id ) ) {
		sgc_cache_invalidate();
	}
}
foreach ( array( 'save_post', 'deleted_post', 'trashed_post', 'untrashed_post', 'edit_attachment', 'delete_attachment', 'clean_post_cache' ) as $sgc_hook ) {
	add_action( $sgc_hook, 'sgc_cache_on_post' );
}
unset( $sgc_hook );

/**
 * Sửa meta của bài (bỏ qua khóa chỉnh sửa – đổi liên tục khi đang soạn).
 *
 * @param int    $meta_id  ID meta (không dùng).
 * @param int    $post_id  ID bài.
 * @param string $meta_key Khóa.
 */
function sgc_cache_on_post_meta( $meta_id, $post_id, $meta_key ) {
	if ( in_array( $meta_key, array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_encloseme', '_pingme' ), true ) ) {
		return;
	}
	sgc_cache_on_post( $post_id );
}
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $sgc_hook ) {
	add_action( $sgc_hook, 'sgc_cache_on_post_meta', 10, 3 );
}
unset( $sgc_hook );

// Danh mục, meta danh mục, gắn danh mục, menu, theme, plugin.
foreach ( array(
	'created_term',
	'edited_term',
	'delete_term',
	'added_term_meta',
	'updated_term_meta',
	'deleted_term_meta',
	'wp_update_nav_menu',
	'wp_update_nav_menu_item',
	'wp_add_nav_menu_item',
	'wp_delete_nav_menu',
	'switch_theme',
	'activated_plugin',
	'deactivated_plugin',
	'upgrader_process_complete',
) as $sgc_hook ) {
	add_action( $sgc_hook, 'sgc_cache_invalidate' );
	if ( ! in_array( $sgc_hook, array( 'activated_plugin', 'deactivated_plugin', 'upgrader_process_complete' ), true ) ) {
		add_action( $sgc_hook, 'sgc_purge_page_cache_soon' );
	}
}
unset( $sgc_hook );

// Gắn/bỏ danh mục của một bài (bỏ qua yêu cầu đặt phòng, tin nhắn).
add_action( 'set_object_terms', 'sgc_cache_on_post' );

/**
 * Cache toàn trang trên hosting (LiteSpeed Cache): tự xóa khi đổi menu, danh mục, cài đặt Sunny Grove – các thay đổi này
 * hiện ở mọi trang (đầu trang, chân trang) mà plugin cache không tự biết. Lưu bài thì plugin cache tự xóa trang liên quan.
 * Không có plugin cache thì lệnh này không làm gì.
 */
function sgc_purge_page_cache_soon() {
	if ( ! has_action( 'shutdown', 'sgc_purge_page_cache' ) ) {
		add_action( 'shutdown', 'sgc_purge_page_cache', 1 );
	}
}

/**
 * Xóa cache toàn trang (gọi 1 lần cuối request).
 */
function sgc_purge_page_cache() {
	do_action( 'litespeed_purge_all', 'Sunny Grove: đổi menu/danh mục/cài đặt' );
	do_action( 'sgc_purge_page_cache' );
}

/**
 * Sửa cài đặt: tùy chọn của Sunny Grove và các cài đặt WordPress ảnh hưởng tới đường dẫn/menu/trang.
 *
 * @param string $option Tên tùy chọn.
 */
function sgc_cache_on_option( $option ) {
	if ( 0 === strpos( $option, SGC_CACHE_PREFIX ) || 0 === strpos( $option, '_transient' ) || 0 === strpos( $option, '_site_transient' ) ) {
		return;
	}
	if ( preg_match( '/^(sg_|sgc_|sgb_|theme_mods_)/', $option ) || in_array( $option, array( 'home', 'siteurl', 'permalink_structure', 'show_on_front', 'page_on_front', 'page_for_posts', 'wp_page_for_privacy_policy', 'blogname', 'blogdescription', 'site_logo', 'stylesheet', 'template', 'WPLANG', 'date_format', 'time_format', 'timezone_string', 'gmt_offset' ), true ) ) {
		sgc_cache_invalidate();
		if ( 'sgc_perf_version' !== $option ) {
			sgc_purge_page_cache_soon();
		}
	}
}
add_action( 'added_option', 'sgc_cache_on_option' );
add_action( 'updated_option', 'sgc_cache_on_option' );
add_action( 'deleted_option', 'sgc_cache_on_option' );

/* -------------------------------------------------------------------------
 * Tùy chọn hay đọc: tự nạp (autoload) thay vì 1 truy vấn riêng mỗi trang
 * ---------------------------------------------------------------------- */

/**
 * Chạy 1 lần sau khi cập nhật: bật tự nạp cho tùy chọn nhỏ hay đọc; tạo sẵn tùy chọn còn thiếu với giá trị
 * mặc định giống hệt cách code đang đọc (mảng rỗng) để không phải hỏi database mỗi lần.
 */
function sgc_perf_upgrade() {
	if ( '1' === get_option( 'sgc_perf_version' ) ) {
		return;
	}
	foreach ( array( 'sg_security', 'sg_seo' ) as $name ) {
		if ( false === get_option( $name ) ) {
			add_option( $name, array(), '', true );
		}
	}
	wp_set_option_autoload_values(
		array(
			'sg_security'        => true,
			'sg_seo'             => true,
			'sg_i18n_merged_ids' => true,
		)
	);
	update_option( 'sgc_perf_version', '1', true );
}
add_action( 'init', 'sgc_perf_upgrade', 1 );

/* -------------------------------------------------------------------------
 * Tên danh mục theo ngôn ngữ: 1 bảng tra cho mọi danh mục thay vì đọc meta từng danh mục
 * ---------------------------------------------------------------------- */

/**
 * Bảng [term_id => tên] của một khóa meta (vd. _sg_en_name). Giống get_term_meta(…, true): lấy giá trị đầu tiên.
 *
 * @param string $meta_key Khóa meta.
 * @return array
 */
function sgc_term_meta_map( $meta_key ) {
	return sgc_cache_get(
		'term_' . $meta_key,
		static function () use ( $meta_key ) {
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key = %s ORDER BY meta_id ASC", $meta_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$map  = array();
			foreach ( $rows as $row ) {
				if ( ! isset( $map[ (int) $row->term_id ] ) ) {
					$map[ (int) $row->term_id ] = (string) maybe_unserialize( $row->meta_value );
				}
			}
			return $map;
		}
	);
}

/* -------------------------------------------------------------------------
 * Slug tiếng Anh → bài: 1 bảng tra thay vì truy vấn meta mỗi lần mở trang EN
 * ---------------------------------------------------------------------- */

/**
 * Bảng [slug => [[ID, loại], …]] (mới nhất trước, giống get_posts mặc định) của bài đã đăng.
 *
 * @param string $meta_key Khóa meta slug của ngôn ngữ.
 * @return array
 */
function sgc_slug_map( $meta_key ) {
	return sgc_cache_get(
		'slugs_' . $meta_key,
		static function () use ( $meta_key ) {
			global $wpdb;
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT p.ID, p.post_type, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_status = 'publish' ORDER BY p.post_date DESC, p.ID DESC", $meta_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$hide = array_flip( sgc_hidden_merged_ids() );
			$map  = array();
			foreach ( $rows as $row ) {
				if ( isset( $hide[ (int) $row->ID ] ) ) {
					continue;
				}
				$map[ strtolower( (string) $row->meta_value ) ][] = array( (int) $row->ID, (string) $row->post_type );
			}
			return $map;
		}
	);
}

/* -------------------------------------------------------------------------
 * Bài tiếng Anh cũ đã ghép: chỉ thêm "NOT IN (…)" khi còn bài chưa vào Thùng rác
 * ---------------------------------------------------------------------- */

/**
 * ID các bài đã ghép còn hiện (chưa vào Thùng rác / chưa xóa).
 *
 * @return int[]
 */
function sgc_hidden_merged_ids() {
	return sgc_cache_get(
		'merged_live',
		static function () {
			global $wpdb;
			$ids = array_filter( array_map( 'intval', (array) get_option( 'sg_i18n_merged_ids', array() ) ) );
			if ( ! $ids ) {
				return array();
			}
			$in = implode( ',', $ids );
			return array_map( 'intval', $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE ID IN ({$in}) AND post_status <> 'trash'" ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	);
}

/* -------------------------------------------------------------------------
 * Query Loop: nạp sẵn ảnh đại diện cả danh sách; bỏ đếm tổng khi không phân trang
 * ---------------------------------------------------------------------- */

/**
 * Block (và các block con) có chứa block cần tìm không.
 *
 * @param array    $blocks Danh sách block (dạng mảng đã parse).
 * @param string[] $names  Tên block (hoặc tiền tố kết thúc bằng *).
 * @return bool
 */
function sgc_blocks_contain( array $blocks, array $names ) {
	foreach ( $blocks as $block ) {
		$name = (string) ( $block['blockName'] ?? '' );
		foreach ( $names as $want ) {
			if ( $name === $want || ( '*' === substr( $want, -1 ) && 0 === strpos( $name, substr( $want, 0, -1 ) ) ) ) {
				return true;
			}
		}
		if ( ! empty( $block['innerBlocks'] ) && sgc_blocks_contain( $block['innerBlocks'], $names ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Ghi nhớ khối Query Loop nào có phân trang/đếm tổng (trước khi khối con post-template chạy truy vấn).
 *
 * @param array $block Block.
 * @return array
 */
function sgc_query_block_scan( $block ) {
	if ( 'core/query' === ( $block['blockName'] ?? '' ) ) {
		$GLOBALS['sgc_query_needs_total'][ (int) ( $block['attrs']['queryId'] ?? 0 ) ] = sgc_blocks_contain( (array) ( $block['innerBlocks'] ?? array() ), array( 'core/query-pagination*', 'core/query-total' ) );
	}
	return $block;
}
add_filter( 'render_block_data', 'sgc_query_block_scan', 1 );

/**
 * Tham số truy vấn của Query Loop.
 *
 * @param array    $query Tham số WP_Query.
 * @param WP_Block $block Block post-template.
 * @return array
 */
function sgc_query_loop_perf( $query, $block ) {
	$qid = (int) ( $block->context['queryId'] ?? 0 );
	if ( isset( $GLOBALS['sgc_query_needs_total'][ $qid ] ) && ! $GLOBALS['sgc_query_needs_total'][ $qid ] ) {
		$query['no_found_rows'] = true;
		// Không phân trang, không xếp ngẫu nhiên → có thể nhớ danh sách ID.
		if ( 'rand' !== strtolower( (string) ( $query['orderby'] ?? '' ) ) ) {
			$query['sgc_cache_ids'] = true;
		}
	}
	if ( sgc_blocks_contain( (array) ( $block->parsed_block['innerBlocks'] ?? array() ), array( 'sunny-grove/motion-media' ) ) ) {
		$query['sgc_prime_thumbs'] = true;
	}
	return $query;
}
add_filter( 'query_loop_block_query_vars', 'sgc_query_loop_perf', 20, 2 );

/**
 * Khóa bộ nhớ đệm của một danh sách: theo câu SQL (đã gồm ngày hôm nay với ưu đãi → tự đổi mỗi ngày).
 *
 * @param WP_Query $query Truy vấn.
 * @return string
 */
function sgc_loop_cache_key( $query ) {
	return 'loop_' . md5( (string) $query->request . '|' . (int) (bool) $query->get( 'sgc_prime_thumbs' ) );
}

/**
 * Danh sách đã nhớ: trả về ID luôn (bỏ truy vấn tìm ID), nạp bài + danh mục + meta + ảnh đại diện chung 1 lượt.
 *
 * @param array|null $posts Kết quả (null = để WordPress tự truy vấn).
 * @param WP_Query   $query Truy vấn.
 * @return array|null
 */
function sgc_loop_from_cache( $posts, $query ) {
	if ( null !== $posts || ! $query->get( 'sgc_cache_ids' ) || ! $query->get( 'no_found_rows' ) || 'ids' === $query->get( 'fields' ) ) {
		return $posts;
	}
	$found = false;
	$data  = sgc_cache_peek( sgc_loop_cache_key( $query ), $found );
	if ( ! $found || ! is_array( $data ) || ! isset( $data['ids'], $data['thumbs'] ) ) {
		$query->set( 'sgc_store_ids', sgc_loop_cache_key( $query ) );
		return null;
	}
	$ids = array_map( 'intval', $data['ids'] );
	if ( $ids ) {
		// Bài và ảnh đại diện của chúng nạp chung một lượt (ảnh không có danh mục nên không tốn thêm).
		_prime_post_caches( array_merge( $ids, array_map( 'intval', $data['thumbs'] ) ), (bool) $query->get( 'update_post_term_cache' ), true );
	}
	return $ids;
}
add_filter( 'posts_pre_query', 'sgc_loop_from_cache', 10, 2 );

/**
 * Lần đầu: lưu ID của danh sách (và ID ảnh đại diện nếu thẻ có ảnh).
 *
 * @param WP_Post[] $posts Bài.
 * @param WP_Query  $query Truy vấn.
 * @return WP_Post[]
 */
function sgc_loop_store( $posts, $query ) {
	if ( $query->get( 'sgc_store_ids' ) ) {
		$key = (string) $query->get( 'sgc_store_ids' );
		$query->set( 'sgc_store_ids', false );
		$ids    = array_map( 'intval', wp_list_pluck( (array) $posts, 'ID' ) );
		$thumbs = array();
		if ( $ids && $query->get( 'sgc_prime_thumbs' ) ) {
			foreach ( $ids as $id ) {
				$thumbs[] = (int) get_post_thumbnail_id( $id );
			}
		}
		sgc_cache_set(
			$key,
			array(
				'ids'    => $ids,
				'thumbs' => array_values( array_filter( $thumbs ) ),
			)
		);
	}
	return $posts;
}
add_filter( 'the_posts', 'sgc_loop_store', 999, 2 );

/**
 * Khi bắt đầu vòng lặp: nạp ảnh đại diện (bài + meta ảnh) của cả danh sách trong 2 truy vấn.
 *
 * @param WP_Query $query Truy vấn.
 */
function sgc_prime_loop_thumbnails( $query ) {
	if ( $query->get( 'sgc_prime_thumbs' ) || ( $query->is_main_query() && ! $query->is_singular() && $query->post_count > 1 ) ) {
		update_post_thumbnail_cache( $query );
	}
}
add_action( 'loop_start', 'sgc_prime_loop_thumbnails' );

/* -------------------------------------------------------------------------
 * Nạp sẵn các bài dùng ở mọi trang (trang chính sách, trang đặt phòng, ảnh nền banner) trong 1 lần
 * ---------------------------------------------------------------------- */

/**
 * Trước khi dựng giao diện: nạp sẵn bài + meta của các ID hay dùng.
 *
 * @param string $template Đường dẫn template.
 * @return string
 */
function sgc_prime_common_posts( $template ) {
	if ( is_admin() || is_feed() ) {
		return $template;
	}
	$ids = array( (int) get_option( 'wp_page_for_privacy_policy' ), (int) sgc_hotel( 'booking_page' ) );
	// Ảnh nền của banner video trong template và trong nội dung trang đang xem.
	$sources = array( (string) ( $GLOBALS['_wp_current_template_content'] ?? '' ) );
	if ( is_singular() ) {
		$sources[] = (string) get_post_field( 'post_content', get_queried_object_id() );
	}
	foreach ( $sources as $src ) {
		if ( false !== strpos( $src, '"posterId"' ) && preg_match_all( '/"posterId":(\d+)/', $src, $m ) ) {
			$ids = array_merge( $ids, array_map( 'intval', $m[1] ) );
		}
	}
	// Mục menu đã lưu: ngôn ngữ phụ đọc bản dịch tên của từng mục (bộ lọc the_title) → nạp sẵn chung lần này.
	$menus = array_values( array_unique( array_filter( array_map( 'intval', (array) get_nav_menu_locations() ) ) ) );
	if ( sg_current_lang() !== sg_default_lang() ) {
		foreach ( $menus as $menu_id ) {
			$found = false;
			$items = sgc_cache_peek( 'menu_' . $menu_id . '_' . sg_current_lang(), $found );
			if ( $found && is_array( $items ) ) {
				$ids = array_merge( $ids, wp_list_pluck( $items, 'ID' ) );
			}
		}
	}
	$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
	if ( $ids ) {
		_prime_post_caches( $ids, false, true );
	}
	// Menu ở các vị trí: nạp đối tượng menu 1 lần.
	if ( $menus ) {
		_prime_term_caches( $menus );
	}
	return $template;
}
add_filter( 'template_include', 'sgc_prime_common_posts', 999 );

/* -------------------------------------------------------------------------
 * Menu: lưu danh sách mục đã dựng (tên, link theo ngôn ngữ) – trạng thái "đang xem" vẫn tính mỗi lần
 * ---------------------------------------------------------------------- */

/**
 * In menu như wp_nav_menu() nhưng lấy danh sách mục từ bộ nhớ đệm (theo menu + ngôn ngữ).
 *
 * @param array $args Tham số wp_nav_menu().
 * @return string|false
 */
function sgc_nav_menu( array $args ) {
	$locations = get_nav_menu_locations();
	$menu_id   = (int) ( $locations[ $args['theme_location'] ?? '' ] ?? 0 );
	if ( ! $menu_id || ! sgc_cache_enabled() ) {
		return wp_nav_menu( $args );
	}
	$key    = 'menu_' . $menu_id . '_' . sg_current_lang();
	$found  = false;
	$cached = sgc_cache_peek( $key, $found );
	if ( $found && is_array( $cached ) ) {
		// Có sẵn: bỏ qua bước đọc mục menu từ database, đưa thẳng danh sách đã lưu cho wp_nav_menu().
		$items     = array_map(
			static function ( $data ) {
				return new WP_Post( (object) $data );
			},
			$cached
		);
		$empty_obj = static function ( $menu ) use ( $menu_id ) {
			if ( $menu instanceof WP_Term && (int) $menu->term_id === $menu_id ) {
				$menu        = clone $menu;
				$menu->count = 0;
			}
			return $menu;
		};
		$inject    = static function ( $list, $menu ) use ( $items, $menu_id ) {
			return ( $menu instanceof WP_Term && (int) $menu->term_id === $menu_id ) ? $items : $list;
		};
		add_filter( 'wp_get_nav_menu_object', $empty_obj, 999 );
		add_filter( 'wp_get_nav_menu_items', $inject, 999, 2 );
		$html = wp_nav_menu( $args );
		remove_filter( 'wp_get_nav_menu_object', $empty_obj, 999 );
		remove_filter( 'wp_get_nav_menu_items', $inject, 999 );
		return $html;
	}
	// Chưa có: dựng như bình thường và lưu danh sách mục (trước khi WordPress đánh dấu mục "đang xem").
	$store = static function ( $list, $menu ) use ( $key, $menu_id ) {
		if ( $menu instanceof WP_Term && (int) $menu->term_id === $menu_id && is_array( $list ) ) {
			$data = array();
			foreach ( $list as $item ) {
				$data[] = get_object_vars( $item );
			}
			sgc_cache_set( $key, $data );
		}
		return $list;
	};
	add_filter( 'wp_get_nav_menu_items', $store, 999, 2 );
	$html = wp_nav_menu( $args );
	remove_filter( 'wp_get_nav_menu_items', $store, 999 );
	return $html;
}

/* -------------------------------------------------------------------------
 * Phía trình duyệt: bỏ thẻ thừa trong <head> (chức năng REST, nhúng, RSS vẫn giữ nguyên)
 * ---------------------------------------------------------------------- */

/**
 * Bỏ script/CSS emoji (trình duyệt hiện nay tự vẽ emoji), link khám phá oEmbed, shortlink, link REST (api.w.org),
 * link RSD (EditURI) – cả trong <head> lẫn header HTTP.
 */
function sgc_clean_head() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 4 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
	remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
	remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
	remove_action( 'template_redirect', 'rest_output_link_header', 11 );
	remove_action( 'wp_head', 'rsd_link' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'sgc_clean_head' );

/**
 * Ảnh nằm sau banner đầu trang (luôn dưới màn hình đầu): tải lười (loading="lazy"). WordPress tự bỏ qua ảnh
 * không có width/height (vd. icon SVG) và để nguyên 3 ảnh đầu trang → ở đây bổ sung cho các ảnh đó.
 * Ảnh trước banner và ảnh đã có loading/fetchpriority giữ nguyên.
 *
 * @param string $html HTML của block.
 * @return string
 */
function sgc_lazy_below_banner( $html ) {
	if ( ! did_action( 'sgc_banner_rendered' ) || is_admin() || false === strpos( $html, '<img' ) ) {
		return $html;
	}
	$proc = new WP_HTML_Tag_Processor( $html );
	while ( $proc->next_tag( 'img' ) ) {
		if ( null === $proc->get_attribute( 'loading' ) && null === $proc->get_attribute( 'fetchpriority' ) ) {
			$proc->set_attribute( 'loading', 'lazy' );
		}
	}
	return $proc->get_updated_html();
}
foreach ( array( 'core/image', 'core/post-featured-image', 'core/cover', 'core/media-text' ) as $sgc_block ) {
	add_filter( 'render_block_' . $sgc_block, 'sgc_lazy_below_banner' );
}
unset( $sgc_block );
