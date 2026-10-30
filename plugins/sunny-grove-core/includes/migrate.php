<?php
/**
 * Chuyển dữ liệu từ Polylang (mỗi ngôn ngữ một bài) sang mô hình "1 bài – 2 ngôn ngữ".
 *
 * Đọc thẳng dữ liệu Polylang trong database (taxonomy language / post_translations / term_translations,
 * tùy chọn polylang, chuỗi dịch) nên chạy được cả khi Polylang đã tắt.
 *
 * Ba bước, mỗi bước chạy riêng:
 * 1. Báo cáo (dry-run): không ghi gì.
 * 2. Chuyển đổi: chỉ THÊM dữ liệu tiếng Anh vào bài tiếng Việt; không xóa bài nào.
 * 3. Dọn dẹp (trash-old): đưa bài tiếng Anh cũ vào Thùng rác – chỉ chạy sau khi đã duyệt báo cáo.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loại nội dung được chuyển.
 *
 * @return string[]
 */
function sg_migrate_post_types() {
	return array_merge( array( 'page', 'post' ), array_keys( sgc_data( 'post-types' ) ) );
}

/**
 * Ngôn ngữ của từng bài theo Polylang: post_id => slug.
 *
 * @return array
 */
function sg_migrate_post_langs() {
	global $wpdb;
	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT tr.object_id, t.slug FROM {$wpdb->term_relationships} tr
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		WHERE tt.taxonomy = 'language'"
	);
	$out = array();
	foreach ( $rows as $row ) {
		$out[ (int) $row->object_id ] = $row->slug;
	}
	return $out;
}

/**
 * Nhóm bản dịch Polylang của một taxonomy (post_translations | term_translations): mảng lang => id.
 *
 * @param string $taxonomy Taxonomy.
 * @return array[]
 */
function sg_migrate_groups( $taxonomy ) {
	global $wpdb;
	$rows   = $wpdb->get_col( $wpdb->prepare( "SELECT description FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s", $taxonomy ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$groups = array();
	foreach ( $rows as $desc ) {
		$group = maybe_unserialize( $desc );
		if ( is_array( $group ) ) {
			unset( $group['sync'] );
			$groups[] = array_map( 'intval', $group );
		}
	}
	return $groups;
}

/**
 * Ngôn ngữ của term theo Polylang: term_id => slug.
 *
 * @return array
 */
function sg_migrate_term_langs() {
	global $wpdb;
	$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT tr.object_id, t.slug FROM {$wpdb->term_relationships} tr
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
		WHERE tt.taxonomy = 'term_language'"
	);
	$out = array();
	foreach ( $rows as $row ) {
		$out[ (int) $row->object_id ] = preg_replace( '/^pll_/', '', $row->slug );
	}
	return $out;
}

/**
 * Slug tiếng Anh đề xuất: bỏ hậu tố "-en" (Polylang thêm để tránh trùng); bỏ "-2", "-3"… nếu khi đó trùng slug VI.
 * Trả rỗng nếu giống slug VI (dùng luôn slug VI).
 *
 * @param string $en_name Slug bài tiếng Anh cũ.
 * @param string $vi_name Slug bài tiếng Việt.
 * @return string
 */
function sg_migrate_en_slug( $en_name, $vi_name ) {
	$slug = preg_replace( '/-en$/', '', (string) $en_name );
	if ( preg_replace( '/-\d+$/', '', $slug ) === $vi_name ) {
		$slug = $vi_name;
	}
	return $slug === $vi_name ? '' : $slug;
}

/**
 * Đường dẫn tương đối (không có tên miền), không dấu / cuối.
 *
 * @param string $url URL.
 * @return string
 */
function sg_migrate_path( $url ) {
	return untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
}

/**
 * Lập kế hoạch chuyển đổi (không ghi gì).
 *
 * @return array
 */
function sg_migrate_plan() {
	$langs     = sg_migrate_post_langs();
	$default   = sg_default_lang();
	$secondary = sg_secondary_langs();
	$types     = sg_migrate_post_types();
	$home      = untrailingslashit( get_option( 'home' ) );
	$front     = (int) get_option( 'page_on_front' );
	$plan      = array(
		'posts'    => array(),
		'en_only'  => array(),
		'terms'    => array(),
		'menus'    => array(),
		'strings'  => array(),
		'front'    => array(),
		'warnings' => array(),
	);

	// Trang chủ: nếu trang đang đặt làm trang chủ là bản tiếng Anh → dùng bản tiếng Việt của nó.
	$front_group = array();
	foreach ( sg_migrate_groups( 'post_translations' ) as $group ) {
		if ( in_array( $front, $group, true ) ) {
			$front_group = $group;
		}
	}
	$front_vi      = $front_group[ $default ] ?? $front;
	$plan['front'] = array(
		'current'      => $front,
		'current_lang' => $langs[ $front ] ?? '?',
		'vi'           => $front_vi,
		'merge'        => array_diff_key( $front_group, array( $default => 1 ) ),
	);

	$in_group = array();
	$slugs    = array();
	foreach ( sg_migrate_groups( 'post_translations' ) as $group ) {
		$vi_id = $group[ $default ] ?? 0;
		$vi    = $vi_id ? get_post( $vi_id ) : null;
		foreach ( $group as $id ) {
			$in_group[ $id ] = true;
		}
		if ( ! $vi || ! in_array( $vi->post_type, $types, true ) || 'trash' === $vi->post_status ) {
			continue;
		}
		foreach ( $secondary as $lang ) {
			$en = ! empty( $group[ $lang ] ) ? get_post( $group[ $lang ] ) : null;
			if ( ! $en || 'trash' === $en->post_status ) {
				continue;
			}
			$item = array(
				'type'      => $vi->post_type,
				'vi'        => $vi->ID,
				'en'        => $en->ID,
				'lang'      => $lang,
				'vi_title'  => $vi->post_title,
				'title'     => $en->post_title,
				'excerpt'   => $en->post_excerpt,
				'slug'      => $vi->ID === $front_vi ? '' : sg_migrate_en_slug( $en->post_name, $vi->post_name ),
				'metas'     => array(),
				'content'   => null,
				'old_url'   => '',
				'new_url'   => '',
				'author_en' => (int) $en->post_author,
			);
			foreach ( sg_i18n_text_meta_keys() as $key ) {
				$value = get_post_meta( $en->ID, $key, true );
				if ( '' !== (string) $value ) {
					$item['metas'][ $key . '_' . $lang ] = $value;
				}
			}
			if ( '' !== trim( $vi->post_content ) && '' !== trim( $en->post_content ) ) {
				$item['content'] = sg_i18n_merge_content( $vi->post_content, $en->post_content, $lang );
			} elseif ( '' !== trim( $en->post_content ) ) {
				$plan['warnings'][] = sprintf( 'Bài #%d "%s": bản VI trống nhưng bản EN có nội dung – cần nhập tay.', $vi->ID, $vi->post_title );
			}
			// Slug tiếng Anh không được trùng trong cùng loại nội dung.
			if ( '' !== $item['slug'] ) {
				$key = $vi->post_type . ':' . $item['slug'];
				if ( isset( $slugs[ $key ] ) ) {
					$item['slug']      .= '-' . $vi->ID;
					$plan['warnings'][] = sprintf( 'Slug EN trùng trong "%s" – đổi thành "%s".', $vi->post_type, $item['slug'] );
				}
				$slugs[ $key ] = true;
			}
			$item['old_url']                       = sg_migrate_old_url( $en, $lang );
			$plan['posts'][ $vi->ID . ':' . $lang ] = $item;
		}
	}

	// Đường dẫn EN mới (cần slug EN của trang cha nên tính sau cùng).
	foreach ( $plan['posts'] as $key => $item ) {
		$plan['posts'][ $key ]['new_url'] = sg_migrate_new_url( $item, $plan['posts'], $front_vi, $home );
	}

	// Bài chỉ có bản tiếng Anh (không có bản VI để ghép).
	foreach ( $langs as $id => $lang ) {
		if ( $lang === $default || isset( $in_group[ $id ] ) && ! empty( sg_migrate_partner( $id, $default ) ) ) {
			continue;
		}
		$post = get_post( $id );
		if ( $post && in_array( $post->post_type, $types, true ) && 'trash' !== $post->post_status && 'auto-draft' !== $post->post_status ) {
			$plan['en_only'][] = array(
				'id'    => $id,
				'type'  => $post->post_type,
				'title' => $post->post_title,
				'lang'  => $lang,
			);
		}
	}

	// Danh mục / nhãn.
	$term_langs = sg_migrate_term_langs();
	foreach ( sg_migrate_groups( 'term_translations' ) as $group ) {
		$vi = ! empty( $group[ $default ] ) ? get_term( $group[ $default ] ) : null;
		if ( ! $vi instanceof WP_Term ) {
			continue;
		}
		foreach ( $secondary as $lang ) {
			$en = ! empty( $group[ $lang ] ) ? get_term( $group[ $lang ] ) : null;
			if ( $en instanceof WP_Term ) {
				$plan['terms'][] = array(
					'taxonomy' => $vi->taxonomy,
					'vi'       => $vi->term_id,
					'vi_name'  => $vi->name,
					'en'       => $en->term_id,
					'name'     => $en->name,
					'lang'     => $lang,
				);
			}
		}
	}
	unset( $term_langs );

	// Menu.
	$plan['menus'] = sg_migrate_menu_plan( $plan['posts'] );

	// Chuỗi dịch (tên/mô tả site).
	foreach ( $secondary as $lang ) {
		foreach ( array( 'blogname', 'blogdescription' ) as $option ) {
			$tr = sg_migrate_string( get_option( $option ), $lang );
			if ( '' !== $tr ) {
				$plan['strings'][ $lang ][ $option ] = $tr;
			}
		}
	}
	return $plan;
}

/**
 * Bản dịch của một bài trong nhóm Polylang.
 *
 * @param int    $post_id ID.
 * @param string $lang    Ngôn ngữ.
 * @return int
 */
function sg_migrate_partner( $post_id, $lang ) {
	static $index = null;
	if ( null === $index ) {
		$index = array();
		foreach ( sg_migrate_groups( 'post_translations' ) as $group ) {
			foreach ( $group as $id ) {
				$index[ $id ] = $group;
			}
		}
	}
	return (int) ( $index[ $post_id ][ $lang ] ?? 0 );
}

/**
 * Đường dẫn EN cũ (Polylang): /en/<slug loại VI>/<slug EN>/ hoặc /en/<đường dẫn trang>/.
 *
 * @param WP_Post $en   Bài tiếng Anh.
 * @param string  $lang Ngôn ngữ.
 * @return string
 */
function sg_migrate_old_url( WP_Post $en, $lang ) {
	if ( sg_i18n_legacy_active() ) {
		return (string) get_permalink( $en );
	}
	$home   = untrailingslashit( get_option( 'home' ) );
	$prefix = sg_lang_prefix( $lang );
	if ( isset( sgc_data( 'post-types' )[ $en->post_type ] ) ) {
		return $home . '/' . $prefix . '/' . sgc_type_slug( $en->post_type ) . '/' . $en->post_name . '/';
	}
	return $home . '/' . $prefix . '/' . get_page_uri( $en ) . '/';
}

/**
 * Đường dẫn EN mới dự kiến.
 *
 * @param array  $item     Mục kế hoạch.
 * @param array  $all      Toàn bộ kế hoạch bài.
 * @param int    $front_vi Trang chủ (VI).
 * @param string $home     Địa chỉ site.
 * @return string
 */
function sg_migrate_new_url( array $item, array $all, $front_vi, $home ) {
	$prefix = sg_lang_prefix( $item['lang'] );
	$vi     = get_post( $item['vi'] );
	if ( (int) $item['vi'] === (int) $front_vi ) {
		return $home . '/' . $prefix . '/';
	}
	$slug = '' !== $item['slug'] ? $item['slug'] : $vi->post_name;
	if ( isset( sgc_data( 'post-types' )[ $vi->post_type ] ) ) {
		return $home . '/' . $prefix . '/' . sg_type_base( $vi->post_type, $item['lang'] ) . '/' . $slug . '/';
	}
	$parts = array( $slug );
	foreach ( get_post_ancestors( $vi ) as $ancestor ) {
		$key = $ancestor . ':' . $item['lang'];
		array_unshift( $parts, ! empty( $all[ $key ]['slug'] ) ? $all[ $key ]['slug'] : get_post_field( 'post_name', $ancestor ) );
	}
	return $home . '/' . $prefix . '/' . implode( '/', $parts ) . '/';
}

/**
 * Kế hoạch ghép menu: mục menu VI nhận tên tiếng Anh từ mục tương ứng của menu EN (cùng vị trí).
 *
 * @param array $posts Kế hoạch bài (để đối chiếu bản dịch).
 * @return array
 */
function sg_migrate_menu_plan( array $posts ) {
	$options = (array) get_option( 'polylang', array() );
	$theme   = get_stylesheet();
	$out     = array();
	$default = sg_default_lang();
	foreach ( (array) ( $options['nav_menus'][ $theme ] ?? array() ) as $location => $by_lang ) {
		$vi_menu = (int) ( $by_lang[ $default ] ?? 0 );
		if ( ! $vi_menu ) {
			continue;
		}
		$vi_items = wp_get_nav_menu_items( $vi_menu, array( 'update_post_term_cache' => false ) );
		foreach ( sg_secondary_langs() as $lang ) {
			$en_menu  = (int) ( $by_lang[ $lang ] ?? 0 );
			$en_items = $en_menu ? wp_get_nav_menu_items( $en_menu, array( 'update_post_term_cache' => false ) ) : array();
			$entry    = array(
				'location' => $location,
				'vi_menu'  => $vi_menu,
				'en_menu'  => $en_menu,
				'lang'     => $lang,
				'items'    => array(),
				'problems' => array(),
			);
			foreach ( (array) $vi_items as $i => $vi_item ) {
				$en_item = $en_items[ $i ] ?? null;
				if ( ! $en_item ) {
					$entry['problems'][] = sprintf( 'Mục "%s" không có mục EN tương ứng.', $vi_item->title );
					continue;
				}
				// Kiểm tra đúng cặp: mục EN trỏ tới bản dịch của trang mà mục VI trỏ tới.
				if ( 'post_type' === $vi_item->type && 'post_type' === $en_item->type && sg_migrate_partner( (int) $vi_item->object_id, $lang ) !== (int) $en_item->object_id ) {
					$entry['problems'][] = sprintf( 'Mục "%s" ↔ "%s" không trỏ tới cùng một trang.', $vi_item->title, $en_item->title );
				}
				$entry['items'][] = array(
					'vi_item' => $vi_item->ID,
					'vi'      => $vi_item->title,
					'title'   => $en_item->title,
					'url'     => 'custom' === $en_item->type ? $en_item->url : '',
				);
			}
			if ( count( (array) $en_items ) > count( (array) $vi_items ) ) {
				$entry['problems'][] = sprintf( 'Menu EN có %d mục thừa.', count( $en_items ) - count( $vi_items ) );
			}
			$out[] = $entry;
		}
	}
	return $out;
}

/**
 * Bản dịch một chuỗi trong bảng chuỗi dịch của Polylang.
 *
 * @param string $original Chuỗi gốc.
 * @param string $lang     Ngôn ngữ.
 * @return string
 */
function sg_migrate_string( $original, $lang ) {
	global $wpdb;
	$term_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT t.term_id FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'language' AND t.slug = %s", $lang ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$strings = $term_id ? get_term_meta( $term_id, '_pll_strings_translations', true ) : array();
	foreach ( (array) $strings as $pair ) {
		if ( is_array( $pair ) && isset( $pair[0], $pair[1] ) && $pair[0] === $original ) {
			return (string) $pair[1];
		}
	}
	return '';
}

/**
 * Báo cáo dạng chữ.
 *
 * @param array $plan Kế hoạch.
 * @return string[]
 */
function sg_migrate_report( array $plan ) {
	$out      = array();
	$matched  = 0;
	$vi_only  = 0;
	$en_only  = 0;
	$problems = array();
	foreach ( $plan['posts'] as $item ) {
		if ( $item['content'] ) {
			$matched += $item['content']['matched'];
			$vi_only += count( $item['content']['vi_only'] );
			$en_only += count( $item['content']['en_only'] ) + count( $item['content']['mismatch'] );
			if ( $item['content']['vi_only'] || $item['content']['en_only'] || $item['content']['mismatch'] ) {
				$problems[] = $item;
			}
		}
	}

	$out[] = '=== BÁO CÁO CHUYỂN ĐỔI ĐA NGÔN NGỮ (Polylang → 1 bài – 2 ngôn ngữ) ===';
	$out[] = '';
	$out[] = '1. TRANG CHỦ';
	$out[] = sprintf( '   Đang đặt làm trang chủ: #%d (%s) "%s"', $plan['front']['current'], $plan['front']['current_lang'], get_the_title( $plan['front']['current'] ) );
	$out[] = sprintf( '   Trang chủ sau chuyển đổi: #%d "%s"', $plan['front']['vi'], get_the_title( $plan['front']['vi'] ) );
	foreach ( $plan['front']['merge'] as $lang => $id ) {
		$out[] = sprintf( '   Ghép nội dung %s từ #%d "%s" (tác giả: %s) vào trang chủ; trang #%d sẽ vào Thùng rác ở bước dọn dẹp.', strtoupper( $lang ), $id, get_the_title( $id ), get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) ) ? get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) ) : '—', $id );
	}
	$out[] = '';

	$by_type = array();
	foreach ( $plan['posts'] as $item ) {
		$by_type[ $item['type'] ][] = $item;
	}
	$out[] = sprintf( '2. CẶP BÀI VI–EN SẼ GHÉP: %d', count( $plan['posts'] ) );
	foreach ( $by_type as $type => $items ) {
		$obj   = get_post_type_object( $type );
		$out[] = sprintf( '   %s (%d):', $obj ? $obj->labels->name : $type, count( $items ) );
		foreach ( $items as $item ) {
			$blocks = $item['content'] ? sprintf( '%d block', $item['content']['matched'] ) : 'không có nội dung block';
			$out[]  = sprintf(
				'   - #%d "%s" ← #%d "%s" | slug EN: %s | %s | %d trường chữ',
				$item['vi'],
				$item['vi_title'],
				$item['en'],
				$item['title'],
				'' !== $item['slug'] ? $item['slug'] : '(dùng slug VI)',
				$blocks,
				count( $item['metas'] )
			);
		}
	}
	$out[] = '';
	$out[] = '3. GHÉP BLOCK';
	$out[] = sprintf( '   Đã ghép được: %d block | Block chỉ có ở bản VI (sẽ hiện "Chưa dịch"): %d | Block EN không ghép được (cần xử lý tay): %d', $matched, $vi_only, $en_only );
	foreach ( $problems as $item ) {
		$out[] = sprintf( '   #%d "%s":', $item['vi'], $item['vi_title'] );
		foreach ( $item['content']['vi_only'] as $desc ) {
			$out[] = '     • Chỉ có bản VI: ' . $desc;
		}
		foreach ( $item['content']['en_only'] as $desc ) {
			$out[] = '     • Bản EN không có chỗ ghép: ' . $desc;
		}
		foreach ( $item['content']['mismatch'] as $desc ) {
			$out[] = '     • Khác cấu trúc (bảng khác số ô): ' . $desc;
		}
	}
	$out[] = '';

	$out[] = sprintf( '4. BÀI CHỈ CÓ BẢN TIẾNG ANH (không có bản VI để ghép): %d', count( $plan['en_only'] ) );
	foreach ( $plan['en_only'] as $item ) {
		$out[] = sprintf( '   - #%d %s "%s"', $item['id'], $item['type'], $item['title'] );
	}
	$out[] = '';

	$redirects = array_filter(
		$plan['posts'],
		static function ( $item ) {
			return sg_migrate_path( $item['old_url'] ) !== sg_migrate_path( $item['new_url'] );
		}
	);
	$out[]     = sprintf( '5. CHUYỂN HƯỚNG 301 (đường dẫn EN cũ → mới): %d', count( $redirects ) );
	foreach ( $redirects as $item ) {
		$out[] = sprintf( '   %s  →  %s', sg_migrate_path( $item['old_url'] ) . '/', sg_migrate_path( $item['new_url'] ) . '/' );
	}
	$same  = count( $plan['posts'] ) - count( $redirects );
	$out[] = sprintf( '   (%d đường dẫn EN giữ nguyên, không cần chuyển hướng)', $same );
	$out[] = '';

	$out[] = sprintf( '6. DANH MỤC / NHÃN: %d tên tiếng Anh', count( $plan['terms'] ) );
	foreach ( $plan['terms'] as $term ) {
		$out[] = sprintf( '   - %s: "%s" → EN "%s"', $term['taxonomy'], wp_specialchars_decode( $term['vi_name'], ENT_QUOTES ), wp_specialchars_decode( $term['name'], ENT_QUOTES ) );
	}
	$out[] = '';

	$out[] = '7. MENU';
	foreach ( $plan['menus'] as $menu ) {
		$out[] = sprintf( '   Vị trí "%s": menu VI #%d nhận tên EN từ menu EN #%d (%d mục)', $menu['location'], $menu['vi_menu'], $menu['en_menu'], count( $menu['items'] ) );
		foreach ( $menu['items'] as $mi ) {
			$out[] = sprintf( '   - "%s" → "%s"', wp_specialchars_decode( $mi['vi'], ENT_QUOTES ), wp_specialchars_decode( $mi['title'], ENT_QUOTES ) );
		}
		foreach ( $menu['problems'] as $p ) {
			$out[] = '   ⚠ ' . $p;
		}
	}
	$out[] = '';

	$out[] = '8. CHUỖI DỊCH CỦA SITE';
	foreach ( $plan['strings'] as $lang => $strings ) {
		foreach ( $strings as $option => $value ) {
			$out[] = sprintf( '   %s (%s): "%s"', $option, strtoupper( $lang ), $value );
		}
	}
	if ( ! $plan['strings'] ) {
		$out[] = '   (không có)';
	}
	$out[] = '   Thông tin khách sạn, nhãn hiển thị, chữ form/email: đã lưu theo từng ngôn ngữ – không cần chuyển.';
	$out[] = '';

	$out[] = '9. CẢNH BÁO';
	foreach ( $plan['warnings'] as $w ) {
		$out[] = '   ⚠ ' . $w;
	}
	if ( ! $plan['warnings'] ) {
		$out[] = '   (không có)';
	}
	$out[] = '';
	$out[] = '10. SAU KHI CHUYỂN ĐỔI, Ở BƯỚC DỌN DẸP (chỉ chạy khi được duyệt) SẼ ĐƯA VÀO THÙNG RÁC:';
	$out[] = sprintf( '   %d bài/trang tiếng Anh cũ (không xóa vĩnh viễn).', count( $plan['posts'] ) );
	$out[] = sprintf( '   Menu EN cũ (%d) và danh mục EN cũ (%d) sẽ hỏi riêng trước khi xóa.', count( array_filter( wp_list_pluck( $plan['menus'], 'en_menu' ) ) ), count( $plan['terms'] ) );
	return $out;
}

/**
 * Thực hiện chuyển đổi: chỉ thêm dữ liệu tiếng Anh vào bài tiếng Việt, không xóa gì.
 *
 * @param array $plan Kế hoạch.
 * @return string[] Nhật ký.
 */
function sg_migrate_apply( array $plan ) {
	global $wpdb;
	$log       = array();
	$redirects = (array) get_option( 'sg_i18n_redirects', array() );

	foreach ( $plan['posts'] as $item ) {
		$vi   = (int) $item['vi'];
		$lang = $item['lang'];
		update_post_meta( $vi, sg_en_key( 'title', $lang ), $item['title'] );
		update_post_meta( $vi, sg_en_key( 'title_h', $lang ), sg_i18n_hash( array( get_post_field( 'post_title', $vi ) ) ) );
		if ( '' !== $item['excerpt'] ) {
			update_post_meta( $vi, sg_en_key( 'excerpt', $lang ), $item['excerpt'] );
			update_post_meta( $vi, sg_en_key( 'excerpt_h', $lang ), sg_i18n_hash( array( get_post_field( 'post_excerpt', $vi ) ) ) );
		}
		if ( '' !== $item['slug'] ) {
			update_post_meta( $vi, sg_en_key( 'slug', $lang ), $item['slug'] );
		}
		foreach ( $item['metas'] as $key => $value ) {
			update_post_meta( $vi, $key, $value );
		}
		if ( $item['content'] && $item['content']['content'] !== get_post_field( 'post_content', $vi, 'raw' ) ) {
			// Ghi thẳng nội dung (không qua wp_update_post) để không kích hoạt bộ lọc/hook của plugin khác.
			$wpdb->update( $wpdb->posts, array( 'post_content' => $item['content']['content'] ), array( 'ID' => $vi ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			clean_post_cache( $vi );
		}
		update_post_meta( $vi, '_sg_i18n_migrated_from', (int) $item['en'] );
		update_post_meta( (int) $item['en'], '_sg_i18n_merged_into', $vi );
		$old = sg_migrate_path( $item['old_url'] );
		if ( $old && $old !== sg_migrate_path( $item['new_url'] ) ) {
			$redirects[ $old ] = array( $vi, $lang );
		}
		$log[] = sprintf( 'Đã ghép #%d ← #%d (%s)', $vi, $item['en'], $item['title'] );
	}
	update_option( 'sg_i18n_redirects', $redirects, false );

	foreach ( $plan['terms'] as $term ) {
		update_term_meta( $term['vi'], sg_en_key( 'name', $term['lang'] ), $term['name'] );
	}
	$log[] = sprintf( 'Đã lưu tên tiếng Anh cho %d danh mục/nhãn.', count( $plan['terms'] ) );

	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	foreach ( $plan['menus'] as $menu ) {
		foreach ( $menu['items'] as $mi ) {
			if ( $mi['title'] !== $mi['vi'] ) {
				update_post_meta( $mi['vi_item'], sg_en_key( 'title', $menu['lang'] ), $mi['title'] );
			}
		}
		$locations[ $menu['location'] ] = $menu['vi_menu'];
	}
	set_theme_mod( 'nav_menu_locations', $locations );
	$log[] = sprintf( 'Đã ghép %d menu.', count( $plan['menus'] ) );

	$strings = (array) get_option( 'sg_i18n_strings', array() );
	foreach ( $plan['strings'] as $lang => $values ) {
		$strings[ $lang ] = array_merge( (array) ( $strings[ $lang ] ?? array() ), $values );
	}
	update_option( 'sg_i18n_strings', $strings );

	if ( (int) $plan['front']['vi'] && (int) $plan['front']['vi'] !== (int) get_option( 'page_on_front' ) ) {
		update_option( 'page_on_front', (int) $plan['front']['vi'] );
		$log[] = sprintf( 'Đặt trang chủ = #%d.', $plan['front']['vi'] );
	}
	update_option( 'sg_i18n_merged_ids', array_values( array_map( 'intval', wp_list_pluck( $plan['posts'], 'en' ) ) ), false );
	update_option(
		'sg_i18n_migration',
		array(
			'time'  => time(),
			'posts' => count( $plan['posts'] ),
			'terms' => count( $plan['terms'] ),
		),
		false
	);
	$log[] = 'Hoàn tất. Chưa xóa bài nào – bài tiếng Anh cũ vẫn còn nguyên cho tới bước dọn dẹp.';
	return $log;
}

/**
 * Dọn dẹp: đưa bài tiếng Anh cũ (đã ghép) vào Thùng rác.
 *
 * @return string[] Nhật ký.
 */
function sg_migrate_trash_old() {
	$ids = get_posts(
		array(
			'post_type'        => sg_migrate_post_types(),
			'post_status'      => 'any',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'suppress_filters' => true,
			'meta_key'         => '_sg_i18n_merged_into', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'sg_include_merged' => true,
		)
	);
	$log = array();
	foreach ( $ids as $id ) {
		$log[] = wp_trash_post( $id ) ? sprintf( 'Đã đưa vào Thùng rác #%d "%s"', $id, get_the_title( $id ) ) : sprintf( 'Không đưa được #%d vào Thùng rác', $id );
	}
	return $log;
}

/**
 * Bài tiếng Anh cũ đã ghép (chờ dọn dẹp).
 *
 * @return int[]
 */
function sg_migrate_merged_ids() {
	return array_map( 'intval', (array) get_option( 'sg_i18n_merged_ids', array() ) );
}

/**
 * Sau khi chuyển đổi và trước bước dọn dẹp: ẩn bài tiếng Anh cũ khỏi mọi danh sách (website, quản trị, REST)
 * để không hiện trùng. Không xóa gì – bước dọn dẹp mới đưa chúng vào Thùng rác.
 *
 * @param WP_Query $query Truy vấn.
 */
function sg_migrate_hide_merged( $query ) {
	if ( sg_i18n_legacy_active() || $query->get( 'sg_include_merged' ) || 'trash' === $query->get( 'post_status' ) ) {
		return;
	}
	$ids = sg_migrate_merged_ids();
	if ( $ids ) {
		$query->set( 'post__not_in', array_merge( (array) $query->get( 'post__not_in' ), $ids ) );
	}
}
add_action( 'pre_get_posts', 'sg_migrate_hide_merged' );
