<?php
/**
 * "Áp dụng bố cục mới": dựng lại nội dung các trang mẫu (Trang chủ, Phòng & Giá…) từ pattern hiện tại của theme.
 *
 * Nội dung trang được lưu trong database lúc nhập nội dung mẫu, nên khi theme có bố cục mới, trang cũ không tự đổi.
 * Công cụ này:
 *  1. Liệt kê các trang mẫu, cho biết trang nào đã được sửa so với bản mẫu (cả chữ tiếng Việt lẫn tiếng Anh).
 *  2. Cho chọn từng trang, hiện màn hình xác nhận, chỉ chạy khi bấm "Áp dụng".
 *  3. Lưu nội dung cũ thành một Bản sửa đổi (Revision) để khôi phục được, rồi thay bằng bố cục mới (VI + EN).
 * Chỉ thay phần nội dung của trang – tiêu đề, đường dẫn, SEO, ảnh đại diện giữ nguyên.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/** Phiên bản bố cục của theme ("Resort boutique" = 2). */
const SGC_LAYOUT_VERSION = 2;

/**
 * Dấu vân tay nội dung để biết trang có bị sửa không: tách riêng chữ tiếng Việt (block) và chữ tiếng Anh (thuộc tính sgEn).
 *
 * @param string $content Nội dung block.
 * @return array { vi: string, en: string }
 */
function sgc_layout_fingerprint( $content ) {
	$en   = array();
	$walk = static function ( array $blocks ) use ( &$walk, &$en ) {
		foreach ( $blocks as $i => $block ) {
			if ( isset( $block['attrs']['sgEn'] ) ) {
				$en[] = $block['attrs']['sgEn'];
				unset( $blocks[ $i ]['attrs']['sgEn'] );
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$blocks[ $i ]['innerBlocks'] = $walk( $block['innerBlocks'] );
			}
		}
		return $blocks;
	};
	$vi   = serialize_blocks( $walk( parse_blocks( (string) $content ) ) );
	return array(
		'vi' => md5( preg_replace( '/\s+/', ' ', $vi ) ),
		'en' => md5( wp_json_encode( $en ) ),
	);
}

/**
 * Ghi lại dấu vân tay sau khi trang được dựng từ pattern (nhập mẫu hoặc áp dụng bố cục).
 *
 * @param int $post_id ID trang.
 */
function sgc_layout_remember( $post_id ) {
	$post = get_post( $post_id );
	if ( $post ) {
		update_post_meta( $post_id, '_sgc_layout_hash', sgc_layout_fingerprint( $post->post_content ) );
		update_post_meta( $post_id, '_sgc_layout_version', SGC_LAYOUT_VERSION );
	}
}

/**
 * Danh sách trang mẫu kèm trạng thái.
 *
 * @return array[] { key, pattern, id, title, edited: bool|null, edited_vi, edited_en, modified, author, version }
 */
function sgc_layout_pages() {
	$data   = sgc_demo_data();
	$pages  = (array) get_option( 'sgc_pages', array() );
	$since  = (int) get_option( 'sgc_demo_imported', 0 );
	$result = array();
	foreach ( (array) ( $data['pages'] ?? array() ) as $page ) {
		$id   = (int) ( $pages[ $page['key'] ][ sg_default_lang() ] ?? 0 );
		$post = $id ? get_post( $id ) : null;
		if ( ! $post || 'trash' === $post->post_status ) {
			continue;
		}
		$hash      = get_post_meta( $id, '_sgc_layout_hash', true );
		$edited_vi = null;
		$edited_en = null;
		if ( is_array( $hash ) && isset( $hash['vi'], $hash['en'] ) ) {
			$now       = sgc_layout_fingerprint( $post->post_content );
			$edited_vi = $now['vi'] !== $hash['vi'];
			$edited_en = $now['en'] !== $hash['en'];
			$edited    = $edited_vi || $edited_en;
		} else {
			// Trang nhập từ bản cũ (chưa có dấu vân tay): coi là đã sửa nếu được lưu sau thời điểm nhập mẫu quá 5 phút.
			$edited = ! $since || strtotime( $post->post_modified_gmt . ' UTC' ) > $since + 5 * MINUTE_IN_SECONDS;
		}
		$last_editor = (int) get_post_meta( $id, '_edit_last', true );
		$result[]    = array(
			'key'       => $page['key'],
			'pattern'   => $page['pattern'],
			'id'        => $id,
			'title'     => get_the_title( $post ),
			'edited'    => $edited,
			'edited_vi' => $edited_vi,
			'edited_en' => $edited_en,
			'modified'  => get_post_modified_time( get_option( 'date_format' ) . ' H:i', false, $post ),
			'author'    => $last_editor ? get_the_author_meta( 'display_name', $last_editor ) : '',
			'version'   => (int) get_post_meta( $id, '_sgc_layout_version', true ),
		);
	}
	return $result;
}

/**
 * Mô tả trạng thái "đã sửa" của một trang (chữ hiển thị cho chủ web).
 *
 * @param array $p Trang từ sgc_layout_pages().
 * @return string
 */
function sgc_layout_status_text( array $p ) {
	if ( ! $p['edited'] ) {
		return __( 'Chưa sửa so với bản mẫu', 'sunny-grove-core' );
	}
	if ( null === $p['edited_vi'] ) {
		/* translators: 1: ngày giờ, 2: người sửa */
		return sprintf( __( 'Đã được lưu lại sau khi nhập mẫu (lần cuối %1$s%2$s) – có thể có chữ tiếng Việt hoặc tiếng Anh đã sửa', 'sunny-grove-core' ), $p['modified'], $p['author'] ? ', ' . $p['author'] : '' );
	}
	$parts = array();
	if ( $p['edited_vi'] ) {
		$parts[] = __( 'tiếng Việt', 'sunny-grove-core' );
	}
	if ( $p['edited_en'] ) {
		$parts[] = __( 'tiếng Anh', 'sunny-grove-core' );
	}
	/* translators: 1: ngôn ngữ, 2: ngày giờ */
	return sprintf( __( 'Đã sửa chữ %1$s (lần cuối %2$s)', 'sunny-grove-core' ), implode( ' + ', $parts ), $p['modified'] );
}

/**
 * Dựng lại một trang từ pattern (VI + EN) sau khi lưu bản cũ thành Bản sửa đổi.
 *
 * @param array $p Trang từ sgc_layout_pages().
 * @return bool
 */
function sgc_layout_apply_page( array $p ) {
	if ( ! function_exists( 'sunny_grove_pattern' ) ) {
		return false;
	}
	// Lưu nội dung hiện tại thành Bản sửa đổi (nếu chưa có bản nào giống hệt).
	wp_save_post_revision( $p['id'] );
	$en     = sg_secondary_langs()[0] ?? 'en';
	$render = static function () use ( $p ) {
		return sunny_grove_pattern( $p['pattern'] );
	};
	$vi      = sg_with_lang( sg_default_lang(), $render );
	$en_text = sg_with_lang( $en, $render );
	if ( '' === trim( $vi ) ) {
		return false;
	}
	$done = wp_update_post(
		wp_slash(
			array(
				'ID'           => $p['id'],
				'post_content' => sgc_demo_bilingual_content( $vi, $en_text ),
			)
		),
		true
	);
	if ( is_wp_error( $done ) ) {
		return false;
	}
	sgc_layout_remember( $p['id'] );
	return true;
}

/**
 * Khung "Áp dụng bố cục mới" trên trang Sunny Grove → Nội dung mẫu.
 * Bước 1: chọn trang. Bước 2 (khi đã chọn): xác nhận.
 */
function sgc_layout_box() {
	$pages = sgc_layout_pages();
	if ( ! $pages ) {
		return;
	}
	$picked = isset( $_GET['sgc_layout'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['sgc_layout'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ hiển thị màn hình xác nhận.
	$action = esc_url( admin_url( 'admin-post.php' ) );

	echo '<div class="sgc-demo-box sgc-layout-box" id="sgc-layout"><h2>' . esc_html__( 'Áp dụng bố cục mới cho trang mẫu', 'sunny-grove-core' ) . '</h2>';

	if ( $picked ) {
		// ---------- Bước 2: xác nhận ----------
		$chosen = array_filter(
			$pages,
			static function ( $p ) use ( $picked ) {
				return in_array( $p['id'], $picked, true );
			}
		);
		echo '<p><strong>' . esc_html__( 'Các trang sau sẽ được thay nội dung bằng bố cục mới (cả tiếng Việt và tiếng Anh):', 'sunny-grove-core' ) . '</strong></p><ul class="sgc-layout-list">';
		$warn = false;
		foreach ( $chosen as $p ) {
			$warn = $warn || $p['edited'];
			echo '<li>' . ( $p['edited'] ? '<span class="sgc-layout-warn">⚠ </span>' : '' ) . '<strong>' . esc_html( $p['title'] ) . '</strong> – ' . esc_html( sgc_layout_status_text( $p ) ) . '</li>';
		}
		echo '</ul>';
		if ( $warn ) {
			echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Trang có dấu ⚠ đã được sửa sau khi nhập mẫu. Những chỗ bạn đã sửa sẽ được thay bằng chữ và ảnh mẫu của bố cục mới – hãy ghi lại nội dung cần giữ trước khi tiếp tục.', 'sunny-grove-core' ) . '</p></div>';
		}
		echo '<p>' . esc_html__( 'Nội dung hiện tại được lưu thành một Bản sửa đổi: muốn quay lại, mở trang đó → thanh bên phải → Bản sửa đổi → chọn bản trước → Khôi phục. Tiêu đề, đường dẫn, SEO và ảnh đại diện của trang giữ nguyên.', 'sunny-grove-core' ) . '</p>';
		if ( ! wp_revisions_enabled( get_post( reset( $chosen )['id'] ) ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Website đang tắt Bản sửa đổi (WP_POST_REVISIONS) – nội dung cũ sẽ KHÔNG được lưu lại. Hãy sao lưu database trước.', 'sunny-grove-core' ) . '</p></div>';
		}
		echo '<form method="post" action="' . $action . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape.
		wp_nonce_field( 'sgc_demo' );
		echo '<input type="hidden" name="action" value="sgc_demo"><input type="hidden" name="do" value="apply_layout">';
		foreach ( $chosen as $p ) {
			echo '<input type="hidden" name="sgc_layout[]" value="' . (int) $p['id'] . '">';
		}
		echo '<p><label><input type="checkbox" name="sgc_layout_ok" value="1" required> ' . esc_html__( 'Tôi hiểu nội dung các trang trên sẽ được thay bằng bố cục mới.', 'sunny-grove-core' ) . '</label></p>';
		submit_button( __( 'Áp dụng bố cục mới', 'sunny-grove-core' ), 'primary', 'submit', false );
		echo ' <a class="button" href="' . esc_url( admin_url( 'admin.php?page=sgc-demo#sgc-layout' ) ) . '">' . esc_html__( 'Quay lại', 'sunny-grove-core' ) . '</a>';
		echo '</form></div>';
		return;
	}

	// ---------- Bước 1: chọn trang ----------
	echo '<p>' . esc_html__( 'Nội dung các trang mẫu được lưu lúc nhập nên không tự đổi theo giao diện mới. Chọn trang muốn dựng lại theo bố cục mới – bước sau sẽ hỏi xác nhận, chưa thay đổi gì ở bước này.', 'sunny-grove-core' ) . '</p>';
	echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '#sgc-layout"><input type="hidden" name="page" value="sgc-demo">';
	echo '<table class="widefat striped sgc-layout-table"><thead><tr><td class="check-column"></td><th>' . esc_html__( 'Trang', 'sunny-grove-core' ) . '</th><th>' . esc_html__( 'Tình trạng', 'sunny-grove-core' ) . '</th></tr></thead><tbody>';
	foreach ( $pages as $p ) {
		$current = SGC_LAYOUT_VERSION === $p['version'] && ! $p['edited'];
		printf(
			'<tr><th class="check-column"><input type="checkbox" name="sgc_layout[]" value="%1$d" id="sgc-layout-%1$d"%2$s></th><td><label for="sgc-layout-%1$d"><strong>%3$s</strong></label><br><a href="%4$s" target="_blank">%5$s</a> · <a href="%6$s">%7$s</a></td><td>%8$s%9$s</td></tr>',
			(int) $p['id'],
			$p['edited'] || $current ? '' : ' checked',
			esc_html( $p['title'] ),
			esc_url( get_permalink( $p['id'] ) ),
			esc_html__( 'Xem', 'sunny-grove-core' ),
			esc_url( get_edit_post_link( $p['id'] ) ),
			esc_html__( 'Sửa', 'sunny-grove-core' ),
			$p['edited'] ? '<span class="sgc-layout-warn">⚠ </span>' : '',
			esc_html( $current ? __( 'Đã dùng bố cục mới', 'sunny-grove-core' ) : sgc_layout_status_text( $p ) )
		);
	}
	echo '</tbody></table><p>';
	submit_button( __( 'Tiếp tục – xem lại trước khi áp dụng', 'sunny-grove-core' ), 'secondary', '', false );
	echo '</p></form></div>';
}

/**
 * Xử lý nút "Áp dụng bố cục mới" (gọi từ sgc_demo_action – đã kiểm tra quyền và nonce).
 *
 * @return string[] Nhật ký.
 */
function sgc_layout_apply_from_request() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce đã kiểm tra ở sgc_demo_action().
	if ( empty( $_POST['sgc_layout_ok'] ) ) {
		return array( __( 'Chưa đánh dấu ô xác nhận – không có trang nào bị thay đổi.', 'sunny-grove-core' ) );
	}
	$ids = isset( $_POST['sgc_layout'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['sgc_layout'] ) ) : array();
	// phpcs:enable
	$log = array();
	foreach ( sgc_layout_pages() as $p ) {
		if ( ! in_array( $p['id'], $ids, true ) ) {
			continue;
		}
		$log[] = sgc_layout_apply_page( $p )
			/* translators: %s: tên trang */
			? sprintf( __( 'Đã áp dụng bố cục mới: %s (bản cũ lưu trong Bản sửa đổi).', 'sunny-grove-core' ), $p['title'] )
			/* translators: %s: tên trang */
			: sprintf( __( 'Không áp dụng được cho trang %s (cần bật theme Sunny Grove).', 'sunny-grove-core' ), $p['title'] );
	}
	return $log ? $log : array( __( 'Chưa chọn trang nào.', 'sunny-grove-core' ) );
}
