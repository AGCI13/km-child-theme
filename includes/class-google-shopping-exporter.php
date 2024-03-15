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
	 * The folder name to store the CSV files
	 *
	 * @var string
	 */
	private $upload_folder_name = 'google-shopping-export';
	private $upload_dir;
	private $upload_url;
	private $tmp_dir_path;
	private $current_date_time;

	/**
	 * Automatically triggered on plugin activation
	 */
	public function __construct() {

		if ( ! is_admin() ) {
			return;
		}
		add_action( 'acf/input/admin_head', array( $this, 'register_acf_side_metabox' ), 10 );
		add_action( 'acf/options_page/save', array( $this, 'handle_generate_csv' ), 10, 2 );
		add_action( 'wp_ajax_clear_csv_files', array( $this, 'clear_csv_files' ) );

		$upload_dir         = wp_upload_dir();
		$this->upload_dir   = trailingslashit( $upload_dir['basedir'] ) . $this->upload_folder_name;
		$this->upload_url   = trailingslashit( $upload_dir['baseurl'] ) . $this->upload_folder_name;
		$this->tmp_dir_path = $this->upload_dir . '/tmp/';
	}

	public function handle_generate_csv( $post_id, $menu_slug ) {

		if ( 'export-google-shopping' !== $menu_slug || ! $post_id ) {
			return;
		}

		$fields     = get_fields( $post_id );
		$flows_data = array();

		// Processus pour les flux principaux.
		if ( isset( $fields['primary_flows'] ) && ! empty( $fields['primary_flows'] ) ) {
			foreach ( $fields['primary_flows'] as $primary_flow ) {
				$shipping_zone_name                = $this->sanitize_shipping_zone_name( $primary_flow['zone_id'] );
				$flows_data[ $shipping_zone_name ] = $this->generate_flow_data( 'primary_flow', $primary_flow, $secondary_flow['zone_id'] );
			}
		}

		// Processus pour les flux secondaires.
		if ( isset( $fields['secondary_flows'] ) && ! empty( $fields['secondary_flows'] ) ) {
			foreach ( $fields['secondary_flows'] as $secondary_flow ) {
				$shipping_zone_name = $this->sanitize_shipping_zone_name( $secondary_flow['zone_id'] );
				if ( ! $shipping_zone_name ) {
					continue;
				}
				$flows_data[ $shipping_zone_name ] = $this->generate_flow_data( 'secondary_flow', $secondary_flow, $secondary_flow['zone_id'], $shipping_zone_name );
			}
		}

		$csv_files = array();
		foreach ( $flows_data as $zone_name => $zone_id_data ) {
			$csv_files[] = $this->generate_csv_files( $zone_name, $zone_id_data );
		}

		if ( ! empty( $csv_files ) ) {

			$csv_archive = $this->zip_csv_files( $csv_files );

			if ( ! empty( $csv_archive ) ) {
				$this->clean_tmp_directory();
			}
		}
	}

	private function sanitize_shipping_zone_name( $zone_id ) {
		$shipping_zone_name = km_get_shipping_zone_name( $zone_id );
		if ( ! $shipping_zone_name ) {
			return false;
		}
		return strtoupper( str_replace( array( ' ', '-', '_' ), '', sanitize_title( $shipping_zone_name ) ) );
	}

	private function generate_flow_data( $flow_type, $fields, $shipping_zone_id, $shipping_zone_name = '' ) {

		if ( ! $fields || ! is_array( $fields ) || empty( $fields ) ) {
			return array();
		}

		$product_ids   = $this->get_filtered_product_ids( $fields['excluded_categories'], $fields['excluded_products'] );
		$products_data = array();

		foreach ( $product_ids as $product_id ) {

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$products_to_process = array( $product );

			if ( $product->is_type( 'variable' ) ) {
				$products_to_process = array_merge( $products_to_process, $product->get_children() );
			}

			foreach ( $products_to_process as $proc_product_id ) {

				$shipping_product_price = km_get_shipping_product_price( $proc_product_id, (int) $fields['zone_id'] );

				if ( ! km_is_shipping_zone_in_thirteen() && ! $shipping_product_price ) {
					continue;
				}

				$proc_product = wc_get_product( $proc_product_id );

				if ( ! $proc_product || $proc_product->is_type( 'variable' ) ) {
					continue;
				}

				if ( 'primary_flow' === $flow_type ) {
					$products_data[] = $this->generate_primary_flow_data( $proc_product, $shipping_zone_id, $shipping_product_price );
				} elseif ( 'secondary_flow' === $flow_type ) {
					$products_data[] = $this->generate_secondary_flow_data( $proc_product, $shipping_zone_id, $shipping_zone_name, $shipping_product_price );
				}
			}
		}

		return $products_data;
	}

	private function generate_primary_flow_data( $product, $shipping_zone_id, $shipping_product_price ) {

		$product_price = $shipping_product_price ? $product->get_price() + $shipping_product_price : $product->get_price();
		$product_price = (string) $product_price . ' EUR';

		return array(
			'titre'          => $product->get_name(),
			'id'             => $product->get_sku(),
			'prix'           => $product_price,
			'prix_soldé'     => $this->get_calculated_price( (float) $product->get_sale_price(), $product, $shipping_zone_id ),
			'état'           => 'neuf',
			'disponibilité'  => $product->is_in_stock() ? 'En stock' : 'En rupture de stock',
			'description'    => $product->get_description(),
			'lien'           => $product->get_permalink(),
			'lien_image'     => wp_get_attachment_url( $product->get_image_id() ),
			'marque'         => 'Kingmatériaux',
			'livraison'      => 'FR:::0.00 EUR',
			'poids du colis' => $product->get_weight(),
		);
	}

	private function generate_secondary_flow_data( $product, $shipping_zone_id, $shipping_zone_name, $shipping_product_price ) {

		$product_price = $shipping_product_price ? $product->get_price() + $shipping_product_price : $product->get_price();
		$product_price = (string) $product_price . ' EUR';

		return array(
			'id'         => $product->get_sku(),
			'region_id'  => $shipping_zone_name,
			'prix'       => $product_price,
			'prix_soldé' => $this->get_calculated_price( (float) $product->get_sale_price(), $product, $shipping_zone_id ),
			'livraison'  => $shipping_product_price ? $shipping_product_price : 'FR:::0.00 EUR',
		);
	}

	private function get_calculated_price( $product_price, $product, $zone_id ) {
		return km_change_product_price_based_on_shipping_zone( $product_price, $product, $zone_id );
	}

	public function get_filtered_product_ids( $excluded_categories, $excluded_products ) {
		$args = array(
			'limit'            => -1,
			'return'           => 'ids',
			'status'           => 'publish',
			'orderby'          => 'title',
			'order'            => 'ASC',
			'category__not_in' => $excluded_categories ? $excluded_categories : array(),
			'exclude'          => $excluded_products ? $excluded_products : array(),
		);

		return wc_get_products( $args );
	}

	private function generate_csv_files( $zone_name, $data ) {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return array();
		}

		if ( ! $this->current_date_time ) {
			$this->current_date_time = current_time( 'Ymd-H\hi-s' );
		}

		if ( ! file_exists( $this->tmp_dir_path ) ) {
			mkdir( $this->tmp_dir_path, 0755, true );
		}

		$file_path = $this->tmp_dir_path . "export-produits-{$zone_name}-{$this->current_date_time}.csv";
		$handle    = fopen( $file_path, 'w' );
		fputcsv( $handle, array_keys( reset( $data ) ) );

		foreach ( $data as $row ) {
			fputcsv( $handle, $row );
		}

		fclose( $handle );

		return $file_path;
	}

	private function zip_csv_files( $csv_files ) {
		if ( ! $this->current_date_time ) {
			$this->current_date_time = current_time( 'Ymd-H\hi-s' );
		}

		$zip_file_path = $this->upload_dir . '/google-shopping-' . $this->current_date_time . '.zip';

		$zip = new ZipArchive();
		if ( $zip->open( $zip_file_path, ZipArchive::CREATE ) === true ) {
			foreach ( $csv_files as $file ) {
				if ( is_string( $file ) && file_exists( $file ) ) {
					$zip->addFile( $file, basename( $file ) );
				}
			}
			$zip->close();
			return $zip_file_path;
		}

		return false;
	}

	private function clean_tmp_directory() {
		if ( is_dir( $this->tmp_dir_path ) ) {
			$files = glob( $this->tmp_dir_path . '*', GLOB_MARK );
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				}
			}
		}
	}

	public function register_acf_side_metabox() {

		if ( ! acf_is_screen( 'product_page_export-google-shopping' ) ) {
			return;
		}
		add_meta_box( 'google-shopping-export-metabox', __( 'Liste des exports', 'kingmateriaux' ), array( $this, 'display_acf_side_metabox' ), 'acf_options_page', 'side' );
	}

	public function display_acf_side_metabox() {
		$csv_files = glob( $this->upload_dir . '/*.zip' );

		wp_enqueue_script( 'google-shopping-script' );

		$html = '<p>Cliquez sur Mise à jour pour lancer une nouvelle exportation.</p><ul id="google-shopping-export-list">';

		if ( empty( $csv_files ) ) {
			echo $html . '<p>Aucun fichier n\'a été généré.</p>';
			return;
		}

		foreach ( $csv_files as $csv_file ) {
			$file_url = $this->upload_url . '/' . basename( $csv_file );
			$html    .= "<li><a href='" . esc_url( $file_url ) . "' download='" . basename( $csv_file ) . "'>" . basename( $csv_file ) . '</a></li>';
		}

		$html .= '</ul>';

		if ( ! empty( $csv_files ) ) {
			$html .= '<div class="actions" style="text-align:right"><span class="spinner" style="float:none;"></span><button class="button button-secondary button-large" id="clear-csv-files">Vider la liste des exports</button></div>';
		}

		echo $html;
	}

	public function clear_csv_files() {
		$csv_files = glob( $this->upload_dir . '/*.zip' );
		foreach ( $csv_files as $csv_file ) {
			$deleted[] = unlink( $csv_file );
		}
		if ( ! empty( $deleted ) && ! in_array( false, $deleted, true ) ) {
			wp_send_json_success( 'Les fichiers ont été supprimés.' );
		} else {
			wp_send_json_error( 'Une erreur est survenue lors de la suppression des fichiers.' );
		}
	}
}
