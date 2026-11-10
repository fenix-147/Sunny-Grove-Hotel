<?php
/**
 * Title: Hero trang chủ + thanh đặt phòng
 * Slug: sunny-grove/hero-home
 * Categories: sunny-grove, featured
 * Keywords: hero, video, banner, slogan, tìm phòng, đặt phòng
 * Description: Ảnh/video tràn màn hình, chữ ở góc trái dưới (slogan lấy từ Thông tin khách sạn – vế sau dấu " – " tự in nghiêng) và thanh đặt phòng trắng nổi đè lên mép dưới.
 *
 * @package SunnyGrove
 */

echo sunny_grove_banner( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	array(
		'media'        => 'hero',
		'video'        => 'video:hero',
		'video_mobile' => 'video:hero-m',
		'height'       => 'full',
		'overlay'      => 38,
		'position'     => 'bottom',
		'align'        => 'left',
		'class'        => 'sg-hero',
		'eyebrow'      => __( 'Khu nghỉ dưỡng gia đình · Dương Đông, Phú Quốc', 'sunny-grove' ),
		'title_bind'   => array( 'hotel', 'slogan' ),
		'text'         => __( 'Vườn nhiệt đới mát lành, hồ bơi cho bé và biển xanh chỉ vài bước chân.', 'sunny-grove' ),
	)
);
?>

<!-- wp:group {"align":"wide","className":"sg-booking-bar","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide sg-booking-bar"><!-- wp:sunny-grove-booking/booking-form {"variant":"compact"} /--></div>
<!-- /wp:group -->
