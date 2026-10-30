<?php
/**
 * Vai trò "Biên tập viên khách sạn": sửa nội dung, phòng, ưu đãi, thông tin khách sạn;
 * không cài plugin, không đổi theme, không sửa Giao diện tổng thể.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

const SGC_ROLES_VERSION = 2;

/**
 * Tạo vai trò và gán quyền.
 */
function sgc_add_roles() {
	$editor = get_role( 'editor' );
	$caps   = $editor ? $editor->capabilities : array( 'read' => true );

	// Không cho phép bất kỳ quyền hệ thống nào.
	foreach ( array( 'edit_theme_options', 'switch_themes', 'install_plugins', 'activate_plugins', 'edit_plugins', 'edit_themes', 'manage_options', 'unfiltered_html', 'update_core', 'list_users', 'promote_users' ) as $cap ) {
		unset( $caps[ $cap ] );
	}
	$caps['manage_sunny_grove'] = true;

	remove_role( 'sg_hotel_editor' );
	add_role( 'sg_hotel_editor', __( 'Biên tập viên khách sạn', 'sunny-grove-core' ), $caps );

	foreach ( array( 'administrator', 'editor' ) as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			$role->add_cap( 'manage_sunny_grove' );
		}
	}
	update_option( 'sgc_roles_version', SGC_ROLES_VERSION );
}

/**
 * Cập nhật vai trò khi plugin được nâng cấp.
 */
function sgc_maybe_update_roles() {
	if ( (int) get_option( 'sgc_roles_version' ) !== SGC_ROLES_VERSION ) {
		sgc_add_roles();
	}
}
add_action( 'admin_init', 'sgc_maybe_update_roles' );
