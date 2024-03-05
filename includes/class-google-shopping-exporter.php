<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Class KM_Google_Shopping_Exporter
 */
class KM_Google_Shopping_Exporter {

	use SingletonTrait;

	/**
	 * Automatically triggered on plugin activation
	 */
	public function __construct() {
		add_action( 'acf/options_page/save', array( $this, 'handle_generate_csv' ), 10, 2 );
	}

	public function handle_generate_csv( $post_id, $menu_slug ) {

		if ( 'export-google-shopping' !== $menu_slug ) {
			return;
		}

		$csv_data = $this->generate_csv_data( $post_id );

		$csv_files   = $this->generate_csv_files( $csv );
		$csv_archive = $this->zip_csv_files( $csv );
		$csv_files   = $this->download_csv_archive_file( $csv_archive );
	}

	private function generate_csv_data( $post_id ) {
		$flows = get_fields( $post_id );
		$this->generate_main_flow_data( $flows['main_flow'] );
		$this->generate_secondary_flow_data( $flows['secondary_flows'] );
	}

	private function generate_main_flow_data( $main_flow ) {
		if ( ! $main_flow ) {
			return;
		}

		$zone_ids = $main_flow['main_flow_zone_ids'];
	}

	private function generate_secondary_flow_data( $secondary_flows ) {
	}

	public function get_fitlered_products( $excluded_categories, $excluded_products ) {
		$args = array(
			'limit'            => -1,
			'status'           => 'publish',
			'orderby'          => 'title',
			'order'            => 'ASC',
			'category__not_in' => $excluded_categories,
			'exclude'          => $excluded_products,
		);

		$products = wc_get_products( $args );
	}
	
	private function generate_csv_files( $csv ) {
	}

	private function zip_csv_files( $csv ) {
	}

	private function download_csv_archive_file( $csv ) {
	}
}
