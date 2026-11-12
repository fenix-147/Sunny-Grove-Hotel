<?php
/**
 * Đánh giá Google Maps: chủ web tự chọn đánh giá trên Google Maps, chép nguyên văn vào trang quản trị
 * (kèm link tới đánh giá gốc), block "Đánh giá khách hàng" hiển thị tối đa 3 đánh giá theo Thứ tự.
 *
 * Không dùng Google Places API, không nhúng widget, không ảnh chụp màn hình.
 * Không xuất schema AggregateRating/Review: Google không hiện sao cho đánh giá do chính doanh nghiệp đăng lại.
 * Schema của website chỉ có Hotel (xem seo.php).
 *
 * Đánh giá mẫu (meta sg_review_sample) chỉ hiện ở môi trường thử nghiệm (127.0.0.1, localhost, staging…).
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loại nội dung "Đánh giá".
 */
function sg_register_review_type() {
	register_post_type(
		'sg_review',
		array(
			'labels'              => array(
				'name'          => __( 'Đánh giá', 'sunny-grove-core' ),
				'singular_name' => __( 'Đánh giá', 'sunny-grove-core' ),
				'add_new'       => __( 'Thêm đánh giá', 'sunny-grove-core' ),
				'add_new_item'  => __( 'Thêm đánh giá Google Maps', 'sunny-grove-core' ),
				'edit_item'     => __( 'Sửa đánh giá', 'sunny-grove-core' ),
				'all_items'     => __( 'Tất cả đánh giá', 'sunny-grove-core' ),
				'menu_name'     => __( 'Đánh giá', 'sunny-grove-core' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => true,
			'menu_position'       => 25,
			'menu_icon'           => 'dashicons-star-filled',
			'supports'            => array( 'title', 'page-attributes' ),
			'map_meta_cap'        => true,
			'rewrite'             => false,
			'query_var'           => false,
		)
	);
}
add_action( 'init', 'sg_register_review_type', 5 );

/**
 * Đánh giá dùng form cổ điển (gọn, báo lỗi "link không hợp lệ" ngay trên trang).
 *
 * @param bool   $use       Dùng trình soạn thảo khối.
 * @param string $post_type Loại nội dung.
 * @return bool
 */
function sg_review_classic_form( $use, $post_type ) {
	return 'sg_review' === $post_type ? false : $use;
}
add_filter( 'use_block_editor_for_post_type', 'sg_review_classic_form', 10, 2 );

/**
 * Chữ gợi ý trong ô tiêu đề.
 *
 * @param string  $text Chữ.
 * @param WP_Post $post Bài.
 * @return string
 */
function sg_review_title_placeholder( $text, $post ) {
	return 'sg_review' === $post->post_type ? __( 'Tên người đánh giá (đúng như trên Google Maps)', 'sunny-grove-core' ) : $text;
}
add_filter( 'enter_title_here', 'sg_review_title_placeholder', 10, 2 );

/**
 * Trường của đánh giá.
 *
 * @param array $fields Trường theo loại nội dung.
 * @return array
 */
function sg_review_fields( $fields ) {
	$langs = array();
	foreach ( sg_languages() as $code => $lang ) {
		$langs[ $code ] = $lang['name'];
	}
	$langs['other'] = __( 'Khác', 'sunny-grove-core' );

	$review = array(
		'sg_avatar'          => array(
			'type'  => 'image',
			'label' => __( 'Ảnh đại diện (tùy chọn)', 'sunny-grove-core' ),
			'help'  => __( 'Để trống thì website hiện chữ cái đầu của tên.', 'sunny-grove-core' ),
		),
		'sg_rating'          => array(
			'type'    => 'select',
			'label'   => __( 'Số sao', 'sunny-grove-core' ),
			'default' => '5',
			'options' => array(
				'5' => '★★★★★',
				'4' => '★★★★',
				'3' => '★★★',
				'2' => '★★',
				'1' => '★',
			),
		),
		'sg_review_date'     => array(
			'type'  => 'date',
			'label' => __( 'Ngày đánh giá', 'sunny-grove-core' ),
			'help'  => __( 'Ngày khách viết đánh giá. Website tự hiện "2 tuần trước" / "2 weeks ago".', 'sunny-grove-core' ),
		),
		'sg_review_original' => array(
			'type'     => 'longtext',
			'label'    => __( 'Nội dung nguyên văn', 'sunny-grove-core' ),
			'help'     => __( 'Chép NGUYÊN VĂN từ Google Maps, không sửa câu chữ.', 'sunny-grove-core' ),
			'required' => true,
		),
		'sg_review_lang'     => array(
			'type'    => 'select',
			'label'   => __( 'Ngôn ngữ gốc', 'sunny-grove-core' ),
			'default' => sg_default_lang(),
			'options' => $langs,
		),
		'sg_review_lang_name' => array(
			'type'  => 'text',
			'label' => __( 'Tên ngôn ngữ gốc (chỉ điền khi chọn Khác)', 'sunny-grove-core' ),
			'help'  => __( 'Ví dụ: tiếng Hàn | Korean. Hiện trong dòng "Đã dịch từ …".', 'sunny-grove-core' ),
		),
	);
	foreach ( sg_languages() as $code => $lang ) {
		$review[ 'sg_review_tr_' . $code ] = array(
			'type'      => 'longtext',
			/* translators: %s: tên ngôn ngữ */
			'label'     => sprintf( __( 'Bản dịch %s (tùy chọn)', 'sunny-grove-core' ), $lang['name'] ),
			'help'      => __( 'Để trống nếu đây là ngôn ngữ gốc. Có bản dịch thì website hiện bản dịch kèm dòng "Đã dịch từ … · Xem bản gốc".', 'sunny-grove-core' ),
			'no_shared' => true,
		);
	}
	$review['sg_review_url']  = array(
		'type'        => 'url',
		'label'       => __( 'Link tới đánh giá trên Google Maps', 'sunny-grove-core' ),
		'help'        => __( 'Bắt buộc. Mở đánh giá trên Google Maps → bấm Chia sẻ → Sao chép đường liên kết, rồi dán vào đây.', 'sunny-grove-core' ),
		'required'    => true,
		'placeholder' => 'https://maps.app.goo.gl/…',
	);
	$review['sg_review_show'] = array(
		'type'     => 'checkbox',
		'label'    => __( 'Hiển thị trên trang chủ', 'sunny-grove-core' ),
		'help'     => __( 'Trang chủ hiện tối đa 3 đánh giá đang bật, theo ô Thứ tự (khung Thuộc tính bên phải, số nhỏ đứng trước).', 'sunny-grove-core' ),
		'default'  => '1',
		'checkbox' => __( 'Bật', 'sunny-grove-core' ),
	);
	$fields['sg_review'] = $review;
	return $fields;
}
add_filter( 'sgc_meta_fields', 'sg_review_fields' );

/**
 * Hướng dẫn ở đầu form đánh giá.
 *
 * @param WP_Post $post Bài.
 */
function sg_review_form_intro( $post ) {
	if ( 'sg_review' !== $post->post_type ) {
		return;
	}
	echo '<div class="sgc-note"><p><strong>' . esc_html__( 'Chép NGUYÊN VĂN nội dung từ Google Maps, không sửa câu chữ.', 'sunny-grove-core' ) . '</strong></p>'
		. '<p>' . esc_html__( 'Cách lấy link: mở đánh giá trên Google Maps → bấm Chia sẻ → Sao chép đường liên kết.', 'sunny-grove-core' ) . '</p></div>';
	if ( sg_review_is_sample( $post->ID ) ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Đây là đánh giá MẪU – chỉ hiện ở website thử nghiệm. Thay bằng đánh giá thật (đổi link Google Maps là hết nhãn MẪU) hoặc xóa đi.', 'sunny-grove-core' ) . '</p></div>';
	}
}
add_action( 'sgc_meta_box_before', 'sg_review_form_intro' );

/**
 * Link có phải link Google Maps không (google.com/maps, maps.google.*, maps.app.goo.gl, goo.gl/maps, g.co, g.page…).
 *
 * @param string $url Link.
 * @return bool
 */
function sg_reviews_is_maps_url( $url ) {
	$parts = wp_parse_url( trim( (string) $url ) );
	if ( empty( $parts['host'] ) || ! in_array( strtolower( $parts['scheme'] ?? '' ), array( 'http', 'https' ), true ) ) {
		return false;
	}
	$host = strtolower( $parts['host'] );
	$path = (string) ( $parts['path'] ?? '' );
	if ( preg_match( '/^(www\.)?google\.[a-z]{2,3}(\.[a-z]{2})?$/', $host ) ) {
		return 0 === strpos( $path, '/maps' );
	}
	if ( preg_match( '/^maps\.google\.[a-z]{2,3}(\.[a-z]{2})?$/', $host ) || in_array( $host, array( 'maps.app.goo.gl', 'g.co', 'g.page' ), true ) ) {
		return true;
	}
	if ( 'goo.gl' === $host ) {
		return 0 === strpos( $path, '/maps' );
	}
	if ( 'search.google.com' === $host ) {
		return 0 === strpos( $path, '/local' );
	}
	return false;
}

/**
 * Đánh giá mẫu?
 *
 * @param int $post_id ID.
 * @return bool
 */
function sg_review_is_sample( $post_id ) {
	return '' !== (string) get_post_meta( $post_id, 'sg_review_sample', true );
}

/**
 * Website đang chạy thử (máy tính, localhost, staging)? Đánh giá mẫu chỉ hiện ở đây.
 *
 * @return bool
 */
function sg_reviews_is_test_site() {
	$host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$test = in_array( wp_get_environment_type(), array( 'local', 'development', 'staging' ), true )
		|| in_array( $host, array( 'localhost', '127.0.0.1', '::1', '[::1]' ), true )
		|| (bool) preg_match( '/\.(local|test|localhost)$/', $host )
		|| (bool) preg_match( '/(^|[.-])staging([.-]|$)/', $host );
	return (bool) apply_filters( 'sg_reviews_is_test_site', $test );
}

/**
 * Kiểm tra khi lưu: link Google Maps bắt buộc và đúng dạng – sai thì giữ ở bản nháp và báo lỗi.
 *
 * @param array $data    Dữ liệu bài sắp lưu.
 * @param array $postarr Dữ liệu gửi lên.
 * @return array
 */
function sg_review_validate_on_save( $data, $postarr ) {
	if ( 'sg_review' !== $data['post_type'] || ! isset( $_POST['sgc_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sgc_meta_nonce'] ), 'sgc_save_meta' ) ) {
		return $data;
	}
	$errors = array();
	$url    = isset( $_POST['sg_review_url'] ) ? trim( wp_unslash( $_POST['sg_review_url'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( '' === $url ) {
		$errors[] = __( 'Chưa có link tới đánh giá trên Google Maps.', 'sunny-grove-core' );
	} elseif ( ! sg_reviews_is_maps_url( $url ) ) {
		$errors[] = __( 'Link chưa đúng: phải là link Google Maps (google.com/maps, maps.app.goo.gl, g.co…). Mở đánh giá trên Google Maps → Chia sẻ → Sao chép đường liên kết.', 'sunny-grove-core' );
	}
	if ( isset( $_POST['sg_review_original'] ) && '' === trim( wp_unslash( $_POST['sg_review_original'] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$errors[] = __( 'Chưa có nội dung đánh giá.', 'sunny-grove-core' );
	}
	if ( $errors ) {
		if ( in_array( $data['post_status'], array( 'publish', 'future' ), true ) ) {
			$data['post_status'] = 'draft';
			$errors[]            = __( 'Đánh giá được giữ ở bản nháp cho tới khi sửa xong.', 'sunny-grove-core' );
		}
		set_transient( 'sg_review_errors_' . get_current_user_id(), $errors, 5 * MINUTE_IN_SECONDS );
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'sg_review_validate_on_save', 10, 2 );

/**
 * Sau khi lưu: đổi link khác link mẫu → không còn là đánh giá mẫu.
 *
 * @param int $post_id ID.
 */
function sg_review_after_save( $post_id ) {
	$sample = (string) get_post_meta( $post_id, 'sg_review_sample', true );
	if ( '' !== $sample && (string) get_post_meta( $post_id, 'sg_review_url', true ) !== $sample ) {
		delete_post_meta( $post_id, 'sg_review_sample' );
	}
}
add_action( 'save_post_sg_review', 'sg_review_after_save', 20 );

/**
 * Báo lỗi kiểm tra trên màn hình sửa đánh giá.
 */
function sg_review_error_notice() {
	$screen = get_current_screen();
	if ( ! $screen || 'sg_review' !== $screen->post_type || 'post' !== $screen->base ) {
		return;
	}
	$key    = 'sg_review_errors_' . get_current_user_id();
	$errors = get_transient( $key );
	if ( ! $errors ) {
		return;
	}
	delete_transient( $key );
	echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Chưa đăng được đánh giá:', 'sunny-grove-core' ) . '</strong></p><ul style="list-style:disc;margin-left:2em">';
	foreach ( (array) $errors as $error ) {
		echo '<li>' . esc_html( $error ) . '</li>';
	}
	echo '</ul></div>';
}
add_action( 'admin_notices', 'sg_review_error_notice' );

/**
 * Nhãn "MẪU" cạnh tên trong danh sách đánh giá.
 *
 * @param array   $states Trạng thái.
 * @param WP_Post $post   Bài.
 * @return array
 */
function sg_review_post_states( $states, $post ) {
	if ( 'sg_review' === $post->post_type && sg_review_is_sample( $post->ID ) ) {
		$states['sg_sample'] = __( 'MẪU', 'sunny-grove-core' );
	}
	return $states;
}
add_filter( 'display_post_states', 'sg_review_post_states', 10, 2 );

/**
 * Cột danh sách đánh giá.
 *
 * @param array $cols Cột.
 * @return array
 */
function sg_review_columns( $cols ) {
	$cols['title'] = __( 'Người đánh giá', 'sunny-grove-core' );
	unset( $cols['date'] );
	return array_merge(
		array_slice( $cols, 0, 2 ),
		array(
			'sg_rating' => __( 'Số sao', 'sunny-grove-core' ),
			'sg_text'   => __( 'Nội dung', 'sunny-grove-core' ),
			'sg_show'   => __( 'Trên trang chủ', 'sunny-grove-core' ),
			'sg_order'  => __( 'Thứ tự', 'sunny-grove-core' ),
			'sg_date'   => __( 'Ngày đánh giá', 'sunny-grove-core' ),
		),
		array_slice( $cols, 2 )
	);
}
add_filter( 'manage_sg_review_posts_columns', 'sg_review_columns' );

/**
 * Nội dung cột.
 *
 * @param string $col     Cột.
 * @param int    $post_id ID.
 */
function sg_review_column( $col, $post_id ) {
	if ( 'sg_rating' === $col ) {
		echo esc_html( str_repeat( '★', max( 1, min( 5, (int) get_post_meta( $post_id, 'sg_rating', true ) ) ) ) );
	} elseif ( 'sg_text' === $col ) {
		echo esc_html( wp_trim_words( (string) get_post_meta( $post_id, 'sg_review_original', true ), 18 ) );
		if ( ! sg_reviews_is_maps_url( (string) get_post_meta( $post_id, 'sg_review_url', true ) ) ) {
			echo '<br><span style="color:#b32d2e">' . esc_html__( 'Thiếu link Google Maps – không hiển thị', 'sunny-grove-core' ) . '</span>';
		}
	} elseif ( 'sg_show' === $col ) {
		echo '1' === (string) get_post_meta( $post_id, 'sg_review_show', true ) ? esc_html__( 'Bật', 'sunny-grove-core' ) : '<span style="color:#646970">' . esc_html__( 'Tắt', 'sunny-grove-core' ) . '</span>';
	} elseif ( 'sg_order' === $col ) {
		echo (int) get_post_field( 'menu_order', $post_id );
	} elseif ( 'sg_date' === $col ) {
		$date = (string) get_post_meta( $post_id, 'sg_review_date', true );
		echo $date ? esc_html( mysql2date( get_option( 'date_format' ), $date ) ) : '—';
	}
}
add_action( 'manage_sg_review_posts_custom_column', 'sg_review_column', 10, 2 );

/**
 * Danh sách đánh giá xếp theo Thứ tự (giống trang chủ).
 *
 * @param WP_Query $query Truy vấn.
 */
function sg_review_admin_order( $query ) {
	if ( is_admin() && $query->is_main_query() && 'sg_review' === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'sg_review_admin_order' );

/* ------------------------------------------------------------------ */
/* Cài đặt: Sunny Grove → Đánh giá                                    */
/* ------------------------------------------------------------------ */

/**
 * Cài đặt đánh giá Google Maps.
 *
 * @return array { rating, total, url_all, url_write, updated, sample }
 */
function sg_reviews_settings() {
	return wp_parse_args(
		(array) get_option( 'sgc_reviews', array() ),
		array(
			'rating'    => '',
			'total'     => '',
			'url_all'   => '',
			'url_write' => '',
			'updated'   => 0,
			'sample'    => '',
		)
	);
}

/**
 * Số ngày kể từ lần cập nhật điểm Google Maps (null = chưa cập nhật bao giờ).
 *
 * @return int|null
 */
function sg_reviews_settings_age() {
	$updated = (int) sg_reviews_settings()['updated'];
	return $updated ? (int) floor( ( time() - $updated ) / DAY_IN_SECONDS ) : null;
}

/**
 * Menu quản trị.
 */
function sg_reviews_admin_menu() {
	add_submenu_page( 'sgc-hotel', __( 'Đánh giá Google Maps', 'sunny-grove-core' ), __( 'Đánh giá', 'sunny-grove-core' ), 'manage_sunny_grove', 'sgc-reviews', 'sg_reviews_render_settings' );
}
add_action( 'admin_menu', 'sg_reviews_admin_menu', 20 );

/**
 * Trang cài đặt.
 */
function sg_reviews_render_settings() {
	$s   = sg_reviews_settings();
	$age = sg_reviews_settings_age();
	echo '<div class="wrap sgc-settings"><h1>' . esc_html__( 'Đánh giá Google Maps', 'sunny-grove-core' ) . '</h1>';
	if ( isset( $_GET['sgc_saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Đã lưu.', 'sunny-grove-core' ) . '</p></div>';
	}
	if ( ! empty( $_GET['sgc_bad'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Có link chưa đúng nên chưa được lưu: link phải là link Google Maps (google.com/maps, maps.app.goo.gl, g.co, g.page…).', 'sunny-grove-core' ) . '</p></div>';
	}
	echo '<p>' . esc_html__( 'Các số liệu này hiện ở đầu khối Đánh giá trên trang chủ. Mở trang khách sạn trên Google Maps, chép đúng điểm trung bình và tổng số đánh giá đang hiển thị ở đó.', 'sunny-grove-core' ) . '</p>';

	if ( null === $age ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Chưa cập nhật lần nào. Điền số liệu thật từ Google Maps rồi bấm Lưu.', 'sunny-grove-core' ) . '</p></div>';
	} else {
		/* translators: 1: ngày, 2: số ngày */
		$when = sprintf( __( 'Cập nhật lần cuối: %1$s (%2$s ngày trước).', 'sunny-grove-core' ), wp_date( get_option( 'date_format' ), (int) $s['updated'] ), number_format_i18n( $age ) );
		if ( $age > 30 ) {
			echo '<div class="notice notice-warning inline"><p><strong>' . esc_html( $when ) . '</strong> ' . esc_html__( 'Đã hơn 30 ngày – hãy mở Google Maps kiểm tra lại điểm và số đánh giá, rồi bấm Lưu.', 'sunny-grove-core' ) . '</p></div>';
		} else {
			echo '<p class="description">' . esc_html( $when ) . '</p>';
		}
	}

	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'sgc_save_reviews' );
	echo '<input type="hidden" name="action" value="sgc_save_reviews"><table class="form-table" role="presentation">';
	$rows = array(
		'rating'    => array( __( 'Điểm trung bình', 'sunny-grove-core' ), __( 'Ví dụ 4,9 – đúng số Google Maps đang hiện.', 'sunny-grove-core' ), '<input type="number" name="sgc_reviews[rating]" value="%s" min="1" max="5" step="0.1" class="small-text">' ),
		'total'     => array( __( 'Tổng số đánh giá', 'sunny-grove-core' ), __( 'Ví dụ 128.', 'sunny-grove-core' ), '<input type="number" name="sgc_reviews[total]" value="%s" min="0" step="1" class="small-text">' ),
		'url_all'   => array( __( 'Link trang đánh giá Google Maps của khách sạn', 'sunny-grove-core' ), __( 'Mở khách sạn trên Google Maps → tab Bài đánh giá → Chia sẻ → Sao chép đường liên kết. Dùng cho link "Xem tất cả đánh giá trên Google Maps".', 'sunny-grove-core' ), '<input type="url" name="sgc_reviews[url_all]" value="%s" class="large-text" placeholder="https://maps.app.goo.gl/…">' ),
		'url_write' => array( __( 'Link "Viết đánh giá"', 'sunny-grove-core' ), __( 'Link do Google Business Profile cung cấp: vào Google Business Profile → Nhận thêm bài đánh giá → Sao chép (dạng g.page/r/…/review).', 'sunny-grove-core' ), '<input type="url" name="sgc_reviews[url_write]" value="%s" class="large-text" placeholder="https://g.page/r/…/review">' ),
	);
	foreach ( $rows as $key => $row ) {
		echo '<tr><th scope="row">' . esc_html( $row[0] ) . '</th><td>';
		printf( $row[2], esc_attr( (string) $s[ $key ] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p class="description">' . esc_html( $row[1] ) . '</p></td></tr>';
	}
	echo '</table>';
	submit_button( __( 'Lưu thay đổi', 'sunny-grove-core' ) );
	echo '</form>';
	echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=sg_review' ) ) . '">' . esc_html__( 'Quản lý từng đánh giá (thêm, sửa, bật/tắt, thứ tự) →', 'sunny-grove-core' ) . '</a></p></div>';
}

/**
 * Lưu cài đặt.
 */
function sg_reviews_save_settings() {
	if ( ! current_user_can( 'manage_sunny_grove' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'sunny-grove-core' ) );
	}
	check_admin_referer( 'sgc_save_reviews' );
	$in  = isset( $_POST['sgc_reviews'] ) && is_array( $_POST['sgc_reviews'] ) ? wp_unslash( $_POST['sgc_reviews'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$old = sg_reviews_settings();
	$bad = false;

	$rating = str_replace( ',', '.', trim( (string) ( $in['rating'] ?? '' ) ) );
	$rating = '' === $rating ? '' : (string) round( max( 1, min( 5, (float) $rating ) ), 1 );
	$total  = trim( (string) ( $in['total'] ?? '' ) );
	$total  = '' === $total ? '' : (string) absint( preg_replace( '/\D/', '', $total ) );
	$urls   = array();
	foreach ( array( 'url_all', 'url_write' ) as $key ) {
		$url = esc_url_raw( trim( (string) ( $in[ $key ] ?? '' ) ) );
		if ( '' !== $url && ! sg_reviews_is_maps_url( $url ) ) {
			$bad = true;
			$url = $old[ $key ];
		}
		$urls[ $key ] = $url;
	}
	update_option(
		'sgc_reviews',
		array(
			'rating'    => $rating,
			'total'     => $total,
			'url_all'   => $urls['url_all'],
			'url_write' => $urls['url_write'],
			'updated'   => time(),
			'sample'    => '', // Chủ web đã tự lưu → không còn là số liệu mẫu.
		)
	);
	wp_safe_redirect( admin_url( 'admin.php?page=sgc-reviews&sgc_saved=1' . ( $bad ? '&sgc_bad=1' : '' ) ) );
	exit;
}
add_action( 'admin_post_sgc_save_reviews', 'sg_reviews_save_settings' );

/* ------------------------------------------------------------------ */
/* Nhắc việc: đánh giá mẫu, thiếu link, số liệu cũ                    */
/* ------------------------------------------------------------------ */

/**
 * Thông báo trên Bảng tin và danh sách đánh giá.
 */
function sg_reviews_admin_notices() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'edit-sg_review' ), true ) || ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$samples = get_posts(
		array(
			'post_type'      => 'sg_review',
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => 'sg_review_sample', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'no_found_rows'  => true,
		)
	);
	$list = admin_url( 'edit.php?post_type=sg_review' );
	if ( $samples ) {
		/* translators: %d: số đánh giá mẫu */
		$msg = sprintf( __( 'Còn %d đánh giá mẫu – thay bằng đánh giá thật trước khi đưa website lên mạng.', 'sunny-grove-core' ), count( $samples ) );
		echo '<div class="notice notice-warning"><p><strong>' . esc_html( $msg ) . '</strong> '
			. esc_html__( 'Đánh giá mẫu chỉ hiện ở website thử nghiệm, website thật tự ẩn.', 'sunny-grove-core' )
			. ( 'dashboard' === $screen->id ? ' <a href="' . esc_url( $list ) . '">' . esc_html__( 'Mở danh sách đánh giá', 'sunny-grove-core' ) . '</a>' : '' ) . '</p></div>';
	}
	$s   = sg_reviews_settings();
	$age = sg_reviews_settings_age();
	if ( current_user_can( 'manage_sunny_grove' ) && ( '' !== $s['sample'] || null === $age || $age > 30 ) ) {
		$msg = '' !== $s['sample'] || null === $age
			? __( 'Điểm trung bình và số đánh giá Google Maps đang là số mẫu.', 'sunny-grove-core' )
			/* translators: %d: số ngày */
			: sprintf( __( 'Điểm và số đánh giá Google Maps đã %d ngày chưa cập nhật.', 'sunny-grove-core' ), $age );
		echo '<div class="notice notice-warning"><p>' . esc_html( $msg ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=sgc-reviews' ) ) . '">' . esc_html__( 'Cập nhật tại Sunny Grove → Đánh giá', 'sunny-grove-core' ) . '</a></p></div>';
	}
	if ( 'edit-sg_review' === $screen->id ) {
		echo '<div class="notice notice-info"><p>' . esc_html__( 'Trang chủ hiện tối đa 3 đánh giá đang bật "Hiển thị trên trang chủ", theo cột Thứ tự.', 'sunny-grove-core' )
			. ' <a href="' . esc_url( admin_url( 'admin.php?page=sgc-reviews' ) ) . '">' . esc_html__( 'Điểm trung bình, tổng số đánh giá và link Google Maps của khách sạn →', 'sunny-grove-core' ) . '</a></p></div>';
	}
}
add_action( 'admin_notices', 'sg_reviews_admin_notices' );

/* ------------------------------------------------------------------ */
/* Hiển thị                                                           */
/* ------------------------------------------------------------------ */

/**
 * Đánh giá sẽ hiển thị: đang bật, có nội dung và link Google Maps đúng, theo Thứ tự.
 * Website thật bỏ qua đánh giá mẫu.
 *
 * @param int $limit Số tối đa.
 * @return WP_Post[]
 */
function sg_reviews_for_display( $limit = 3 ) {
	$limit = max( 1, (int) $limit );
	$test  = sg_reviews_is_test_site();
	// Danh sách ID có bộ nhớ đệm (tự xóa khi lưu đánh giá); bài + meta nạp chung 1 lần.
	$ids = sgc_cache_get(
		'reviews_' . $limit . '_' . ( $test ? 'test' : 'live' ),
		static function () use ( $limit ) {
			return wp_list_pluck( sg_reviews_query( $limit ), 'ID' );
		}
	);
	if ( ! $ids ) {
		return array();
	}
	_prime_post_caches( $ids, false, true );
	return array_values( array_filter( array_map( 'get_post', $ids ) ) );
}

/**
 * Truy vấn đánh giá hiển thị (không bộ nhớ đệm).
 *
 * @param int $limit Số đánh giá tối đa.
 * @return WP_Post[]
 */
function sg_reviews_query( $limit ) {
	$meta = array(
		array(
			'key'   => 'sg_review_show',
			'value' => '1',
		),
	);
	if ( ! sg_reviews_is_test_site() ) {
		$meta[] = array(
			'key'     => 'sg_review_sample',
			'compare' => 'NOT EXISTS',
		);
	}
	$posts = get_posts(
		array(
			'post_type'      => 'sg_review',
			'post_status'    => 'publish',
			'posts_per_page' => 20,
			'orderby'        => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'meta_query'     => $meta, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'no_found_rows'  => true,
		)
	);
	$posts = array_filter(
		$posts,
		static function ( $p ) {
			return '' !== trim( (string) get_post_meta( $p->ID, 'sg_review_original', true ) ) && sg_reviews_is_maps_url( (string) get_post_meta( $p->ID, 'sg_review_url', true ) );
		}
	);
	return array_slice( array_values( $posts ), 0, max( 1, (int) $limit ) );
}

/**
 * "2 tuần trước" / "2 weeks ago".
 *
 * @param string $date Ngày Y-m-d.
 * @param string $lang Ngôn ngữ.
 * @return string
 */
function sg_reviews_ago( $date, $lang = '' ) {
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $date ) ) {
		return '';
	}
	$days = (int) floor( ( strtotime( wp_date( 'Y-m-d' ) . ' 00:00:00 UTC' ) - strtotime( $date . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS );
	if ( $days < 1 ) {
		return sgc_label( 'reviews_ago_today', array(), $lang );
	}
	if ( $days < 7 ) {
		$n = $days;
		$u = 'day';
	} elseif ( $days < 30 ) {
		$n = (int) floor( $days / 7 );
		$u = 'week';
	} elseif ( $days < 365 ) {
		$n = max( 1, (int) floor( $days / 30 ) );
		$u = 'month';
	} else {
		$n = (int) floor( $days / 365 );
		$u = 'year';
	}
	return sgc_label( 'reviews_ago_' . $u . ( 1 === $n ? '' : 's' ), array( 'n' => number_format_i18n( $n ) ), $lang );
}

/**
 * Chữ cái đầu của tên (tối đa 2: từ đầu và từ cuối), bỏ qua cách xưng hô "Anh", "Chị", "Cô"…
 *
 * @param string $name Tên.
 * @return string
 */
function sg_reviews_initials( $name ) {
	$honorifics = array( 'anh', 'chị', 'cô', 'chú', 'bác', 'em', 'ông', 'bà', 'mr', 'mr.', 'mrs', 'mrs.', 'ms', 'ms.' );
	$words      = array_values(
		array_filter(
			preg_split( '/\s+/u', trim( (string) $name ) ),
			static function ( $w ) {
				return (bool) preg_match( '/^\p{L}/u', $w );
			}
		)
	);
	if ( count( $words ) > 1 && in_array( mb_strtolower( $words[0] ), $honorifics, true ) ) {
		array_shift( $words );
	}
	if ( ! $words ) {
		return '';
	}
	$first = mb_substr( $words[0], 0, 1 );
	$last  = count( $words ) > 1 ? mb_substr( end( $words ), 0, 1 ) : '';
	return mb_strtoupper( $first . $last );
}

/**
 * Sao dạng thanh đo (tô màu theo điểm, kể cả số lẻ như 4,6).
 *
 * @param float  $rating Điểm.
 * @param string $label  Nhãn cho trình đọc màn hình.
 * @param string $class  Class thêm.
 * @return string
 */
function sg_reviews_stars( $rating, $label, $class = '' ) {
	$fill = max( 0, min( 100, round( (float) $rating / 5 * 100, 1 ) ) );
	return '<span class="sg-stars-meter' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '" role="img" aria-label="' . esc_attr( $label ) . '"><span style="width:' . esc_attr( (string) $fill ) . '%"></span></span>';
}

/**
 * Số theo ngôn ngữ (4,9 / 4.9 – 1.234 / 1,234).
 *
 * @param float  $number   Số.
 * @param int    $decimals Số chữ số thập phân.
 * @param string $lang     Ngôn ngữ.
 * @return string
 */
function sg_reviews_number( $number, $decimals, $lang ) {
	$vi = 'vi' === $lang;
	return number_format( (float) $number, $decimals, $vi ? ',' : '.', $vi ? '.' : ',' );
}

/**
 * Thêm ngoặc kép nếu nội dung chưa có.
 *
 * @param string $text Nội dung.
 * @return string HTML.
 */
function sg_reviews_quote_html( $text ) {
	$text = trim( (string) $text );
	if ( ! preg_match( '/^["“„«]/u', $text ) ) {
		$text = '“' . $text . '”';
	}
	return nl2br( esc_html( $text ) );
}

/**
 * Link mở tab mới (kèm chữ cho trình đọc màn hình).
 *
 * @param string $url   Link.
 * @param string $text  Chữ (đã escape).
 * @param string $class Class.
 * @param string $aria  Nhãn đầy đủ (tùy chọn).
 * @return string
 */
function sg_reviews_ext_link( $url, $text, $class = 'sg-ul', $aria = '' ) {
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer"' . ( $aria ? ' aria-label="' . esc_attr( $aria . ' ' . sgc_label( 'reviews_new_tab' ) ) . '"' : '' ) . '>'
		. $text . ( $aria ? '' : '<span class="screen-reader-text"> ' . esc_html( sgc_label( 'reviews_new_tab' ) ) . '</span>' ) . '</a>';
}

/**
 * HTML một đánh giá.
 *
 * @param WP_Post $review Đánh giá.
 * @param string  $lang   Ngôn ngữ đang xem.
 * @return string
 */
function sg_reviews_item_html( $review, $lang ) {
	$id        = $review->ID;
	$name      = get_the_title( $review );
	$url       = (string) get_post_meta( $id, 'sg_review_url', true );
	$rating    = max( 1, min( 5, (int) get_post_meta( $id, 'sg_rating', true ) ) );
	$orig_lang = (string) get_post_meta( $id, 'sg_review_lang', true );
	$orig_lang = '' === $orig_lang ? sg_default_lang() : $orig_lang;
	$original  = (string) get_post_meta( $id, 'sg_review_original', true );
	$tr        = $orig_lang !== $lang ? trim( (string) get_post_meta( $id, 'sg_review_tr_' . $lang, true ) ) : '';
	$orig_attr = isset( sg_languages()[ $orig_lang ] ) ? ' lang="' . esc_attr( $orig_lang ) . '"' : '';

	if ( '' !== $tr ) {
		$quote = '<p class="sg-greview__text" lang="' . esc_attr( $lang ) . '">' . sg_reviews_quote_html( $tr ) . '</p>'
			. '<p class="sg-greview__text"' . $orig_attr . ' hidden>' . sg_reviews_quote_html( $original ) . '</p>';
		if ( isset( sg_languages()[ $orig_lang ] ) ) {
			$from = sgc_label( 'reviews_lang_' . $orig_lang );
		} else {
			$from = trim( (string) sg_meta_tr( $id, 'sg_review_lang_name', $lang ) );
			$from = '' !== $from ? $from : sgc_label( 'reviews_lang_other' );
		}
		$note = '<p class="sg-greview__tr">' . esc_html( sgc_label( 'reviews_translated', array( 'lang' => $from ) ) ) . ' · '
			. '<button type="button" class="sg-greview__toggle sg-ul" aria-pressed="false" data-original="' . esc_attr( sgc_label( 'reviews_show_original' ) ) . '" data-translation="' . esc_attr( sgc_label( 'reviews_show_translation' ) ) . '">' . esc_html( sgc_label( 'reviews_show_original' ) ) . '</button></p>';
	} else {
		$quote = '<p class="sg-greview__text"' . $orig_attr . '>' . sg_reviews_quote_html( $original ) . '</p>';
		$note  = '';
	}

	$avatar_id = (int) get_post_meta( $id, 'sg_avatar', true );
	$avatar    = $avatar_id ? wp_get_attachment_image(
		$avatar_id,
		array( 72, 72 ),
		false,
		array(
			'class'   => 'sg-greview__avatar',
			'alt'     => '',
			'loading' => 'lazy',
			'sizes'   => '36px',
		)
	) : '';
	if ( ! $avatar ) {
		$avatar = '<span class="sg-greview__avatar" aria-hidden="true">' . esc_html( sg_reviews_initials( $name ) ) . '</span>';
	}

	$date = (string) get_post_meta( $id, 'sg_review_date', true );
	$ago  = sg_reviews_ago( $date, $lang );
	$sub  = array( sg_reviews_stars( $rating, sgc_label( 'reviews_score', array( 'n' => $rating ) ) ) );
	if ( $ago ) {
		$sub[] = '<time datetime="' . esc_attr( $date ) . '">' . esc_html( $ago ) . '</time>';
	}
	$sub[] = sg_reviews_ext_link( $url, 'Google Maps <span aria-hidden="true">↗</span>', 'sg-ul', sgc_label( 'reviews_view_on_maps', array( 'ten' => $name ) ) );

	return '<figure class="sg-greview">'
		. '<blockquote class="sg-greview__quote" cite="' . esc_url( $url ) . '">' . $quote . '</blockquote>'
		. '<p class="sg-greview__more" hidden>' . sg_reviews_ext_link( $url, esc_html( sgc_label( 'reviews_read_more' ) ) . ' <span aria-hidden="true">↗</span>' ) . '</p>'
		. $note
		. '<figcaption class="sg-greview__by">' . $avatar
		. '<span class="sg-greview__who"><span class="sg-greview__name">' . esc_html( $name ) . '</span>'
		. '<span class="sg-greview__sub">' . implode( ' <span aria-hidden="true">·</span> ', $sub ) . '</span></span>'
		. '</figcaption></figure>';
}

/**
 * HTML khối Đánh giá (dùng cho block). Không có đánh giá nào để hiện → trả về chuỗi rỗng (cả khối nền tự ẩn).
 *
 * @param array $attributes Thuộc tính block.
 * @return string
 */
function sg_render_reviews( $attributes ) {
	$reviews = sg_reviews_for_display( max( 1, min( 3, (int) ( $attributes['count'] ?? 3 ) ) ) );
	if ( ! $reviews ) {
		return '';
	}
	$lang = sg_current_lang();
	$s    = sg_reviews_settings();
	// Số liệu mẫu (từ nội dung mẫu) chỉ hiện ở website thử nghiệm.
	if ( '' !== $s['sample'] && ! sg_reviews_is_test_site() ) {
		$s['rating'] = '';
		$s['total']  = '';
	}

	$title = explode( '|', sgc_label( 'reviews_title' ), 2 );
	$head  = '<header class="sg-greviews__head">'
		. '<p class="sg-greviews__eyebrow is-style-sg-eyebrow">' . esc_html( sgc_label( 'reviews_eyebrow' ) ) . '</p>'
		. '<h2 class="sg-greviews__title wp-block-heading">' . esc_html( trim( $title[0] ) ) . ( isset( $title[1] ) ? ' <em class="sg-accent">' . esc_html( trim( $title[1] ) ) . '</em>' : '' ) . '</h2>';
	$parts = array();
	if ( '' !== (string) $s['rating'] ) {
		$score   = sg_reviews_number( $s['rating'], 1, $lang );
		$parts[] = '<span class="sg-greviews__score">' . esc_html( $score ) . '</span>' . sg_reviews_stars( (float) $s['rating'], sgc_label( 'reviews_score', array( 'n' => $score ) ), 'sg-stars-meter--lg' );
	}
	if ( '' !== (string) $s['total'] ) {
		$parts[] = '<span class="sg-greviews__total">' . esc_html( sgc_label( 'reviews_total', array( 'n' => sg_reviews_number( (int) $s['total'], 0, $lang ) ) ) ) . '</span>';
	}
	if ( $parts ) {
		$head .= '<p class="sg-greviews__summary">' . implode( '<span class="sg-greviews__sep" aria-hidden="true"></span>', $parts ) . '</p>';
	}
	$head .= '</header>';

	$items = '';
	foreach ( $reviews as $review ) {
		$items .= sg_reviews_item_html( $review, $lang );
	}

	$links = array();
	if ( '' !== (string) $s['url_all'] ) {
		$links[] = sg_reviews_ext_link( $s['url_all'], esc_html( sgc_label( 'reviews_all' ) ) . ' <span aria-hidden="true">↗</span>' );
	}
	if ( '' !== (string) $s['url_write'] ) {
		$links[] = sg_reviews_ext_link( $s['url_write'], esc_html( sgc_label( 'reviews_write' ) ) );
	}
	$foot = $links ? '<p class="sg-greviews__links">' . implode( '<span class="sg-greviews__sep" aria-hidden="true"></span>', $links ) . '</p>' : '';

	$wrap = get_block_wrapper_attributes(
		array(
			'class'      => 'sg-greviews' . ( empty( $attributes['align'] ) ? ' alignwide' : '' ),
			'aria-label' => sgc_label( 'reviews_label' ),
			'style'      => '--sg-n:' . count( $reviews ),
		)
	);
	return '<section ' . $wrap . '>' . $head . '<div class="sg-greviews__list">' . $items . '</div>' . $foot . '</section>';
}

/**
 * Khối nền chỉ chứa block Đánh giá mà block không có gì để hiện → ẩn luôn khối nền (không để lại khoảng trống).
 *
 * @param string $content Nội dung đã dựng.
 * @param array  $block   Block.
 * @return string
 */
function sg_reviews_hide_empty_section( $content, $block ) {
	$inner = $block['innerBlocks'] ?? array();
	if ( ! $inner || false !== strpos( $content, 'sg-greviews' ) ) {
		return $content;
	}
	foreach ( $inner as $child ) {
		if ( 'sunny-grove/reviews' !== $child['blockName'] ) {
			return $content;
		}
	}
	return '';
}
add_filter( 'render_block_core/group', 'sg_reviews_hide_empty_section', 10, 2 );

/* ------------------------------------------------------------------ */
/* Nâng cấp dữ liệu cũ (một lần)                                       */
/* ------------------------------------------------------------------ */

/**
 * Ghi các trường của một đánh giá mẫu từ dữ liệu nội dung mẫu (demo/content.json).
 *
 * @param int   $id   ID.
 * @param array $item Dữ liệu.
 */
function sg_reviews_apply_sample( $id, array $item ) {
	$days = (int) ( $item['days_ago'] ?? 0 );
	update_post_meta( $id, 'sg_rating', (string) (int) $item['rating'] );
	update_post_meta( $id, 'sg_review_date', wp_date( 'Y-m-d', time() - $days * DAY_IN_SECONDS ) );
	update_post_meta( $id, 'sg_review_original', (string) $item['text'] );
	update_post_meta( $id, 'sg_review_lang', (string) $item['lang'] );
	foreach ( array_keys( sg_languages() ) as $code ) {
		$tr = (string) ( $item['tr'][ $code ] ?? '' );
		if ( '' !== $tr ) {
			update_post_meta( $id, 'sg_review_tr_' . $code, $tr );
		} else {
			delete_post_meta( $id, 'sg_review_tr_' . $code );
		}
	}
	update_post_meta( $id, 'sg_review_url', (string) $item['url'] );
	update_post_meta( $id, 'sg_review_sample', (string) $item['url'] );
	update_post_meta( $id, 'sg_review_show', '1' );
	foreach ( array( 'sg_source', 'sg_review_text', 'sg_review_from' ) as $old ) {
		delete_post_meta( $id, $old );
		foreach ( sg_secondary_langs() as $code ) {
			delete_post_meta( $id, $old . '_' . $code );
		}
	}
}

/**
 * Số liệu Google Maps mẫu (chỉ ghi khi chủ web chưa tự nhập).
 */
function sg_reviews_seed_sample_settings() {
	$s = sg_reviews_settings();
	if ( (int) $s['updated'] && '' === $s['sample'] ) {
		return;
	}
	$url = 'https://www.google.com/maps/search/?api=1&query=Sunny+Grove+Hotel+Phu+Quoc';
	update_option(
		'sgc_reviews',
		array(
			'rating'    => '4.9',
			'total'     => '128',
			'url_all'   => $url,
			'url_write' => $url,
			'updated'   => 0,
			'sample'    => '1',
		)
	);
}

/**
 * Chuyển đánh giá kiểu cũ (nội dung VI/EN, nguồn, "đến từ") sang kiểu Google Maps. Chạy một lần.
 * Đánh giá mẫu lấy lại từ nội dung mẫu; đánh giá do chủ web nhập giữ nội dung, cần thêm link Google Maps mới hiển thị.
 */
function sg_reviews_upgrade() {
	if ( (int) get_option( 'sgc_reviews_version', 1 ) >= 2 ) {
		return;
	}
	update_option( 'sgc_reviews_version', 2, true );
	$samples = array();
	foreach ( (array) ( sgc_demo_data()['reviews'] ?? array() ) as $item ) {
		$samples[ 'sg_review:' . $item['key'] ] = $item;
	}
	$ids = get_posts(
		array(
			'post_type'      => 'sg_review',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	$has_sample = false;
	foreach ( $ids as $id ) {
		$key = (string) get_post_meta( $id, '_sgc_demo_key', true );
		$key = preg_replace( '/:' . preg_quote( sg_default_lang(), '/' ) . '$/', '', $key );
		if ( isset( $samples[ $key ] ) ) {
			sg_reviews_apply_sample( $id, $samples[ $key ] );
			$has_sample = true;
			continue;
		}
		if ( '' !== (string) get_post_meta( $id, 'sg_review_original', true ) ) {
			continue;
		}
		$vi = (string) get_post_meta( $id, 'sg_review_text', true );
		update_post_meta( $id, 'sg_review_original', $vi );
		update_post_meta( $id, 'sg_review_lang', sg_default_lang() );
		foreach ( sg_secondary_langs() as $code ) {
			$en = (string) get_post_meta( $id, 'sg_review_text_' . $code, true );
			if ( '' !== $en ) {
				update_post_meta( $id, 'sg_review_tr_' . $code, $en );
			}
		}
		update_post_meta( $id, 'sg_review_show', '1' );
	}
	if ( $has_sample ) {
		sg_reviews_seed_sample_settings();
	}
}
add_action( 'init', 'sg_reviews_upgrade', 20 );
