<?php
/**
 * Bộ lọc nhanh theo nhóm phân loại.
 *
 * @package SunnyGroveCore
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$sgc_tax = sanitize_key( $attributes['taxonomy'] );
if ( ! taxonomy_exists( $sgc_tax ) ) {
	return;
}
$sgc_terms = get_terms(
	array(
		'taxonomy'   => $sgc_tax,
		'hide_empty' => true,
	)
);
if ( ! $sgc_terms || is_wp_error( $sgc_terms ) ) {
	return;
}
$sgc_wrap = get_block_wrapper_attributes(
	array(
		'class'         => 'sg-filter',
		'data-taxonomy' => $sgc_tax,
	)
);
?>
<div <?php echo $sgc_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="sg-filter__chips" role="group">
		<button type="button" class="sg-filter__chip is-active" aria-pressed="true" data-term=""><?php echo esc_html( sgc_label( 'filter_all' ) ); ?></button>
		<?php foreach ( $sgc_terms as $sgc_term ) : ?>
			<button type="button" class="sg-filter__chip" aria-pressed="false" data-term="<?php echo esc_attr( $sgc_term->slug ); ?>"><?php echo esc_html( $sgc_term->name ); ?></button>
		<?php endforeach; ?>
	</div>
	<p class="sg-filter__status screen-reader-text" aria-live="polite"></p>
</div>
