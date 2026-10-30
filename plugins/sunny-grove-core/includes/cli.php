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
