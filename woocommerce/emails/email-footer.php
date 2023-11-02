<?php

/**
 * Email Footer
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-footer.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 7.4.0
 */

defined('ABSPATH') || exit;

$this_theme_uri = get_stylesheet_directory_uri();
?>

<!-- Footer -->
<?php printf(__('À bientôt,<br> l’équipe %s !', 'woocommerce'), '<a href="' . get_home_url() . '">King Matériaux</a>'); ?></p>
<table border="0" cellpadding="10" cellspacing="0" width="100%" id="template_footer">
	<tr>
		<td valign="top" class="km-info">
			<table border="0" cellpadding="10" cellspacing="0" width="100%">
				<tr>
					<td colspan="2" valign="middle">
						<h4><?php echo __('Notre showroom', 'woocommerce'); ?><img src="<?php echo $this_theme_uri . '/assets/img/icon-crown.png'; ?>"></h4>
						<table>
							<tr>
								<td><a href="https://www.google.com/maps/@43.5160293,5.2320563,3a,75y,135.88h,83.35t/data=!3m6!1e1!3m4!1sUPY3jOLPqU7mK26NGa7PYA!2e0!7i16384!8i8192?entry=ttu"><?php echo __('13B rue André Marie Ampère,', 'woocommerce'); ?></a></td>
							</tr>
							<tr>
								<td><a href="https://www.google.com/maps/@43.5160293,5.2320563,3a,75y,135.88h,83.35t/data=!3m6!1e1!3m4!1sUPY3jOLPqU7mK26NGa7PYA!2e0!7i16384!8i8192?entry=ttu"><?php echo __('13880 Velaux', 'woocommerce'); ?></a></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<td valign="top" class="km-info">
			<table border="0" cellpadding="10" cellspacing="0" width="100%">
				<tr>
					<td colspan="2" valign="middle" style="border-right:1px solid #c6c6c6;border-left:1px solid #c6c6c6;">
						<h4><?php echo __('Besoin d\'aide ?', 'woocommerce'); ?></h4>
						<table>
							<tr>
								<td><b><?php echo __('Service transport :', 'woocommerce'); ?></b></td>
							</tr>
							<tr>
								<td><a href="tel:0033442025399"><?php echo __('(+33) 04 42 02 53 99', 'woocommerce'); ?></a></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
		<td valign="top" class="km-info">
			<table border="0" cellpadding="10" cellspacing="0" width="100%">
				<tr>
					<td colspan="2" valign="middle">
						<h4><?php echo __('Notre dépot', 'woocommerce'); ?></h4>
						<table>
							<tr>
								<td><a href="https://www.google.fr/maps/place/KING+MAT%C3%89RIAUX/@43.503956,5.2173679,16z/data=!4m6!3m5!1s0x12c9e5594c6f8699:0xbbdd5911626d562d!8m2!3d43.503956!4d5.2217453!15sChhzaG93cm9vbSBraW5nIG1hdMOpcmlhdXhaGiIYc2hvd3Jvb20ga2luZyBtYXTDqXJpYXV4kgEIaGFuZHltYW4?shorturl=1"><?php echo __('CD20 Les Barjaquets,', 'woocommerce'); ?></a></td>
							</tr>
							<tr>
								<td><a href="https://www.google.fr/maps/place/KING+MAT%C3%89RIAUX/@43.503956,5.2173679,16z/data=!4m6!3m5!1s0x12c9e5594c6f8699:0xbbdd5911626d562d!8m2!3d43.503956!4d5.2217453!15sChhzaG93cm9vbSBraW5nIG1hdMOpcmlhdXhaGiIYc2hvd3Jvb20ga2luZyBtYXTDqXJpYXV4kgEIaGFuZHltYW4?shorturl=1"><?php echo __('13340 Rognac', 'woocommerce'); ?></a></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td id="km-social" colspan="3">
			<h2><?php echo __('Suivez-nous sur :', 'woocommerce'); ?></h2>
			<div id="km-social-icons">
				<a href="https://www.facebook.com/KingMateriaux/"><img width="" src="<?php echo $this_theme_uri . '/assets/img/icon-facebook.png'; ?>" alt="facebook" /></a>
				<a href="https://www.instagram.com/kingmateriaux/"><img src="<?php echo $this_theme_uri . '/assets/img/icon-instagram.png'; ?>" alt="instagram" /></a>
				<a href="https://www.youtube.com/channel/UCh0NmWSBCvDVvE5xG5z6kmg"><img src="<?php echo $this_theme_uri . '/assets/img/icon-youtube.png'; ?>" alt="youtube" /></a>
			</div>
		</td>
	</tr>
	<tr>
		<td id="km-legal-footer" colspan="3">
			<p><?php echo __('King Matériaux - Rte départementale 20 à Rognac (13340)', 'woocommerce'); ?><br>
				<a href="<?php echo get_home_url(); ?>/conditions-generales-de-vente/">Conditions Générales de Vente</a>
			</p>

			<p>Si vous avez besoin d’aide, veuillez contacter le <strong>service transport</strong><br>
				au <a href="tel:0033442025399"><?php echo __('(+33) 04 42 02 53 99', 'woocommerce'); ?></a> ou par e-mail : <a href="mailto:support@kingmateriaux.com"><?php echo __('support@kingmateriaux.com', 'woocommerce'); ?></a></p>
		</td>
	</tr>
</table>
<!-- End Footer -->
</div>
</td>
</tr>
</table>
<!-- End Content -->
</td>
</tr>
</table>
<!-- End Body -->
</td>
</tr>
</table>
</td>
</tr>
</table>
</div>
</td>
<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
</tr>
</table>
</body>

</html>