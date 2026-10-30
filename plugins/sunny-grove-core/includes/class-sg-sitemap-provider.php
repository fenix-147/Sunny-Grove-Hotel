<?php
/**
 * Nhóm sitemap cho một ngôn ngữ phụ: liệt kê đường dẫn tiếng Anh (/en/…) của mọi trang, bài, phòng, tiện ích,
 * địa điểm, ưu đãi. Sitemap tiếng Việt do WordPress tự tạo.
 *
 * @package SunnyGroveCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sitemap đường dẫn của một ngôn ngữ.
 */
class SG_Sitemap_Provider extends WP_Sitemaps_Provider {

	/**
	 * Ngôn ngữ.
	 *
	 * @var string
	 */
	protected $lang;

	/**
	 * Khởi tạo.
	 *
	 * @param string $lang Ngôn ngữ.
	 */
	public function __construct( $lang ) {
		$this->lang        = $lang;
		$this->name        = 'lang' . $lang;
		$this->object_type = 'post';
	}

	/**
	 * Loại nội dung (mỗi loại một trang sitemap con).
	 *
	 * @return array
	 */
	public function get_object_subtypes() {
		$types = array();
		foreach ( sg_i18n_post_types() as $type ) {
			$obj = get_post_type_object( $type );
			if ( $obj && $obj->public ) {
				$types[ $type ] = $obj;
			}
		}
		return $types;
	}

	/**
	 * Danh sách đường dẫn.
	 *
	 * @param int    $page_num Trang.
	 * @param string $subtype  Loại nội dung.
	 * @return array
	 */
	public function get_url_list( $page_num, $subtype = '' ) {
		if ( ! isset( $this->get_object_subtypes()[ $subtype ] ) ) {
			return array();
		}
		$query = new WP_Query(
			array(
				'post_type'      => $subtype,
				'post_status'    => 'publish',
				'posts_per_page' => wp_sitemaps_get_max_urls( $this->object_type ),
				'paged'          => $page_num,
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'has_password'   => false,
			)
		);
		$front = (int) get_option( 'page_on_front' );
		$urls  = array();
		foreach ( $query->posts as $post ) {
			$urls[] = array(
				'loc'     => $front === $post->ID ? sg_home_url( $this->lang ) : sg_permalink( $post, $this->lang ),
				'lastmod' => wp_date( DATE_W3C, strtotime( $post->post_modified_gmt ) ),
			);
		}
		return $urls;
	}

	/**
	 * Số trang sitemap con.
	 *
	 * @param string $subtype Loại nội dung.
	 * @return int
	 */
	public function get_max_num_pages( $subtype = '' ) {
		$count = wp_count_posts( $subtype );
		return (int) ceil( ( isset( $count->publish ) ? (int) $count->publish : 0 ) / wp_sitemaps_get_max_urls( $this->object_type ) );
	}
}
