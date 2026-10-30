<?php
/**
 * Trang quản trị: danh sách yêu cầu, chi tiết, trạng thái, cài đặt, xuất CSV.
 *
 * @package SunnyGroveBooking
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Danh sách yêu cầu
 * ---------------------------------------------------------------------- */

/**
 * Cột của danh sách.
 *
 * @return array
 */
function sgb_columns() {
	return array(
		'cb'         => '<input type="checkbox">',
		'sgb_status' => __( 'Trạng thái', 'sunny-grove-booking' ),
		'title'      => __( 'Khách', 'sunny-grove-booking' ),
		'sgb_dates'  => __( 'Ngày ở', 'sunny-grove-booking' ),
		'sgb_room'   => __( 'Phòng · Số khách', 'sunny-grove-booking' ),
		'sgb_phone'  => __( 'Liên hệ', 'sunny-grove-booking' ),
		'sgb_lang'   => __( 'Ngôn ngữ', 'sunny-grove-booking' ),
		'date'       => __( 'Gửi lúc', 'sunny-grove-booking' ),
	);
}
add_filter( 'manage_sgb_request_posts_columns', 'sgb_columns' );

/**
 * Nội dung các cột.
 *
 * @param string $col     Cột.
 * @param int    $post_id ID.
 */
function sgb_column_content( $col, $post_id ) {
	switch ( $col ) {
		case 'sgb_status':
			$status = sgb_get( $post_id, 'status' );
			$labels = sgb_statuses();
			printf( '<span class="sgb-badge sgb-badge--%1$s">%2$s</span>', esc_attr( $status ), esc_html( $labels[ $status ] ?? $status ) );
			break;
		case 'sgb_dates':
			$nights = sgb_nights( sgb_get( $post_id, 'checkin' ), sgb_get( $post_id, 'checkout' ) );
			echo esc_html( sgb_get( $post_id, 'checkin' ) . ' → ' . sgb_get( $post_id, 'checkout' ) );
			/* translators: %d: số đêm */
			echo '<br><small>' . esc_html( sprintf( _n( '%d đêm', '%d đêm', $nights, 'sunny-grove-booking' ), $nights ) ) . '</small>';
			break;
		case 'sgb_room':
			echo esc_html( sgb_room_label( $post_id, sgb_default_lang() ) );
			/* translators: 1: người lớn, 2: trẻ em */
			echo '<br><small>' . esc_html( sprintf( __( '%1$s người lớn, %2$s trẻ em', 'sunny-grove-booking' ), sgb_get( $post_id, 'adults' ), sgb_get( $post_id, 'children' ) ) ) . '</small>';
			break;
		case 'sgb_phone':
			$phone = sgb_get( $post_id, 'phone' );
			printf( '<a href="tel:%1$s">%2$s</a>', esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ), esc_html( $phone ) );
			$method = sgb_get( $post_id, 'contact_method' );
			if ( $method && 'phone' !== $method ) {
				echo '<br><small>' . esc_html( ucfirst( $method ) . ( sgb_get( $post_id, 'contact_id' ) ? ': ' . sgb_get( $post_id, 'contact_id' ) : '' ) ) . '</small>';
			}
			if ( sgb_get( $post_id, 'email' ) ) {
				echo '<br><small>' . esc_html( sgb_get( $post_id, 'email' ) ) . '</small>';
			}
			break;
		case 'sgb_lang':
			echo esc_html( strtoupper( sgb_get( $post_id, 'lang' ) ) );
			break;
	}
}
add_action( 'manage_sgb_request_posts_custom_column', 'sgb_column_content', 10, 2 );

/**
 * Bộ lọc theo trạng thái (Mới / Đã liên hệ / …) phía trên danh sách.
 *
 * @param array $views Liên kết.
 * @return array
 */
function sgb_status_views( $views ) {
	$current = isset( $_GET['sgb_status'] ) ? sanitize_key( $_GET['sgb_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$base    = admin_url( 'edit.php?post_type=sgb_request' );
	$new     = array();
	foreach ( sgb_statuses() as $key => $label ) {
		$count       = sgb_count_status( $key );
		$new[ $key ] = sprintf(
			'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
			esc_url( add_query_arg( 'sgb_status', $key, $base ) ),
			$current === $key ? ' class="current" aria-current="page"' : '',
			esc_html( $label ),
			(int) $count
		);
	}
	return array_merge( array( 'all' => $views['all'] ?? '' ), $new, array_diff_key( $views, array( 'all' => 1, 'publish' => 1 ) ) );
}
add_filter( 'views_edit-sgb_request', 'sgb_status_views' );

/**
 * Đếm yêu cầu theo trạng thái.
 *
 * @param string $status Trạng thái.
 * @return int
 */
function sgb_count_status( $status ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'sgb_request',
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
 * Áp dụng bộ lọc trạng thái và tìm kiếm theo tên/điện thoại.
 *
 * @param WP_Query $query Truy vấn.
 */
function sgb_filter_list( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'sgb_request' !== $query->get( 'post_type' ) ) {
		return;
	}
	$status = isset( $_GET['sgb_status'] ) ? sanitize_key( $_GET['sgb_status'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( sgb_statuses()[ $status ] ) ) {
		$query->set( 'meta_key', '_sgb_status' );
		$query->set( 'meta_value', $status );
	}
	$search = $query->get( 's' );
	if ( $search ) {
		$query->set( 's', '' );
		$query->set(
			'meta_query',
			array(
				'relation' => 'OR',
				array(
					'key'     => '_sgb_name',
					'value'   => $search,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_sgb_phone',
					'value'   => $search,
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_sgb_email',
					'value'   => $search,
					'compare' => 'LIKE',
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'sgb_filter_list' );

/**
 * Thao tác hàng loạt: đổi trạng thái.
 *
 * @param array $actions Thao tác.
 * @return array
 */
function sgb_bulk_actions( $actions ) {
	unset( $actions['edit'] );
	foreach ( sgb_statuses() as $key => $label ) {
		/* translators: %s: trạng thái */
		$actions[ 'sgb_mark_' . $key ] = sprintf( __( 'Chuyển sang: %s', 'sunny-grove-booking' ), $label );
	}
	return $actions;
}
add_filter( 'bulk_actions-edit-sgb_request', 'sgb_bulk_actions' );

/**
 * Xử lý thao tác hàng loạt.
 *
 * @param string $redirect URL quay lại.
 * @param string $action   Thao tác.
 * @param int[]  $ids      ID.
 * @return string
 */
function sgb_handle_bulk( $redirect, $action, $ids ) {
	if ( 0 !== strpos( $action, 'sgb_mark_' ) ) {
		return $redirect;
	}
	$status = substr( $action, 9 );
	if ( ! isset( sgb_statuses()[ $status ] ) ) {
		return $redirect;
	}
	foreach ( $ids as $id ) {
		if ( current_user_can( 'edit_post', $id ) ) {
			update_post_meta( $id, '_sgb_status', $status );
		}
	}
	return add_query_arg( 'sgb_updated', count( $ids ), $redirect );
}
add_filter( 'handle_bulk_actions-edit-sgb_request', 'sgb_handle_bulk', 10, 3 );

/**
 * Thao tác nhanh trên từng dòng.
 *
 * @param array   $actions Thao tác.
 * @param WP_Post $post    Bài.
 * @return array
 */
function sgb_row_actions( $actions, $post ) {
	if ( 'sgb_request' !== $post->post_type ) {
		return $actions;
	}
	unset( $actions['inline hide-if-no-js'] );
	$current = sgb_get( $post->ID, 'status' );
	foreach ( sgb_statuses() as $key => $label ) {
		if ( $key === $current ) {
			continue;
		}
		$url                       = wp_nonce_url( admin_url( 'admin-post.php?action=sgb_set_status&post=' . $post->ID . '&status=' . $key ), 'sgb_set_status_' . $post->ID );
		$actions[ 'sgb_' . $key ] = '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'sgb_row_actions', 10, 2 );

/**
 * Đổi trạng thái một yêu cầu.
 */
function sgb_set_status() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	$status  = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
	check_admin_referer( 'sgb_set_status_' . $post_id );
	if ( ! current_user_can( 'edit_post', $post_id ) || 'sgb_request' !== get_post_type( $post_id ) || ! isset( sgb_statuses()[ $status ] ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'sunny-grove-booking' ) );
	}
	update_post_meta( $post_id, '_sgb_status', $status );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=sgb_request' ) );
	exit;
}
add_action( 'admin_post_sgb_set_status', 'sgb_set_status' );

/**
 * Số yêu cầu mới hiện cạnh menu.
 */
function sgb_menu_bubble() {
	global $menu;
	$count = sgb_count_status( 'new' );
	if ( ! $count || ! is_array( $menu ) ) {
		return;
	}
	foreach ( $menu as $i => $item ) {
		if ( isset( $item[2] ) && 'edit.php?post_type=sgb_request' === $item[2] ) {
			$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $count . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
}
add_action( 'admin_menu', 'sgb_menu_bubble', 99 );

/**
 * CSS quản trị.
 *
 * @param string $hook Trang.
 */
function sgb_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( ( $screen && 'sgb_request' === $screen->post_type ) || false !== strpos( $hook, 'sgb-settings' ) ) {
		wp_enqueue_style( 'sgb-admin', SGB_URL . 'assets/admin.css', array(), SGB_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'sgb_admin_assets' );

/* -------------------------------------------------------------------------
 * Màn hình chi tiết
 * ---------------------------------------------------------------------- */

/**
 * Hộp thông tin.
 */
function sgb_meta_boxes() {
	add_meta_box( 'sgb_details', __( 'Thông tin yêu cầu', 'sunny-grove-booking' ), 'sgb_render_details', 'sgb_request', 'normal', 'high' );
	add_meta_box( 'sgb_status_box', __( 'Trạng thái xử lý', 'sunny-grove-booking' ), 'sgb_render_status_box', 'sgb_request', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'sgb_meta_boxes' );

/**
 * Nội dung hộp thông tin.
 *
 * @param WP_Post $post Bài.
 */
function sgb_render_details( $post ) {
	wp_nonce_field( 'sgb_save_request', 'sgb_request_nonce' );
	$is_new = 'auto-draft' === $post->post_status;
	if ( ! $is_new ) {
		echo '<p class="sgb-code">' . esc_html__( 'Mã yêu cầu:', 'sunny-grove-booking' ) . ' <strong>' . esc_html( sgb_request_code( $post->ID ) ) . '</strong></p>';
	}
	echo '<table class="form-table sgb-details"><tbody>';
	foreach ( sgb_request_fields() as $key => $label ) {
		$value = sgb_get( $post->ID, $key );
		echo '<tr><th scope="row"><label for="sgb-' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th><td>';
		switch ( $key ) {
			case 'checkin':
			case 'checkout':
				printf( '<input type="date" id="sgb-%1$s" name="sgb[%1$s]" value="%2$s">', esc_attr( $key ), esc_attr( $value ) );
				break;
			case 'adults':
			case 'children':
				printf( '<input type="number" min="0" id="sgb-%1$s" name="sgb[%1$s]" value="%2$s" class="small-text">', esc_attr( $key ), esc_attr( $value ) );
				break;
			case 'room_id':
				$rooms = post_type_exists( sgb_room_post_type() ) ? get_posts(
					array(
						'post_type'      => sgb_room_post_type(),
						'posts_per_page' => 100,
						'lang'           => '',
					)
				) : array();
				echo '<select id="sgb-room_id" name="sgb[room_id]"><option value="0">' . esc_html( sgb_text( 'room_any', sgb_default_lang() ) ) . '</option>';
				foreach ( $rooms as $room ) {
					printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $room->ID, selected( (int) $value, $room->ID, false ), esc_html( get_the_title( $room ) ) );
				}
				echo '</select>';
				if ( ! $is_new ) {
					echo ' <span class="description">' . esc_html__( 'Khách đã chọn:', 'sunny-grove-booking' ) . ' ' . esc_html( sgb_room_label( $post->ID, sgb_default_lang() ) ) . '</span>';
				}
				break;
			case 'contact_method':
				echo '<select id="sgb-contact_method" name="sgb[contact_method]">';
				foreach ( array( 'phone', 'zalo', 'whatsapp' ) as $m ) {
					printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $m ), selected( $value, $m, false ), esc_html( sgb_text( 'method_' . $m, sgb_default_lang() ) ) );
				}
				echo '</select>';
				break;
			case 'note':
				printf( '<textarea id="sgb-note" name="sgb[note]" rows="3" class="large-text">%s</textarea>', esc_textarea( $value ) );
				break;
			case 'phone':
				printf( '<input type="text" id="sgb-phone" name="sgb[phone]" value="%1$s" class="regular-text">', esc_attr( $value ) );
				if ( $value ) {
					printf( ' <a class="button" href="tel:%1$s">%2$s</a>', esc_attr( preg_replace( '/[^0-9+]/', '', $value ) ), esc_html__( 'Gọi', 'sunny-grove-booking' ) );
				}
				break;
			default:
				printf( '<input type="text" id="sgb-%1$s" name="sgb[%1$s]" value="%2$s" class="regular-text">', esc_attr( $key ), esc_attr( $value ) );
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';

	if ( ! $is_new ) {
		$langs = sgb_languages();
		$lang  = sgb_get( $post->ID, 'lang' );
		$mails = array(
			'sent'    => __( 'Đã gửi', 'sunny-grove-booking' ),
			'failed'  => __( 'Gửi lỗi – kiểm tra cấu hình email (SMTP)', 'sunny-grove-booking' ),
			'skipped' => __( 'Không gửi (khách không nhập email)', 'sunny-grove-booking' ),
		);
		echo '<p class="description">';
		echo esc_html__( 'Ngôn ngữ khách xem:', 'sunny-grove-booking' ) . ' ' . esc_html( $langs[ $lang ]['name'] ?? $lang ) . ' · ';
		echo esc_html__( 'Email cho khách:', 'sunny-grove-booking' ) . ' ' . esc_html( $mails[ sgb_get( $post->ID, 'mail_guest' ) ] ?? '—' ) . ' · ';
		echo esc_html__( 'Email cho khách sạn:', 'sunny-grove-booking' ) . ' ' . esc_html( $mails[ sgb_get( $post->ID, 'mail_admin' ) ] ?? '—' );
		if ( sgb_get( $post->ID, 'source' ) ) {
			echo ' · <a href="' . esc_url( sgb_get( $post->ID, 'source' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Trang khách gửi', 'sunny-grove-booking' ) . '</a>';
		}
		echo '</p>';
		if ( sgb_get( $post->ID, 'mail_error' ) ) {
			echo '<p class="description sgb-error-text">' . esc_html( sgb_get( $post->ID, 'mail_error' ) ) . '</p>';
		}
	}
}

/**
 * Hộp trạng thái + ghi chú nội bộ.
 *
 * @param WP_Post $post Bài.
 */
function sgb_render_status_box( $post ) {
	$current = sgb_get( $post->ID, 'status' );
	$current = $current ? $current : 'new';
	foreach ( sgb_statuses() as $key => $label ) {
		printf(
			'<label class="sgb-status-choice"><input type="radio" name="sgb[status]" value="%1$s"%2$s> <span class="sgb-badge sgb-badge--%1$s">%3$s</span></label>',
			esc_attr( $key ),
			checked( $current, $key, false ),
			esc_html( $label )
		);
	}
	echo '<p><label for="sgb-internal"><strong>' . esc_html__( 'Ghi chú nội bộ (khách không thấy)', 'sunny-grove-booking' ) . '</strong></label>';
	printf( '<textarea id="sgb-internal" name="sgb[internal_note]" rows="4" class="widefat">%s</textarea></p>', esc_textarea( sgb_get( $post->ID, 'internal_note' ) ) );
	if ( 'auto-draft' !== $post->post_status ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=sgb_resend&post=' . $post->ID ), 'sgb_resend_' . $post->ID );
		echo '<p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Gửi lại email xác nhận', 'sunny-grove-booking' ) . '</a></p>';
	}
}

/**
 * Lưu khi nhân viên sửa yêu cầu.
 *
 * @param int $post_id ID.
 */
function sgb_save_request( $post_id ) {
	if ( ! isset( $_POST['sgb_request_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sgb_request_nonce'] ), 'sgb_save_request' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['sgb'] ) && is_array( $_POST['sgb'] ) ? wp_unslash( $_POST['sgb'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	foreach ( array_keys( sgb_request_fields() ) as $key ) {
		if ( ! isset( $in[ $key ] ) ) {
			continue;
		}
		switch ( $key ) {
			case 'adults':
			case 'children':
			case 'room_id':
				$value = absint( $in[ $key ] );
				break;
			case 'email':
				$value = sanitize_email( $in[ $key ] );
				break;
			case 'note':
				$value = sanitize_textarea_field( $in[ $key ] );
				break;
			case 'checkin':
			case 'checkout':
				$value = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $in[ $key ] ) ? $in[ $key ] : '';
				break;
			case 'contact_method':
				$value = in_array( $in[ $key ], array( 'phone', 'zalo', 'whatsapp' ), true ) ? $in[ $key ] : 'phone';
				break;
			default:
				$value = sanitize_text_field( $in[ $key ] );
		}
		update_post_meta( $post_id, '_sgb_' . $key, $value );
		if ( 'room_id' === $key ) {
			update_post_meta( $post_id, '_sgb_room_title', $value ? get_the_title( $value ) : '' );
		}
	}
	if ( isset( $in['status'] ) && isset( sgb_statuses()[ $in['status'] ] ) ) {
		update_post_meta( $post_id, '_sgb_status', $in['status'] );
	}
	if ( isset( $in['internal_note'] ) ) {
		update_post_meta( $post_id, '_sgb_internal_note', sanitize_textarea_field( $in['internal_note'] ) );
	}
	if ( ! sgb_get( $post_id, 'lang' ) ) {
		update_post_meta( $post_id, '_sgb_lang', sgb_default_lang() );
	}

	// Cập nhật tiêu đề cho dễ tìm.
	remove_action( 'save_post_sgb_request', 'sgb_save_request' );
	wp_update_post(
		array(
			'ID'         => $post_id,
			'post_title' => sprintf( '%1$s · %2$s → %3$s', sgb_get( $post_id, 'name' ), sgb_get( $post_id, 'checkin' ), sgb_get( $post_id, 'checkout' ) ),
		)
	);
	add_action( 'save_post_sgb_request', 'sgb_save_request' );
}
add_action( 'save_post_sgb_request', 'sgb_save_request' );

/**
 * Gửi lại email.
 */
function sgb_resend() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	check_admin_referer( 'sgb_resend_' . $post_id );
	if ( ! current_user_can( 'edit_post', $post_id ) || 'sgb_request' !== get_post_type( $post_id ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'sunny-grove-booking' ) );
	}
	sgb_send_emails( $post_id );
	wp_safe_redirect( admin_url( 'post.php?post=' . $post_id . '&action=edit&sgb_resent=1' ) );
	exit;
}
add_action( 'admin_post_sgb_resend', 'sgb_resend' );

/* -------------------------------------------------------------------------
 * Xuất CSV
 * ---------------------------------------------------------------------- */

/**
 * Nút xuất CSV phía trên danh sách.
 *
 * @param string $which Vị trí.
 */
function sgb_export_button( $which ) {
	$screen = get_current_screen();
	if ( 'top' !== $which || ! $screen || 'sgb_request' !== $screen->post_type ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=sgb_export' ), 'sgb_export' );
	echo '<div class="alignleft actions"><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Tải danh sách (CSV/Excel)', 'sunny-grove-booking' ) . '</a></div>';
}
add_action( 'manage_posts_extra_tablenav', 'sgb_export_button' );

/**
 * Xuất CSV.
 */
function sgb_export() {
	check_admin_referer( 'sgb_export' );
	if ( ! current_user_can( 'edit_others_sgb_requests' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'sunny-grove-booking' ) );
	}
	$ids = get_posts(
		array(
			'post_type'      => 'sgb_request',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=yeu-cau-dat-phong-' . wp_date( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- BOM để Excel đọc đúng tiếng Việt.
	$statuses = sgb_statuses();
	$header   = array_merge( array( __( 'Mã', 'sunny-grove-booking' ), __( 'Gửi lúc', 'sunny-grove-booking' ), __( 'Trạng thái', 'sunny-grove-booking' ) ), array_values( sgb_request_fields() ), array( __( 'Ngôn ngữ', 'sunny-grove-booking' ), __( 'Ghi chú nội bộ', 'sunny-grove-booking' ) ) );
	fputcsv( $out, $header );
	foreach ( $ids as $id ) {
		$row = array( sgb_request_code( $id ), get_the_date( 'Y-m-d H:i', $id ), $statuses[ sgb_get( $id, 'status' ) ] ?? '' );
		foreach ( array_keys( sgb_request_fields() ) as $key ) {
			$row[] = 'room_id' === $key ? sgb_room_label( $id, sgb_default_lang() ) : sgb_get( $id, $key );
		}
		$row[] = sgb_get( $id, 'lang' );
		$row[] = sgb_get( $id, 'internal_note' );
		fputcsv( $out, array_map( 'sgb_csv_safe', $row ) );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_sgb_export', 'sgb_export' );

/**
 * Chống chèn công thức khi mở CSV bằng Excel.
 *
 * @param string $value Giá trị.
 * @return string
 */
function sgb_csv_safe( $value ) {
	$value = (string) $value;
	return preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;
}

/* -------------------------------------------------------------------------
 * Cài đặt
 * ---------------------------------------------------------------------- */

/**
 * Trang cài đặt nằm trong menu Đặt phòng.
 */
function sgb_settings_menu() {
	add_submenu_page( 'edit.php?post_type=sgb_request', __( 'Cài đặt đặt phòng', 'sunny-grove-booking' ), __( 'Cài đặt', 'sunny-grove-booking' ), 'manage_sgb_settings', 'sgb-settings', 'sgb_render_settings' );
}
add_action( 'admin_menu', 'sgb_settings_menu' );

/**
 * Hiển thị trang cài đặt.
 */
function sgb_render_settings() {
	$langs = sgb_languages();
	$tab   = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$tab   = isset( $langs[ $tab ] ) ? $tab : 'general';
	$base  = admin_url( 'edit.php?post_type=sgb_request&page=sgb-settings' );

	echo '<div class="wrap sgb-settings"><h1>' . esc_html__( 'Cài đặt đặt phòng', 'sunny-grove-booking' ) . '</h1>';
	if ( isset( $_GET['saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Đã lưu.', 'sunny-grove-booking' ) . '</p></div>';
	}
	echo '<nav class="nav-tab-wrapper">';
	printf( '<a class="nav-tab %1$s" href="%2$s">%3$s</a>', 'general' === $tab ? 'nav-tab-active' : '', esc_url( $base ), esc_html__( 'Chung', 'sunny-grove-booking' ) );
	foreach ( $langs as $slug => $lang ) {
		/* translators: %s: tên ngôn ngữ */
		printf( '<a class="nav-tab %1$s" href="%2$s">%3$s</a>', $tab === $slug ? 'nav-tab-active' : '', esc_url( add_query_arg( 'tab', $slug, $base ) ), esc_html( sprintf( __( 'Chữ & email – %s', 'sunny-grove-booking' ), $lang['name'] ) ) );
	}
	echo '</nav>';

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'sgb_save_settings' );
	echo '<input type="hidden" name="action" value="sgb_save_settings"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';

	if ( 'general' === $tab ) {
		echo '<table class="form-table" role="presentation">';
		foreach ( sgb_defaults( 'general' ) as $key => $field ) {
			$value = sgb_general( $key );
			echo '<tr><th scope="row"><label for="sgb-' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			switch ( $field['type'] ) {
				case 'textarea':
					printf( '<textarea id="sgb-%1$s" name="sgb[%1$s]" rows="3" class="large-text">%2$s</textarea>', esc_attr( $key ), esc_textarea( $value ) );
					break;
				case 'checkbox':
					printf( '<input type="hidden" name="sgb[%1$s]" value="0"><label><input type="checkbox" id="sgb-%1$s" name="sgb[%1$s]" value="1"%2$s> %3$s</label>', esc_attr( $key ), checked( $value, '1', false ), esc_html__( 'Bật', 'sunny-grove-booking' ) );
					break;
				case 'page':
					wp_dropdown_pages(
						array(
							'name'              => 'sgb[' . esc_attr( $key ) . ']',
							'id'                => 'sgb-' . esc_attr( $key ),
							'selected'          => (int) $value,
							'show_option_none'  => '—',
							'option_none_value' => '',
							'lang'              => sgb_default_lang(),
						)
					);
					break;
				case 'password':
					printf( '<input type="password" id="sgb-%1$s" name="sgb[%1$s]" value="%2$s" class="regular-text" autocomplete="new-password">', esc_attr( $key ), esc_attr( $value ) );
					break;
				case 'number':
					printf( '<input type="number" min="0" id="sgb-%1$s" name="sgb[%1$s]" value="%2$s" class="small-text">', esc_attr( $key ), esc_attr( $value ) );
					break;
				default:
					printf( '<input type="%3$s" id="sgb-%1$s" name="sgb[%1$s]" value="%2$s" class="regular-text">', esc_attr( $key ), esc_attr( $value ), 'email' === $field['type'] ? 'email' : 'text' );
			}
			if ( ! empty( $field['help'] ) ) {
				echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table>';
	} else {
		$schema = sgb_defaults( 'texts' );
		$texts  = (array) get_option( 'sgb_texts', array() );
		echo '<p>' . esc_html__( 'Chữ trong ngoặc nhọn sẽ được thay bằng thông tin thật. Biến có thể dùng:', 'sunny-grove-booking' ) . ' <code>{ten_khach} {ma_yeu_cau} {ngay_nhan} {ngay_tra} {so_dem} {phong} {nguoi_lon} {tre_em} {ghi_chu} {so_dien_thoai} {email} {lien_he_qua} {so_lien_he} {hotline} {zalo} {whatsapp} {ten_khach_san} {ngon_ngu} {link_quan_tri}</code></p>';
		foreach ( $schema['groups'] as $group => $group_label ) {
			if ( 'admin_email' === $group && sgb_default_lang() !== $tab ) {
				continue; // Email cho khách sạn luôn dùng ngôn ngữ mặc định.
			}
			echo '<h2 class="sgb-group-title">' . esc_html( $group_label ) . '</h2><table class="form-table" role="presentation">';
			foreach ( $schema['fields'] as $key => $field ) {
				if ( $field['group'] !== $group ) {
					continue;
				}
				$value = $texts[ $tab ][ $key ] ?? sgb_default_text( $field, $tab );
				echo '<tr><th scope="row"><label for="sgb-' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
				if ( 'textarea' === ( $field['type'] ?? '' ) ) {
					printf( '<textarea id="sgb-%1$s" name="sgb[%1$s]" rows="%3$d" class="large-text">%2$s</textarea>', esc_attr( $key ), esc_textarea( $value ), false !== strpos( $key, 'body' ) ? 14 : 3 );
				} else {
					printf( '<input type="text" id="sgb-%1$s" name="sgb[%1$s]" value="%2$s" class="large-text">', esc_attr( $key ), esc_attr( $value ) );
				}
				echo '</td></tr>';
			}
			echo '</table>';
		}
	}
	submit_button( __( 'Lưu thay đổi', 'sunny-grove-booking' ) );
	echo '</form></div>';
}

/**
 * Lưu cài đặt.
 */
function sgb_save_settings() {
	if ( ! current_user_can( 'manage_sgb_settings' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'sunny-grove-booking' ) );
	}
	check_admin_referer( 'sgb_save_settings' );
	$tab   = isset( $_POST['tab'] ) ? sanitize_key( $_POST['tab'] ) : 'general';
	$in    = isset( $_POST['sgb'] ) && is_array( $_POST['sgb'] ) ? wp_unslash( $_POST['sgb'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$langs = sgb_languages();

	if ( 'general' === $tab ) {
		$general = (array) get_option( 'sgb_general', array() );
		foreach ( sgb_defaults( 'general' ) as $key => $field ) {
			if ( ! isset( $in[ $key ] ) ) {
				continue;
			}
			switch ( $field['type'] ) {
				case 'textarea':
					$general[ $key ] = sanitize_textarea_field( $in[ $key ] );
					break;
				case 'email':
					$general[ $key ] = sanitize_email( $in[ $key ] );
					break;
				case 'checkbox':
					$general[ $key ] = '1' === (string) $in[ $key ] ? '1' : '0';
					break;
				case 'number':
				case 'page':
					$general[ $key ] = (string) absint( $in[ $key ] );
					break;
				default:
					$general[ $key ] = sanitize_text_field( $in[ $key ] );
			}
		}
		update_option( 'sgb_general', $general );
	} elseif ( isset( $langs[ $tab ] ) ) {
		$schema = sgb_defaults( 'texts' );
		$texts  = (array) get_option( 'sgb_texts', array() );
		foreach ( $schema['fields'] as $key => $field ) {
			if ( isset( $in[ $key ] ) ) {
				$texts[ $tab ][ $key ] = 'textarea' === ( $field['type'] ?? '' ) ? sanitize_textarea_field( $in[ $key ] ) : sanitize_text_field( $in[ $key ] );
			}
		}
		update_option( 'sgb_texts', $texts );
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=sgb_request&page=sgb-settings&tab=' . $tab . '&saved=1' ) );
	exit;
}
add_action( 'admin_post_sgb_save_settings', 'sgb_save_settings' );
