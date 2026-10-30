<?php
/**
 * Nút chuyển ngôn ngữ: link tới cùng trang ở ngôn ngữ kia.
 *
 * @package SunnyGroveCore
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'sg_languages' ) || count( sg_languages() ) < 2 ) {
	return;
}
$sgc_langs = array();
foreach ( sg_languages() as $sgc_slug => $sgc_info ) {
	$sgc_langs[] = array(
		'slug'         => $sgc_slug,
		'name'         => $sgc_info['name'],
		'url'          => sg_translation_url( $sgc_slug ),
		'current_lang' => sg_current_lang() === $sgc_slug,
	);
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
