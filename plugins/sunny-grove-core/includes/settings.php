<?php
/**
 * Trang cài đặt: Thông tin khách sạn & Nhãn hiển thị (song ngữ).
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Giá trị mặc định của một trường song ngữ theo ngôn ngữ.
 *
 * @param array  $field Định nghĩa trường.
 * @param string $lang  Ngôn ngữ.
 * @return string
 */
function sgc_default_for_lang( $field, $lang ) {
	if ( isset( $field[ $lang ] ) ) {
		return $field[ $lang ];
	}
	return $field['en'] ?? ( $field['vi'] ?? '' );
}

/**
 * Nạp giá trị mặc định (chỉ những trường còn trống) – chạy khi kích hoạt plugin.
 */
function sgc_seed_options() {
	$hotel   = sgc_data( 'hotel' );
	$general = (array) get_option( 'sgc_hotel', array() );
	foreach ( $hotel['general'] as $key => $field ) {
		if ( ! isset( $general[ $key ] ) ) {
			$general[ $key ] = $field['value'];
		}
	}
	update_option( 'sgc_hotel', $general );

	$i18n   = (array) get_option( 'sgc_hotel_i18n', array() );
	$labels = (array) get_option( 'sgc_labels', array() );
	$schema = sgc_data( 'labels' );
	foreach ( array_keys( sg_languages() ) as $lang ) {
		foreach ( $hotel['i18n'] as $key => $field ) {
			if ( ! isset( $i18n[ $lang ][ $key ] ) ) {
				$i18n[ $lang ][ $key ] = sgc_default_for_lang( $field, $lang );
			}
		}
		foreach ( $schema['fields'] as $key => $field ) {
			if ( ! isset( $labels[ $lang ][ $key ] ) ) {
				$labels[ $lang ][ $key ] = sgc_default_for_lang( $field, $lang );
			}
		}
	}
	update_option( 'sgc_hotel_i18n', $i18n );
	update_option( 'sgc_labels', $labels );
}

/**
 * Lấy thông tin khách sạn. Trường song ngữ tự lấy theo ngôn ngữ đang xem.
 *
 * @param string $key  Khóa.
 * @param string $lang Ngôn ngữ.
 * @return string
 */
function sgc_hotel( $key, $lang = '' ) {
	$hotel = sgc_data( 'hotel' );
	if ( isset( $hotel['i18n'][ $key ] ) ) {
		$lang = $lang ? $lang : sg_current_lang();
		$i18n = (array) get_option( 'sgc_hotel_i18n', array() );
		if ( isset( $i18n[ $lang ][ $key ] ) && '' !== $i18n[ $lang ][ $key ] ) {
			return $i18n[ $lang ][ $key ];
		}
		return sgc_default_for_lang( $hotel['i18n'][ $key ], $lang );
	}
	$general = (array) get_option( 'sgc_hotel', array() );
	if ( isset( $general[ $key ] ) ) {
		return $general[ $key ];
	}
	return $hotel['general'][ $key ]['value'] ?? '';
}

/**
 * Lấy nhãn hiển thị theo ngôn ngữ, có thay biến.
 *
 * @param string $key  Khóa nhãn.
 * @param array  $vars Biến cần thay, ví dụ [ 'n' => 2 ].
 * @param string $lang Ngôn ngữ.
 * @return string
 */
function sgc_label( $key, $vars = array(), $lang = '' ) {
	$lang   = $lang ? $lang : sg_current_lang();
	$labels = (array) get_option( 'sgc_labels', array() );
	if ( isset( $labels[ $lang ][ $key ] ) && '' !== $labels[ $lang ][ $key ] ) {
		$text = $labels[ $lang ][ $key ];
	} else {
		$schema = sgc_data( 'labels' );
		$text   = isset( $schema['fields'][ $key ] ) ? sgc_default_for_lang( $schema['fields'][ $key ], $lang ) : '';
	}
	$vars['nam'] = $vars['nam'] ?? wp_date( 'Y' );
	return sgc_fill( $text, $vars );
}

/**
 * Link trang Đặt phòng theo ngôn ngữ đang xem.
 *
 * @param array $args Tham số thêm vào URL.
 * @return string
 */
function sgc_booking_url( $args = array() ) {
	$page_id = (int) sgc_hotel( 'booking_page' );
	$url     = $page_id ? get_permalink( $page_id ) : '';
	if ( ! $url ) {
		$url = home_url( '/' );
	}
	return $args ? add_query_arg( array_map( 'rawurlencode', $args ), $url ) : $url;
}

/**
 * Menu quản trị.
 */
function sgc_admin_menu() {
	add_menu_page(
		__( 'Sunny Grove', 'sunny-grove-core' ),
		__( 'Sunny Grove', 'sunny-grove-core' ),
		'manage_sunny_grove',
		'sgc-hotel',
		'sgc_render_hotel_page',
		'dashicons-palmtree',
		3
	);
	add_submenu_page( 'sgc-hotel', __( 'Thông tin khách sạn', 'sunny-grove-core' ), __( 'Thông tin khách sạn', 'sunny-grove-core' ), 'manage_sunny_grove', 'sgc-hotel', 'sgc_render_hotel_page' );
	add_submenu_page( 'sgc-hotel', __( 'Nhãn hiển thị', 'sunny-grove-core' ), __( 'Nhãn hiển thị', 'sunny-grove-core' ), 'manage_sunny_grove', 'sgc-labels', 'sgc_render_labels_page' );
}
add_action( 'admin_menu', 'sgc_admin_menu' );

/**
 * CSS chung cho trang cài đặt.
 *
 * @param string $hook Trang.
 */
function sgc_settings_assets( $hook ) {
	if ( false !== strpos( $hook, 'sgc-' ) ) {
		wp_enqueue_style( 'sgc-admin', SGC_URL . 'assets/admin.css', array(), SGC_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'sgc_settings_assets' );

/**
 * Ô nhập theo kiểu.
 *
 * @param string $name  Tên input.
 * @param array  $field Định nghĩa.
 * @param mixed  $value Giá trị.
 * @param string $lang  Ngôn ngữ của ô (để gắn thuộc tính lang cho trình kiểm tra chính tả).
 */
function sgc_settings_input( $name, $field, $value, $lang = '' ) {
	$type     = $field['type'] ?? 'text';
	$lang_att = $lang ? ' lang="' . esc_attr( sg_languages()[ $lang ]['locale'] ?? $lang ) . '"' : '';
	switch ( $type ) {
		case 'textarea':
			printf( '<textarea name="%1$s" class="large-text" rows="3"%3$s>%2$s</textarea>', esc_attr( $name ), esc_textarea( $value ), $lang_att ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			break;
		case 'checkbox':
			printf( '<input type="hidden" name="%1$s" value="0"><label><input type="checkbox" name="%1$s" value="1" %2$s> %3$s</label>', esc_attr( $name ), checked( $value, '1', false ), esc_html__( 'Bật', 'sunny-grove-core' ) );
			break;
		case 'select':
			printf( '<select name="%s">', esc_attr( $name ) );
			foreach ( $field['options'] as $opt => $label ) {
				printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $opt ), selected( $value, $opt, false ), esc_html( $label ) );
			}
			echo '</select>';
			break;
		case 'page':
			wp_dropdown_pages(
				array(
					'name'              => esc_attr( $name ),
					'selected'          => (int) $value,
					'show_option_none'  => '—',
					'option_none_value' => '',
				)
			);
			break;
		default:
			$input = array(
				'email'  => 'email',
				'url'    => 'url',
				'number' => 'number',
			);
			printf( '<input type="%1$s" name="%2$s" value="%3$s" class="regular-text"%4$s%5$s>', esc_attr( $input[ $type ] ?? 'text' ), esc_attr( $name ), esc_attr( $value ), 'number' === $type ? ' step="any"' : '', $lang_att ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( ! empty( $field['help'] ) && ! $lang ) {
		echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
	}
}

/**
 * Hàng song ngữ: tên trường | ô Tiếng Việt | ô English.
 *
 * @param string   $label  Tên trường.
 * @param callable $render Hàm in ô nhập cho một ngôn ngữ: fn( $lang ).
 * @param string   $help   Hướng dẫn.
 */
function sgc_settings_bilingual_row( $label, $render, $help = '' ) {
	echo '<tr><th scope="row">' . esc_html( $label );
	if ( $help ) {
		echo '<p class="description">' . esc_html( $help ) . '</p>';
	}
	echo '</th>';
	foreach ( array_keys( sg_languages() ) as $lang ) {
		echo '<td class="sgc-lang-cell" data-lang="' . esc_attr( $lang ) . '">';
		$render( $lang );
		echo '</td>';
	}
	echo '</tr>';
}

/**
 * Tiêu đề cột ngôn ngữ của bảng song ngữ.
 */
function sgc_settings_bilingual_head() {
	echo '<thead><tr><th></th>';
	foreach ( sg_languages() as $lang ) {
		echo '<th scope="col" class="sgc-lang-head">' . esc_html( $lang['name'] ) . '</th>';
	}
	echo '</tr></thead>';
}

/**
 * Tên và mô tả website theo ngôn ngữ (ngôn ngữ mặc định lưu ở Cài đặt → Tổng quan của WordPress).
 *
 * @param string $option blogname | blogdescription.
 * @param string $lang   Ngôn ngữ.
 * @return string
 */
function sgc_site_string( $option, $lang ) {
	if ( sg_default_lang() === $lang ) {
		return (string) get_option( $option );
	}
	$strings = (array) get_option( 'sg_i18n_strings', array() );
	return (string) ( $strings[ $lang ][ $option ] ?? '' );
}

/**
 * Trang Thông tin khách sạn.
 */
function sgc_render_hotel_page() {
	$hotel = sgc_data( 'hotel' );
	echo '<div class="wrap sgc-settings"><h1>' . esc_html__( 'Thông tin khách sạn', 'sunny-grove-core' ) . '</h1>';
	sgc_settings_notice();
	echo '<p>' . esc_html__( 'Các thông tin này hiển thị ở đầu trang, chân trang, trang Liên hệ và nút chat nổi. Sửa ở đây là toàn bộ website cập nhật theo.', 'sunny-grove-core' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'sgc_save_settings' );
	echo '<input type="hidden" name="action" value="sgc_save_settings"><input type="hidden" name="sgc_page" value="hotel">';

	echo '<h2 class="sgc-group-title">' . esc_html__( 'Chữ hiển thị (nhập cả hai ngôn ngữ)', 'sunny-grove-core' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'Ô English để trống thì website bản tiếng Anh hiện chữ tiếng Việt.', 'sunny-grove-core' ) . '</p>';
	echo '<table class="form-table sgc-bilingual" role="presentation">';
	sgc_settings_bilingual_head();
	sgc_settings_bilingual_row(
		__( 'Tên website', 'sunny-grove-core' ),
		static function ( $lang ) {
			sgc_settings_input( 'sgc_site[' . $lang . '][blogname]', array(), sgc_site_string( 'blogname', $lang ), $lang );
		}
	);
	sgc_settings_bilingual_row(
		__( 'Mô tả ngắn của website (tagline)', 'sunny-grove-core' ),
		static function ( $lang ) {
			sgc_settings_input( 'sgc_site[' . $lang . '][blogdescription]', array(), sgc_site_string( 'blogdescription', $lang ), $lang );
		}
	);
	foreach ( $hotel['i18n'] as $key => $field ) {
		sgc_settings_bilingual_row(
			$field['label'],
			static function ( $lang ) use ( $key, $field ) {
				sgc_settings_input( 'sgc_i18n[' . $lang . '][' . $key . ']', $field, sgc_hotel( $key, $lang ), $lang );
			},
			$field['help'] ?? ''
		);
	}
	echo '</table>';

	echo '<h2 class="sgc-group-title">' . esc_html__( 'Thông tin chung (dùng cho mọi ngôn ngữ)', 'sunny-grove-core' ) . '</h2>';
	echo '<table class="form-table" role="presentation">';
	foreach ( $hotel['general'] as $key => $field ) {
		echo '<tr><th scope="row">' . esc_html( $field['label'] ) . '</th><td>';
		sgc_settings_input( 'sgc_general[' . $key . ']', $field, sgc_hotel( $key ) );
		echo '</td></tr>';
	}
	echo '</table>';
	submit_button( __( 'Lưu thay đổi', 'sunny-grove-core' ) );
	echo '</form></div>';
}

/**
 * Trang Nhãn hiển thị.
 */
function sgc_render_labels_page() {
	$schema = sgc_data( 'labels' );
	$labels = (array) get_option( 'sgc_labels', array() );
	echo '<div class="wrap sgc-settings"><h1>' . esc_html__( 'Nhãn hiển thị', 'sunny-grove-core' ) . '</h1>';
	sgc_settings_notice();
	echo '<p>' . esc_html__( 'Các chữ nhỏ dùng chung trên website (nút, đơn vị, thông báo). Chữ trong ngoặc nhọn như {n}, {gia} sẽ được thay bằng số liệu thật – hãy giữ nguyên chúng.', 'sunny-grove-core' ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'sgc_save_settings' );
	echo '<input type="hidden" name="action" value="sgc_save_settings"><input type="hidden" name="sgc_page" value="labels">';
	foreach ( $schema['groups'] as $group => $group_label ) {
		echo '<h2 class="sgc-group-title">' . esc_html( $group_label ) . '</h2><table class="form-table sgc-bilingual" role="presentation">';
		sgc_settings_bilingual_head();
		foreach ( $schema['fields'] as $key => $field ) {
			if ( $field['group'] !== $group ) {
				continue;
			}
			sgc_settings_bilingual_row(
				$field['label'],
				static function ( $lang ) use ( $key, $field, $labels ) {
					$value = $labels[ $lang ][ $key ] ?? sgc_default_for_lang( $field, $lang );
					sgc_settings_input( 'sgc_labels[' . $lang . '][' . $key . ']', array(), $value, $lang );
				}
			);
		}
		echo '</table>';
	}
	submit_button( __( 'Lưu thay đổi', 'sunny-grove-core' ) );
	echo '</form></div>';
}

/**
 * Thông báo đã lưu.
 */
function sgc_settings_notice() {
	if ( isset( $_GET['sgc_saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Đã lưu.', 'sunny-grove-core' ) . '</p></div>';
	}
}

/**
 * Lưu cài đặt.
 */
function sgc_save_settings() {
	if ( ! current_user_can( 'manage_sunny_grove' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'sunny-grove-core' ) );
	}
	check_admin_referer( 'sgc_save_settings' );

	$page  = isset( $_POST['sgc_page'] ) ? sanitize_key( $_POST['sgc_page'] ) : '';
	$langs = sg_languages();
	$read  = static function ( $key ) {
		return isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	};

	if ( 'hotel' === $page ) {
		$hotel   = sgc_data( 'hotel' );
		$general = (array) get_option( 'sgc_hotel', array() );
		$input   = $read( 'sgc_general' );
		foreach ( $hotel['general'] as $key => $field ) {
			if ( isset( $input[ $key ] ) ) {
				$general[ $key ] = sgc_sanitize_setting( $input[ $key ], $field );
			}
		}
		update_option( 'sgc_hotel', $general );

		$i18n  = (array) get_option( 'sgc_hotel_i18n', array() );
		$input = $read( 'sgc_i18n' );
		foreach ( array_keys( $langs ) as $lang ) {
			foreach ( $hotel['i18n'] as $key => $field ) {
				if ( isset( $input[ $lang ][ $key ] ) ) {
					$i18n[ $lang ][ $key ] = sgc_sanitize_setting( $input[ $lang ][ $key ], $field );
				}
			}
		}
		update_option( 'sgc_hotel_i18n', $i18n );

		// Tên/mô tả website: ngôn ngữ mặc định lưu vào tùy chọn của WordPress, ngôn ngữ khác lưu riêng.
		$site    = $read( 'sgc_site' );
		$strings = (array) get_option( 'sg_i18n_strings', array() );
		foreach ( array_keys( $langs ) as $lang ) {
			foreach ( array( 'blogname', 'blogdescription' ) as $option ) {
				if ( ! isset( $site[ $lang ][ $option ] ) ) {
					continue;
				}
				$value = sanitize_text_field( $site[ $lang ][ $option ] );
				if ( sg_default_lang() === $lang ) {
					update_option( $option, $value );
				} else {
					$strings[ $lang ][ $option ] = $value;
				}
			}
		}
		update_option( 'sg_i18n_strings', $strings );
	} elseif ( 'labels' === $page ) {
		$schema = sgc_data( 'labels' );
		$labels = (array) get_option( 'sgc_labels', array() );
		$input  = $read( 'sgc_labels' );
		foreach ( array_keys( $langs ) as $lang ) {
			foreach ( array_keys( $schema['fields'] ) as $key ) {
				if ( isset( $input[ $lang ][ $key ] ) ) {
					$labels[ $lang ][ $key ] = sanitize_text_field( $input[ $lang ][ $key ] );
				}
			}
		}
		update_option( 'sgc_labels', $labels );
	}

	do_action( 'sgc_settings_saved', $page );
	$slug = 'labels' === $page ? 'sgc-labels' : 'sgc-hotel';
	wp_safe_redirect( admin_url( 'admin.php?page=' . $slug . '&sgc_saved=1' ) );
	exit;
}
add_action( 'admin_post_sgc_save_settings', 'sgc_save_settings' );

/**
 * Làm sạch một giá trị cài đặt.
 *
 * @param mixed $value Giá trị.
 * @param array $field Định nghĩa.
 * @return string
 */
function sgc_sanitize_setting( $value, $field ) {
	switch ( $field['type'] ?? 'text' ) {
		case 'textarea':
			return sanitize_textarea_field( $value );
		case 'email':
			return sanitize_email( $value );
		case 'url':
			return esc_url_raw( $value );
		case 'checkbox':
			return '1' === (string) $value ? '1' : '0';
		case 'page':
			return (string) absint( $value );
		case 'select':
			return isset( $field['options'][ $value ] ) ? $value : (string) array_key_first( $field['options'] );
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Cung cấp thông tin liên hệ cho plugin Sunny Grove Booking khi ô bên đó để trống.
 *
 * @param array  $info Thông tin từ plugin đặt phòng.
 * @param string $lang Ngôn ngữ.
 * @return array
 */
function sgc_share_contact_info( $info, $lang = '' ) {
	$map = array(
		'hotline'    => sgc_hotel( 'phone' ),
		'zalo'       => sgc_hotel( 'zalo' ),
		'whatsapp'   => sgc_hotel( 'whatsapp' ),
		'hotel_name' => sgc_hotel( 'name', $lang ),
	);
	foreach ( $map as $key => $value ) {
		if ( empty( $info[ $key ] ) && $value ) {
			$info[ $key ] = $value;
		}
	}
	return $info;
}
add_filter( 'sgb_contact_info', 'sgc_share_contact_info', 10, 2 );
