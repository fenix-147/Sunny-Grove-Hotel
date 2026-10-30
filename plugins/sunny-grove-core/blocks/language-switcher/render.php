<?php
/**
 * Nút chuyển ngôn ngữ (Polylang).
 *
 * @package SunnyGroveCore
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'pll_the_languages' ) ) {
	return;
}
$sgc_langs = pll_the_languages(
	array(
		'raw'                    => 1,
		'hide_if_no_translation' => 0,
		'hide_if_empty'          => 0,
	)
);
if ( ! $sgc_langs || count( $sgc_langs ) < 2 ) {
	return;
}
$sgc_wrap = get_block_wrapper_attributes(
	array(
		'class'      => 'sg-lang',
		'aria-label' => sgc_label( 'language' ),
	)
);
?>
<nav <?php echo $sgc_wrap; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<ul class="sg-lang__list" role="list">
		<?php foreach ( $sgc_langs as $sgc_lang ) : ?>
			<li class="sg-lang__item">
				<a class="sg-lang__link<?php echo $sgc_lang['current_lang'] ? ' is-current' : ''; ?>"
					href="<?php echo esc_url( $sgc_lang['url'] ); ?>"
					hreflang="<?php echo esc_attr( $sgc_lang['slug'] ); ?>"
					lang="<?php echo esc_attr( $sgc_lang['slug'] ); ?>"
					<?php echo $sgc_lang['current_lang'] ? 'aria-current="true"' : ''; ?>
					title="<?php echo esc_attr( $sgc_lang['name'] ); ?>">
					<?php echo esc_html( 'name' === $attributes['display'] ? $sgc_lang['name'] : strtoupper( $sgc_lang['slug'] ) ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
