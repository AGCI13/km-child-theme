<?php

class Shipping_method_1_express extends WC_Shipping_Method {

	/**
	 * Instance unique de la classe.
	 *
	 * @var KM_Shipping_Methods
	 */
	private $km_shipping_methods;

	/**
	 *  Constructor.
	 *   */
	public function __construct( $instance_id = 0 ) {
		$this->id           = 'option1express';
		$this->method_title = 'Option 1 Express';
		$this->tax_status   = 'taxable';
		$this->instance_id  = absint( $instance_id );
		$this->supports     = array(
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
			'enabled'          => array(
				'title'   => __( 'Activer', 'kingmateriaux' ),
				'type'    => 'checkbox',
				'label'   => __( 'Activer cette méthode d\'expédition', 'kingmateriaux' ),
				'default' => 'yes',
			),
			'title'            => array(
				'title'       => 'Nom affiché',
				'type'        => 'text',
				'description' => __( 'Entrez le nom affiché pour cette méthode d\'expédition . ', 'kingmateriaux' ),
				'default'     => $this->method_title,
			),
			'description_8'    => array(
				'title' => 'Description de 0 à 2 tonnes',
				'type'  => 'textarea',
			),
			'description_7'    => array(
				'title' => 'Description de 2 à 8 tonnes',
				'type'  => 'textarea',
			),
			'description_6'    => array(
				'title' => 'Description de 8 à 15 tonnes',
				'type'  => 'textarea',
			),
			'description_5'    => array(
				'title' => 'Description de 15 à 30 tonnes',
				'type'  => 'textarea',
			),
			'description_4'    => array(
				'title' => 'Description de 30 à 32 tonnes',
				'type'  => 'textarea',
			),
			'description_3'    => array(
				'title' => 'Description de 32 à 38 tonnes',
				'type'  => 'textarea',
			),
			'description_2'    => array(
				'title' => 'Description de 38 à 45 tonnes',
				'type'  => 'textarea',
			),
			'description_1'    => array(
				'title' => 'Description de 45 à 60 tonnes',
				'type'  => 'textarea',
			),
			'access_condition' => array(
				'title'       => 'Condition d\'accès au chantier',
				'type'        => 'textarea',
				'description' => __( 'Entrez la condition d\'accès. Laissez vide pour ne pas afficher.', 'kingmateriaux' ),
				'default'     => 'Accessible aux poids lourds.',
			),
			'unload_condition' => array(
				'title'       => 'Condition de déchargement',
				'type'        => 'textarea',
				'description' => __( 'Entrez la condition de déchargement. Laissez vide pour ne pas afficher.', 'kingmateriaux' ),
				'default'     => 'Ouverture et/ou portail de minimum de 3m de large, pas d\'angle droit/pente, ni câbles téléphoniques à moins de 3m.',
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

		$shipping_info = KM_Shipping_Methods::get_instance()->calculate_shipping_method_price( $this->id, $this->method_title );

		if ( ! $shipping_info || 0 === $shipping_info['cost'] ) {
			return;
		}

		$description_key          = isset( $shipping_info['weight_class'] ) && ! empty( $shipping_info['weight_class'] ) ? 'description_' . $shipping_info['weight_class'] : 'description_1';
		$this->method_description = $this->get_option( $description_key );

		$this->title = $this->get_option( 'title', $this->method_title );

		$rate = array(
			'id'        => $this->id,
			'label'     => $this->title,
			'cost'      => $shipping_info['cost'],
			'meta_data' => array(
				'description' => $this->method_description,
			),
		);

		$this->add_rate( $rate );
	}
}
