<?php

/**
 * Classe de la méthode d'expédition Incluse
 *
 * @package KingMateriaux
 */
class Shipping_method_out_13 extends WC_Shipping_Method {

	/**
	 *  Constructor.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'out13';
		$this->method_title       = 'Hors 13';
		$this->method_description = 'Livraison hors 13';
		$this->tax_status         = 'taxable';

		$this->instance_id = absint( $instance_id );
		$this->supports    = array(
			'settings',
			'shipping-zones',
		);
		$this->init();
	}


	/**
	 * Initialise les paramètres de la méthode d'expédition.
	 *
	 * @return void
	 */
	public function init() {
		$this->init_form_fields();
		$this->init_settings();

		$this->enabled            = $this->get_option( 'enabled' );
		$this->title              = $this->get_option( 'title' );
		$this->method_description = $this->get_option( 'description' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialise les champs de configuration de la méthode d'expédition.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => __( 'Activer', 'kingmateriaux' ),
				'type'    => 'checkbox',
				'label'   => __( 'Activer cette méthode d\'expédition', 'kingmateriaux' ),
				'default' => 'yes',
			),
			'title'       => array(
				'title'       => 'Nom affiché',
				'type'        => 'text',
				'description' => __( 'Entrez le nom affiché pour cette méthode d\'expédition . ', 'kingmateriaux' ),
				'default'     => $this->method_title,
			),
			'description' => array(
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => __( 'Entrez la description pour cette méthode d\'expédition . ', 'kingmateriaux' ),
				'default'     => 'Description de ' . $this->method_title,
			),
		);
	}

	/**
	 * Calcule les frais d'expédition.
	 *
	 * @param array $package
	 * @return void
	 */
	public function calculate_shipping( $package = array() ): void {

		if ( 'yes' !== $this->get_option( 'enabled', 'yes' ) ) {
			return;
		}

		$this->title = $this->get_option( 'title', $this->method_title );

		$rate = array(
			'id'        => $this->id,
			'label'     => $this->title,
			'cost'      => 0,
			'meta_data' => array(
				'description' => $this->method_description,
			),
		);

		$this->add_rate( $rate );
	}
}
