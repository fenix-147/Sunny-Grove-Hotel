<?php
/**
 * Title: Tất cả ưu đãi
 * Slug: sunny-grove/offer-grid
 * Categories: sunny-grove
 * Keywords: ưu đãi, combo, khuyến mãi
 * Description: Toàn bộ ưu đãi còn hạn, tự ẩn khi hết hạn.
 *
 * @package SunnyGrove
 */

echo sunny_grove_section_open(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_open( 'sg_offer', 30, array( 'type' => 'default' ), 'sg-grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_offer_card(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_query_close( 'offers_empty' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo sunny_grove_section_close(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
