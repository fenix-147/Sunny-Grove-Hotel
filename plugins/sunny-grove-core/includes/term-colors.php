<?php
/**
 * Màu nhãn cho danh mục (VD: loại phòng Gia đình / Cặp đôi / Nhóm bạn).
 *
 * Mỗi danh mục có ô "Màu nhãn" chọn từ bảng màu của theme (chỉ hiện những màu đặt chữ trắng lên vẫn dễ đọc – tương phản ≥ 4.5:1).
 * Nhãn hiện nền đặc, chữ trắng ở thẻ phòng (trên ảnh) và trang chi tiết phòng. Thêm nhóm mới vẫn chọn được màu.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh mục có màu nhãn.
 *
 * @return string[]
 */
function sgc_color_taxonomies() {
	return (array) apply_filters( 'sgc_color_taxonomies', array( 'sg_guest_type' ) );
}

/**
 * Tương phản giữa chữ trắng và một màu hex.
 *
 * @param string $hex Màu #RRGGBB.
 * @return float
 */
function sgc_white_contrast( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	if ( 6 !== strlen( $hex ) ) {
		return 0;
	}
	$lum = 0;
	foreach ( array( 0.2126, 0.7152, 0.0722 ) as $i => $weight ) {
		$c    = hexdec( substr( $hex, $i * 2, 2 ) ) / 255;
		$c    = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		$lum += $weight * $c;
	}
	return 1.05 / ( $lum + 0.05 );
}

/**
 * Màu chọn được: màu trong bảng màu theme mà chữ trắng đạt ≥ 4.5:1.
 *
 * @return array slug => array( name, hex, ratio )
 */
function sgc_label_color_choices() {
	$palette = function_exists( 'wp_get_global_settings' ) ? (array) wp_get_global_settings( array( 'color', 'palette', 'theme' ) ) : array();
	$out     = array();
	foreach ( $palette as $color ) {
		if ( empty( $color['slug'] ) || empty( $color['color'] ) || ! sanitize_hex_color( $color['color'] ) ) {
			continue;
		}
		$ratio = sgc_white_contrast( $color['color'] );
		if ( $ratio >= 4.5 ) {
			$out[ $color['slug'] ] = array( $color['name'] ?? $color['slug'], $color['color'], $ratio );
		}
	}
	return $out;
}

/**
 * Màu mặc định theo đường dẫn danh mục (nội dung mẫu).
 *
 * @param WP_Term $term Danh mục.
 * @return string Slug màu.
 */
function sgc_default_term_color( $term ) {
	$map = array(
		'gia-dinh' => 'primary',
		'family'   => 'primary',
		'cap-doi'  => 'accent',
		'couples'  => 'accent',
		'couple'   => 'accent',
		'nhom-ban' => 'gold',
		'friends'  => 'gold',
	);
	return $map[ $term->slug ] ?? 'ink';
}

/**
 * Slug màu nhãn của danh mục.
 *
 * @param WP_Term|int $term Danh mục.
 * @return string
 */
function sgc_term_color( $term ) {
	$term = get_term( $term );
	if ( ! $term || is_wp_error( $term ) ) {
		return 'ink';
	}
	$slug = sanitize_key( (string) get_term_meta( $term->term_id, 'sg_color', true ) );
	return $slug ? $slug : sgc_default_term_color( $term );
}

/**
 * HTML nhãn nền đặc chữ trắng.
 *
 * @param WP_Term $term Danh mục.
 * @param string  $tag  Thẻ HTML.
 * @return string
 */
function sgc_term_badge_html( $term, $tag = 'span' ) {
	return sprintf(
		'<%1$s class="sg-type-badge sg-type-badge--%2$s" style="background-color:var(--wp--preset--color--%3$s, %5$s)">%4$s</%1$s>',
		tag_escape( $tag ),
		esc_attr( $term->slug ),
		esc_attr( sgc_term_color( $term ) ),
		esc_html( $term->name ),
		esc_attr( sgc_label_color_choices()[ sgc_term_color( $term ) ][1] ?? '#26251F' )
	);
}

/**
 * Ô chọn màu (ô tròn có màu + tên).
 *
 * @param string $current Slug đang chọn.
 * @return string
 */
function sgc_term_color_field( $current ) {
	$html = '<div class="sgc-color-field" role="radiogroup">';
	foreach ( sgc_label_color_choices() as $slug => $info ) {
		$html .= sprintf(
			'<label class="sgc-color-field__item"><input type="radio" name="sg_color" value="%1$s"%2$s><span class="sgc-color-field__swatch" style="background:%3$s">Aa</span><span>%4$s</span></label>',
			esc_attr( $slug ),
			checked( $slug, $current, false ),
			esc_attr( $info[1] ),
			esc_html( $info[0] )
		);
	}
	return $html . '</div>';
}

/**
 * Gắn ô "Màu nhãn" vào form danh mục.
 */
function sgc_term_color_hooks() {
	foreach ( sgc_color_taxonomies() as $tax ) {
		add_action( "{$tax}_add_form_fields", 'sgc_term_color_add_field' );
		add_action( "{$tax}_edit_form_fields", 'sgc_term_color_edit_field' );
		add_action( "created_{$tax}", 'sgc_term_color_save' );
		add_action( "edited_{$tax}", 'sgc_term_color_save' );
		add_filter( "manage_edit-{$tax}_columns", 'sgc_term_color_column' );
		add_filter( "manage_{$tax}_custom_column", 'sgc_term_color_column_value', 10, 3 );
	}
}
add_action( 'admin_init', 'sgc_term_color_hooks' );

/**
 * Ô ở form thêm mới.
 */
function sgc_term_color_add_field() {
	wp_nonce_field( 'sgc_term_color', 'sgc_term_color_nonce' );
	echo '<div class="form-field"><label>' . esc_html__( 'Màu nhãn', 'sunny-grove-core' ) . '</label>';
	echo sgc_term_color_field( 'ink' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p>' . esc_html__( 'Màu nền của nhãn trên ảnh thẻ phòng và trang chi tiết phòng (chữ trắng). Chỉ hiện các màu đủ đậm để chữ trắng dễ đọc.', 'sunny-grove-core' ) . '</p></div>';
}

/**
 * Ô ở form sửa.
 *
 * @param WP_Term $term Danh mục.
 */
function sgc_term_color_edit_field( $term ) {
	wp_nonce_field( 'sgc_term_color', 'sgc_term_color_nonce' );
	echo '<tr class="form-field"><th scope="row">' . esc_html__( 'Màu nhãn', 'sunny-grove-core' ) . '</th><td>';
	echo sgc_term_color_field( sgc_term_color( $term ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p class="description">' . esc_html__( 'Màu nền của nhãn trên ảnh thẻ phòng và trang chi tiết phòng (chữ trắng). Chỉ hiện các màu đủ đậm để chữ trắng dễ đọc.', 'sunny-grove-core' ) . '</p></td></tr>';
}

/**
 * Lưu màu nhãn.
 *
 * @param int $term_id ID.
 */
function sgc_term_color_save( $term_id ) {
	if ( ! isset( $_POST['sgc_term_color_nonce'], $_POST['sg_color'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sgc_term_color_nonce'] ), 'sgc_term_color' ) || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}
	$slug = sanitize_key( wp_unslash( $_POST['sg_color'] ) );
	if ( isset( sgc_label_color_choices()[ $slug ] ) ) {
		update_term_meta( $term_id, 'sg_color', $slug );
	}
}

/**
 * Cột "Màu nhãn" trong bảng danh mục.
 *
 * @param array $cols Cột.
 * @return array
 */
function sgc_term_color_column( $cols ) {
	$out = array();
	foreach ( $cols as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'name' === $key ) {
			$out['sg_color'] = esc_html__( 'Màu nhãn', 'sunny-grove-core' );
		}
	}
	return $out;
}

/**
 * Giá trị cột: nhãn mẫu.
 *
 * @param string $value   Giá trị trước.
 * @param string $column  Cột.
 * @param int    $term_id ID.
 * @return string
 */
function sgc_term_color_column_value( $value, $column, $term_id ) {
	if ( 'sg_color' !== $column ) {
		return $value;
	}
	$term = get_term( $term_id );
	return $term && ! is_wp_error( $term ) ? sgc_term_badge_html( $term ) : $value;
}

/**
 * CSS ô chọn màu ở trang danh mục.
 *
 * @param string $hook Trang quản trị.
 */
function sgc_term_color_assets( $hook ) {
	$screen = get_current_screen();
	if ( $screen && in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) && in_array( $screen->taxonomy, sgc_color_taxonomies(), true ) ) {
		wp_enqueue_style( 'sgc-admin', SGC_URL . 'assets/admin.css', array(), SGC_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'sgc_term_color_assets' );
