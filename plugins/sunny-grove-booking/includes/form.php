<?php
/**
 * Block "Form đặt phòng" và phần hiển thị form.
 *
 * @package SunnyGroveBooking
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loại nội dung chứa danh sách phòng (mặc định: Phòng của Sunny Grove Core).
 *
 * @return string
 */
function sgb_room_post_type() {
	return (string) apply_filters( 'sgb_room_post_type', 'sg_room' );
}

/**
 * Danh sách phòng theo ngôn ngữ đang xem.
 *
 * @return WP_Post[]
 */
function sgb_rooms() {
	$type = sgb_room_post_type();
	if ( ! post_type_exists( $type ) ) {
		return array();
	}
	$args = array(
		'post_type'      => $type,
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'orderby'        => array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
		),
		'no_found_rows'  => true,
	);
	return get_posts( $args );
}

/**
 * Đăng ký block và tài nguyên.
 */
function sgb_register_block() {
	wp_register_style( 'sgb-form', SGB_URL . 'assets/form.css', array(), SGB_VERSION );
	wp_register_script(
		'sgb-form',
		SGB_URL . 'assets/form.js',
		array(),
		SGB_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_register_script( 'sgb-editor', SGB_URL . 'assets/editor.js', array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ), SGB_VERSION, true );
	register_block_type( SGB_DIR . 'blocks/booking-form' );
	add_shortcode( 'sunny_grove_booking', 'sgb_shortcode' );
}
add_action( 'init', 'sgb_register_block' );

/**
 * Shortcode [sunny_grove_booking variant="compact"] – dùng được ở nơi không hỗ trợ block.
 *
 * @param array $atts Thuộc tính.
 * @return string
 */
function sgb_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'variant' => 'full',
			'room'    => 0,
		),
		$atts
	);
	return sgb_render_form(
		array(
			'variant'     => $atts['variant'],
			'defaultRoom' => (int) $atts['room'],
		)
	);
}

/**
 * Dữ liệu cho JavaScript (theo ngôn ngữ đang xem).
 *
 * @param string $lang Ngôn ngữ.
 */
function sgb_localize( $lang ) {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done    = true;
	$contact = sgb_contact_info( $lang );
	wp_localize_script(
		'sgb-form',
		'sgbForm',
		array(
			'rest'       => esc_url_raw( rest_url( 'sunny-grove-booking/v1/' ) ),
			'maxNights'  => (int) sgb_general( 'max_nights' ),
			'texts'      => array(
				'nights'     => sgb_text( 'nights', $lang ),
				'submit'     => sgb_text( 'submit', $lang ),
				'submitting' => sgb_text( 'submitting', $lang ),
				'required'   => sgb_text( 'err_required', $lang ),
				'dates'      => sgb_text( 'err_dates', $lang ),
				'past'       => sgb_text( 'err_past', $lang ),
				'email'      => sgb_text( 'err_email', $lang ),
				'phone'      => sgb_text( 'err_phone', $lang ),
				'generic'    => sgb_text( 'err_generic', $lang ),
				'network'    => sgb_text( 'err_network', $lang, array( 'hotline' => $contact['hotline'] ) ),
			),
		)
	);
}

/**
 * Ô chọn số.
 *
 * @param string $name  Tên.
 * @param string $id    ID.
 * @param int    $min   Nhỏ nhất.
 * @param int    $max   Lớn nhất.
 * @param int    $value Giá trị.
 * @return string
 */
function sgb_number_select( $name, $id, $min, $max, $value ) {
	$out = sprintf( '<select name="%1$s" id="%2$s" class="sgb-input">', esc_attr( $name ), esc_attr( $id ) );
	for ( $i = $min; $i <= $max; $i++ ) {
		$out .= sprintf( '<option value="%1$d"%2$s>%1$d</option>', $i, selected( $value, $i, false ) );
	}
	return $out . '</select>';
}

/**
 * Hiển thị form.
 *
 * @param array $attributes Thuộc tính block.
 * @return string
 */
function sgb_render_form( $attributes ) {
	$variant  = ( $attributes['variant'] ?? 'full' ) === 'compact' ? 'compact' : 'full';
	$lang     = sgb_current_lang();
	$uid      = wp_unique_id( 'sgb-' );
	$t        = static function ( $key, $vars = array() ) use ( $lang ) {
		return sgb_text( $key, $lang, $vars );
	};
	$max_a    = max( 1, (int) sgb_general( 'max_adults' ) );
	$max_c    = max( 0, (int) sgb_general( 'max_children' ) );
	$today    = wp_date( 'Y-m-d' );
	$tomorrow = wp_date( 'Y-m-d', time() + DAY_IN_SECONDS );

	wp_enqueue_style( 'sgb-form' );
	wp_enqueue_script( 'sgb-form' );
	sgb_localize( $lang );

	$star = ' <span class="sgb-req" aria-hidden="true">*</span>';

	ob_start();

	if ( 'compact' === $variant ) {
		$action = sgb_booking_page_url( $lang );
		?>
		<form class="sgb-form sgb-form--compact" method="get" action="<?php echo esc_url( $action ); ?>" data-sgb="compact">
			<div class="sgb-compact">
				<div class="sgb-field">
					<label for="<?php echo esc_attr( $uid ); ?>-in"><?php echo esc_html( $t( 'checkin' ) ); ?></label>
					<input class="sgb-input" type="date" id="<?php echo esc_attr( $uid ); ?>-in" name="checkin" min="<?php echo esc_attr( $today ); ?>" required data-sgb-checkin>
				</div>
				<div class="sgb-field">
					<label for="<?php echo esc_attr( $uid ); ?>-out"><?php echo esc_html( $t( 'checkout' ) ); ?></label>
					<input class="sgb-input" type="date" id="<?php echo esc_attr( $uid ); ?>-out" name="checkout" min="<?php echo esc_attr( $tomorrow ); ?>" required data-sgb-checkout>
				</div>
				<div class="sgb-field sgb-field--num">
					<label for="<?php echo esc_attr( $uid ); ?>-a"><?php echo esc_html( $t( 'adults' ) ); ?></label>
					<?php echo sgb_number_select( 'adults', $uid . '-a', 1, $max_a, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="sgb-field sgb-field--num">
					<label for="<?php echo esc_attr( $uid ); ?>-c"><?php echo esc_html( $t( 'children' ) ); ?></label>
					<?php echo sgb_number_select( 'children', $uid . '-c', 0, $max_c, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="sgb-field sgb-field--submit">
					<button type="submit" class="sgb-button wp-element-button"><?php echo esc_html( $t( 'compact_submit' ) ); ?></button>
				</div>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	$rooms   = sgb_rooms();
	$default = (int) ( $attributes['defaultRoom'] ?? 0 );
	$contact = sgb_contact_info( $lang );
	$status  = isset( $_GET['sgb'] ) ? sanitize_key( $_GET['sgb'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$req_em  = '1' === sgb_general( 'require_email' );
	?>
	<div class="sgb-wrap" id="sgb-form">
		<?php if ( 'ok' === $status ) : ?>
			<div class="sgb-success" role="status" tabindex="-1">
				<p class="sgb-success__title"><?php echo esc_html( $t( 'success_title', array( 'ten_khach' => '' ) ) ); ?></p>
			</div>
		<?php endif; ?>
		<form class="sgb-form sgb-form--full" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-sgb="full" novalidate>
			<input type="hidden" name="action" value="sgb_submit">
			<input type="hidden" name="sgb_nonce" value="<?php echo esc_attr( wp_create_nonce( 'sgb_submit' ) ); ?>">
			<input type="hidden" name="sgb_lang" value="<?php echo esc_attr( $lang ); ?>">
			<input type="hidden" name="sgb_ts" value="<?php echo esc_attr( (string) time() ); ?>">
			<input type="hidden" name="sgb_source" value="<?php echo esc_url( get_permalink() ? get_permalink() : home_url( '/' ) ); ?>">
			<div class="sgb-hp" aria-hidden="true">
				<label for="<?php echo esc_attr( $uid ); ?>-web">Website</label>
				<input type="text" id="<?php echo esc_attr( $uid ); ?>-web" name="sgb_website" tabindex="-1" autocomplete="off">
			</div>

			<?php if ( 'error' === $status ) : ?>
				<p class="sgb-alert" role="alert"><?php echo esc_html( $t( 'err_spam', array( 'hotline' => $contact['hotline'] ) ) ); ?></p>
			<?php endif; ?>
			<p class="sgb-alert" role="alert" data-sgb-alert hidden></p>

			<div class="sgb-grid">
				<div class="sgb-field">
					<label for="<?php echo esc_attr( $uid ); ?>-in"><?php echo esc_html( $t( 'checkin' ) ) . $star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input class="sgb-input" type="date" id="<?php echo esc_attr( $uid ); ?>-in" name="checkin" min="<?php echo esc_attr( $today ); ?>" required aria-required="true" data-sgb-checkin aria-describedby="<?php echo esc_attr( $uid ); ?>-in-err">
					<p class="sgb-error" id="<?php echo esc_attr( $uid ); ?>-in-err" data-error-for="checkin"></p>
				</div>
				<div class="sgb-field">
					<label for="<?php echo esc_attr( $uid ); ?>-out"><?php echo esc_html( $t( 'checkout' ) ) . $star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input class="sgb-input" type="date" id="<?php echo esc_attr( $uid ); ?>-out" name="checkout" min="<?php echo esc_attr( $tomorrow ); ?>" required aria-required="true" data-sgb-checkout aria-describedby="<?php echo esc_attr( $uid ); ?>-out-err <?php echo esc_attr( $uid ); ?>-nights">
					<p class="sgb-hint" id="<?php echo esc_attr( $uid ); ?>-nights" data-sgb-nights aria-live="polite"></p>
					<p class="sgb-error" id="<?php echo esc_attr( $uid ); ?>-out-err" data-error-for="checkout"></p>
				</div>

				<div class="sgb-field sgb-field--wide">
					<label for="<?php echo esc_attr( $uid ); ?>-room"><?php echo esc_html( $t( 'room' ) ); ?></label>
					<?php if ( $rooms ) : ?>
						<select class="sgb-input" id="<?php echo esc_attr( $uid ); ?>-room" name="room" aria-describedby="<?php echo esc_attr( $uid ); ?>-room-err">
							<option value="0"><?php echo esc_html( $t( 'room_any' ) ); ?></option>
							<?php foreach ( $rooms as $room ) : ?>
								<option value="<?php echo (int) $room->ID; ?>"<?php selected( $default, $room->ID ); ?>><?php echo esc_html( get_the_title( $room ) ); ?></option>
							<?php endforeach; ?>
						</select>
					<?php else : ?>
						<input class="sgb-input" type="text" id="<?php echo esc_attr( $uid ); ?>-room" name="room_text" maxlength="120">
					<?php endif; ?>
					<p class="sgb-error" id="<?php echo esc_attr( $uid ); ?>-room-err" data-error-for="room"></p>
				</div>

				<div class="sgb-field">
					<label for="<?php echo esc_attr( $uid ); ?>-a"><?php echo esc_html( $t( 'adults' ) ) . $star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<?php echo sgb_number_select( 'adults', $uid . '-a', 1, $max_a, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<p class="sgb-error" data-error-for="adults"></p>
				</div>
				<div class="sgb-field">
					<label for="<?php echo esc_attr( $uid ); ?>-c"><?php echo esc_html( $t( 'children' ) ); ?></label>
					<?php echo sgb_number_select( 'children', $uid . '-c', 0, $max_c, 0 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="sgb-field sgb-field--wide">
					<label for="<?php echo esc_attr( $uid ); ?>-name"><?php echo esc_html( $t( 'name' ) ) . $star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input class="sgb-input" type="text" id="<?php echo esc_attr( $uid ); ?>-name" name="name" autocomplete="name" maxlength="100" required aria-required="true" aria-describedby="<?php echo esc_attr( $uid ); ?>-name-err">
					<p class="sgb-error" id="<?php echo esc_attr( $uid ); ?>-name-err" data-error-for="name"></p>
				</div>
				<div class="sgb-field">
					<label for="<?php echo esc_attr( $uid ); ?>-phone"><?php echo esc_html( $t( 'phone' ) ) . $star; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input class="sgb-input" type="tel" id="<?php echo esc_attr( $uid ); ?>-phone" name="phone" autocomplete="tel" inputmode="tel" maxlength="25" required aria-required="true" aria-describedby="<?php echo esc_attr( $uid ); ?>-phone-err">
					<p class="sgb-error" id="<?php echo esc_attr( $uid ); ?>-phone-err" data-error-for="phone"></p>
				</div>
				<div class="sgb-field">
					<label for="<?php echo esc_attr( $uid ); ?>-email"><?php echo esc_html( $t( 'email' ) ) . ( $req_em ? $star : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
					<input class="sgb-input" type="email" id="<?php echo esc_attr( $uid ); ?>-email" name="email" autocomplete="email" maxlength="120"<?php echo $req_em ? ' required aria-required="true"' : ''; ?> aria-describedby="<?php echo esc_attr( $uid ); ?>-email-err">
					<p class="sgb-error" id="<?php echo esc_attr( $uid ); ?>-email-err" data-error-for="email"></p>
				</div>

				<fieldset class="sgb-field sgb-field--wide sgb-methods">
					<legend><?php echo esc_html( $t( 'contact_method' ) ); ?></legend>
					<?php foreach ( array( 'phone', 'zalo', 'whatsapp' ) as $i => $method ) : ?>
						<label class="sgb-choice">
							<input type="radio" name="contact_method" value="<?php echo esc_attr( $method ); ?>"<?php checked( 0 === $i ); ?>>
							<span><?php echo esc_html( $t( 'method_' . $method ) ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<div class="sgb-field sgb-field--wide">
					<label for="<?php echo esc_attr( $uid ); ?>-cid"><?php echo esc_html( $t( 'contact_id' ) ); ?></label>
					<input class="sgb-input" type="text" id="<?php echo esc_attr( $uid ); ?>-cid" name="contact_id" maxlength="40" inputmode="tel">
				</div>

				<div class="sgb-field sgb-field--wide">
					<label for="<?php echo esc_attr( $uid ); ?>-note"><?php echo esc_html( $t( 'note' ) ); ?></label>
					<textarea class="sgb-input" id="<?php echo esc_attr( $uid ); ?>-note" name="note" rows="3" maxlength="1000"></textarea>
				</div>
			</div>

			<p class="sgb-hint"><?php echo esc_html( $t( 'required_hint' ) ); ?></p>
			<p class="sgb-privacy"><?php echo esc_html( $t( 'privacy' ) ); ?></p>
			<button type="submit" class="sgb-button wp-element-button" data-sgb-submit><?php echo esc_html( $t( 'submit' ) ); ?></button>
		</form>
		<div class="sgb-success" data-sgb-success role="status" tabindex="-1" hidden></div>
	</div>
	<?php
	return (string) ob_get_clean();
}
