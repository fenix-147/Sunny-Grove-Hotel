<?php
/**
 * Menu theo vị trí; bản EN dùng tên tiếng Anh của từng mục và đường dẫn /en/.
 *
 * @package SunnyGroveCore
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$sgc_location = sanitize_key( $attributes['location'] );
if ( ! has_nav_menu( $sgc_location ) ) {
	return;
}
$sgc_menu = sgc_nav_menu(
	array(
		'theme_location' => $sgc_location,
		'container'      => false,
		'menu_class'     => 'sg-menu__list',
		'depth'          => 1,
		'fallback_cb'    => false,
		'echo'           => false,
	)
);
if ( ! $sgc_menu ) {
	return;
}
$sgc_id     = wp_unique_id( 'sg-menu-' );
$sgc_toggle = $attributes['mobileToggle'];
$sgc_wrap   = get_block_wrapper_attributes(
	array(
		'class'      => 'sg-menu sg-menu--' . $sgc_location . ( $sgc_toggle ? ' has-toggle' : '' ),
		'aria-label' => wp_get_nav_menu_name( $sgc_location ),
	)
);
?>
<nav <?php echo $sgc_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $sgc_toggle ) : ?>
		<button type="button" class="sg-menu__toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $sgc_id ); ?>">
			<span class="sg-menu__icon-open"><?php echo sgc_icon( 'menu', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="sg-menu__icon-close"><?php echo sgc_icon( 'close', 24 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="sg-menu__toggle-text"><?php echo esc_html( sgc_label( 'menu_open' ) ); ?></span>
		</button>
	<?php endif; ?>
	<div class="sg-menu__panel" id="<?php echo esc_attr( $sgc_id ); ?>">
		<?php echo $sgc_menu; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php
		// Cuối menu điện thoại: nút Đặt phòng + hotline (chỉ hiện khi menu mở trên màn hình hẹp).
		if ( $sgc_toggle && ! empty( $attributes['panelExtras'] ) ) :
			$sgc_book  = sgc_hotel_value( 'booking_url' );
			$sgc_phone = sgc_hotel( 'phone' );
			?>
			<div class="sg-menu__extras">
				<?php if ( $sgc_book ) : ?>
					<a class="sg-menu__book wp-element-button" href="<?php echo esc_url( $sgc_book ); ?>"><?php echo esc_html( sgc_label( 'book_now' ) ); ?></a>
				<?php endif; ?>
				<?php if ( $sgc_phone ) : ?>
					<a class="sg-menu__phone" href="<?php echo esc_url( sgc_hotel_value( 'tel_url' ) ); ?>"><?php echo sgc_icon( 'phone', 20 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><span><?php echo esc_html( sgc_label( 'menu_hotline' ) ); ?> <strong><?php echo esc_html( $sgc_phone ); ?></strong></span></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</nav>
