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
	private $folder_name = 'google-shopping-export';

	/**
	 * Automatically triggered on plugin activation
	 */
	public function __construct() {
		add_action( 'acf/options_page/save', array( $this, 'handle_generate_csv' ), 10, 2 );
	}

	public function handle_generate_csv( $post_id, $menu_slug ) {

		if ( 'export-google-shopping' !== $menu_slug || ! $post_id ) {
			return;
		}

		$fields = get_fields( $post_id );

		foreach ( $fields['primary_flows'] as $primary_flow ) {
			$flows_data[ $primary_flow['zone_id'] ] = $this->generate_flow_data( 'primary_flow', $primary_flow );
		}

		foreach ( $fields['secondary_flows'] as $secondary_flow ) {
			$flows_data[ $secondary_flow['zone_id'] ] = $this->generate_flow_data( 'secondary_flow', $secondary_flow );
		}

		foreach ( $flows_data as $zone_id => $zone_id_data ) {
			$csv_files[] = $this->generate_csv_files( $zone_id, $zone_id_data );
		}

		$csv_archive = $this->zip_csv_files( $csv_files );
	}

	private function generate_flow_data( $flow_type, $fields ) {

		if ( ! $fields || ! is_array( $fields ) || empty( $fields ) ) {
			return array();
		}

		$product_ids   = $this->get_filtered_product_ids( $fields['excluded_categories'], $fields['excluded_products'] );
		$products_data = array();

		foreach ( $product_ids as $product_id ) {

			if ( ! km_is_purchasable_in_zone( $product_id, (int) $fields['zone_id'] ) ) {
				continue;
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$products_to_process = array( $product );
			if ( $product->is_type( 'variable' ) ) {
				$products_to_process = array_merge( $products_to_process, $product->get_children() );
			}

			foreach ( $products_to_process as $proc_product_id ) {
				$proc_product = wc_get_product( $proc_product_id );
				if ( ! $proc_product ) {
					continue;
				}

				if ( 'primary_flow' === $flow_type ) {
					$products_data[] = $this->generate_primary_flow_data( $proc_product );
				} elseif ( 'secondary_flow' === $flow_type ) {
					$products_data[] = $this->generate_secondary_flow_data( $proc_product );
				}
			}
		}

		return $products_data;
	}

	private function generate_primary_flow_data( $product ) {
		return array(
			'id'             => $product->get_sku(),
			'titre'          => $product->get_name(),
			'description'    => $product->get_description(),
			'lien'           => $product->get_permalink(),
			'lien_image'     => wp_get_attachment_url( $product->get_image_id() ),
			'disponibilité'  => $product->is_in_stock() ? 'En stock' : 'En rupture de stock',
			'prix'           => $product->get_price(),
			'prix_soldé'     => $product->get_sale_price(),
			'marque'         => 'Kingmatériaux',
			'livraison'      => 'FR:::0.00 EUR',
			'poids du colis' => $product->get_weight(),
		);
	}

	private function generate_secondary_flow_data( $product ) {
		return array(
			'id'         => $product->get_sku(),
			'region_id'  => $product->get_name(),
			'prix'       => $product->get_price(),
			'prix_soldé' => $product->get_sale_price(),
			'livraison'  => 'FR:::0.00 EUR',
		);
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

	private function generate_csv_files( $zone_id, $data ) {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return array();
		}

		$upload_dir = wp_upload_dir();
		$base_dir   = trailingslashit( $upload_dir['basedir'] ) . $this->folder_name . '/';

		if ( ! file_exists( $base_dir ) ) {
			mkdir( $base_dir, 0755, true );
		}

		$csv_files = array();
			$file_path = $base_dir . "export-{$zone_id}.csv";
			$handle    = fopen( $file_path, 'w' );
			fputcsv( $handle, array_keys( reset( $data ) ) );
			foreach ( $data as $row ) {
				fputcsv( $handle, $row );
			}
			fclose( $handle );
			$csv_files[] = $file_path;
	

		return $csv_files;
	}

	private function zip_csv_files( $csv_files ) {
		$upload_dir        = wp_upload_dir();
		$current_date_time = gmdate( 'Y-m-d_H-i-s' );
		$zip_file_path     = trailingslashit( $upload_dir['basedir'] ) . $this->folder_name . '/' . $current_date_time . '-export.zip';

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


	private function download_csv_archive_file( $zip_file_path ) {
		if ( file_exists( $zip_file_path ) ) {
			// Force le navigateur à télécharger le fichier ZIP
			header( 'Content-Type: application/zip' );
			header( 'Content-Disposition: attachment; filename="' . basename( $zip_file_path ) . '"' );
			header( 'Content-Length: ' . filesize( $zip_file_path ) );
			flush(); // Flush system output buffer
			readfile( $zip_file_path );
			// Après le téléchargement, vous pourriez vouloir supprimer le fichier pour nettoyer
			unlink( $zip_file_path );
			exit;
		} else {
			// Gérer le cas où le fichier n'existe pas
			wp_die( 'Une erreur est survenue lors de la tentative de téléchargement du fichier.' );
		}
	}
}
