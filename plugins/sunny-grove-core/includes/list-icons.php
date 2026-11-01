<?php
/**
 * Danh sách có biểu tượng: mỗi mục danh sách (gạch đầu dòng) có một icon riêng.
 *
 * - Mục danh sách lưu tên icon trong thuộc tính sgIcon (dùng chung cho mọi ngôn ngữ). Chưa chọn → icon lá.
 * - Icon được chèn khi hiển thị (SVG, aria-hidden) nên nội dung bài và bản dịch không bị ảnh hưởng.
 * - Danh sách đánh số (1, 2, 3…) giữ nguyên số.
 * - Tiện nghi trong phòng: mỗi tiện nghi có icon riêng (term meta sg_icon).
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Hiển thị ngoài website
 * ---------------------------------------------------------------------- */

/**
 * Thuộc tính sgIcon cho block mục danh sách.
 *
 * @param array  $args Tham số block.
 * @param string $name Tên block.
 * @return array
 */
function sgc_list_icon_block_args( $args, $name ) {
	if ( 'core/list-item' === $name ) {
		$args['attributes']           = $args['attributes'] ?? array();
		$args['attributes']['sgIcon'] = array( 'type' => 'string' );
	}
	return $args;
}
add_filter( 'register_block_type_args', 'sgc_list_icon_block_args', 10, 2 );

/**
 * Đánh dấu mục nằm trong danh sách đánh số (không gắn icon).
 *
 * @param array         $block  Block sắp hiển thị.
 * @param array         $source Block gốc.
 * @param WP_Block|null $parent Block cha.
 * @return array
 */
function sgc_list_icon_block_data( $block, $source = array(), $parent = null ) {
	if ( 'core/list-item' === ( $block['blockName'] ?? '' ) && $parent instanceof WP_Block && 'core/list' === $parent->name ) {
		$block['sgcOrdered'] = ! empty( $parent->attributes['ordered'] );
	}
	return $block;
}
add_filter( 'render_block_data', 'sgc_list_icon_block_data', 10, 3 );

/**
 * Chèn icon vào đầu mục danh sách (sau khi đã thay chữ theo ngôn ngữ).
 *
 * @param string $content HTML mục.
 * @param array  $block   Block.
 * @return string
 */
function sgc_list_icon_render_item( $content, $block ) {
	if ( ! empty( $block['sgcOrdered'] ) || false !== strpos( $content, 'sg-li-icon' ) ) {
		return $content;
	}
	$icon = sgc_icon( sgc_icon_or_default( $block['attrs']['sgIcon'] ?? '' ), 20, 'sg-li-icon' );
	return preg_replace( '~^(\s*<li\b[^>]*>)~', '$1' . $icon, $content, 1 );
}
add_filter( 'render_block_core/list-item', 'sgc_list_icon_render_item', 20, 2 );

/**
 * Danh sách gạch đầu dòng → class sg-icon-list (bỏ chấm tròn, chừa chỗ cho icon).
 *
 * @param string $content HTML danh sách.
 * @param array  $block   Block.
 * @return string
 */
function sgc_list_icon_render_list( $content, $block ) {
	if ( ! empty( $block['attrs']['ordered'] ) ) {
		return $content;
	}
	$proc = new WP_HTML_Tag_Processor( $content );
	if ( $proc->next_tag( array( 'tag_name' => 'ul' ) ) ) {
		$proc->add_class( 'sg-icon-list' );
		return $proc->get_updated_html();
	}
	return $content;
}
add_filter( 'render_block_core/list', 'sgc_list_icon_render_list', 20, 2 );

/**
 * CSS của danh sách có biểu tượng: chỉ nạp khi trang có danh sách; trong trình soạn thảo luôn nạp.
 */
function sgc_list_icon_styles() {
	wp_register_style( 'sgc-list-icons', SGC_URL . 'assets/list-icons.css', array(), SGC_VERSION );
	wp_enqueue_block_style(
		'core/list',
		array(
			'handle' => 'sgc-list-icons',
			'src'    => SGC_URL . 'assets/list-icons.css',
			'path'   => SGC_DIR . 'assets/list-icons.css',
			'ver'    => SGC_VERSION,
		)
	);
}
add_action( 'init', 'sgc_list_icon_styles' );

/**
 * CSS trong khung soạn thảo.
 */
function sgc_list_icon_canvas_styles() {
	if ( is_admin() ) {
		wp_enqueue_style( 'sgc-list-icons' );
	}
}
add_action( 'enqueue_block_assets', 'sgc_list_icon_canvas_styles' );

/* -------------------------------------------------------------------------
 * Trình soạn thảo: nút chọn icon cho từng mục
 * ---------------------------------------------------------------------- */

/**
 * Danh sách icon cho JavaScript (tên VI, EN, từ khóa, hình).
 *
 * @return array
 */
function sgc_icon_choices_js() {
	$out = array();
	foreach ( sgc_icon_choices() as $name => $icon ) {
		$out[] = array(
			'name' => $name,
			'vi'   => $icon['vi'],
			'en'   => $icon['en'] ?? $icon['vi'],
			'k'    => $icon['k'] ?? '',
			'd'    => $icon['d'],
		);
	}
	return $out;
}

/**
 * Nhãn của bảng chọn icon.
 *
 * @return array
 */
function sgc_icon_picker_labels() {
	return array(
		'button'   => __( 'Chọn icon cho mục này', 'sunny-grove-core' ),
		'locked'   => __( 'Icon dùng chung cho cả hai ngôn ngữ – gạt về VI để đổi', 'sunny-grove-core' ),
		'title'    => __( 'Icon của mục danh sách', 'sunny-grove-core' ),
		'search'   => __( 'Tìm icon (VD: bơi, pool, giường…)', 'sunny-grove-core' ),
		'none'     => __( 'Không tìm thấy icon phù hợp.', 'sunny-grove-core' ),
		'reset'    => __( 'Dùng icon mặc định (lá)', 'sunny-grove-core' ),
		'current'  => __( 'Đang dùng:', 'sunny-grove-core' ),
		'default'  => __( 'mặc định', 'sunny-grove-core' ),
		'noIcon'   => __( 'Danh sách đánh số không dùng icon.', 'sunny-grove-core' ),
	);
}

/**
 * Nạp script chọn icon trong trình soạn thảo.
 */
function sgc_list_icon_editor_assets() {
	wp_enqueue_script(
		'sgc-list-icons-editor',
		SGC_URL . 'assets/list-icons-editor.js',
		array( 'wp-hooks', 'wp-compose', 'wp-element', 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-data' ),
		SGC_VERSION,
		true
	);
	wp_add_inline_script(
		'sgc-list-icons-editor',
		'window.sgcListIcons = ' . wp_json_encode(
			array(
				'icons'  => sgc_icon_choices_js(),
				'def'    => SGC_DEFAULT_ICON,
				'lang'   => 0 === strpos( get_user_locale(), 'vi' ) ? 'vi' : 'en',
				'labels' => sgc_icon_picker_labels(),
			)
		) . ';',
		'before'
	);
	wp_enqueue_style( 'sgc-list-icons-editor-ui', SGC_URL . 'assets/list-icons-editor.css', array(), SGC_VERSION );
}
add_action( 'enqueue_block_editor_assets', 'sgc_list_icon_editor_assets' );

/* -------------------------------------------------------------------------
 * Tiện nghi trong phòng: icon riêng cho từng tiện nghi
 * ---------------------------------------------------------------------- */

/**
 * Phân loại có ô chọn icon.
 *
 * @return string[]
 */
function sgc_icon_taxonomies() {
	return array( 'sg_room_feature' );
}

/**
 * Icon của một tiện nghi (mặc định: lá).
 *
 * @param int $term_id ID.
 * @return string
 */
function sgc_term_icon( $term_id ) {
	return sgc_icon_or_default( get_term_meta( (int) $term_id, 'sg_icon', true ) );
}

/**
 * Bảng chọn icon dạng lưới (ô radio) cho form trong trang quản trị.
 *
 * @param string $name    Tên trường.
 * @param string $current Icon đang chọn.
 * @return string
 */
function sgc_icon_field_html( $name, $current ) {
	$current = sgc_icon_or_default( $current );
	$labels  = sgc_icon_picker_labels();
	$lang    = 0 === strpos( get_user_locale(), 'vi' ) ? 'vi' : 'en';
	$html    = '<div class="sgc-icon-field">';
	$html   .= '<input type="search" class="sgc-icon-field__search" placeholder="' . esc_attr( $labels['search'] ) . '" aria-label="' . esc_attr( $labels['search'] ) . '">';
	$html   .= '<div class="sgc-icon-field__grid" role="radiogroup" aria-label="' . esc_attr( $labels['title'] ) . '">';
	foreach ( sgc_icon_choices() as $icon_name => $icon ) {
		$label = $icon[ $lang ] ?? $icon['vi'];
		$html .= sprintf(
			'<label class="sgc-icon-field__item" title="%1$s" data-search="%2$s"><input type="radio" name="%3$s" value="%4$s"%5$s><span class="sgc-icon-field__svg">%6$s</span><span class="screen-reader-text">%1$s</span></label>',
			esc_attr( $label ),
			esc_attr( $icon['vi'] . ' ' . ( $icon['en'] ?? '' ) . ' ' . ( $icon['k'] ?? '' ) ),
			esc_attr( $name ),
			esc_attr( $icon_name ),
			checked( $icon_name, $current, false ),
			sgc_icon( $icon_name, 24 )
		);
	}
	$html .= '</div><p class="sgc-icon-field__none" hidden>' . esc_html( $labels['none'] ) . '</p></div>';
	return $html;
}

/**
 * Gắn ô chọn icon vào form tiện nghi.
 */
function sgc_icon_term_hooks() {
	foreach ( sgc_icon_taxonomies() as $tax ) {
		add_action( "{$tax}_add_form_fields", 'sgc_icon_term_add_field' );
		add_action( "{$tax}_edit_form_fields", 'sgc_icon_term_edit_field' );
		add_action( "created_{$tax}", 'sgc_icon_term_save' );
		add_action( "edited_{$tax}", 'sgc_icon_term_save' );
		add_filter( "manage_edit-{$tax}_columns", 'sgc_icon_term_column' );
		add_filter( "manage_{$tax}_custom_column", 'sgc_icon_term_column_value', 10, 3 );
	}
}
add_action( 'admin_init', 'sgc_icon_term_hooks' );

/**
 * Ô ở form thêm mới.
 */
function sgc_icon_term_add_field() {
	wp_nonce_field( 'sgc_term_icon', 'sgc_term_icon_nonce' );
	echo '<div class="form-field"><label>' . esc_html__( 'Icon', 'sunny-grove-core' ) . '</label>';
	echo sgc_icon_field_html( 'sg_icon', SGC_DEFAULT_ICON ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p>' . esc_html__( 'Icon hiện trước tên tiện nghi ở thẻ phòng và trang chi tiết phòng (dùng chung cho cả bản tiếng Anh).', 'sunny-grove-core' ) . '</p></div>';
}

/**
 * Ô ở form sửa.
 *
 * @param WP_Term $term Term.
 */
function sgc_icon_term_edit_field( $term ) {
	wp_nonce_field( 'sgc_term_icon', 'sgc_term_icon_nonce' );
	echo '<tr class="form-field"><th scope="row">' . esc_html__( 'Icon', 'sunny-grove-core' ) . '</th><td>';
	echo sgc_icon_field_html( 'sg_icon', sgc_term_icon( $term->term_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p class="description">' . esc_html__( 'Icon hiện trước tên tiện nghi ở thẻ phòng và trang chi tiết phòng (dùng chung cho cả bản tiếng Anh).', 'sunny-grove-core' ) . '</p></td></tr>';
}

/**
 * Lưu icon của tiện nghi.
 *
 * @param int $term_id ID.
 */
function sgc_icon_term_save( $term_id ) {
	if ( ! isset( $_POST['sgc_term_icon_nonce'], $_POST['sg_icon'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sgc_term_icon_nonce'] ), 'sgc_term_icon' ) || ! current_user_can( 'edit_term', $term_id ) ) {
		return;
	}
	update_term_meta( $term_id, 'sg_icon', sgc_icon_or_default( sanitize_key( wp_unslash( $_POST['sg_icon'] ) ) ) );
}

/**
 * Cột icon trong bảng tiện nghi.
 *
 * @param array $cols Cột.
 * @return array
 */
function sgc_icon_term_column( $cols ) {
	$out = array();
	foreach ( $cols as $key => $label ) {
		if ( 'name' === $key ) {
			$out['sg_icon'] = esc_html__( 'Icon', 'sunny-grove-core' );
		}
		$out[ $key ] = $label;
	}
	return $out;
}

/**
 * Giá trị cột icon.
 *
 * @param string $value   Giá trị.
 * @param string $column  Cột.
 * @param int    $term_id ID.
 * @return string
 */
function sgc_icon_term_column_value( $value, $column, $term_id ) {
	return 'sg_icon' === $column ? '<span class="sgc-icon-cell">' . sgc_icon( sgc_term_icon( $term_id ), 22 ) . '</span>' : $value;
}

/**
 * CSS & JS cho ô chọn icon ở trang tiện nghi.
 *
 * @param string $hook Trang.
 */
function sgc_icon_term_assets( $hook ) {
	$screen = get_current_screen();
	if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) || ! $screen || ! in_array( $screen->taxonomy, sgc_icon_taxonomies(), true ) ) {
		return;
	}
	wp_enqueue_style( 'sgc-list-icons-editor-ui', SGC_URL . 'assets/list-icons-editor.css', array(), SGC_VERSION );
	wp_enqueue_script( 'sgc-icon-field', SGC_URL . 'assets/icon-field.js', array(), SGC_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'sgc_icon_term_assets' );

/* -------------------------------------------------------------------------
 * Gợi ý icon theo nội dung chữ (dùng khi chuyển nội dung cũ và nhập nội dung mẫu)
 * ---------------------------------------------------------------------- */

/**
 * Đoán icon phù hợp với một dòng chữ. Không chắc → chuỗi rỗng (dùng icon lá mặc định).
 *
 * @param string $text Chữ của mục (VI hoặc EN).
 * @return string
 */
function sgc_icon_guess( $text ) {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) ) ) );
	$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
	if ( '' === $text ) {
		return '';
	}
	// Các câu cụ thể có sẵn trong nội dung mẫu.
	$exact = array(
		'nôi em bé, giường phụ được chuẩn bị sẵn' => 'crib',
	);
	if ( isset( $exact[ $text ] ) ) {
		return $exact[ $text ];
	}
	// Quy tắc theo từ khóa – quy tắc đứng trước được ưu tiên.
	$rules = array(
		'bunk'      => array( 'giường tầng', 'bunk' ),
		'sofa'      => array( 'sofa' ),
		'bed'       => array( 'giường', 'bed', 'beds' ),
		'crib'      => array( 'nôi', 'cũi', 'cot', 'cots', 'crib' ),
		'dining'    => array( 'thực đơn', 'bữa sáng', 'bữa trưa', 'bữa tối', 'nhà hàng', 'ẩm thực', 'breakfast', 'lunch', 'dinner', 'restaurant', 'menu' ),
		'greeting'  => array( 'nhân viên', 'tiếng anh', 'tiếng việt', 'xin chào', 'thân thiện', 'staff', 'friendly', 'english' ),
		'voucher'   => array( 'giá tốt', 'giảm giá', 'ưu đãi', 'trung gian', 'khuyến mãi', 'discount', 'best price', 'deal' ),
		'phone'     => array( 'gọi', 'gọi lại', 'điện thoại', 'hotline', 'call', 'phone' ),
		'card'      => array( 'thanh toán', 'chuyển khoản', 'đặt cọc', 'payment', 'pay', 'deposit' ),
		'mail'      => array( 'email', 'e-mail' ),
		'bath'      => array( 'bồn tắm', 'bathtub', 'bath' ),
		'shower'    => array( 'vòi sen', 'shower' ),
		'balcony'   => array( 'ban công', 'balcony', 'terrace' ),
		'heart'     => array( 'kỷ niệm', 'trăng mật', 'lãng mạn', 'ngày cưới', 'honeymoon', 'romantic', 'anniversary', 'anniversaries' ),
		'users'     => array( 'nhóm', 'bạn bè', 'người bạn', 'friends', 'group', 'groups' ),
		'family'    => array( 'gia đình', 'người lớn', 'trẻ em', 'family', 'families', 'adults', 'children' ),
		'juice'     => array( 'sinh tố', 'nước ép', 'smoothie', 'smoothies', 'juice' ),
		'cocktail'  => array( 'cocktail', 'quầy bar', 'bar' ),
		'coffee'    => array( 'cà phê', 'trà', 'coffee', 'tea', 'kettle' ),
		'icecream'  => array( 'kem', 'ice cream' ),
		'fire'      => array( 'nướng', 'bbq', 'grill', 'grilled' ),
		'bowl'      => array( 'bún', 'phở', 'bánh canh', 'cháo', 'súp', 'noodle', 'noodles', 'soup' ),
		'pool'      => array( 'hồ bơi', 'bể bơi', 'pool' ),
		'swimring'  => array( 'phao', 'swim ring' ),
		'toy'       => array( 'kids club', 'đồ chơi', 'toy', 'toys' ),
		'beach'     => array( 'bãi biển', 'biển', 'beach', 'sea' ),
		'shuttle'   => array( 'đưa đón', 'đón tiễn', 'sân bay', 'shuttle', 'transfer', 'airport' ),
		'plane'     => array( 'máy bay', 'chuyến bay', 'flight' ),
		'motorbike' => array( 'xe máy', 'motorbike', 'scooter' ),
		'bicycle'   => array( 'xe đạp', 'bicycle', 'bike' ),
		'wifi'      => array( 'wifi', 'wi-fi', 'internet' ),
		'ac'        => array( 'điều hòa', 'máy lạnh', 'air conditioning', 'air-conditioning' ),
		'tv'        => array( 'tivi', 'tv' ),
		'fridge'    => array( 'tủ lạnh', 'fridge', 'minibar' ),
		'safe'      => array( 'két sắt', 'két', 'in-room safe', 'safe box' ),
		'parking'   => array( 'đậu xe', 'đỗ xe', 'parking' ),
		'reception' => array( 'lễ tân', '24h', '24/7', 'reception' ),
		'support'   => array( 'hỗ trợ', 'tư vấn', 'support' ),
		'map'       => array( 'bản đồ', 'map' ),
		'pin'       => array( 'vị trí', 'địa chỉ', 'location' ),
		'gift'      => array( 'quà', 'tặng', 'gift' ),
		'shield'    => array( 'an toàn', 'cứu hộ', 'safety', 'lifeguard' ),
		'view'      => array( 'hướng nhìn', 'view' ),
		'sun'       => array( 'mặt trời', 'nắng', 'sun', 'sunny' ),
		'leaf'      => array( 'vườn', 'cây xanh', 'garden' ),
	);
	foreach ( $rules as $icon => $words ) {
		foreach ( $words as $word ) {
			if ( preg_match( '/(*UCP)(?<![\w])' . preg_quote( $word, '/' ) . '(?![\w])/u', $text ) ) {
				return $icon;
			}
		}
	}
	return '';
}

/* -------------------------------------------------------------------------
 * Chuyển nội dung cũ (kiểu "Danh sách dấu tích") sang danh sách có biểu tượng – chạy 1 lần
 * ---------------------------------------------------------------------- */

const SGC_LIST_ICONS_VERSION = 1;

/**
 * Chạy chuyển đổi nếu chưa chạy.
 */
function sgc_list_icons_maybe_migrate() {
	if ( (int) get_option( 'sgc_list_icons_version', 0 ) >= SGC_LIST_ICONS_VERSION ) {
		return;
	}
	update_option( 'sgc_list_icons_version', SGC_LIST_ICONS_VERSION, true );
	update_option( 'sgc_list_icons_report', sgc_list_icons_migrate(), false );
}
add_action( 'init', 'sgc_list_icons_maybe_migrate', 30 );

/**
 * Chuyển toàn bộ nội dung: bỏ kiểu dấu tích, gán icon theo nội dung từng mục, thêm tiện nghi vào thẻ phòng,
 * gán icon cho tiện nghi phòng.
 *
 * @return array Báo cáo: changed (ID bài đã sửa), unsure (mục dùng icon lá vì chưa chắc).
 */
function sgc_list_icons_migrate() {
	global $wpdb;
	$report = array(
		'changed' => array(),
		'unsure'  => array(),
		'terms'   => array(),
	);
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$rows = $wpdb->get_results(
		"SELECT ID, post_content FROM {$wpdb->posts}
		WHERE post_type NOT IN ('revision','attachment','nav_menu_item')
		AND ( post_content LIKE '%wp:list-item%' OR post_content LIKE '%sunny-grove/room-facts {\"compact\":true}%' )"
	);
	foreach ( $rows as $row ) {
		$unsure = array();
		$blocks = parse_blocks( $row->post_content );
		$blocks = sgc_list_icons_walk( $blocks, $unsure, false );
		$new    = serialize_blocks( $blocks );
		if ( $new !== $row->post_content ) {
			$wpdb->update( $wpdb->posts, array( 'post_content' => $new ), array( 'ID' => $row->ID ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			clean_post_cache( (int) $row->ID );
			$report['changed'][] = (int) $row->ID;
		}
		foreach ( $unsure as $text ) {
			$report['unsure'][] = array(
				'post' => (int) $row->ID,
				'text' => $text,
			);
		}
	}
	foreach ( sgc_icon_taxonomies() as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => $tax,
				'hide_empty' => false,
			)
		);
		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			if ( get_term_meta( $term->term_id, 'sg_icon', true ) ) {
				continue;
			}
			$original = get_term_field( 'name', $term->term_id, $tax, 'raw' );
			$icon     = sgc_icon_guess( is_string( $original ) ? $original : $term->name );
			update_term_meta( $term->term_id, 'sg_icon', $icon ? $icon : SGC_DEFAULT_ICON );
			if ( ! $icon ) {
				$report['terms'][] = (int) $term->term_id;
			}
		}
	}
	return $report;
}

/**
 * Duyệt cây block và chuyển đổi.
 *
 * @param array $blocks  Block.
 * @param array $unsure  Chữ các mục chưa chắc icon (ghi thêm vào).
 * @param bool  $ordered Đang ở trong danh sách đánh số.
 * @return array
 */
function sgc_list_icons_walk( array $blocks, array &$unsure, $ordered ) {
	foreach ( $blocks as $i => $block ) {
		$name = $block['blockName'] ?? '';
		if ( 'core/list' === $name ) {
			$class = $block['attrs']['className'] ?? '';
			if ( false !== strpos( $class, 'is-style-sg-checks' ) ) {
				$class = trim( preg_replace( '/\s*\bis-style-sg-checks\b\s*/', ' ', $class ) );
				if ( '' === $class ) {
					unset( $blocks[ $i ]['attrs']['className'] );
				} else {
					$blocks[ $i ]['attrs']['className'] = $class;
				}
				$strip                         = static function ( $html ) {
					return is_string( $html ) ? preg_replace( '/ ?\bis-style-sg-checks\b/', '', $html ) : $html;
				};
				$blocks[ $i ]['innerHTML']    = $strip( $block['innerHTML'] );
				$blocks[ $i ]['innerContent'] = array_map( $strip, $block['innerContent'] );
			}
			$ordered_list = ! empty( $block['attrs']['ordered'] );
			$blocks[ $i ]['innerBlocks'] = sgc_list_icons_walk( $block['innerBlocks'], $unsure, $ordered_list );
			continue;
		}
		if ( 'core/list-item' === $name ) {
			if ( ! $ordered && empty( $block['attrs']['sgIcon'] ) ) {
				$first = is_string( $block['innerContent'][0] ?? null ) ? $block['innerContent'][0] : $block['innerHTML'];
				$text  = trim( wp_strip_all_tags( $first ) );
				$icon  = sgc_icon_guess( $text );
				if ( $icon ) {
					$blocks[ $i ]['attrs']['sgIcon'] = $icon;
				} elseif ( '' !== $text ) {
					$unsure[] = $text;
				}
			}
			$blocks[ $i ]['innerBlocks'] = sgc_list_icons_walk( $block['innerBlocks'], $unsure, false );
			continue;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$blocks[ $i ]['innerBlocks'] = sgc_list_icons_walk( $block['innerBlocks'], $unsure, false );
			$blocks[ $i ]                = sgc_list_icons_room_card( $blocks[ $i ] );
		}
	}
	return $blocks;
}

/**
 * Thẻ phòng: thêm danh sách tiện nghi (có icon) ngay sau thông tin nhanh của phòng.
 *
 * @param array $block Block cha.
 * @return array
 */
function sgc_list_icons_room_card( array $block ) {
	$children = $block['innerBlocks'];
	foreach ( $children as $index => $child ) {
		if ( 'sunny-grove/room-facts' !== $child['blockName'] || empty( $child['attrs']['compact'] ) ) {
			continue;
		}
		$next = $children[ $index + 1 ] ?? null;
		if ( $next && 'sunny-grove/post-terms-list' === $next['blockName'] && 'sg_room_feature' === ( $next['attrs']['taxonomy'] ?? 'sg_room_feature' ) ) {
			return $block;
		}
		$new = array(
			'blockName'    => 'sunny-grove/post-terms-list',
			'attrs'        => array(
				'taxonomy' => 'sg_room_feature',
				'variant'  => 'compact',
				'limit'    => 4,
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
		array_splice( $block['innerBlocks'], $index + 1, 0, array( $new ) );
		// Chèn chỗ giữ block mới vào innerContent ngay sau block room-facts.
		$seen = -1;
		foreach ( $block['innerContent'] as $pos => $piece ) {
			if ( null === $piece && ++$seen === $index ) {
				array_splice( $block['innerContent'], $pos + 1, 0, array( "\n", null ) );
				break;
			}
		}
		return $block;
	}
	return $block;
}

/**
 * Thông báo trong trang quản trị: các mục đang dùng icon lá vì chưa chắc icon nào phù hợp.
 */
function sgc_list_icons_notice() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	if ( isset( $_GET['sgc_icons_dismiss'] ) && check_admin_referer( 'sgc_icons_dismiss' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		delete_option( 'sgc_list_icons_report' );
		return;
	}
	$report = get_option( 'sgc_list_icons_report' );
	if ( ! is_array( $report ) || ( empty( $report['unsure'] ) && empty( $report['terms'] ) ) ) {
		return;
	}
	echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Danh sách có biểu tượng:', 'sunny-grove-core' ) . '</strong> ' . esc_html__( 'các mục dưới đây đang dùng icon lá vì chưa rõ icon nào phù hợp. Mở bài, bấm vào mục đó rồi bấm nút icon trên thanh công cụ để chọn lại.', 'sunny-grove-core' ) . '</p><ul style="list-style:disc;padding-left:1.5em">';
	foreach ( array_slice( $report['unsure'], 0, 30 ) as $item ) {
		$link = get_edit_post_link( $item['post'] );
		printf( '<li>%s – <a href="%s">%s</a></li>', esc_html( $item['text'] ), esc_url( (string) $link ), esc_html( get_the_title( $item['post'] ) ) );
	}
	foreach ( $report['terms'] as $term_id ) {
		$term = get_term( $term_id );
		if ( $term && ! is_wp_error( $term ) ) {
			printf( '<li>%s – <a href="%s">%s</a></li>', esc_html( $term->name ), esc_url( (string) get_edit_term_link( $term ) ), esc_html__( 'Tiện nghi trong phòng', 'sunny-grove-core' ) );
		}
	}
	printf( '</ul><p><a href="%s">%s</a></p></div>', esc_url( wp_nonce_url( add_query_arg( 'sgc_icons_dismiss', 1 ), 'sgc_icons_dismiss' ) ), esc_html__( 'Đã xem, ẩn thông báo này', 'sunny-grove-core' ) );
}
add_action( 'admin_notices', 'sgc_list_icons_notice' );
