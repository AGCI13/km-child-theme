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
	public function __construct() {
		parent::__construct();
		$this->km_shipping_methods = KM_Shipping_Methods::get_instance();

		$this->id                 = 'drive';
		$this->method_title       = 'Retrait au King Drive';
		$this->method_description = 'Drive';
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
		$this->method_location    = $this->get_option( 'location' );
		$this->cost               = $this->get_option( 'cost' );

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
		if ( ! $this->km_shipping_methods->km_shipping_zone->is_in_thirteen() ) {
			return;
		}

		if ( 'yes' !== $this->get_option( 'enabled', 'yes' ) ) {
			return;
		}

		$this->title = $this->get_option( 'title', $this->method_title );

		$rate = array(
			'id'    => $this->id,
			'label' => $this->title,
			'cost'  => $this->cost,
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

		$days = array();
		for ( $i = 0; $i < 20; $i++ ) {
			$day = date_i18n( 'l d F', strtotime( '+' . $i . ' days' ) );

			if ( str_contains( $day, 'dimanche' ) ) {
				continue;
			}
			$days[] = $day;
		}
		?>
	<div class="drive-datetimepicker">
		<div class="drive-datepicker-day">	
			<h3><?php esc_html_e( 'Sélectionnez une date', 'kingmateriaux' ); ?></h3>
			<ul class="day-list">
			<?php foreach ( $days as $i => $day ) : ?>
				<li class="day">
					<?php echo esc_html( $day ); ?>
				</li>
			<?php endforeach; ?>
			<li class="day load-more-days">
				<?php esc_html_e( '+ Plus de jours', 'kingmateriaux' ); ?>
			</li>
			</ul>
		</div>

		<h3><?php esc_html_e( 'Sélectionnez un créneau horaire', 'kingmateriaux' ); ?></h3>
		<div class="drive-datepicker-time">
		<!-- Morning Slots -->
			<div class="time-slot morning">
			<h4>Matin</h4>
			<div class="slots">
				<div class="slot" data-time="07h00">07h00</div>
				<div class="slot" data-time="07h30">07h30</div>
				<div class="slot" data-time="08h00">08h00</div>
				<div class="slot" data-time="08h30">08h30</div>
				<div class="slot" data-time="09h00">09h00</div>
				<div class="slot" data-time="09h30">09h30</div>
				<div class="slot" data-time="10h00">10h00</div>
				<div class="slot" data-time="10h30">10h30</div>
				<div class="slot" data-time="11h00">11h00</div>
				<div class="slot" data-time="11h30">11h30</div>
			</div>
			</div>
			<!-- Afternoon Slots -->
			<div class="time-slot afternoon">
			<h4>Après-midi</h4>
			<div class="slots">
				<div class="slot" data-time="13h00">13h00</div>
				<div class="slot" data-time="13h30">13h30</div>
				<div class="slot" data-time="14h00">14h00</div>
				<div class="slot" data-time="14h30">14h30</div>
				<div class="slot" data-time="15h00">15h00</div>
				<div class="slot" data-time="15h30">15h30</div>
				<div class="slot" data-time="16h00">16h00</div>
				<div class="slot" data-time="16h30">16h30</div>
				<div class="slot" data-time="17h00">17h00</div>
				<div class="slot" data-time="17h30">17h30</div>
			</div>
			</div>

		<!-- Evening Slot -->
		<div class="time-slot evening">
				<h4>Soir</h4>
				<div class="slots">
					<div class="slot">18h00</div>
				</div>
			</div>
		</div>
		
		<?php if ( $this->method_location ) : ?>
		<div class="drive-location-adress">
			<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/location-pin.svg' ); ?>" alt="King Drive pin">
			<?php echo wp_kses_post( wpautop( $this->method_location ) ); ?>
		</div>
		<?php endif; ?>

		<input type="hidden" name="drive_date" class="drive_date" value="">
		<input type="hidden" name="drive_time" class="drive_time" value="">
	</div>


		<?php
	}
}
