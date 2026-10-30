<?php
/**
 * Quản trị bản dịch: trường meta tiếng Anh, tên tiếng Anh cho danh mục và mục menu,
 * trạng thái dịch và cột "EN" trong danh sách bài/trang.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loại nội dung có bản dịch.
 *
 * @return string[]
 */
function sg_i18n_post_types() {
	return apply_filters( 'sg_i18n_post_types', array_merge( array( 'page', 'post' ), array_keys( sgc_data( 'post-types' ) ) ) );
}

/**
 * Taxonomy có tên tiếng Anh.
 *
 * @return string[]
 */
function sg_i18n_taxonomies() {
	return apply_filters( 'sg_i18n_taxonomies', array( 'category', 'post_tag', 'sg_guest_type', 'sg_room_feature', 'sg_place_tag' ) );
}

/**
 * Đăng ký meta tiếng Anh (dùng được trong trình soạn thảo qua REST).
 */
function sg_i18n_register_meta() {
	$auth = static function ( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	};
	foreach ( sg_i18n_post_types() as $type ) {
		foreach ( sg_secondary_langs() as $lang ) {
			$fields = array(
				'title'     => 'sanitize_text_field',
				'title_h'   => 'sanitize_key',
				'excerpt'   => 'sanitize_textarea_field',
				'excerpt_h' => 'sanitize_key',
				'slug'      => 'sanitize_title',
			);
			foreach ( $fields as $field => $sanitize ) {
				register_post_meta(
					$type,
					sg_en_key( $field, $lang ),
					array(
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => true,
						'default'           => '',
						'sanitize_callback' => $sanitize,
						'auth_callback'     => $auth,
					)
				);
			}
		}
	}
	// Trường chữ của Phòng/Tiện ích/Địa điểm/Ưu đãi: bản tiếng Anh "<khóa>_en".
	foreach ( sgc_meta_fields() as $type => $fields ) {
		foreach ( $fields as $key => $field ) {
			if ( ! in_array( $field['type'], array( 'text', 'textarea' ), true ) ) {
				continue;
			}
			foreach ( sg_secondary_langs() as $lang ) {
				register_post_meta(
					$type,
					$key . '_' . $lang,
					array(
						'type'              => 'string',
						'single'            => true,
						'show_in_rest'      => true,
						'sanitize_callback' => 'textarea' === $field['type'] ? 'sanitize_textarea_field' : 'sanitize_text_field',
						'auth_callback'     => $auth,
					)
				);
			}
		}
	}
}
add_action( 'init', 'sg_i18n_register_meta', 7 );

/**
 * Thuộc tính sgEn cho các block dịch được (để trình soạn thảo và REST chấp nhận).
 *
 * @param array  $args Tham số block.
 * @param string $name Tên block.
 * @return array
 */
function sg_i18n_block_type_args( $args, $name ) {
	if ( isset( sg_i18n_block_spec()[ $name ] ) ) {
		$args['attributes'] = $args['attributes'] ?? array();
		foreach ( sg_secondary_langs() as $lang ) {
			$args['attributes'][ 'sg' . ucfirst( $lang ) ] = array( 'type' => 'object' );
		}
	}
	return $args;
}
add_filter( 'register_block_type_args', 'sg_i18n_block_type_args', 10, 2 );

/* -------------------------------------------------------------------------
 * Trạng thái dịch
 * ---------------------------------------------------------------------- */

/**
 * Đếm phần đã dịch / chưa dịch / cần cập nhật của một bài.
 *
 * @param int    $post_id ID.
 * @param string $lang    Ngôn ngữ.
 * @return array{done:int,missing:int,outdated:int,state:string}
 */
function sg_i18n_compute_status( $post_id, $lang = 'en' ) {
	$post  = get_post( $post_id );
	$count = array(
		'done'     => 0,
		'missing'  => 0,
		'outdated' => 0,
	);
	if ( ! $post ) {
		return $count + array( 'state' => 'missing' );
	}
	$check = static function ( $vi, $field ) use ( $post, $lang, &$count ) {
		if ( '' === sg_i18n_normalize( $vi ) ) {
			return;
		}
		$tr = (string) get_post_meta( $post->ID, sg_en_key( $field, $lang ), true );
		if ( '' === trim( $tr ) ) {
			++$count['missing'];
			return;
		}
		$hash = (string) get_post_meta( $post->ID, sg_en_key( $field . '_h', $lang ), true );
		++$count[ $hash && sg_i18n_hash( array( $vi ) ) !== $hash ? 'outdated' : 'done' ];
	};
	$check( $post->post_title, 'title' );
	$check( $post->post_excerpt, 'excerpt' );

	foreach ( sg_i18n_flatten( parse_blocks( $post->post_content ) ) as $item ) {
		$state = sg_i18n_block_status( $item['block'], $lang );
		if ( isset( $count[ $state ] ) ) {
			++$count[ $state ];
		}
	}
	foreach ( sgc_meta_fields()[ $post->post_type ] ?? array() as $key => $field ) {
		if ( in_array( $field['type'], array( 'text', 'textarea' ), true ) && '' !== trim( (string) get_post_meta( $post->ID, $key, true ) ) ) {
			++$count[ '' !== trim( (string) get_post_meta( $post->ID, $key . '_' . $lang, true ) ) ? 'done' : 'missing' ];
		}
	}
	if ( $count['outdated'] ) {
		$state = 'outdated';
	} elseif ( $count['missing'] ) {
		$state = 'missing';
	} else {
		$state = 'done';
	}
	return $count + array( 'state' => $state );
}

/**
 * Lưu trạng thái dịch sau khi lưu bài (cả trình soạn thảo khối lẫn màn hình cổ điển).
 *
 * @param int|WP_Post $post Bài.
 */
function sg_i18n_store_status( $post ) {
	$post = get_post( $post );
	if ( ! $post || wp_is_post_revision( $post ) || ! in_array( $post->post_type, sg_i18n_post_types(), true ) ) {
		return;
	}
	foreach ( sg_secondary_langs() as $lang ) {
		update_post_meta( $post->ID, '_sg_' . $lang . '_status', sg_i18n_compute_status( $post->ID, $lang ) );
	}
}
add_action( 'save_post', 'sg_i18n_store_status', 99 );
foreach ( array_merge( array( 'page', 'post' ), array_keys( sgc_data( 'post-types' ) ) ) as $sg_i18n_type ) {
	add_action( "rest_after_insert_{$sg_i18n_type}", 'sg_i18n_store_status', 99 );
}
unset( $sg_i18n_type );

/**
 * Cột "EN" trong danh sách bài/trang.
 */
function sg_i18n_admin_columns() {
	foreach ( sg_i18n_post_types() as $type ) {
		add_filter(
			"manage_{$type}_posts_columns",
			static function ( $cols ) {
				foreach ( sg_secondary_langs() as $lang ) {
					$cols[ 'sg_tr_' . $lang ] = strtoupper( $lang );
				}
				return $cols;
			}
		);
		add_action(
			"manage_{$type}_posts_custom_column",
			static function ( $col, $post_id ) {
				if ( 0 !== strpos( $col, 'sg_tr_' ) ) {
					return;
				}
				$lang   = substr( $col, 6 );
				$status = get_post_meta( $post_id, '_sg_' . $lang . '_status', true );
				if ( ! is_array( $status ) || ! isset( $status['state'] ) ) {
					$status = sg_i18n_compute_status( $post_id, $lang );
				}
				sg_i18n_status_badge( $status );
			},
			10,
			2
		);
	}
}
add_action( 'admin_init', 'sg_i18n_admin_columns' );

/**
 * In nhãn trạng thái dịch.
 *
 * @param array $status Trạng thái.
 */
function sg_i18n_status_badge( array $status ) {
	$map = array(
		'done'     => array( '✓', __( 'Đủ', 'sunny-grove-core' ), '' ),
		'outdated' => array( '⚠', __( 'Cần cập nhật', 'sunny-grove-core' ), (int) $status['outdated'] ),
		'missing'  => array( '✗', __( 'Chưa dịch', 'sunny-grove-core' ), (int) $status['missing'] ),
	);
	list( $icon, $label, $n ) = $map[ $status['state'] ] ?? $map['missing'];
	printf(
		'<span class="sg-tr-badge sg-tr-badge--%1$s" title="%5$s">%2$s %3$s%4$s</span>',
		esc_attr( $status['state'] ),
		esc_html( $icon ),
		esc_html( $label ),
		$n ? ' <span class="count">(' . (int) $n . ')</span>' : '',
		esc_attr(
			sprintf(
				/* translators: 1: số phần đã dịch, 2: chưa dịch, 3: cần cập nhật */
				__( 'Đã dịch %1$d · Chưa dịch %2$d · Cần cập nhật %3$d', 'sunny-grove-core' ),
				(int) $status['done'],
				(int) $status['missing'],
				(int) $status['outdated']
			)
		)
	);
}

/**
 * CSS nhỏ cho cột EN.
 */
function sg_i18n_admin_list_css() {
	$screen = get_current_screen();
	if ( $screen && 'edit' === $screen->base ) {
		wp_add_inline_style( 'common', '.column-sg_tr_en{width:9em}.sg-tr-badge{white-space:nowrap;font-weight:600}.sg-tr-badge--done{color:#00753a}.sg-tr-badge--outdated{color:#9a6700}.sg-tr-badge--missing{color:#b32d2e}.sg-tr-badge .count{font-weight:400}' );
	}
}
add_action( 'admin_enqueue_scripts', 'sg_i18n_admin_list_css' );

/* -------------------------------------------------------------------------
 * Danh mục / nhãn: ô "Tên tiếng Anh"
 * ---------------------------------------------------------------------- */

/**
 * Gắn ô tên tiếng Anh vào form danh mục.
 */
function sg_i18n_term_fields() {
	foreach ( sg_i18n_taxonomies() as $tax ) {
		add_action( "{$tax}_add_form_fields", 'sg_i18n_term_add_field' );
		add_action( "{$tax}_edit_form_fields", 'sg_i18n_term_edit_field' );
		add_action( "created_{$tax}", 'sg_i18n_term_save' );
		add_action( "edited_{$tax}", 'sg_i18n_term_save' );
	}
}
add_action( 'admin_init', 'sg_i18n_term_fields' );

/**
 * Ô ở form thêm mới.
 */
function sg_i18n_term_add_field() {
	wp_nonce_field( 'sg_i18n_term', 'sg_i18n_term_nonce' );
	foreach ( sg_secondary_langs() as $lang ) {
		printf(
			'<div class="form-field"><label for="sg-term-%1$s">%2$s</label><input type="text" id="sg-term-%1$s" name="sg_term_name[%1$s]" value="" lang="%3$s"><p>%4$s</p></div>',
			esc_attr( $lang ),
			esc_html( sprintf( /* translators: %s: tên ngôn ngữ */ __( 'Tên (%s)', 'sunny-grove-core' ), sg_languages()[ $lang ]['name'] ) ),
			esc_attr( sg_languages()[ $lang ]['locale'] ),
			esc_html__( 'Để trống thì bản tiếng Anh hiện tên tiếng Việt.', 'sunny-grove-core' )
		);
	}
}

/**
 * Ô ở form sửa.
 *
 * @param WP_Term $term Term.
 */
function sg_i18n_term_edit_field( $term ) {
	wp_nonce_field( 'sg_i18n_term', 'sg_i18n_term_nonce' );
	foreach ( sg_secondary_langs() as $lang ) {
		printf(
			'<tr class="form-field"><th scope="row"><label for="sg-term-%1$s">%2$s</label></th><td><input type="text" id="sg-term-%1$s" name="sg_term_name[%1$s]" value="%3$s" lang="%4$s"><p class="description">%5$s</p></td></tr>',
			esc_attr( $lang ),
			esc_html( sprintf( /* translators: %s: tên ngôn ngữ */ __( 'Tên (%s)', 'sunny-grove-core' ), sg_languages()[ $lang ]['name'] ) ),
			esc_attr( get_term_meta( $term->term_id, sg_en_key( 'name', $lang ), true ) ),
			esc_attr( sg_languages()[ $lang ]['locale'] ),
			esc_html__( 'Để trống thì bản tiếng Anh hiện tên tiếng Việt.', 'sunny-grove-core' )
		);
	}
}

/**
 * Lưu tên tiếng Anh của term.
 *
 * @param int $term_id ID.
 */
function sg_i18n_term_save( $term_id ) {
	if ( ! isset( $_POST['sg_i18n_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sg_i18n_term_nonce'] ), 'sg_i18n_term' ) || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}
	$names = isset( $_POST['sg_term_name'] ) && is_array( $_POST['sg_term_name'] ) ? wp_unslash( $_POST['sg_term_name'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	foreach ( sg_secondary_langs() as $lang ) {
		$value = isset( $names[ $lang ] ) ? sanitize_text_field( $names[ $lang ] ) : '';
		if ( '' === $value ) {
			delete_term_meta( $term_id, sg_en_key( 'name', $lang ) );
		} else {
			update_term_meta( $term_id, sg_en_key( 'name', $lang ), $value );
		}
	}
}

/* -------------------------------------------------------------------------
 * Mục menu: ô "Tên tiếng Anh"
 * ---------------------------------------------------------------------- */

/**
 * Ô tên tiếng Anh trong mỗi mục menu (Giao diện → Menu).
 *
 * @param int $item_id ID mục menu.
 */
function sg_i18n_menu_item_field( $item_id ) {
	wp_nonce_field( 'sg_i18n_menu', 'sg_i18n_menu_nonce' );
	foreach ( sg_secondary_langs() as $lang ) {
		printf(
			'<p class="description description-wide"><label for="sg-menu-%1$d-%2$s">%3$s<br><input type="text" id="sg-menu-%1$d-%2$s" class="widefat" name="sg_menu_title[%1$d][%2$s]" value="%4$s" lang="%5$s"></label></p>',
			(int) $item_id,
			esc_attr( $lang ),
			esc_html( sprintf( /* translators: %s: tên ngôn ngữ */ __( 'Tên hiển thị (%s)', 'sunny-grove-core' ), sg_languages()[ $lang ]['name'] ) ),
			esc_attr( get_post_meta( $item_id, sg_en_key( 'title', $lang ), true ) ),
			esc_attr( sg_languages()[ $lang ]['locale'] )
		);
	}
}
add_action( 'wp_nav_menu_item_custom_fields', 'sg_i18n_menu_item_field' );

/**
 * Lưu tên tiếng Anh của mục menu.
 *
 * @param int $menu_id ID menu.
 * @param int $item_id ID mục.
 */
function sg_i18n_menu_item_save( $menu_id, $item_id ) {
	if ( ! isset( $_POST['sg_i18n_menu_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sg_i18n_menu_nonce'] ), 'sg_i18n_menu' ) || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$titles = isset( $_POST['sg_menu_title'][ $item_id ] ) && is_array( $_POST['sg_menu_title'][ $item_id ] ) ? wp_unslash( $_POST['sg_menu_title'][ $item_id ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	foreach ( sg_secondary_langs() as $lang ) {
		$value = isset( $titles[ $lang ] ) ? sanitize_text_field( $titles[ $lang ] ) : '';
		if ( '' === $value ) {
			delete_post_meta( $item_id, sg_en_key( 'title', $lang ) );
		} else {
			update_post_meta( $item_id, sg_en_key( 'title', $lang ), $value );
		}
	}
}
add_action( 'wp_update_nav_menu_item', 'sg_i18n_menu_item_save', 10, 2 );

/* -------------------------------------------------------------------------
 * Trình soạn thảo: nút gạt VI | EN và sửa bản dịch tại chỗ
 * ---------------------------------------------------------------------- */

/**
 * Bảng block dịch được cho JavaScript: trường bản dịch → tên thuộc tính block trong trình soạn thảo.
 *
 * @return array
 */
function sg_i18n_editor_spec() {
	$attr_names = array(
		'core/media-text' => array( 'alt' => 'mediaAlt' ),
	);
	$out        = array();
	foreach ( sg_i18n_block_spec() as $name => $spec ) {
		$fields = array();
		foreach ( array_merge( array_keys( $spec['html'] ?? array() ), $spec['attrs'] ?? array() ) as $field ) {
			$fields[ $field ] = $attr_names[ $name ][ $field ] ?? $field;
		}
		$out[ $name ] = array( 'fields' => $fields );
		if ( ! empty( $spec['text'] ) ) {
			$out[ $name ]['text'] = $spec['text'];
		}
		if ( ! empty( $spec['cells'] ) ) {
			$out[ $name ]['cells'] = true;
		}
	}
	return $out;
}

/**
 * Nạp script/CSS dịch trong trình soạn thảo.
 */
function sg_i18n_editor_assets() {
	$lang = sg_secondary_langs()[0] ?? '';
	if ( ! $lang ) {
		return;
	}
	global $wp_post_types;
	$bases = array();
	foreach ( array_keys( sgc_data( 'post-types' ) ) as $type ) {
		if ( isset( $wp_post_types[ $type ] ) ) {
			$bases[] = $wp_post_types[ $type ]->rest_base ? $wp_post_types[ $type ]->rest_base : $type;
		}
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$type   = $screen ? $screen->post_type : '';
	wp_enqueue_script( 'sg-editor-i18n', SGC_URL . 'assets/editor-i18n.js', array( 'wp-data', 'wp-hooks', 'wp-compose', 'wp-element', 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-plugins', 'wp-api-fetch', 'wp-url', 'wp-i18n', 'wp-notices', 'wp-core-data', 'wp-dom-ready' ), SGC_VERSION, true );
	wp_enqueue_style( 'sg-editor-i18n', SGC_URL . 'assets/editor-i18n.css', array(), SGC_VERSION );
	wp_localize_script(
		'sg-editor-i18n',
		'sgI18nEditor',
		array(
			'lang'            => $lang,
			'defaultLang'     => sg_default_lang(),
			'locale'          => str_replace( '_', '-', sg_languages()[ $lang ]['locale'] ),
			'spec'            => sg_i18n_editor_spec(),
			'bases'           => $bases,
			'postEditor'      => $screen && in_array( $screen->base, array( 'post', 'site-editor' ), true ),
			'types'           => sg_i18n_post_types(),
			'supportsExcerpt' => $type ? post_type_supports( $type, 'excerpt' ) : false,
			'keys'            => array(
				'title'       => sg_en_key( 'title', $lang ),
				'titleHash'   => sg_en_key( 'title_h', $lang ),
				'excerpt'     => sg_en_key( 'excerpt', $lang ),
				'excerptHash' => sg_en_key( 'excerpt_h', $lang ),
				'slug'        => sg_en_key( 'slug', $lang ),
			),
			'labels'          => array(
				'switchLabel'  => __( 'Ngôn ngữ đang sửa', 'sunny-grove-core' ),
				'panelTitle'   => __( 'Tiếng Anh', 'sunny-grove-core' ),
				'missing'      => __( 'Chưa dịch', 'sunny-grove-core' ),
				'outdated'     => __( 'Cần cập nhật bản EN', 'sunny-grove-core' ),
				'statDone'     => __( 'Đã dịch', 'sunny-grove-core' ),
				'statMissing'  => __( 'Chưa dịch', 'sunny-grove-core' ),
				'statOutdated' => __( 'Cần cập nhật', 'sunny-grove-core' ),
				'notice'       => __( 'Đang sửa bản tiếng Anh: bấm vào chữ để dịch. Bố cục và ảnh dùng chung cho cả hai ngôn ngữ nên không thêm, xóa, di chuyển block hay đổi ảnh được ở chế độ này – hãy gạt về VI để làm việc đó.', 'sunny-grove-core' ),
				'help'         => __( 'Gạt sang EN để dịch ngay trên trang. Chữ nào chưa dịch, website bản tiếng Anh sẽ hiện tiếng Việt.', 'sunny-grove-core' ),
				'title'        => __( 'Tiêu đề (tiếng Anh)', 'sunny-grove-core' ),
				'titleLabel'   => __( 'Tiêu đề tiếng Anh', 'sunny-grove-core' ),
				'excerpt'      => __( 'Tóm tắt (tiếng Anh)', 'sunny-grove-core' ),
				'slug'         => __( 'Đường dẫn tiếng Anh (slug, tùy chọn)', 'sunny-grove-core' ),
				'slugHelp'     => __( 'Để trống thì dùng đường dẫn tiếng Việt, chỉ thêm /en/ phía trước.', 'sunny-grove-core' ),
			),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'sg_i18n_editor_assets', 5 );

/**
 * CSS bên trong khung soạn thảo (nhãn "Chưa dịch", tiêu đề tiếng Anh).
 */
function sg_i18n_canvas_assets() {
	if ( is_admin() ) {
		wp_enqueue_style( 'sg-editor-i18n-canvas', SGC_URL . 'assets/editor-i18n.css', array(), SGC_VERSION );
	}
}
add_action( 'enqueue_block_assets', 'sg_i18n_canvas_assets' );
