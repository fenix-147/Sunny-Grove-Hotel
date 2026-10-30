<?php
/**
 * Thông tin liên hệ.
 *
 * @package SunnyGroveCore
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$sgc_items = array();
foreach ( (array) $attributes['fields'] as $sgc_field ) {
	switch ( $sgc_field ) {
		case 'name':
			$sgc_items[] = array( 'class' => 'name', 'text' => sgc_hotel( 'name' ) );
			break;
		case 'about':
			$sgc_items[] = array( 'class' => 'about', 'text' => sgc_hotel( 'footer_about' ) );
			break;
		case 'phone':
			$sgc_items[] = array( 'class' => 'phone', 'icon' => 'phone', 'label' => sgc_label( 'hotline' ), 'text' => sgc_hotel( 'phone' ), 'url' => sgc_hotel_value( 'tel_url' ) );
			break;
		case 'zalo':
			$sgc_items[] = array( 'class' => 'zalo', 'icon' => 'zalo', 'label' => sgc_label( 'zalo' ), 'text' => sgc_hotel( 'zalo' ), 'url' => sgc_hotel_value( 'zalo_url' ), 'external' => true );
			break;
		case 'whatsapp':
			$sgc_items[] = array( 'class' => 'whatsapp', 'icon' => 'whatsapp', 'label' => sgc_label( 'whatsapp' ), 'text' => sgc_hotel( 'whatsapp' ), 'url' => sgc_hotel_value( 'whatsapp_url' ), 'external' => true );
			break;
		case 'email':
			$sgc_items[] = array( 'class' => 'email', 'icon' => 'mail', 'label' => sgc_label( 'email' ), 'text' => sgc_hotel( 'email' ), 'url' => sgc_hotel_value( 'mailto_url' ) );
			break;
		case 'address':
			$sgc_items[] = array( 'class' => 'address', 'icon' => 'pin', 'label' => sgc_label( 'address' ), 'text' => sgc_hotel( 'address' ), 'url' => sgc_hotel( 'map_link' ), 'external' => true );
			break;
		case 'checkin':
			$sgc_items[] = array( 'class' => 'checkin', 'icon' => 'clock', 'text' => sgc_hotel( 'checkin' ) );
			break;
	}
}
$sgc_items  = array_filter(
	$sgc_items,
	static function ( $item ) {
		return '' !== trim( (string) $item['text'] );
	}
);
$sgc_layout = in_array( $attributes['layout'], array( 'list', 'inline', 'buttons' ), true ) ? $attributes['layout'] : 'list';
$sgc_wrap   = get_block_wrapper_attributes( array( 'class' => 'sg-contact sg-contact--' . $sgc_layout ) );
?>
<div <?php echo $sgc_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $sgc_items ) : ?>
		<ul class="sg-contact__list" role="list">
			<?php foreach ( $sgc_items as $sgc_item ) : ?>
				<li class="sg-contact__item sg-contact__item--<?php echo esc_attr( $sgc_item['class'] ); ?>">
					<?php
					$sgc_inner = '';
					if ( ! empty( $sgc_item['icon'] ) ) {
						$sgc_inner .= '<span class="sg-contact__icon">' . sgc_icon( $sgc_item['icon'], 20 ) . '</span>';
					}
					$sgc_inner .= '<span class="sg-contact__text">';
					if ( ! empty( $sgc_item['label'] ) ) {
						$sgc_inner .= '<span class="sg-contact__label">' . esc_html( $sgc_item['label'] ) . '</span> ';
					}
					$sgc_inner .= '<span class="sg-contact__value">' . esc_html( $sgc_item['text'] ) . '</span></span>';
					if ( ! empty( $sgc_item['url'] ) ) {
						printf(
							'<a class="sg-contact__link" href="%1$s"%2$s>%3$s</a>',
							esc_url( $sgc_item['url'] ),
							! empty( $sgc_item['external'] ) ? ' target="_blank" rel="noopener"' : '',
							$sgc_inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						);
					} else {
						echo '<span class="sg-contact__link">' . $sgc_inner . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php
	$sgc_embed = sgc_hotel( 'map_embed' );
	if ( $attributes['showMap'] && $sgc_embed ) :
		?>
		<div class="sg-contact__map">
			<iframe src="<?php echo esc_url( $sgc_embed ); ?>" title="<?php echo esc_attr( sgc_label( 'map_title' ) ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
		</div>
		<?php if ( sgc_hotel( 'map_link' ) ) : ?>
			<p class="sg-contact__maplink"><a href="<?php echo esc_url( sgc_hotel( 'map_link' ) ); ?>" target="_blank" rel="noopener"><?php echo sgc_icon( 'external', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( sgc_label( 'open_in_maps' ) ); ?></a></p>
		<?php endif; ?>
	<?php endif; ?>
</div>
