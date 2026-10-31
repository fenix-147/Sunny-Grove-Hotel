<?php
/**
 * Phía khách: nút chat nổi, tự tạo ảnh WebP, tải trước ảnh banner đầu trang.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nạp CSS nút chat khi được bật.
 */
function sgc_chat_assets() {
	if ( '1' === sgc_hotel( 'chat_enabled' ) ) {
		wp_enqueue_style( 'sgc-chat', SGC_URL . 'assets/chat.css', array(), SGC_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'sgc_chat_assets' );

/**
 * Nút chat nổi Zalo / WhatsApp / Gọi.
 */
function sgc_render_chat() {
	if ( '1' !== sgc_hotel( 'chat_enabled' ) ) {
		return;
	}
	// Mỗi nút bật/tắt riêng; nút Gọi chỉ hiện trên điện thoại. Không tải script bên ngoài.
	$buttons = array();
	if ( sgc_hotel( 'zalo' ) && '0' !== sgc_hotel( 'chat_zalo' ) ) {
		$buttons[] = array( 'zalo', sgc_hotel_value( 'zalo_url' ), sgc_label( 'zalo' ), true );
	}
	if ( sgc_hotel( 'whatsapp' ) && '0' !== sgc_hotel( 'chat_whatsapp' ) ) {
		$buttons[] = array( 'whatsapp', sgc_hotel_value( 'whatsapp_url' ), sgc_label( 'whatsapp' ), true );
	}
	if ( sgc_hotel( 'phone' ) && '0' !== sgc_hotel( 'chat_phone' ) ) {
		$buttons[] = array( 'phone', sgc_hotel_value( 'tel_url' ), sgc_label( 'call' ), false );
	}
	if ( ! $buttons ) {
		return;
	}
	$side = 'left' === sgc_hotel( 'chat_side' ) ? 'left' : 'right';
	echo '<div class="sg-chat sg-chat--' . esc_attr( $side ) . '" role="complementary" aria-label="' . esc_attr( sgc_label( 'chat_toggle' ) ) . '"><ul class="sg-chat__list">';
	foreach ( $buttons as $b ) {
		printf(
			'<li><a class="sg-chat__btn sg-chat__btn--%1$s" href="%2$s"%3$s>%4$s<span class="sg-chat__label">%5$s</span></a></li>',
			esc_attr( $b[0] ),
			esc_url( $b[1] ),
			$b[3] ? ' target="_blank" rel="noopener"' : '',
			sgc_icon( $b[0], 24 ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $b[2] )
		);
	}
	echo '</ul></div>';
}
add_action( 'wp_footer', 'sgc_render_chat' );

/**
 * Tạo bản WebP cho các kích thước ảnh khi tải ảnh JPEG/PNG lên (chạy được trên mọi hosting có GD/Imagick hỗ trợ WebP).
 *
 * @param array $formats Ánh xạ định dạng.
 * @return array
 */
function sgc_webp_output( $formats ) {
	if ( '1' !== sgc_hotel( 'webp' ) || ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return $formats;
	}
	$formats['image/jpeg'] = 'image/webp';
	$formats['image/png']  = 'image/webp';
	return $formats;
}
add_filter( 'image_editor_output_format', 'sgc_webp_output' );

/**
 * Tải trước ảnh nền của banner đầu trang (ảnh lớn nhất ở màn hình đầu tiên) để trang hiện nhanh hơn.
 * Chỉ áp dụng khi banner là block đầu tiên có ảnh trong nội dung trang đang xem.
 */
function sgc_preload_hero() {
	if ( ! is_singular() ) {
		return;
	}
	$blocks = parse_blocks( (string) get_post_field( 'post_content', get_queried_object_id() ) );
	$stack  = $blocks;
	$poster = 0;
	$guard  = 0;
	while ( $stack && $guard++ < 40 ) {
		$b = array_shift( $stack );
		if ( 'sunny-grove/video-banner' === $b['blockName'] ) {
			$poster = (int) ( $b['attrs']['posterId'] ?? 0 );
			break;
		}
		if ( in_array( $b['blockName'], array( 'core/image', 'core/cover', 'sunny-grove/motion-media', 'sunny-grove/media-gallery' ), true ) ) {
			return; // Ảnh khác đứng trước banner: không đoán, để trình duyệt tự xử lý.
		}
		if ( ! empty( $b['innerBlocks'] ) ) {
			$stack = array_merge( $b['innerBlocks'], $stack );
		}
	}
	if ( ! $poster || ! wp_attachment_is_image( $poster ) ) {
		return;
	}
	$src = wp_get_attachment_image_src( $poster, 'full' );
	if ( ! $src ) {
		return;
	}
	$srcset = wp_get_attachment_image_srcset( $poster, 'full' );
	printf(
		'<link rel="preload" as="image" href="%1$s"%2$s fetchpriority="high">' . "\n",
		esc_url( $src[0] ),
		$srcset ? ' imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="100vw"' : ''
	);
}
add_action( 'wp_head', 'sgc_preload_hero', 1 );
