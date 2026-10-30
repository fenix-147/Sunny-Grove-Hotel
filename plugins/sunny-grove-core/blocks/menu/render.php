<?php
/**
 * Menu theo vị trí, tương thích Polylang (mỗi ngôn ngữ một menu).
 *
 * @package SunnyGroveCore
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$sgc_location = sanitize_key( $attributes['location'] );
if ( ! has_nav_menu( $sgc_location ) ) {
	return;
}
$sgc_menu = wp_nav_menu(
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
	</div>
</nav>
