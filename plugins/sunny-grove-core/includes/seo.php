<?php
/**
 * SEO tự viết (không cần Rank Math / Yoast):
 * - Ô SEO song ngữ cho từng bài: tiêu đề, mô tả, ảnh chia sẻ (trống thì tự lấy tiêu đề / tóm tắt / ảnh đại diện).
 * - <head>: title, meta description, canonical, hreflang (vi, en, x-default), Open Graph, Twitter Card.
 * - Schema JSON-LD: Hotel, HotelRoom, Offer, TouristAttraction, BreadcrumbList, Article – theo ngôn ngữ đang xem.
 * - Sitemap có sẵn của WordPress (wp-sitemap.xml) thêm phần đường dẫn tiếng Anh.
 * - Cài đặt kỹ thuật (Search Console, Analytics, chặn lập chỉ mục) chỉ quản trị viên sửa được.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cài đặt SEO kỹ thuật.
 *
 * @param string $key Khóa.
 * @return string
 */
function sg_seo_option( $key ) {
	$opts = (array) get_option( 'sg_seo', array() );
	return (string) ( $opts[ $key ] ?? '' );
}

/**
 * Khóa meta SEO theo ngôn ngữ (ngôn ngữ mặc định không có hậu tố).
 *
 * @param string $field title | desc.
 * @param string $lang  Ngôn ngữ.
 * @return string
 */
function sg_seo_key( $field, $lang ) {
	return '_sg_seo_' . $field . ( sg_default_lang() === $lang ? '' : '_' . $lang );
}

/**
 * Đăng ký meta SEO.
 */
function sg_seo_register_meta() {
	$auth = static function ( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	};
	foreach ( sg_i18n_post_types() as $type ) {
		foreach ( array_keys( sg_languages() ) as $lang ) {
			foreach ( array( 'title', 'desc' ) as $field ) {
				register_post_meta(
					$type,
					sg_seo_key( $field, $lang ),
					array(
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => 'title' === $field ? 'sanitize_text_field' : 'sanitize_textarea_field',
						'auth_callback'     => $auth,
					)
				);
			}
		}
		register_post_meta(
			$type,
			'_sg_seo_image',
			array(
				'type'              => 'integer',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => $auth,
			)
		);
	}
}
add_action( 'init', 'sg_seo_register_meta', 8 );

/* -------------------------------------------------------------------------
 * Hộp "SEO" dưới trình soạn thảo
 * ---------------------------------------------------------------------- */

/**
 * Thêm hộp SEO.
 */
function sg_seo_meta_box() {
	foreach ( sg_i18n_post_types() as $type ) {
		add_meta_box( 'sg_seo', __( 'SEO – hiển thị trên Google và mạng xã hội', 'sunny-grove-core' ), 'sg_seo_render_box', $type, 'normal', 'low' );
	}
}
add_action( 'add_meta_boxes', 'sg_seo_meta_box' );

/**
 * Nội dung hộp SEO.
 *
 * @param WP_Post $post Bài.
 */
function sg_seo_render_box( $post ) {
	wp_nonce_field( 'sg_seo_save', 'sg_seo_nonce' );
	echo '<p class="description">' . esc_html__( 'Tùy chọn. Để trống thì website tự lấy tiêu đề, tóm tắt và ảnh đại diện của bài.', 'sunny-grove-core' ) . '</p>';
	$rows = array(
		'title' => array( __( 'Tiêu đề SEO', 'sunny-grove-core' ), __( 'Nên dưới 60 ký tự.', 'sunny-grove-core' ) ),
		'desc'  => array( __( 'Mô tả SEO', 'sunny-grove-core' ), __( 'Nên 120–160 ký tự, hiện dưới tiêu đề trên Google.', 'sunny-grove-core' ) ),
	);
	foreach ( $rows as $field => $info ) {
		echo '<div class="sgc-field"><label><strong>' . esc_html( $info[0] ) . '</strong></label><div class="sgc-bilingual-field">';
		foreach ( sg_languages() as $lang => $meta ) {
			$name  = sg_seo_key( $field, $lang );
			$value = (string) get_post_meta( $post->ID, $name, true );
			echo '<div class="sgc-bilingual-field__col"><span class="sgc-bilingual-field__lang">' . esc_html( $meta['name'] ) . '</span>';
			if ( 'desc' === $field ) {
				printf( '<textarea name="%1$s" rows="2" maxlength="320" lang="%3$s">%2$s</textarea>', esc_attr( $name ), esc_textarea( $value ), esc_attr( $meta['locale'] ) );
			} else {
				printf( '<input type="text" class="widefat" name="%1$s" value="%2$s" maxlength="120" lang="%3$s">', esc_attr( $name ), esc_attr( $value ), esc_attr( $meta['locale'] ) );
			}
			echo '</div>';
		}
		echo '</div><p class="description">' . esc_html( $info[1] ) . '</p></div>';
	}
	$image = (int) get_post_meta( $post->ID, '_sg_seo_image', true );
	echo '<div class="sgc-field sgc-field--video"><label><strong>' . esc_html__( 'Ảnh chia sẻ mạng xã hội (dùng chung cho mọi ngôn ngữ)', 'sunny-grove-core' ) . '</strong></label>';
	printf( '<input type="text" class="widefat sgc-video-input" name="_sg_seo_image" value="%s" placeholder="%s"> ', $image ? (int) $image : '', esc_attr__( 'ID ảnh – bấm nút bên cạnh để chọn', 'sunny-grove-core' ) );
	printf( '<button type="button" class="button sgc-image-pick">%s</button>', esc_html__( 'Chọn ảnh', 'sunny-grove-core' ) );
	echo '<p class="description">' . esc_html__( 'Nên dùng ảnh ngang 1200 × 630px. Để trống thì dùng ảnh đại diện.', 'sunny-grove-core' ) . '</p></div>';
}

/**
 * Thư viện ảnh và nút "Chọn ảnh" cho hộp SEO (mọi loại bài).
 *
 * @param string $hook Trang quản trị.
 */
function sg_seo_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! in_array( $screen->post_type, sg_i18n_post_types(), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'sgc-admin', SGC_URL . 'assets/admin.css', array(), SGC_VERSION );
	wp_add_inline_script(
		'media-editor',
		'jQuery(function($){$(document).on("click",".sgc-image-pick",function(e){e.preventDefault();var input=$(this).prev("input"),frame=wp.media({title:' . wp_json_encode( __( 'Chọn ảnh chia sẻ', 'sunny-grove-core' ) ) . ',library:{type:"image"},multiple:false});frame.on("select",function(){input.val(frame.state().get("selection").first().get("id")).trigger("change");});frame.open();});});'
	);
}
add_action( 'admin_enqueue_scripts', 'sg_seo_admin_assets' );

/**
 * Lưu hộp SEO.
 *
 * @param int $post_id ID.
 */
function sg_seo_save( $post_id ) {
	if ( ! isset( $_POST['sg_seo_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sg_seo_nonce'] ), 'sg_seo_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array_keys( sg_languages() ) as $lang ) {
		foreach ( array( 'title', 'desc' ) as $field ) {
			$key = sg_seo_key( $field, $lang );
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw   = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$value = 'title' === $field ? sanitize_text_field( $raw ) : sanitize_textarea_field( $raw );
			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
	if ( isset( $_POST['_sg_seo_image'] ) ) {
		$image = absint( $_POST['_sg_seo_image'] );
		if ( $image ) {
			update_post_meta( $post_id, '_sg_seo_image', $image );
		} else {
			delete_post_meta( $post_id, '_sg_seo_image' );
		}
	}
}
add_action( 'save_post', 'sg_seo_save' );

/* -------------------------------------------------------------------------
 * Dữ liệu SEO của trang đang xem
 * ---------------------------------------------------------------------- */

/**
 * Bài đang xem (trang đơn, trang chủ tĩnh, trang blog).
 *
 * @return int
 */
function sg_seo_object_id() {
	if ( is_singular() || is_front_page() ) {
		return (int) get_queried_object_id();
	}
	if ( is_home() && get_option( 'page_for_posts' ) ) {
		return (int) get_option( 'page_for_posts' );
	}
	return 0;
}

/**
 * Cắt chữ gọn cho mô tả.
 *
 * @param string $text Chữ.
 * @param int    $max  Số ký tự tối đa.
 * @return string
 */
function sg_seo_trim( $text, $max = 160 ) {
	$text = sg_i18n_normalize( strip_shortcodes( (string) $text ) );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}
	$cut = mb_substr( $text, 0, $max - 1 );
	$pos = mb_strrpos( $cut, ' ' );
	return rtrim( $pos ? mb_substr( $cut, 0, $pos ) : $cut, ' ,.;:–-' ) . '…';
}

/**
 * Mô tả SEO.
 *
 * @param int    $post_id ID (0 = trang không phải bài).
 * @param string $lang    Ngôn ngữ.
 * @return string
 */
function sg_seo_description( $post_id, $lang ) {
	if ( $post_id ) {
		$desc = (string) get_post_meta( $post_id, sg_seo_key( 'desc', $lang ), true );
		if ( '' !== $desc ) {
			return sg_seo_trim( $desc );
		}
		$excerpt = sg_with_lang(
			$lang,
			static function () use ( $post_id ) {
				$short = sgc_meta_value( $post_id, 'short' );
				return '' !== $short ? $short : get_the_excerpt( $post_id );
			},
			false
		);
		if ( '' !== trim( $excerpt ) && (int) get_option( 'page_on_front' ) !== $post_id ) {
			return sg_seo_trim( $excerpt );
		}
	}
	$about = sgc_hotel( 'footer_about', $lang );
	return sg_seo_trim( '' !== $about ? $about : get_bloginfo( 'description' ) );
}

/**
 * Ảnh chia sẻ: [ url, width, height ].
 *
 * @param int $post_id ID.
 * @return array
 */
function sg_seo_image( $post_id ) {
	$id = $post_id ? (int) get_post_meta( $post_id, '_sg_seo_image', true ) : 0;
	if ( ! $id && $post_id ) {
		$id = (int) get_post_thumbnail_id( $post_id );
	}
	if ( ! $id ) {
		$id = (int) sg_seo_option( 'default_image' );
	}
	if ( ! $id && $post_id ) {
		$id = sg_seo_first_content_image( $post_id );
	}
	if ( ! $id && $post_id ) {
		// Ảnh đầu tiên trong thư viện ảnh thực tế (phòng, tiện ích).
		$gallery = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, 'sg_gallery', true ) ) ) );
		$id      = $gallery ? (int) reset( $gallery ) : 0;
	}
	$src = $id ? wp_get_attachment_image_src( $id, 'large' ) : false;
	return $src ? array( $src[0], (int) $src[1], (int) $src[2] ) : array();
}

/**
 * Ảnh đầu tiên trong nội dung: ảnh poster của banner video hoặc block ảnh.
 *
 * @param int $post_id ID.
 * @return int
 */
function sg_seo_first_content_image( $post_id ) {
	$find = static function ( $blocks ) use ( &$find ) {
		foreach ( $blocks as $block ) {
			$attrs = (array) ( $block['attrs'] ?? array() );
			foreach ( array( 'posterId', 'id', 'mediaId' ) as $key ) {
				if ( ! empty( $attrs[ $key ] ) && wp_attachment_is_image( (int) $attrs[ $key ] ) ) {
					return (int) $attrs[ $key ];
				}
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = $find( $block['innerBlocks'] );
				if ( $found ) {
					return $found;
				}
			}
		}
		return 0;
	};
	return $find( parse_blocks( (string) get_post_field( 'post_content', $post_id ) ) );
}

/**
 * Link chuẩn (canonical) của trang đang xem.
 *
 * @return string
 */
function sg_seo_canonical() {
	$lang = sg_current_lang();
	if ( is_front_page() ) {
		return sg_home_url( $lang );
	}
	if ( is_singular() || ( is_home() && get_option( 'page_for_posts' ) ) ) {
		$url   = sg_permalink( sg_seo_object_id(), $lang );
		$paged = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
		return $paged > 1 ? trailingslashit( $url ) . user_trailingslashit( 'page/' . $paged ) : $url;
	}
	if ( is_search() || is_404() ) {
		return '';
	}
	return strtok( sg_current_url(), '?' );
}

/**
 * Tiêu đề trang (thẻ <title>) – dùng tiêu đề SEO nếu có.
 *
 * @param string $title Tiêu đề do WordPress tạo.
 * @return string
 */
function sg_seo_document_title( $title ) {
	$id = sg_seo_object_id();
	if ( $id ) {
		$seo = (string) get_post_meta( $id, sg_seo_key( 'title', sg_current_lang() ), true );
		if ( '' !== $seo ) {
			return esc_html( $seo );
		}
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'sg_seo_document_title' );

/**
 * Chặn lập chỉ mục khi đang thử nghiệm.
 *
 * @param array $robots Chỉ thị robots.
 * @return array
 */
function sg_seo_robots( $robots ) {
	if ( '1' === sg_seo_option( 'noindex' ) || is_search() || is_404() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = '1' === sg_seo_option( 'noindex' );
		unset( $robots['max-image-preview'] );
	} else {
		$robots['max-image-preview'] = 'large';
	}
	return $robots;
}
add_filter( 'wp_robots', 'sg_seo_robots' );

/**
 * Bỏ canonical mặc định của WordPress (tự xuất canonical có hreflang).
 */
function sg_seo_cleanup_head() {
	remove_action( 'wp_head', 'rel_canonical' );
}
add_action( 'init', 'sg_seo_cleanup_head' );

/**
 * Xuất thẻ SEO trong <head>.
 */
function sg_seo_head() {
	if ( is_admin() || is_feed() ) {
		return;
	}
	$lang  = sg_current_lang();
	$langs = sg_languages();
	$id    = sg_seo_object_id();
	$title = wp_get_document_title();
	$desc  = sg_seo_description( $id, $lang );
	$url   = sg_seo_canonical();
	$image = sg_seo_image( $id ? $id : (int) get_option( 'page_on_front' ) );
	$out   = array();

	if ( $desc ) {
		$out[] = '<meta name="description" content="' . esc_attr( $desc ) . '">';
	}
	if ( $url ) {
		$out[] = '<link rel="canonical" href="' . esc_url( $url ) . '">';
		if ( ! is_search() && ! is_404() ) {
			foreach ( array_keys( $langs ) as $code ) {
				$out[] = '<link rel="alternate" hreflang="' . esc_attr( $code ) . '" href="' . esc_url( sg_translation_url( $code ) ) . '">';
			}
			$out[] = '<link rel="alternate" hreflang="x-default" href="' . esc_url( sg_translation_url( sg_default_lang() ) ) . '">';
		}
	}

	// Open Graph & Twitter Card.
	$type  = is_singular( 'post' ) ? 'article' : 'website';
	$og    = array(
		'og:locale'    => $langs[ $lang ]['og_locale'] ?? $langs[ $lang ]['locale'],
		'og:type'      => $type,
		'og:title'     => $title,
		'og:description' => $desc,
		'og:url'       => $url ? $url : sg_current_url(),
		'og:site_name' => get_bloginfo( 'name' ),
	);
	foreach ( $og as $property => $content ) {
		if ( '' !== (string) $content ) {
			$out[] = '<meta property="' . esc_attr( $property ) . '" content="' . esc_attr( $content ) . '">';
		}
	}
	foreach ( $langs as $code => $info ) {
		if ( $code !== $lang ) {
			$out[] = '<meta property="og:locale:alternate" content="' . esc_attr( $info['og_locale'] ?? $info['locale'] ) . '">';
		}
	}
	if ( $image ) {
		$out[] = '<meta property="og:image" content="' . esc_url( $image[0] ) . '">';
		$out[] = '<meta property="og:image:width" content="' . (int) $image[1] . '">';
		$out[] = '<meta property="og:image:height" content="' . (int) $image[2] . '">';
	}
	if ( 'article' === $type ) {
		$out[] = '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c', $id ) ) . '">';
		$out[] = '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c', $id ) ) . '">';
	}
	$out[] = '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">';
	$out[] = '<meta name="twitter:title" content="' . esc_attr( $title ) . '">';
	if ( $desc ) {
		$out[] = '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">';
	}
	if ( $image ) {
		$out[] = '<meta name="twitter:image" content="' . esc_url( $image[0] ) . '">';
	}

	$gsc = sg_seo_option( 'gsc' );
	if ( $gsc ) {
		$out[] = '<meta name="google-site-verification" content="' . esc_attr( $gsc ) . '">';
	}

	$schema = sg_seo_schema();
	if ( $schema ) {
		$out[] = '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $schema ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>';
	}

	echo "\n<!-- Sunny Grove SEO -->\n" . implode( "\n", $out ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'sg_seo_head', 2 );

/**
 * Google Analytics 4 (chỉ khi đã nhập mã đo lường; không theo dõi người đang đăng nhập quản trị).
 */
function sg_seo_analytics() {
	$id = sg_seo_option( 'ga_id' );
	if ( ! $id || ! preg_match( '/^G-[A-Z0-9]+$/', $id ) || current_user_can( 'edit_posts' ) ) {
		return;
	}
	printf(
		"<script async src=\"%1\$s\"></script>\n<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config',%2\$s,{anonymize_ip:true});</script>\n",
		esc_url( 'https://www.googletagmanager.com/gtag/js?id=' . $id ),
		wp_json_encode( $id )
	);
}
add_action( 'wp_head', 'sg_seo_analytics', 20 );

/* -------------------------------------------------------------------------
 * Schema JSON-LD
 * ---------------------------------------------------------------------- */

/**
 * ID schema của khách sạn.
 *
 * @return string
 */
function sg_seo_hotel_id() {
	return untrailingslashit( get_option( 'home' ) ) . '/#hotel';
}

/**
 * Schema khách sạn (Hotel / LodgingBusiness).
 *
 * @param string $lang Ngôn ngữ.
 * @return array
 */
function sg_seo_hotel_schema( $lang ) {
	$hotel = array(
		'@type'      => array( 'Hotel', 'LodgingBusiness' ),
		'@id'        => sg_seo_hotel_id(),
		'name'       => sgc_hotel( 'name', $lang ),
		'url'        => sg_home_url( $lang ),
		'inLanguage' => str_replace( '_', '-', sg_languages()[ $lang ]['locale'] ),
	);
	$map   = array(
		'description'  => sgc_hotel( 'slogan', $lang ),
		'telephone'    => sgc_hotel( 'phone' ),
		'email'        => sgc_hotel( 'email' ),
		'priceRange'   => sgc_hotel( 'price_range' ),
		'checkinTime'  => sgc_hotel( 'checkin_time' ),
		'checkoutTime' => sgc_hotel( 'checkout_time' ),
		'hasMap'       => sgc_hotel( 'map_link' ),
	);
	foreach ( $map as $key => $value ) {
		if ( '' !== (string) $value ) {
			$hotel[ $key ] = $value;
		}
	}
	$address = sgc_hotel( 'address', $lang );
	if ( $address ) {
		$hotel['address'] = array(
			'@type'          => 'PostalAddress',
			'streetAddress'  => $address,
			'addressCountry' => 'VN',
		);
	}
	$lat = sgc_hotel( 'latitude' );
	$lng = sgc_hotel( 'longitude' );
	if ( '' !== (string) $lat && '' !== (string) $lng ) {
		$hotel['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $lat,
			'longitude' => (float) $lng,
		);
	}
	$stars = sgc_hotel( 'star_rating' );
	if ( '' !== (string) $stars ) {
		$hotel['starRating'] = array(
			'@type'       => 'Rating',
			'ratingValue' => (string) $stars,
		);
	}
	$image = sg_seo_image( (int) get_option( 'page_on_front' ) );
	if ( $image ) {
		$hotel['image'] = $image[0];
	}
	$same = array_values( array_filter( array_map( 'sgc_hotel', array( 'facebook', 'instagram', 'tiktok', 'youtube', 'tripadvisor' ) ) ) );
	if ( $same ) {
		$hotel['sameAs'] = $same;
	}
	return $hotel;
}

/**
 * Trang danh sách của từng loại nội dung (cho BreadcrumbList).
 *
 * @param string $type Loại nội dung.
 * @return int
 */
function sg_seo_section_page( $type ) {
	$keys  = array(
		'sg_room'    => 'rooms',
		'sg_amenity' => 'amenities',
		'sg_offer'   => 'offers',
		'sg_place'   => 'explore',
		'post'       => 'blog',
	);
	$pages = (array) get_option( 'sgc_pages', array() );
	$key   = $keys[ $type ] ?? '';
	if ( ! $key || empty( $pages[ $key ] ) ) {
		return 'post' === $type ? (int) get_option( 'page_for_posts' ) : 0;
	}
	$id = $pages[ $key ][ sg_default_lang() ] ?? reset( $pages[ $key ] );
	return get_post( (int) $id ) ? (int) $id : 0;
}

/**
 * Toàn bộ schema của trang đang xem.
 *
 * @return array
 */
function sg_seo_schema() {
	$lang   = sg_current_lang();
	$graph  = array();
	$id     = sg_seo_object_id();
	$locale = str_replace( '_', '-', sg_languages()[ $lang ]['locale'] );

	if ( is_front_page() || ( $id && (int) sgc_hotel( 'booking_page' ) === $id ) ) {
		$graph[] = sg_seo_hotel_schema( $lang );
	}
	if ( ! is_singular() || is_front_page() ) {
		return $graph;
	}

	$post  = get_post( $id );
	$url   = sg_permalink( $id, $lang );
	$name  = sg_seo_plain_title( $id );
	$desc  = sg_seo_description( $id, $lang );
	$image = sg_seo_image( $id );
	$base  = array(
		'name'        => $name,
		'description' => $desc,
		'url'         => $url,
		'inLanguage'  => $locale,
	);
	if ( $image ) {
		$base['image'] = $image[0];
	}
	switch ( $post->post_type ) {
		case 'sg_room':
			$room   = array_merge( array( '@type' => 'HotelRoom' ), $base, array( 'containedInPlace' => array( '@id' => sg_seo_hotel_id() ) ) );
			$adults = (int) get_post_meta( $id, 'sg_adults', true );
			$kids   = (int) get_post_meta( $id, 'sg_children', true );
			if ( $adults + $kids ) {
				$room['occupancy'] = array(
					'@type'    => 'QuantitativeValue',
					'maxValue' => $adults + $kids,
				);
			}
			$area = get_post_meta( $id, 'sg_area', true );
			if ( '' !== (string) $area ) {
				$room['floorSize'] = array(
					'@type'    => 'QuantitativeValue',
					'value'    => (float) $area,
					'unitCode' => 'MTK',
				);
			}
			$bed = sg_meta_tr( $id, 'sg_bed', $lang );
			if ( $bed ) {
				$room['bed'] = $bed;
			}
			$features = get_the_terms( $id, 'sg_room_feature' );
			if ( $features && ! is_wp_error( $features ) ) {
				$room['amenityFeature'] = array_map(
					static function ( $t ) {
						return array(
							'@type' => 'LocationFeatureSpecification',
							'name'  => wp_specialchars_decode( $t->name ),
							'value' => true,
						);
					},
					$features
				);
			}
			$price = get_post_meta( $id, 'sg_price_from', true );
			if ( '' !== (string) $price ) {
				$room['offers'] = array(
					'@type'         => 'Offer',
					'price'         => (float) $price,
					'priceCurrency' => 'VND',
					'url'           => $url,
				);
			}
			$graph[] = $room;
			break;
		case 'sg_offer':
			$offer = array_merge(
				array( '@type' => 'Offer' ),
				$base,
				array( 'offeredBy' => array( '@id' => sg_seo_hotel_id() ) )
			);
			$from  = get_post_meta( $id, 'sg_valid_from', true );
			$to    = get_post_meta( $id, 'sg_valid_to', true );
			if ( $from ) {
				$offer['validFrom'] = $from;
			}
			if ( $to ) {
				$offer['validThrough'] = $to . 'T23:59:59';
			}
			$graph[] = $offer;
			break;
		case 'sg_place':
			$place = array_merge( array( '@type' => 'TouristAttraction' ), $base );
			$map   = get_post_meta( $id, 'sg_map_url', true );
			if ( $map ) {
				$place['hasMap'] = $map;
			}
			$graph[] = $place;
			break;
		case 'post':
			$graph[] = array_merge(
				array( '@type' => 'Article' ),
				$base,
				array(
					'headline'      => $name,
					'datePublished' => get_the_date( 'c', $id ),
					'dateModified'  => get_the_modified_date( 'c', $id ),
					'author'        => array( '@id' => sg_seo_hotel_id() ),
					'publisher'     => array(
						'@id'   => sg_seo_hotel_id(),
						'@type' => 'Organization',
						'name'  => sgc_hotel( 'name', $lang ),
					),
					'mainEntityOfPage' => $url,
				)
			);
			break;
	}

	// Đường dẫn phân cấp: Trang chủ › Danh sách › Bài.
	$crumbs  = array( array( wp_strip_all_tags( sgc_label( 'breadcrumb_home' ) ? sgc_label( 'breadcrumb_home' ) : get_bloginfo( 'name' ) ), sg_home_url( $lang ) ) );
	$section = sg_seo_section_page( $post->post_type );
	if ( $section && $section !== $id ) {
		$crumbs[] = array( sg_seo_plain_title( $section ), sg_permalink( $section, $lang ) );
	}
	foreach ( array_reverse( get_post_ancestors( $post ) ) as $ancestor ) {
		$crumbs[] = array( sg_seo_plain_title( $ancestor ), sg_permalink( $ancestor, $lang ) );
	}
	$crumbs[] = array( $name, $url );
	$items    = array();
	foreach ( $crumbs as $i => $crumb ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $crumb[0],
			'item'     => $crumb[1],
		);
	}
	$graph[] = array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
	return $graph;
}

/* -------------------------------------------------------------------------
 * Sitemap: thêm đường dẫn tiếng Anh vào wp-sitemap.xml
 * ---------------------------------------------------------------------- */

/**
 * Đăng ký nhóm sitemap cho từng ngôn ngữ phụ.
 */
function sg_seo_register_sitemaps() {
	if ( ! class_exists( 'WP_Sitemaps_Provider' ) ) {
		return;
	}
	require_once SGC_DIR . 'includes/class-sg-sitemap-provider.php';
	foreach ( sg_secondary_langs() as $lang ) {
		wp_register_sitemap_provider( 'lang' . $lang, new SG_Sitemap_Provider( $lang ) );
	}
}
add_action( 'init', 'sg_seo_register_sitemaps', 20 );

/**
 * Sitemap không cần liệt kê người dùng (trang tác giả không dùng).
 *
 * @param WP_Sitemaps_Provider $provider Nhóm.
 * @param string               $name     Tên nhóm.
 * @return WP_Sitemaps_Provider|false
 */
function sg_seo_sitemap_providers( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'sg_seo_sitemap_providers', 10, 2 );

/* -------------------------------------------------------------------------
 * Trang cài đặt SEO (chỉ quản trị viên)
 * ---------------------------------------------------------------------- */

/**
 * Menu cài đặt SEO.
 */
function sg_seo_admin_menu() {
	add_submenu_page( 'sgc-hotel', __( 'SEO', 'sunny-grove-core' ), __( 'SEO', 'sunny-grove-core' ), 'manage_options', 'sg-seo', 'sg_seo_render_settings' );
}
add_action( 'admin_menu', 'sg_seo_admin_menu', 20 );

/**
 * Nội dung trang cài đặt SEO.
 */
function sg_seo_render_settings() {
	echo '<div class="wrap sgc-settings"><h1>' . esc_html__( 'SEO', 'sunny-grove-core' ) . '</h1>';
	sgc_settings_notice();
	echo '<p>' . esc_html__( 'Tiêu đề và mô tả của từng trang sửa trong hộp "SEO" bên dưới trình soạn thảo. Trang này chỉ dành cho cài đặt kỹ thuật.', 'sunny-grove-core' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'sg_seo_settings' );
	echo '<input type="hidden" name="action" value="sg_seo_settings"><table class="form-table" role="presentation">';
	$fields = array(
		'gsc'           => array( __( 'Mã xác minh Google Search Console', 'sunny-grove-core' ), 'text', __( 'Trong Search Console chọn "Thẻ HTML", chỉ dán phần trong content="…".', 'sunny-grove-core' ) ),
		'ga_id'         => array( __( 'Mã Google Analytics 4 (tùy chọn)', 'sunny-grove-core' ), 'text', __( 'Dạng G-XXXXXXX. Để trống thì không tải Google Analytics.', 'sunny-grove-core' ) ),
		'default_image' => array( __( 'Ảnh chia sẻ mặc định (ID ảnh)', 'sunny-grove-core' ), 'number', __( 'Dùng khi trang không có ảnh đại diện.', 'sunny-grove-core' ) ),
		'noindex'       => array( __( 'Chặn Google lập chỉ mục (khi đang thử nghiệm)', 'sunny-grove-core' ), 'checkbox', __( 'Nhớ tắt khi website chạy chính thức.', 'sunny-grove-core' ) ),
	);
	foreach ( $fields as $key => $f ) {
		echo '<tr><th scope="row">' . esc_html( $f[0] ) . '</th><td>';
		if ( 'checkbox' === $f[1] ) {
			printf( '<input type="hidden" name="sg_seo[%1$s]" value="0"><label><input type="checkbox" name="sg_seo[%1$s]" value="1" %2$s> %3$s</label>', esc_attr( $key ), checked( sg_seo_option( $key ), '1', false ), esc_html__( 'Bật', 'sunny-grove-core' ) );
		} else {
			printf( '<input type="%1$s" class="regular-text" name="sg_seo[%2$s]" value="%3$s">', esc_attr( $f[1] ), esc_attr( $key ), esc_attr( sg_seo_option( $key ) ) );
		}
		echo '<p class="description">' . esc_html( $f[2] ) . '</p></td></tr>';
	}
	echo '</table>';
	printf( '<p>%s <a href="%s" target="_blank" rel="noopener">wp-sitemap.xml</a></p>', esc_html__( 'Sơ đồ trang để khai báo với Google:', 'sunny-grove-core' ), esc_url( home_url( '/wp-sitemap.xml' ) ) );
	submit_button( __( 'Lưu thay đổi', 'sunny-grove-core' ) );
	echo '</form></div>';
}

/**
 * Lưu cài đặt SEO.
 */
function sg_seo_save_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'sunny-grove-core' ) );
	}
	check_admin_referer( 'sg_seo_settings' );
	$in  = isset( $_POST['sg_seo'] ) && is_array( $_POST['sg_seo'] ) ? wp_unslash( $_POST['sg_seo'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$out = array(
		'gsc'           => sanitize_text_field( $in['gsc'] ?? '' ),
		'ga_id'         => strtoupper( preg_replace( '/[^A-Za-z0-9-]/', '', (string) ( $in['ga_id'] ?? '' ) ) ),
		'default_image' => (string) absint( $in['default_image'] ?? 0 ),
		'noindex'       => '1' === (string) ( $in['noindex'] ?? '' ) ? '1' : '0',
	);
	update_option( 'sg_seo', $out );
	wp_safe_redirect( admin_url( 'admin.php?page=sg-seo&sgc_saved=1' ) );
	exit;
}
add_action( 'admin_post_sg_seo_settings', 'sg_seo_save_settings' );

/**
 * Tiêu đề dạng chữ thường (không thẻ, không mã HTML) theo ngôn ngữ đang xem.
 *
 * @param int $post_id ID.
 * @return string
 */
function sg_seo_plain_title( $post_id ) {
	return html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
}
