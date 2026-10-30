<?php
/**
 * Đa ngôn ngữ tự viết (thay Polylang) – mô hình "1 bài – 2 ngôn ngữ".
 *
 * - Ngôn ngữ mặc định không có tiền tố; ngôn ngữ khác có tiền tố (/en/…) – cấu hình ở data/languages.json.
 * - Router: nhận diện tiền tố, đặt ngôn ngữ hiện tại, bỏ tiền tố rồi để WordPress tìm bài như bình thường.
 * - Mọi link nội bộ tự giữ ngôn ngữ đang xem; bài và loại nội dung có slug tiếng Anh riêng (tùy chọn).
 *
 * Trong lúc Polylang còn bật (giai đoạn chuyển dữ liệu), phần router/link đứng yên để không xung đột.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Polylang còn bật? (chỉ dùng trong giai đoạn chuyển đổi).
 *
 * @return bool
 */
function sg_i18n_legacy_active() {
	return defined( 'POLYLANG_VERSION' );
}

/**
 * Cấu hình ngôn ngữ.
 *
 * @return array{default:string,languages:array}
 */
function sg_i18n_config() {
	$config = sgc_data( 'languages' );
	return apply_filters(
		'sg_i18n_config',
		array(
			'default'   => $config['default'] ?? 'vi',
			'languages' => $config['languages'] ?? array(),
		)
	);
}

/**
 * Danh sách ngôn ngữ: slug => [ name, locale, prefix, og_locale ].
 *
 * @return array
 */
function sg_languages() {
	return sg_i18n_config()['languages'];
}

/**
 * Ngôn ngữ mặc định.
 *
 * @return string
 */
function sg_default_lang() {
	return sg_i18n_config()['default'];
}

/**
 * Ngôn ngữ phụ (khác mặc định).
 *
 * @return string[]
 */
function sg_secondary_langs() {
	return array_values( array_diff( array_keys( sg_languages() ), array( sg_default_lang() ) ) );
}

/**
 * Mã ngôn ngữ hợp lệ?
 *
 * @param string $lang Mã.
 * @return bool
 */
function sg_is_lang( $lang ) {
	return is_string( $lang ) && isset( sg_languages()[ $lang ] );
}

/**
 * Ngôn ngữ hiện tại – hàm duy nhất mọi phần code dùng.
 *
 * @return string
 */
function sg_current_lang() {
	if ( ! empty( $GLOBALS['sg_lang_override'] ) ) {
		return $GLOBALS['sg_lang_override'];
	}
	if ( sg_i18n_legacy_active() && function_exists( 'pll_current_language' ) && pll_current_language() ) {
		return pll_current_language();
	}
	return ! empty( $GLOBALS['sg_lang'] ) ? $GLOBALS['sg_lang'] : sg_default_lang();
}

/**
 * Chạy một hàm trong ngôn ngữ $lang (link, nhãn, định dạng số/ngày theo ngôn ngữ đó).
 *
 * @param string   $lang     Mã ngôn ngữ.
 * @param callable $callback Hàm.
 * @param bool     $locale   Đổi cả locale (bản dịch chuỗi, định dạng số).
 * @return mixed
 */
function sg_with_lang( $lang, $callback, $locale = true ) {
	if ( ! sg_is_lang( $lang ) ) {
		return $callback();
	}
	$previous                    = $GLOBALS['sg_lang_override'] ?? '';
	$GLOBALS['sg_lang_override'] = $lang;
	$switched                    = $locale && switch_to_locale( sg_languages()[ $lang ]['locale'] );
	try {
		return $callback();
	} finally {
		if ( $switched ) {
			restore_previous_locale();
		}
		$GLOBALS['sg_lang_override'] = $previous;
	}
}

/**
 * Tiền tố đường dẫn của ngôn ngữ ('' với ngôn ngữ mặc định).
 *
 * @param string $lang Mã.
 * @return string
 */
function sg_lang_prefix( $lang ) {
	$langs = sg_languages();
	return isset( $langs[ $lang ] ) ? trim( (string) $langs[ $lang ]['prefix'], '/' ) : '';
}

/**
 * Đường dẫn gốc của site (ví dụ "/" hoặc "/thu-muc/").
 *
 * @return string
 */
function sg_home_path() {
	$path = wp_parse_url( get_option( 'home' ), PHP_URL_PATH );
	return trailingslashit( $path ? $path : '/' );
}

/* -------------------------------------------------------------------------
 * Router
 * ---------------------------------------------------------------------- */

/**
 * Nhận diện tiền tố ngôn ngữ trên URL và bỏ tiền tố để WordPress xử lý như bình thường.
 * Chạy sớm (plugins_loaded) để locale đúng ngay từ đầu.
 */
function sg_i18n_detect() {
	if ( sg_i18n_legacy_active() || is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$uri  = wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$home = sg_home_path();
	if ( 0 !== strpos( $uri, $home ) ) {
		return;
	}
	$rest = substr( $uri, strlen( $home ) );
	foreach ( sg_secondary_langs() as $lang ) {
		$prefix = sg_lang_prefix( $lang );
		if ( '' === $prefix ) {
			continue;
		}
		if ( preg_match( '#^' . preg_quote( $prefix, '#' ) . '(/|\?|$)#', $rest, $m ) ) {
			$GLOBALS['sg_lang']             = $lang;
			$GLOBALS['sg_i18n_request_uri'] = $uri;
			$stripped                       = substr( $rest, strlen( $prefix ) );
			$stripped                       = ltrim( $stripped, '/' );
			$_SERVER['REQUEST_URI']         = $home . $stripped;
			return;
		}
	}
}
add_action( 'plugins_loaded', 'sg_i18n_detect', 0 );

/**
 * Trả lại URI gốc sau khi WordPress phân tích xong (để redirect_canonical so sánh đúng).
 */
function sg_i18n_restore_uri() {
	if ( isset( $GLOBALS['sg_i18n_request_uri'] ) ) {
		$_SERVER['REQUEST_URI'] = $GLOBALS['sg_i18n_request_uri'];
	}
}
add_action( 'parse_request', 'sg_i18n_restore_uri', 1 );

/**
 * Locale theo ngôn ngữ đang xem (ngoài trang quản trị) – chữ của theme/plugin dùng bản dịch .l10n.php.
 *
 * @param string $locale Locale.
 * @return string
 */
function sg_i18n_locale( $locale ) {
	if ( sg_i18n_legacy_active() || is_admin() ) {
		return $locale;
	}
	$lang = ! empty( $GLOBALS['sg_lang'] ) ? $GLOBALS['sg_lang'] : '';
	return $lang && sg_is_lang( $lang ) ? sg_languages()[ $lang ]['locale'] : $locale;
}
add_filter( 'locale', 'sg_i18n_locale', 1 );

/**
 * Slug loại nội dung theo ngôn ngữ.
 *
 * @param string $type Loại nội dung.
 * @param string $lang Ngôn ngữ.
 * @return string
 */
function sg_type_base( $type, $lang ) {
	$data = sgc_data( 'post-types' );
	$base = sgc_type_slug( $type );
	if ( $lang !== sg_default_lang() && ! empty( $data[ $type ][ 'slug_' . $lang ] ) ) {
		$base = $data[ $type ][ 'slug_' . $lang ];
	}
	return apply_filters( 'sg_type_base', $base, $type, $lang );
}

/**
 * Luật đường dẫn cho slug loại nội dung bản tiếng Anh: /en/rooms/ten-phong/.
 */
function sg_i18n_rewrite_rules() {
	foreach ( array_keys( sgc_data( 'post-types' ) ) as $type ) {
		foreach ( sg_secondary_langs() as $lang ) {
			$base = sg_type_base( $type, $lang );
			if ( $base && $base !== sgc_type_slug( $type ) ) {
				add_rewrite_rule( '^' . preg_quote( $base, '/' ) . '/([^/]+)/?$', 'index.php?post_type=' . $type . '&name=$matches[1]&sg_base_lang=' . $lang, 'top' );
			}
		}
	}
}
add_action( 'init', 'sg_i18n_rewrite_rules', 20 );

/**
 * Biến truy vấn riêng.
 *
 * @param array $vars Biến.
 * @return array
 */
function sg_i18n_query_vars( $vars ) {
	$vars[] = 'sg_base_lang';
	return $vars;
}
add_filter( 'query_vars', 'sg_i18n_query_vars' );

/**
 * Tìm bài theo slug tiếng Anh.
 *
 * @param string          $slug  Slug.
 * @param string|string[] $types Loại nội dung.
 * @param string          $lang  Ngôn ngữ.
 * @return WP_Post|null
 */
function sg_i18n_find_by_slug( $slug, $types, $lang ) {
	$found = get_posts(
		array(
			'post_type'        => (array) $types,
			'post_status'      => 'publish',
			'posts_per_page'   => 1,
			'no_found_rows'    => true,
			'suppress_filters' => true,
			'meta_query'       => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'   => sg_en_key( 'slug', $lang ),
					'value' => sanitize_title( $slug ),
				),
			),
		)
	);
	return $found ? $found[0] : null;
}

/**
 * Đổi slug tiếng Anh trong truy vấn thành bài thật; chặn đường dẫn slug EN khi không có tiền tố /en/.
 *
 * @param array $vars Biến truy vấn.
 * @return array
 */
function sg_i18n_request( $vars ) {
	if ( sg_i18n_legacy_active() ) {
		return $vars;
	}
	$lang = sg_current_lang();
	if ( ! empty( $vars['sg_base_lang'] ) ) {
		if ( $vars['sg_base_lang'] !== $lang ) {
			return array( 'error' => '404' );
		}
		unset( $vars['sg_base_lang'] );
	}
	if ( $lang === sg_default_lang() ) {
		return $vars;
	}

	// Bài viết / loại nội dung: "name".
	if ( ! empty( $vars['name'] ) ) {
		$types = ! empty( $vars['post_type'] ) ? (array) $vars['post_type'] : array( 'post' );
		$post  = sg_i18n_find_by_slug( $vars['name'], $types, $lang );
		if ( $post ) {
			$vars['name'] = $post->post_name;
			if ( 'post' !== $post->post_type ) {
				$vars['post_type'] = $post->post_type;
				$vars[ $post->post_type ] = $post->post_name;
			}
		}
	}
	foreach ( array_keys( sgc_data( 'post-types' ) ) as $type ) {
		if ( ! empty( $vars[ $type ] ) && empty( $vars['name'] ) ) {
			$post = sg_i18n_find_by_slug( $vars[ $type ], $type, $lang );
			if ( $post ) {
				$vars[ $type ] = $post->post_name;
				$vars['name']  = $post->post_name;
			}
		}
	}

	// Trang: "pagename" có thể gồm nhiều cấp cha/con.
	if ( ! empty( $vars['pagename'] ) ) {
		$parts = explode( '/', trim( $vars['pagename'], '/' ) );
		$real  = array();
		foreach ( $parts as $part ) {
			$page   = sg_i18n_find_by_slug( $part, 'page', $lang );
			$real[] = $page ? $page->post_name : $part;
		}
		$vars['pagename'] = implode( '/', $real );
	}
	return $vars;
}
add_filter( 'request', 'sg_i18n_request', 5 );

/* -------------------------------------------------------------------------
 * Link nội bộ
 * ---------------------------------------------------------------------- */

/**
 * Thêm tiền tố ngôn ngữ vào mọi link tạo từ home_url() khi đang ở ngôn ngữ phụ.
 *
 * @param string $url  URL.
 * @param string $path Đường dẫn yêu cầu.
 * @return string
 */
function sg_i18n_home_url( $url, $path ) {
	if ( sg_i18n_legacy_active() ) {
		return $url;
	}
	$lang   = sg_current_lang();
	$prefix = sg_lang_prefix( $lang );
	if ( '' === $prefix ) {
		return $url;
	}
	$path = ltrim( (string) $path, '/' );
	// Không đổi link kỹ thuật: REST, quản trị, tệp tĩnh, feed, sitemap.
	if ( preg_match( '#^(wp-|\?rest_route|' . preg_quote( rest_get_url_prefix(), '#' ) . '|feed|sitemap|robots\.txt|favicon)#', $path ) ) {
		return $url;
	}
	return sg_add_prefix( $url, $prefix );
}
add_filter( 'home_url', 'sg_i18n_home_url', 10, 2 );

/**
 * Thêm tiền tố vào một URL nội bộ (không thêm lần hai).
 *
 * @param string $url    URL.
 * @param string $prefix Tiền tố.
 * @return string
 */
function sg_add_prefix( $url, $prefix ) {
	$home = untrailingslashit( get_option( 'home' ) );
	if ( '' === $prefix || 0 !== strpos( $url, $home ) ) {
		return $url;
	}
	$rest = substr( $url, strlen( $home ) );
	if ( preg_match( '#^/' . preg_quote( $prefix, '#' ) . '(/|\?|\#|$)#', $rest ) ) {
		return $url;
	}
	$rest = '' === $rest ? '/' : $rest;
	return $home . '/' . $prefix . ( '/' === $rest[0] ? $rest : '/' . $rest );
}

/**
 * Bỏ tiền tố ngôn ngữ khỏi URL nội bộ.
 *
 * @param string $url URL.
 * @return array{0:string,1:string} [ URL không tiền tố, ngôn ngữ của URL ]
 */
function sg_strip_prefix( $url ) {
	$home = untrailingslashit( get_option( 'home' ) );
	if ( 0 !== strpos( $url, $home ) ) {
		return array( $url, '' );
	}
	$rest = substr( $url, strlen( $home ) );
	foreach ( sg_secondary_langs() as $lang ) {
		$prefix = sg_lang_prefix( $lang );
		if ( $prefix && preg_match( '#^/' . preg_quote( $prefix, '#' ) . '(?=/|\?|\#|$)#', $rest ) ) {
			$rest = substr( $rest, strlen( $prefix ) + 1 );
			return array( $home . ( '' === $rest ? '/' : $rest ), $lang );
		}
	}
	return array( $url, sg_default_lang() );
}

/**
 * Slug tiếng Anh (nếu có) của bài.
 *
 * @param int    $post_id ID.
 * @param string $lang    Ngôn ngữ.
 * @return string
 */
function sg_post_slug( $post_id, $lang ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}
	if ( $lang !== sg_default_lang() ) {
		$slug = (string) get_post_meta( $post->ID, sg_en_key( 'slug', $lang ), true );
		if ( '' !== $slug ) {
			return $slug;
		}
	}
	return $post->post_name;
}

/**
 * Đường dẫn trang (cha/con) dùng slug theo ngôn ngữ.
 *
 * @param string  $uri  Đường dẫn gốc.
 * @param WP_Post $page Trang.
 * @return string
 */
function sg_i18n_page_uri( $uri, $page ) {
	$lang = sg_current_lang();
	if ( sg_i18n_legacy_active() || $lang === sg_default_lang() || ! $page instanceof WP_Post ) {
		return $uri;
	}
	$parts = array( sg_post_slug( $page->ID, $lang ) );
	foreach ( get_post_ancestors( $page ) as $ancestor ) {
		array_unshift( $parts, sg_post_slug( $ancestor, $lang ) );
	}
	return implode( '/', $parts );
}
add_filter( 'get_page_uri', 'sg_i18n_page_uri', 10, 2 );

/**
 * Link bài viết/loại nội dung: dùng slug loại nội dung và slug bài theo ngôn ngữ.
 *
 * @param string  $url  Link.
 * @param WP_Post $post Bài.
 * @return string
 */
function sg_i18n_post_type_link( $url, $post ) {
	$lang = sg_current_lang();
	if ( sg_i18n_legacy_active() || $lang === sg_default_lang() || ! $post instanceof WP_Post || ! get_option( 'permalink_structure' ) ) {
		return $url;
	}
	$slug = sg_post_slug( $post->ID, $lang );
	if ( isset( sgc_data( 'post-types' )[ $post->post_type ] ) ) {
		$base = sg_type_base( $post->post_type, $lang );
		return home_url( user_trailingslashit( $base . '/' . $slug ) );
	}
	if ( $slug !== $post->post_name ) {
		$url = preg_replace( '#/' . preg_quote( $post->post_name, '#' ) . '(/?)$#', '/' . $slug . '$1', $url );
	}
	return $url;
}
add_filter( 'post_type_link', 'sg_i18n_post_type_link', 10, 2 );
add_filter( 'post_link', 'sg_i18n_post_type_link', 10, 2 );

/**
 * Link của bài/trang ở một ngôn ngữ cụ thể.
 *
 * @param int|WP_Post $post Bài.
 * @param string      $lang Ngôn ngữ.
 * @return string
 */
function sg_permalink( $post, $lang ) {
	return sg_with_lang(
		$lang,
		static function () use ( $post ) {
			return (string) get_permalink( $post );
		},
		false
	);
}

/**
 * Trang chủ ở một ngôn ngữ.
 *
 * @param string $lang Ngôn ngữ.
 * @return string
 */
function sg_home_url( $lang = '' ) {
	return sg_with_lang(
		$lang ? $lang : sg_current_lang(),
		static function () {
			return home_url( '/' );
		},
		false
	);
}

/**
 * Tìm bài ứng với một URL nội bộ (hiểu cả slug tiếng Anh).
 *
 * @param string $url URL.
 * @return int
 */
function sg_url_to_postid( $url ) {
	list( $plain, $lang ) = sg_strip_prefix( $url );
	$path                 = trim( (string) wp_parse_url( $plain, PHP_URL_PATH ), '/' );
	$home                 = trim( sg_home_path(), '/' );
	if ( $home && 0 === strpos( $path, $home ) ) {
		$path = trim( substr( $path, strlen( $home ) ), '/' );
	}
	if ( '' === $path ) {
		return (int) get_option( 'page_on_front' );
	}
	if ( $lang && $lang !== sg_default_lang() ) {
		$parts = explode( '/', $path );
		foreach ( array_keys( sgc_data( 'post-types' ) ) as $type ) {
			if ( 2 === count( $parts ) && sg_type_base( $type, $lang ) === $parts[0] ) {
				$post = sg_i18n_find_by_slug( $parts[1], $type, $lang );
				if ( $post ) {
					return $post->ID;
				}
				$post = get_page_by_path( $parts[1], OBJECT, $type );
				return $post ? $post->ID : 0;
			}
		}
		$post = sg_i18n_find_by_slug( end( $parts ), array( 'page', 'post' ), $lang );
		if ( $post ) {
			return $post->ID;
		}
	}
	$id = sg_with_lang(
		sg_default_lang(),
		static function () use ( $plain ) {
			return url_to_postid( $plain );
		},
		false
	);
	return (int) $id;
}

/**
 * Đổi một URL sang ngôn ngữ khác. URL ngoài site giữ nguyên.
 *
 * @param string $url  URL (bất kỳ ngôn ngữ nào).
 * @param string $lang Ngôn ngữ đích.
 * @return string
 */
function sg_url( $url, $lang ) {
	$home = untrailingslashit( get_option( 'home' ) );
	if ( ! sg_is_lang( $lang ) || ! is_string( $url ) || 0 !== strpos( $url, $home ) ) {
		return $url;
	}
	$parts    = wp_parse_url( $url );
	$suffix   = ( isset( $parts['query'] ) ? '?' . $parts['query'] : '' ) . ( isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '' );
	$post_id  = sg_url_to_postid( $url );
	if ( $post_id ) {
		$target = (int) get_option( 'page_on_front' ) === $post_id ? sg_home_url( $lang ) : sg_permalink( $post_id, $lang );
		return $target . $suffix;
	}
	list( $plain ) = sg_strip_prefix( $url );
	return sg_add_prefix( $plain, sg_lang_prefix( $lang ) );
}

/**
 * URL của trang đang xem.
 *
 * @return string
 */
function sg_current_url() {
	$uri = isset( $GLOBALS['sg_i18n_request_uri'] ) ? $GLOBALS['sg_i18n_request_uri'] : ( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$home = wp_parse_url( get_option( 'home' ) );
	return $home['scheme'] . '://' . $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . $uri;
}

/**
 * Link của trang đang xem ở ngôn ngữ khác (dùng cho nút VI | EN và hreflang).
 *
 * @param string $lang Ngôn ngữ đích.
 * @return string
 */
function sg_translation_url( $lang ) {
	if ( is_front_page() ) {
		return sg_home_url( $lang );
	}
	if ( is_singular() ) {
		return sg_permalink( get_queried_object_id(), $lang );
	}
	if ( is_home() && get_option( 'page_for_posts' ) ) {
		return sg_permalink( (int) get_option( 'page_for_posts' ), $lang );
	}
	return sg_url( remove_query_arg( array( 'sg_dev' ), sg_current_url() ), $lang );
}

/**
 * Ngôn ngữ mặc định ở thẻ <html lang> của trang quản trị không bị ảnh hưởng; ngoài website dùng locale đã đặt.
 * Tên và mô tả site theo ngôn ngữ đang xem (bản dịch lưu trong tùy chọn sg_i18n_strings).
 *
 * @param string $value Giá trị.
 * @param string $option Tên tùy chọn.
 * @return string
 */
function sg_i18n_site_strings( $value, $option ) {
	$lang = sg_current_lang();
	if ( sg_i18n_legacy_active() || $lang === sg_default_lang() || is_admin() ) {
		return $value;
	}
	$strings = (array) get_option( 'sg_i18n_strings', array() );
	return ! empty( $strings[ $lang ][ $option ] ) ? $strings[ $lang ][ $option ] : $value;
}
add_filter( 'option_blogname', 'sg_i18n_site_strings', 10, 2 );
add_filter( 'option_blogdescription', 'sg_i18n_site_strings', 10, 2 );

/**
 * Khóa meta lưu bản tiếng Anh của tiêu đề/tóm tắt/slug: _sg_en_title, _sg_en_excerpt, _sg_en_slug.
 *
 * @param string $field title | excerpt | slug.
 * @param string $lang  Ngôn ngữ.
 * @return string
 */
function sg_en_key( $field, $lang = 'en' ) {
	return '_sg_' . $lang . '_' . $field;
}

/* -------------------------------------------------------------------------
 * Chuyển hướng 301
 * ---------------------------------------------------------------------- */

/**
 * - Đường dẫn cũ (bảng sg_i18n_redirects tạo khi chuyển dữ liệu từ Polylang) → đường dẫn mới.
 * - Bài ở ngôn ngữ phụ mở bằng đường dẫn chưa chuẩn (slug loại nội dung/slug bài tiếng Việt) → đường dẫn chuẩn.
 */
function sg_i18n_redirects() {
	if ( sg_i18n_legacy_active() || is_admin() || is_preview() ) {
		return;
	}
	$current = sg_current_url();
	$path    = (string) wp_parse_url( $current, PHP_URL_PATH );
	if ( is_404() ) {
		$map = (array) get_option( 'sg_i18n_redirects', array() );
		$key = untrailingslashit( $path );
		if ( isset( $map[ $key ] ) ) {
			list( $post_id, $lang ) = array_pad( (array) $map[ $key ], 2, sg_default_lang() );
			$target                 = (int) get_option( 'page_on_front' ) === (int) $post_id ? sg_home_url( $lang ) : sg_permalink( (int) $post_id, $lang );
			if ( $target ) {
				wp_safe_redirect( $target, 301 );
				exit;
			}
		}
		return;
	}
	$lang = sg_current_lang();
	if ( $lang !== sg_default_lang() && is_singular() && ! is_front_page() ) {
		$expected = sg_permalink( get_queried_object_id(), $lang );
		$exp_path = (string) wp_parse_url( $expected, PHP_URL_PATH );
		if ( $expected && untrailingslashit( $exp_path ) !== untrailingslashit( $path ) && ! get_query_var( 'page' ) && ! get_query_var( 'paged' ) ) {
			$query = wp_parse_url( $current, PHP_URL_QUERY );
			wp_safe_redirect( $expected . ( $query ? '?' . $query : '' ), 301 );
			exit;
		}
	}
}
add_action( 'template_redirect', 'sg_i18n_redirects', 0 );
