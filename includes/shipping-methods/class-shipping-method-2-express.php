<?php

class Shipping_method_2_express extends WC_Shipping_Method {

	/**
	 * Instance unique de la classe.
	 *
	 * @var KM_Shipping_Methods
	 */
	private $km_shipping_methods;

	/**
	 *  Constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->km_shipping_methods = KM_Shipping_Methods::get_instance();

		$this->id                 = 'option2express';
		$this->method_title       = 'Option 2 Express';
		$this->method_description = 'Livraison option 2 Express';
		$this->tax_status         = 'taxable';
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
		add_action( 'woocommerce_after_shipping_rate', array( $this, 'display_shipping_method_description' ), 10, 2 );
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
		if ( ! $this->km_shipping_methods->km_shipping_zone->is_in_thirteen() ) {
			return;
		}

		if ( 'yes' !== $this->get_option( 'enabled', 'yes' ) ) {
			return;
		}

		$shipping_cost = $this->km_shipping_methods->calculate_shipping_method_price( $this->method_title );
		if ( ! $shipping_cost || 0 === $shipping_cost ) {
			return;
		}

		$this->title = $this->get_option( 'title', $this->method_title );

		$rate = array(
			'id'    => $this->id,
			'label' => $this->title,
			'cost'  => $shipping_cost,
		);

		$this->add_rate( $rate );
	}

	/**
	 * Affiche la description de la méthode d'expédition.
	 *
	 * @param WC_Shipping_Rate $method
	 * @param int              $index
	 * @return void
	 */
	public function display_shipping_method_description( $method, $index ) {
		if ( $method->method_id !== $this->id || ! $this->method_description ) {
			return;
		}

		echo '<div class="shipping-method-description shipping-method-' . esc_html( $this->id ) . '-description">' . esc_html( $this->method_description ) . '</div>';
	}
}
