<?php
/**
 * Lệnh WP-CLI: wp sunny-grove demo import | remove
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Quản lý nội dung mẫu Sunny Grove.
 */
class SGC_CLI_Demo {

	/**
	 * Nhập nội dung mẫu song ngữ.
	 *
	 * ## EXAMPLES
	 *
	 *     wp sunny-grove demo import
	 */
	public function import() {
		sgc_demo_import();
		WP_CLI::success( 'Sunny Grove demo imported.' );
	}

	/**
	 * Xóa nội dung mẫu.
	 */
	public function remove() {
		$count = sgc_demo_remove();
		WP_CLI::success( sprintf( 'Removed %d demo items.', $count ) );
	}
}

WP_CLI::add_command( 'sunny-grove demo', 'SGC_CLI_Demo' );

/**
 * Chuyển dữ liệu đa ngôn ngữ từ Polylang sang mô hình "1 bài – 2 ngôn ngữ".
 *
 * ## OPTIONS
 *
 * [--dry-run]
 * : Chỉ in báo cáo, không ghi gì.
 *
 * [--trash-old]
 * : Đưa các bài tiếng Anh cũ (đã ghép) vào Thùng rác. Chỉ chạy sau khi đã duyệt báo cáo.
 *
 * [--yes]
 * : Không hỏi xác nhận.
 *
 * ## EXAMPLES
 *
 *     wp sunny-grove migrate-i18n --dry-run
 *     wp sunny-grove migrate-i18n
 *     wp sunny-grove migrate-i18n --trash-old
 *
 * @param array $args       Tham số.
 * @param array $assoc_args Tùy chọn.
 */
function sgc_cli_migrate_i18n( $args, $assoc_args ) {
	if ( ! empty( $assoc_args['trash-old'] ) ) {
		WP_CLI::confirm( 'Đưa toàn bộ bài tiếng Anh cũ đã ghép vào Thùng rác?', $assoc_args );
		foreach ( sg_migrate_trash_old() as $line ) {
			WP_CLI::log( $line );
		}
		WP_CLI::success( 'Đã dọn dẹp.' );
		return;
	}
	$plan = sg_migrate_plan();
	foreach ( sg_migrate_report( $plan ) as $line ) {
		WP_CLI::log( $line );
	}
	if ( ! empty( $assoc_args['dry-run'] ) ) {
		WP_CLI::success( 'Dry-run: chưa ghi gì.' );
		return;
	}
	WP_CLI::confirm( 'Ghi dữ liệu tiếng Anh vào bài tiếng Việt theo báo cáo trên? (không xóa bài nào)', $assoc_args );
	foreach ( sg_migrate_apply( $plan ) as $line ) {
		WP_CLI::log( $line );
	}
	WP_CLI::success( 'Đã chuyển đổi.' );
}
WP_CLI::add_command( 'sunny-grove migrate-i18n', 'sgc_cli_migrate_i18n' );
