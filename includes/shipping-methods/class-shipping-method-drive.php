<?php

/**
 * Classe de la méthode d'expédition Drive pickup
 */
class Shipping_method_drive extends WC_Shipping_Method {

	/**
	 * Instance unique de la classe.
	 *
	 * @var KM_Shipping_Methods
	 */
	private $km_shipping_methods;

	public $method_location;
	public $cost;

	/**
	 *  Constructor.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'drive';
		$this->method_title       = 'Retrait au King Drive';
		$this->method_description = 'Drive';
		$this->tax_status         = 'taxable';
		$this->instance_id        = absint( $instance_id );
		$this->supports           = array(
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
		$this->method_location    = $this->get_option( 'location' );
		$this->cost               = $this->get_option( 'cost' );

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
			'cost'        => array(
				'title'       => __( 'Cost', 'woocommerce' ),
				'type'        => 'text',
				'placeholder' => '0',
				'description' => __( 'Optional cost for local pickup.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'location'    => array(
				'title'   => __( 'Adresse de retrait', 'woocommerce' ),
				'type'    => 'textarea',
				'default' => '',
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
			'id'      => $this->id,
			'label'   => $this->title,
			'package' => $package,
			'cost'    => $this->cost,
		);

		$this->add_rate( $rate );
	}
}
