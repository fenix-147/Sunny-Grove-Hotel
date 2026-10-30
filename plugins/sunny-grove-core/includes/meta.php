<?php
/**
 * Trường tùy chỉnh cho Phòng, Tiện ích, Địa điểm, Ưu đãi (không cần ACF).
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Định nghĩa trường theo loại nội dung.
 *
 * Kiểu: number, int, text, textarea, url, date, select, gallery, video.
 *
 * @return array
 */
function sgc_meta_fields() {
	$fields = array(
		'sg_room'    => array(
			'sg_price_from' => array(
				'type'  => 'number',
				'label' => __( 'Giá từ (VNĐ / đêm)', 'sunny-grove-core' ),
				'help'  => __( 'Chỉ nhập số, ví dụ 1290000. Website tự định dạng theo ngôn ngữ.', 'sunny-grove-core' ),
			),
			'sg_area'       => array(
				'type'  => 'number',
				'label' => __( 'Diện tích (m²)', 'sunny-grove-core' ),
			),
			'sg_adults'     => array(
				'type'  => 'int',
				'label' => __( 'Số người lớn tối đa', 'sunny-grove-core' ),
			),
			'sg_children'   => array(
				'type'  => 'int',
				'label' => __( 'Số trẻ em tối đa', 'sunny-grove-core' ),
			),
			'sg_bed'        => array(
				'type'  => 'text',
				'label' => __( 'Loại giường', 'sunny-grove-core' ),
				'help'  => __( 'Ví dụ: 1 giường đôi + 1 giường tầng cho bé', 'sunny-grove-core' ),
			),
			'sg_view'       => array(
				'type'  => 'text',
				'label' => __( 'Hướng nhìn (view)', 'sunny-grove-core' ),
			),
			'sg_gallery'    => array(
				'type'  => 'gallery',
				'label' => __( 'Thư viện ảnh / video thực tế', 'sunny-grove-core' ),
			),
			'sg_video'      => array(
				'type'  => 'video',
				'label' => __( 'Video ngắn (tùy chọn)', 'sunny-grove-core' ),
				'help'  => __( 'MP4 dưới 5MB, 10–20 giây. Chọn từ Thư viện hoặc dán đường dẫn.', 'sunny-grove-core' ),
			),
		),
		'sg_amenity' => array(
			'sg_short'   => array(
				'type'  => 'textarea',
				'label' => __( 'Mô tả ngắn (hiện trên thẻ)', 'sunny-grove-core' ),
			),
			'sg_hours'   => array(
				'type'  => 'text',
				'label' => __( 'Giờ hoạt động', 'sunny-grove-core' ),
				'help'  => __( 'Ví dụ: 06:00 – 20:00 hằng ngày', 'sunny-grove-core' ),
			),
			'sg_gallery' => array(
				'type'  => 'gallery',
				'label' => __( 'Thư viện ảnh / video thực tế', 'sunny-grove-core' ),
			),
			'sg_video'   => array(
				'type'  => 'video',
				'label' => __( 'Video ngắn cho thẻ (ảnh động)', 'sunny-grove-core' ),
				'help'  => __( 'Video tự chạy không tiếng trên thẻ tiện ích. Để trống thì dùng ảnh đại diện.', 'sunny-grove-core' ),
			),
		),
		'sg_place'   => array(
			'sg_distance' => array(
				'type'  => 'number',
				'label' => __( 'Khoảng cách từ khách sạn (km)', 'sunny-grove-core' ),
			),
			'sg_time'     => array(
				'type'  => 'int',
				'label' => __( 'Thời gian di chuyển (phút)', 'sunny-grove-core' ),
			),
			'sg_mode'     => array(
				'type'    => 'select',
				'label'   => __( 'Phương tiện', 'sunny-grove-core' ),
				'options' => array(
					'walk'      => __( 'Đi bộ', 'sunny-grove-core' ),
					'car'       => __( 'Ô tô', 'sunny-grove-core' ),
					'motorbike' => __( 'Xe máy', 'sunny-grove-core' ),
				),
			),
			'sg_compare'  => array(
				'type'  => 'text',
				'label' => __( 'Dòng so sánh vui (tùy chọn)', 'sunny-grove-core' ),
				'help'  => __( 'Ví dụ: Không cần tới Ý?', 'sunny-grove-core' ),
			),
			'sg_map_url'  => array(
				'type'  => 'url',
				'label' => __( 'Link Google Maps', 'sunny-grove-core' ),
			),
			'sg_video'    => array(
				'type'  => 'video',
				'label' => __( 'Video ngắn (tùy chọn)', 'sunny-grove-core' ),
			),
		),
		'sg_offer'   => array(
			'sg_price'      => array(
				'type'  => 'text',
				'label' => __( 'Giá ưu đãi', 'sunny-grove-core' ),
				'help'  => __( 'Ví dụ: 3.990.000đ / gia đình hoặc Giảm 15%', 'sunny-grove-core' ),
			),
			'sg_old_price'  => array(
				'type'  => 'text',
				'label' => __( 'Giá gốc (gạch ngang, tùy chọn)', 'sunny-grove-core' ),
			),
			'sg_badge'      => array(
				'type'  => 'text',
				'label' => __( 'Nhãn nổi bật (tùy chọn)', 'sunny-grove-core' ),
				'help'  => __( 'Ví dụ: -20% hoặc Bán chạy', 'sunny-grove-core' ),
			),
			'sg_valid_from' => array(
				'type'  => 'date',
				'label' => __( 'Áp dụng từ ngày', 'sunny-grove-core' ),
			),
			'sg_valid_to'   => array(
				'type'  => 'date',
				'label' => __( 'Áp dụng đến ngày', 'sunny-grove-core' ),
				'help'  => __( 'Hết hạn sẽ tự ẩn khỏi website. Để trống = không thời hạn.', 'sunny-grove-core' ),
			),
			'sg_conditions' => array(
				'type'  => 'textarea',
				'label' => __( 'Điều kiện áp dụng', 'sunny-grove-core' ),
			),
		),
	);
	return apply_filters( 'sgc_meta_fields', $fields );
}

/**
 * Đăng ký meta để dùng được với REST và Block Bindings.
 */
function sgc_register_meta() {
	foreach ( sgc_meta_fields() as $post_type => $fields ) {
		foreach ( $fields as $key => $field ) {
			$type = 'string';
			if ( 'number' === $field['type'] ) {
				$type = 'number';
			} elseif ( 'int' === $field['type'] ) {
				$type = 'integer';
			}
			register_post_meta(
				$post_type,
				$key,
				array(
					'type'              => $type,
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => static function ( $value ) use ( $field ) {
						return sgc_sanitize_field( $value, $field );
					},
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}
	}
}
add_action( 'init', 'sgc_register_meta', 6 );

/**
 * Làm sạch giá trị theo kiểu trường.
 *
 * @param mixed $value Giá trị.
 * @param array $field Định nghĩa.
 * @return mixed
 */
function sgc_sanitize_field( $value, $field ) {
	switch ( $field['type'] ) {
		case 'number':
			$value = str_replace( ',', '.', (string) $value );
			return '' === trim( $value ) ? '' : (float) preg_replace( '/[^0-9.]/', '', $value );
		case 'int':
			return '' === trim( (string) $value ) ? '' : absint( $value );
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'url':
			return esc_url_raw( trim( (string) $value ) );
		case 'video':
			$value = trim( (string) $value );
			return ctype_digit( $value ) ? $value : esc_url_raw( $value );
		case 'date':
			return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ? $value : '';
		case 'select':
			return isset( $field['options'][ $value ] ) ? $value : '';
		case 'gallery':
			$ids = array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );
			return implode( ',', $ids );
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Hộp nhập liệu dưới trình soạn thảo.
 */
function sgc_add_meta_boxes() {
	$titles = array(
		'sg_room'    => __( 'Thông tin phòng', 'sunny-grove-core' ),
		'sg_amenity' => __( 'Thông tin tiện ích', 'sunny-grove-core' ),
		'sg_place'   => __( 'Thông tin địa điểm', 'sunny-grove-core' ),
		'sg_offer'   => __( 'Thông tin ưu đãi', 'sunny-grove-core' ),
		'sg_review'  => __( 'Thông tin đánh giá', 'sunny-grove-core' ),
	);
	foreach ( sgc_meta_fields() as $post_type => $fields ) {
		add_meta_box( 'sgc_fields', $titles[ $post_type ] ?? __( 'Thông tin', 'sunny-grove-core' ), 'sgc_render_meta_box', $post_type, 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'sgc_add_meta_boxes' );

/**
 * Hiển thị hộp nhập liệu.
 *
 * @param WP_Post $post Bài.
 */
function sgc_render_meta_box( $post ) {
	$fields = sgc_meta_fields()[ $post->post_type ] ?? array();
	wp_nonce_field( 'sgc_save_meta', 'sgc_meta_nonce' );
	echo '<div class="sgc-fields">';
	foreach ( $fields as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		$id    = 'sgc-' . $key;
		echo '<div class="sgc-field sgc-field--' . esc_attr( $field['type'] ) . '">';
		echo '<label for="' . esc_attr( $id ) . '"><strong>' . esc_html( $field['label'] ) . '</strong>' . ( in_array( $field['type'], array( 'text', 'textarea' ), true ) ? '' : ' <span class="sgc-shared">' . esc_html__( '(dùng chung cho mọi ngôn ngữ)', 'sunny-grove-core' ) . '</span>' ) . '</label>';
		switch ( $field['type'] ) {
			case 'text':
			case 'textarea':
				// Trường chữ: hai ô Tiếng Việt | English cạnh nhau. Ô English trống thì bản tiếng Anh hiện tiếng Việt.
				echo '<div class="sgc-bilingual-field">';
				foreach ( array_keys( sg_languages() ) as $lang ) {
					$name  = sg_default_lang() === $lang ? $key : $key . '_' . $lang;
					$v     = sg_default_lang() === $lang ? $value : get_post_meta( $post->ID, $name, true );
					$in_id = sg_default_lang() === $lang ? $id : $id . '-' . $lang;
					echo '<div class="sgc-bilingual-field__col"><span class="sgc-bilingual-field__lang">' . esc_html( sg_languages()[ $lang ]['name'] ) . '</span>';
					if ( 'textarea' === $field['type'] ) {
						printf( '<textarea id="%1$s" name="%2$s" rows="3" lang="%4$s">%3$s</textarea>', esc_attr( $in_id ), esc_attr( $name ), esc_textarea( $v ), esc_attr( sg_languages()[ $lang ]['locale'] ) );
					} else {
						printf( '<input type="text" id="%1$s" name="%2$s" value="%3$s" class="widefat" lang="%4$s">', esc_attr( $in_id ), esc_attr( $name ), esc_attr( $v ), esc_attr( sg_languages()[ $lang ]['locale'] ) );
					}
					echo '</div>';
				}
				echo '</div>';
				break;
			case 'select':
				printf( '<select id="%1$s" name="%2$s"><option value="">—</option>', esc_attr( $id ), esc_attr( $key ) );
				foreach ( $field['options'] as $opt => $label ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $opt ), selected( $value, $opt, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;
			case 'gallery':
				printf( '<input type="hidden" id="%1$s" name="%2$s" value="%3$s" class="sgc-gallery-input">', esc_attr( $id ), esc_attr( $key ), esc_attr( $value ) );
				echo '<div class="sgc-gallery-preview">';
				foreach ( array_filter( array_map( 'absint', explode( ',', (string) $value ) ) ) as $att_id ) {
					echo sgc_admin_media_thumb( $att_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				echo '</div>';
				printf(
					'<button type="button" class="button sgc-gallery-pick">%1$s</button> <button type="button" class="button-link sgc-gallery-clear">%2$s</button>',
					esc_html__( 'Chọn ảnh / video', 'sunny-grove-core' ),
					esc_html__( 'Xóa hết', 'sunny-grove-core' )
				);
				break;
			case 'video':
				printf( '<input type="text" id="%1$s" name="%2$s" value="%3$s" class="widefat sgc-video-input" placeholder="https://…/video.mp4">', esc_attr( $id ), esc_attr( $key ), esc_attr( $value ) );
				printf( ' <button type="button" class="button sgc-video-pick">%s</button>', esc_html__( 'Chọn từ Thư viện', 'sunny-grove-core' ) );
				break;
			default:
				$input_type = array(
					'number' => 'number',
					'int'    => 'number',
					'url'    => 'url',
					'date'   => 'date',
				);
				$step       = 'number' === $field['type'] ? ' step="any" min="0"' : ( 'int' === $field['type'] ? ' step="1" min="0"' : '' );
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="widefat"%5$s>',
					esc_attr( $input_type[ $field['type'] ] ?? 'text' ),
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( $value ),
					$step // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
		}
		if ( ! empty( $field['help'] ) ) {
			echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
		}
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Ảnh thu nhỏ trong hộp gallery.
 *
 * @param int $att_id ID tệp.
 * @return string
 */
function sgc_admin_media_thumb( $att_id ) {
	if ( wp_attachment_is( 'video', $att_id ) ) {
		return '<span class="sgc-thumb sgc-thumb--video" data-id="' . (int) $att_id . '"><span class="dashicons dashicons-video-alt3"></span></span>';
	}
	$img = wp_get_attachment_image( $att_id, array( 80, 80 ) );
	return $img ? '<span class="sgc-thumb" data-id="' . (int) $att_id . '">' . $img . '</span>' : '';
}

/**
 * Lưu dữ liệu.
 *
 * @param int $post_id ID.
 */
function sgc_save_meta( $post_id ) {
	if ( ! isset( $_POST['sgc_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sgc_meta_nonce'] ), 'sgc_save_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$fields = sgc_meta_fields()[ get_post_type( $post_id ) ] ?? array();
	foreach ( $fields as $key => $field ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$value = sgc_sanitize_field( wp_unslash( $_POST[ $key ] ), $field ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( '' === $value || null === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
		// Bản tiếng Anh của trường chữ.
		if ( in_array( $field['type'], array( 'text', 'textarea' ), true ) ) {
			foreach ( sg_secondary_langs() as $lang ) {
				$tr_key = $key . '_' . $lang;
				if ( ! isset( $_POST[ $tr_key ] ) ) {
					continue;
				}
				$tr = sgc_sanitize_field( wp_unslash( $_POST[ $tr_key ] ), $field ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( '' === $tr ) {
					delete_post_meta( $post_id, $tr_key );
				} else {
					update_post_meta( $post_id, $tr_key, $tr );
				}
			}
		}
	}
}
add_action( 'save_post', 'sgc_save_meta' );

/**
 * Tài nguyên cho màn hình sửa bài.
 *
 * @param string $hook Trang quản trị.
 */
function sgc_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen || ! isset( sgc_meta_fields()[ $screen->post_type ] ) || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'sgc-admin', SGC_URL . 'assets/admin.css', array(), SGC_VERSION );
	wp_enqueue_script( 'sgc-admin', SGC_URL . 'assets/admin.js', array( 'jquery' ), SGC_VERSION, true );
	wp_localize_script(
		'sgc-admin',
		'sgcAdmin',
		array(
			'galleryTitle' => __( 'Chọn ảnh / video cho thư viện', 'sunny-grove-core' ),
			'videoTitle'   => __( 'Chọn video', 'sunny-grove-core' ),
			'choose'       => __( 'Dùng các mục đã chọn', 'sunny-grove-core' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'sgc_admin_assets' );
