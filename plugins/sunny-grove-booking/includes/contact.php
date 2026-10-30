<?php
/**
 * Form liên hệ (thay Contact Form 7): block "Form liên hệ", lưu tin nhắn trong mục "Liên hệ"
 * (Mới / Đã trả lời), email báo khách sạn và email cảm ơn khách theo ngôn ngữ đang xem.
 * Dùng chung hạ tầng với form đặt phòng: mã bảo mật qua REST (chạy đúng khi trang được cache),
 * honeypot, thời gian điền tối thiểu, giới hạn theo IP.
 *
 * @package SunnyGroveBooking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trạng thái tin nhắn.
 *
 * @return array
 */
function sgb_message_statuses() {
	return array(
		'new'     => __( 'Mới', 'sunny-grove-booking' ),
		'replied' => __( 'Đã trả lời', 'sunny-grove-booking' ),
	);
}

/**
 * Loại nội dung "Liên hệ".
 */
function sgb_register_message_type() {
	register_post_type(
		'sgb_message',
		array(
			'labels'              => array(
				'name'               => __( 'Tin nhắn liên hệ', 'sunny-grove-booking' ),
				'singular_name'      => __( 'Tin nhắn liên hệ', 'sunny-grove-booking' ),
				'menu_name'          => __( 'Liên hệ', 'sunny-grove-booking' ),
				'all_items'          => __( 'Tất cả tin nhắn', 'sunny-grove-booking' ),
				'edit_item'          => __( 'Chi tiết tin nhắn', 'sunny-grove-booking' ),
				'search_items'       => __( 'Tìm tin nhắn', 'sunny-grove-booking' ),
				'not_found'          => __( 'Chưa có tin nhắn nào.', 'sunny-grove-booking' ),
				'not_found_in_trash' => __( 'Thùng rác trống.', 'sunny-grove-booking' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-email-alt',
			'supports'            => false,
			'capability_type'     => array( 'sgb_request', 'sgb_requests' ),
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'        => true,
			'rewrite'             => false,
			'query_var'           => false,
		)
	);
}
add_action( 'init', 'sgb_register_message_type' );

/* -------------------------------------------------------------------------
 * Block "Form liên hệ"
 * ---------------------------------------------------------------------- */

/**
 * Đăng ký block và tài nguyên.
 */
function sgb_register_contact_block() {
	wp_register_script(
		'sgb-contact',
		SGB_URL . 'assets/contact.js',
		array(),
		SGB_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	register_block_type( SGB_DIR . 'blocks/contact-form' );
	add_shortcode( 'sunny_grove_contact', 'sgb_render_contact_form' );
}
add_action( 'init', 'sgb_register_contact_block' );

/**
 * HTML form liên hệ.
 *
 * @return string
 */
function sgb_render_contact_form() {
	$lang    = sgb_current_lang();
	$contact = sgb_contact_info( $lang );
	$t       = static function ( $key, $vars = array() ) use ( $lang ) {
		return sgb_text( $key, $lang, $vars );
	};
	wp_enqueue_style( 'sgb-form' );
	wp_enqueue_script( 'sgb-contact' );
	wp_localize_script(
		'sgb-contact',
		'sgbContact',
		array(
			'rest'  => esc_url_raw( rest_url( 'sunny-grove-booking/v1/' ) ),
			'texts' => array(
				'submit'     => $t( 'contact_submit' ),
				'submitting' => $t( 'contact_submitting' ),
				'required'   => $t( 'err_required' ),
				'email'      => $t( 'err_email' ),
				'short'      => $t( 'err_message_short' ),
				'generic'    => $t( 'err_generic' ),
				'network'    => $t( 'err_network', array( 'hotline' => $contact['hotline'] ) ),
			),
		)
	);
	$uid = wp_unique_id( 'sgb-c-' );
	ob_start();
	?>
	<div class="sgb-wrap sgb-contact" id="<?php echo esc_attr( $uid ); ?>">
		<form class="sgb-form sgb-form--contact" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate data-sgb-contact>
			<div class="sgb-alert" role="alert" aria-live="assertive" data-sgb-alert hidden></div>
			<div class="sgb-grid">
				<p class="sgb-field">
					<label for="<?php echo esc_attr( $uid ); ?>-name"><?php echo esc_html( $t( 'contact_name' ) ); ?> <span class="sgb-req" aria-hidden="true">*</span></label>
					<input class="sgb-input" type="text" id="<?php echo esc_attr( $uid ); ?>-name" name="name" aria-describedby="<?php echo esc_attr( $uid ); ?>-name-error" required maxlength="100" autocomplete="name">
					<span class="sgb-error" id="<?php echo esc_attr( $uid ); ?>-name-error" data-error-for="name"></span>
				</p>
				<p class="sgb-field">
					<label for="<?php echo esc_attr( $uid ); ?>-email"><?php echo esc_html( $t( 'contact_email' ) ); ?> <span class="sgb-req" aria-hidden="true">*</span></label>
					<input class="sgb-input" type="email" id="<?php echo esc_attr( $uid ); ?>-email" name="email" aria-describedby="<?php echo esc_attr( $uid ); ?>-email-error" required maxlength="120" autocomplete="email">
					<span class="sgb-error" id="<?php echo esc_attr( $uid ); ?>-email-error" data-error-for="email"></span>
				</p>
				<p class="sgb-field sgb-field--wide">
					<label for="<?php echo esc_attr( $uid ); ?>-phone"><?php echo esc_html( $t( 'contact_phone' ) ); ?></label>
					<input class="sgb-input" type="tel" id="<?php echo esc_attr( $uid ); ?>-phone" name="phone" aria-describedby="<?php echo esc_attr( $uid ); ?>-phone-error" maxlength="30" autocomplete="tel">
					<span class="sgb-error" id="<?php echo esc_attr( $uid ); ?>-phone-error" data-error-for="phone"></span>
				</p>
				<p class="sgb-field sgb-field--wide">
					<label for="<?php echo esc_attr( $uid ); ?>-message"><?php echo esc_html( $t( 'contact_message' ) ); ?> <span class="sgb-req" aria-hidden="true">*</span></label>
					<textarea class="sgb-input" id="<?php echo esc_attr( $uid ); ?>-message" name="message" aria-describedby="<?php echo esc_attr( $uid ); ?>-message-error" rows="5" required maxlength="2000"></textarea>
					<span class="sgb-error" id="<?php echo esc_attr( $uid ); ?>-message-error" data-error-for="message"></span>
				</p>
			</div>
			<p class="sgb-hp" aria-hidden="true"><label>Website <input type="text" name="sgb_website" tabindex="-1" autocomplete="off"></label></p>
			<input type="hidden" name="sgb_ts" value="<?php echo esc_attr( (string) time() ); ?>">
			<input type="hidden" name="sgb_lang" value="<?php echo esc_attr( $lang ); ?>">
			<input type="hidden" name="sgb_source" value="">
			<input type="hidden" name="action" value="sgb_contact">
			<?php wp_nonce_field( 'sgb_submit', 'sgb_nonce', false ); ?>
			<p class="sgb-privacy"><?php echo esc_html( $t( 'contact_privacy' ) ); ?></p>
			<button type="submit" class="sgb-button wp-element-button" data-sgb-submit><?php echo esc_html( $t( 'contact_submit' ) ); ?></button>
		</form>
		<div class="sgb-success" data-sgb-success hidden tabindex="-1"></div>
	</div>
	<?php
	return (string) ob_get_clean();
}

/* -------------------------------------------------------------------------
 * Nhận tin nhắn
 * ---------------------------------------------------------------------- */

/**
 * Đăng ký REST.
 */
function sgb_register_contact_route() {
	register_rest_route(
		'sunny-grove-booking/v1',
		'/contact',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => static function ( WP_REST_Request $request ) {
				sgb_no_cache();
				$params = $request->get_json_params();
				$result = sgb_handle_contact( is_array( $params ) ? $params : (array) $request->get_body_params() );
				return new WP_REST_Response( $result, $result['ok'] ? 200 : 422 );
			},
		)
	);
}
add_action( 'rest_api_init', 'sgb_register_contact_route' );

/**
 * Gửi form không có JavaScript (dự phòng).
 */
function sgb_contact_admin_post() {
	sgb_no_cache();
	$result = sgb_handle_contact( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce được kiểm tra trong sgb_spam_check().
	$back   = wp_get_referer() ? wp_get_referer() : home_url( '/' );
	wp_safe_redirect( add_query_arg( 'sgb_contact', $result['ok'] ? 'ok' : 'error', $back ) );
	exit;
}
add_action( 'admin_post_nopriv_sgb_contact', 'sgb_contact_admin_post' );
add_action( 'admin_post_sgb_contact', 'sgb_contact_admin_post' );

/**
 * Kiểm tra, lưu tin nhắn và gửi email.
 *
 * @param array $data Dữ liệu thô.
 * @return array { ok, errors, message, html? }
 */
function sgb_handle_contact( array $data ) {
	$langs   = sgb_languages();
	$lang    = isset( $data['sgb_lang'] ) ? sanitize_key( $data['sgb_lang'] ) : '';
	$lang    = isset( $langs[ $lang ] ) ? $lang : sgb_default_lang();
	$contact = sgb_contact_info( $lang );
	$fail    = static function ( $key, $errors = array() ) use ( $lang, $contact ) {
		return array(
			'ok'      => false,
			'errors'  => $errors,
			'message' => sgb_text( $key, $lang, array( 'hotline' => $contact['hotline'] ) ),
		);
	};

	$spam = sgb_spam_check( $data );
	if ( $spam ) {
		return $fail( $spam );
	}

	$clean = array(
		'name'    => mb_substr( sanitize_text_field( (string) ( $data['name'] ?? '' ) ), 0, 100 ),
		'email'   => sanitize_email( (string) ( $data['email'] ?? '' ) ),
		'phone'   => mb_substr( sanitize_text_field( (string) ( $data['phone'] ?? '' ) ), 0, 30 ),
		'message' => mb_substr( sanitize_textarea_field( (string) ( $data['message'] ?? '' ) ), 0, 2000 ),
		'source'  => esc_url_raw( (string) ( $data['sgb_source'] ?? '' ) ),
	);
	$errors = array();
	if ( '' === trim( $clean['name'] ) ) {
		$errors['name'] = sgb_text( 'err_required', $lang );
	}
	if ( '' === trim( (string) ( $data['email'] ?? '' ) ) ) {
		$errors['email'] = sgb_text( 'err_required', $lang );
	} elseif ( ! is_email( $clean['email'] ) ) {
		$errors['email'] = sgb_text( 'err_email', $lang );
	}
	if ( '' !== $clean['phone'] && ! preg_match( '/^[0-9+().\s-]{8,30}$/', $clean['phone'] ) ) {
		$errors['phone'] = sgb_text( 'err_phone', $lang );
	}
	if ( '' === trim( $clean['message'] ) ) {
		$errors['message'] = sgb_text( 'err_required', $lang );
	} elseif ( mb_strlen( trim( $clean['message'] ) ) < 10 ) {
		$errors['message'] = sgb_text( 'err_message_short', $lang );
	}
	if ( $errors ) {
		return $fail( 'err_generic', $errors );
	}

	sgb_rate_hit();
	$post_id = wp_insert_post(
		array(
			'post_type'   => 'sgb_message',
			'post_status' => 'publish',
			'post_title'  => sprintf( '%1$s · %2$s', $clean['name'], mb_substr( preg_replace( '/\s+/', ' ', $clean['message'] ), 0, 60 ) ),
		),
		true
	);
	if ( is_wp_error( $post_id ) ) {
		return $fail( 'err_network' );
	}
	foreach ( $clean as $key => $value ) {
		update_post_meta( $post_id, '_sgb_' . $key, $value );
	}
	update_post_meta( $post_id, '_sgb_lang', $lang );
	update_post_meta( $post_id, '_sgb_status', 'new' );
	sgb_send_contact_emails( $post_id );

	$vars = array(
		'ten_khach' => $clean['name'],
		'hotline'   => $contact['hotline'],
	);
	$html = '<p class="sgb-success__title">' . esc_html( sgb_text( 'contact_success_title', $lang, $vars ) ) . '</p>'
		. '<p class="sgb-success__text">' . esc_html( sgb_text( 'contact_success_message', $lang, $vars ) ) . '</p>'
		. '<button type="button" class="sgb-link" data-sgb-again>' . esc_html( sgb_text( 'contact_again', $lang ) ) . '</button>';
	return array(
		'ok'      => true,
		'errors'  => array(),
		'message' => sgb_text( 'contact_success_title', $lang, $vars ),
		'html'    => $html,
		'id'      => (int) $post_id,
	);
}

/**
 * Biến cho email tin nhắn.
 *
 * @param int    $post_id ID.
 * @param string $lang    Ngôn ngữ.
 * @return array
 */
function sgb_contact_vars( $post_id, $lang ) {
	$contact = sgb_contact_info( $lang );
	$langs   = sgb_languages();
	$guest   = sgb_get( $post_id, 'lang' );
	$empty   = sgb_text( 'empty_value', $lang );
	return array(
		'ten_khach'     => sgb_get( $post_id, 'name' ),
		'email'         => sgb_get( $post_id, 'email' ),
		'dien_thoai'    => sgb_get( $post_id, 'phone' ) ? sgb_get( $post_id, 'phone' ) : $empty,
		'noi_dung'      => sgb_get( $post_id, 'message' ),
		'ngon_ngu'      => $langs[ $guest ]['name'] ?? $guest,
		'hotline'       => $contact['hotline'],
		'zalo'          => $contact['zalo'],
		'whatsapp'      => $contact['whatsapp'],
		'link_quan_tri' => admin_url( 'edit.php?post_type=sgb_message' ),
	);
}

/**
 * Gửi email: cảm ơn khách (đúng ngôn ngữ khách xem) và báo khách sạn (ngôn ngữ mặc định).
 *
 * @param int $post_id ID tin nhắn.
 */
function sgb_send_contact_emails( $post_id ) {
	$langs      = sgb_languages();
	$guest_lang = sgb_get( $post_id, 'lang' );
	$guest_lang = isset( $langs[ $guest_lang ] ) ? $guest_lang : sgb_default_lang();
	$admin_lang = sgb_default_lang();
	$notify     = sgb_notify_emails();
	$email      = sgb_get( $post_id, 'email' );

	if ( $email && is_email( $email ) ) {
		$switched = switch_to_locale( $langs[ $guest_lang ]['locale'] );
		$vars     = sgb_contact_vars( $post_id, $guest_lang );
		$sent     = sgb_mail( $email, sgb_text( 'contact_guest_subject', $guest_lang, $vars ), sgb_text( 'contact_guest_body', $guest_lang, $vars ), $notify[0] ?? '' );
		if ( $switched ) {
			restore_previous_locale();
		}
		update_post_meta( $post_id, '_sgb_mail_guest', $sent ? 'sent' : 'failed' );
	}
	$switched = isset( $langs[ $admin_lang ] ) ? switch_to_locale( $langs[ $admin_lang ]['locale'] ) : false;
	$vars     = sgb_contact_vars( $post_id, $admin_lang );
	$sent     = sgb_mail( $notify, sgb_text( 'contact_admin_subject', $admin_lang, $vars ), sgb_text( 'contact_admin_body', $admin_lang, $vars ), $email );
	if ( $switched ) {
		restore_previous_locale();
	}
	update_post_meta( $post_id, '_sgb_mail_admin', $sent ? 'sent' : 'failed' );
}

/* -------------------------------------------------------------------------
 * Quản trị: danh sách, trạng thái, chi tiết
 * ---------------------------------------------------------------------- */

/**
 * Cột danh sách.
 *
 * @return array
 */
function sgb_message_columns() {
	return array(
		'cb'          => '<input type="checkbox">',
		'sgb_status'  => __( 'Trạng thái', 'sunny-grove-booking' ),
		'title'       => __( 'Khách · Nội dung', 'sunny-grove-booking' ),
		'sgb_contact' => __( 'Liên hệ', 'sunny-grove-booking' ),
		'sgb_lang'    => __( 'Ngôn ngữ', 'sunny-grove-booking' ),
		'date'        => __( 'Gửi lúc', 'sunny-grove-booking' ),
	);
}
add_filter( 'manage_sgb_message_posts_columns', 'sgb_message_columns' );

/**
 * Nội dung cột.
 *
 * @param string $col     Cột.
 * @param int    $post_id ID.
 */
function sgb_message_column( $col, $post_id ) {
	switch ( $col ) {
		case 'sgb_status':
			$status = sgb_get( $post_id, 'status' );
			$labels = sgb_message_statuses();
			printf( '<span class="sgb-badge sgb-badge--%1$s">%2$s</span>', esc_attr( 'replied' === $status ? 'confirmed' : 'new' ), esc_html( $labels[ $status ] ?? $status ) );
			break;
		case 'sgb_contact':
			$email = sgb_get( $post_id, 'email' );
			printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $email ) );
			if ( sgb_get( $post_id, 'phone' ) ) {
				echo '<br><small>' . esc_html( sgb_get( $post_id, 'phone' ) ) . '</small>';
			}
			break;
		case 'sgb_lang':
			echo esc_html( strtoupper( sgb_get( $post_id, 'lang' ) ) );
			break;
	}
}
add_action( 'manage_sgb_message_posts_custom_column', 'sgb_message_column', 10, 2 );

/**
 * Lọc theo trạng thái phía trên danh sách.
 *
 * @param array $views Liên kết.
 * @return array
 */
function sgb_message_views( $views ) {
	$current = isset( $_GET['sgb_status'] ) ? sanitize_key( $_GET['sgb_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$base    = admin_url( 'edit.php?post_type=sgb_message' );
	$new     = array();
	foreach ( sgb_message_statuses() as $key => $label ) {
		$new[ $key ] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
			esc_url( add_query_arg( 'sgb_status', $key, $base ) ),
			$current === $key ? ' class="current" aria-current="page"' : '',
			esc_html( $label ),
			(int) sgb_count_messages( $key )
		);
	}
	return array_merge( array( 'all' => $views['all'] ?? '' ), $new, array_diff_key( $views, array( 'all' => 1, 'publish' => 1 ) ) );
}
add_filter( 'views_edit-sgb_message', 'sgb_message_views' );

/**
 * Đếm tin nhắn theo trạng thái.
 *
 * @param string $status Trạng thái.
 * @return int
 */
function sgb_count_messages( $status ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'sgb_message',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_sgb_status', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $status, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	return (int) $q->found_posts;
}

/**
 * Áp dụng bộ lọc trạng thái.
 *
 * @param WP_Query $query Truy vấn.
 */
function sgb_message_filter( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'sgb_message' !== $query->get( 'post_type' ) ) {
		return;
	}
	$status = isset( $_GET['sgb_status'] ) ? sanitize_key( $_GET['sgb_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( sgb_message_statuses()[ $status ] ) ) {
		$query->set( 'meta_key', '_sgb_status' );
		$query->set( 'meta_value', $status );
	}
}
add_action( 'pre_get_posts', 'sgb_message_filter' );

/**
 * Thao tác nhanh: đánh dấu đã trả lời / chưa trả lời.
 *
 * @param array   $actions Thao tác.
 * @param WP_Post $post    Bài.
 * @return array
 */
function sgb_message_row_actions( $actions, $post ) {
	if ( 'sgb_message' !== $post->post_type ) {
		return $actions;
	}
	$next = 'replied' === sgb_get( $post->ID, 'status' ) ? 'new' : 'replied';
	$url  = wp_nonce_url( admin_url( 'admin-post.php?action=sgb_message_status&id=' . $post->ID . '&status=' . $next ), 'sgb_message_status_' . $post->ID );
	$new  = array( 'sgb_status' => '<a href="' . esc_url( $url ) . '">' . esc_html( 'replied' === $next ? __( 'Đánh dấu đã trả lời', 'sunny-grove-booking' ) : __( 'Đánh dấu chưa trả lời', 'sunny-grove-booking' ) ) . '</a>' );
	unset( $actions['inline hide-if-no-js'] );
	return array_merge( $new, $actions );
}
add_filter( 'post_row_actions', 'sgb_message_row_actions', 10, 2 );

/**
 * Đổi trạng thái tin nhắn.
 */
function sgb_message_set_status() {
	$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
	$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
	check_admin_referer( 'sgb_message_status_' . $id );
	if ( ! current_user_can( 'edit_post', $id ) || 'sgb_message' !== get_post_type( $id ) || ! isset( sgb_message_statuses()[ $status ] ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'sunny-grove-booking' ) );
	}
	update_post_meta( $id, '_sgb_status', $status );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=sgb_message' ) );
	exit;
}
add_action( 'admin_post_sgb_message_status', 'sgb_message_set_status' );

/**
 * Số tin nhắn mới cạnh menu "Liên hệ".
 */
function sgb_message_bubble() {
	global $menu;
	$count = sgb_count_messages( 'new' );
	if ( ! $count || ! is_array( $menu ) ) {
		return;
	}
	foreach ( $menu as $i => $item ) {
		if ( isset( $item[2] ) && 'edit.php?post_type=sgb_message' === $item[2] ) {
			$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $count . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
}
add_action( 'admin_menu', 'sgb_message_bubble', 99 );

/**
 * Hộp chi tiết tin nhắn.
 */
function sgb_message_meta_boxes() {
	add_meta_box( 'sgb_message_details', __( 'Nội dung tin nhắn', 'sunny-grove-booking' ), 'sgb_message_render', 'sgb_message', 'normal', 'high' );
	add_meta_box( 'sgb_message_status', __( 'Trạng thái', 'sunny-grove-booking' ), 'sgb_message_render_status', 'sgb_message', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'sgb_message_meta_boxes' );

/**
 * Nội dung tin nhắn (chỉ đọc).
 *
 * @param WP_Post $post Bài.
 */
function sgb_message_render( $post ) {
	$rows = array(
		__( 'Họ tên', 'sunny-grove-booking' )      => sgb_get( $post->ID, 'name' ),
		__( 'Email', 'sunny-grove-booking' )       => sgb_get( $post->ID, 'email' ),
		__( 'Điện thoại', 'sunny-grove-booking' )  => sgb_get( $post->ID, 'phone' ),
		__( 'Ngôn ngữ', 'sunny-grove-booking' )    => strtoupper( sgb_get( $post->ID, 'lang' ) ),
		__( 'Gửi từ trang', 'sunny-grove-booking' ) => sgb_get( $post->ID, 'source' ),
	);
	echo '<table class="form-table sgb-details" role="presentation">';
	foreach ( $rows as $label => $value ) {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $value ) . '</td></tr>';
	}
	echo '<tr><th scope="row">' . esc_html__( 'Nội dung', 'sunny-grove-booking' ) . '</th><td>' . nl2br( esc_html( sgb_get( $post->ID, 'message' ) ) ) . '</td></tr></table>';
	$email = sgb_get( $post->ID, 'email' );
	if ( $email ) {
		printf(
			'<p><a class="button button-primary" href="%1$s">%2$s</a></p>',
			esc_url( 'mailto:' . $email . '?subject=' . rawurlencode( 'Re: ' . get_bloginfo( 'name' ) ) ),
			esc_html__( 'Trả lời qua email', 'sunny-grove-booking' )
		);
	}
}

/**
 * Hộp trạng thái.
 *
 * @param WP_Post $post Bài.
 */
function sgb_message_render_status( $post ) {
	wp_nonce_field( 'sgb_message_save', 'sgb_message_nonce' );
	$current = sgb_get( $post->ID, 'status' );
	echo '<select name="sgb_status" class="widefat">';
	foreach ( sgb_message_statuses() as $key => $label ) {
		printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $current, $key, false ), esc_html( $label ) );
	}
	echo '</select>';
}

/**
 * Lưu trạng thái.
 *
 * @param int $post_id ID.
 */
function sgb_message_save( $post_id ) {
	if ( ! isset( $_POST['sgb_message_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sgb_message_nonce'] ), 'sgb_message_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$status = isset( $_POST['sgb_status'] ) ? sanitize_key( $_POST['sgb_status'] ) : '';
	if ( isset( sgb_message_statuses()[ $status ] ) ) {
		update_post_meta( $post_id, '_sgb_status', $status );
	}
}
add_action( 'save_post_sgb_message', 'sgb_message_save' );
