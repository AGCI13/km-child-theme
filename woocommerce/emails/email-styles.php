<?php

/**
 * Email Styles
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-styles.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 7.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load colors.
$bg        = get_option( 'woocommerce_email_background_color' );
$body      = get_option( 'woocommerce_email_body_background_color' );
$base      = get_option( 'woocommerce_email_base_color' );
$base_text = wc_light_or_dark( $base, '#202020', '#ffffff' );
$text      = get_option( 'woocommerce_email_text_color' );

// Pick a contrasting color for links.
$link_color = wc_hex_is_light( $base ) ? $base : $base_text;

if ( wc_hex_is_light( $body ) ) {
	$link_color = wc_hex_is_light( $base ) ? $base_text : $base;
}

$bg_darker_10    = wc_hex_darker( $bg, 10 );
$body_darker_10  = wc_hex_darker( $body, 10 );
$base_lighter_20 = wc_hex_lighter( $base, 20 );
$base_lighter_40 = wc_hex_lighter( $base, 40 );
$text_lighter_20 = wc_hex_lighter( $text, 20 );
$text_lighter_40 = wc_hex_lighter( $text, 40 );

// !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
// body{padding: 0;} ensures proper scale/positioning of the email in the iOS native email app.
?>
body {
background-color: <?php echo esc_attr( $bg ); ?>;
padding: 0;
text-align: center;
}

#outer_wrapper {
background-color: #B99F6E !important;
background: center / cover no-repeat url('<?php echo get_home_url(); ?>/wp-content/uploads/2021/10/galet-marbre-noir.png'), #B99F6E;
}

#wrapper {
margin: 35px auto;
padding: 30px 30px 0 30px;
-webkit-text-size-adjust: none !important;
width: 100%;
max-width: 600px;
box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1) !important;
background-color: <?php echo esc_attr( $body ); ?>;
border: 1px solid <?php echo esc_attr( $bg_darker_10 ); ?>;
}

#template_header {
color: <?php echo esc_attr( $base_text ); ?>;
font-weight: bold;
line-height: 100%;
vertical-align: middle;
font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
}

#template_header h1,
#template_header h1 a {
color: <?php echo esc_attr( $base_text ); ?>;
background-color: inherit;
text-align:center;
}

.highlighted{
color: #be9e67 !important;
}

#template_header_image{
width: 80%;
margin-bottom:20px;
border-bottom: 1px solid #C6C6C6;
padding: 10px 0 15px;
}

#template_header_image img {
margin-left: 0;
margin-right: 0;
max-width:200px;
}


#template_footer {
margin-top: 40px;
margin-bottom:10px;
border-top:1px solid #c6c6c6;
color: <?php echo esc_attr( $text_lighter_40 ); ?>;
text-align: center;
color: #636363;
font-family: "Helvetica Neue",Helvetica,Roboto,Arial,sans-serif;
font-size: 14px;
line-height: 150%;
text-align: left;
}

#body_content {
background-color: <?php echo esc_attr( $body ); ?>;
}

#body_content td ul.wc-item-meta {
font-size: small;
margin: 1em 0 0;
padding: 0;
list-style: none;
}

#body_content td ul.wc-item-meta li {
margin: 0.5em 0 0;
padding: 0;
}

#body_content td ul.wc-item-meta li p {
margin: 0;
}

#body_content p {
margin: 0 0 16px;
}

#body_content_inner {
color: <?php echo esc_attr( $text_lighter_20 ); ?>;
font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
font-size: 14px;
line-height: 150%;
text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

.td {
color: <?php echo esc_attr( $text_lighter_20 ); ?>;
border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
vertical-align: middle;
}

.address {
padding: 12px;
color: <?php echo esc_attr( $text_lighter_20 ); ?>;
}

.text {
color: <?php echo esc_attr( $text ); ?>;
font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
}

.link {
color: <?php echo esc_attr( $link_color ); ?>;
}

#header_wrapper {
padding: 36px 48px;
display: block;
}

h1 {
color: <?php echo esc_attr( $base ); ?>;
font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
font-size: 24px;
font-weight: 600;
line-height: 150%;
margin: 0 0 40px 0;
text-align: center;
}

h2 {
color: <?php echo esc_attr( $base ); ?>;
display: block;
font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
font-size: 16px;
font-weight: bold;
line-height: 130%;
margin: 30px 0 20px;
text-align: center;
text-transform: uppercase;
}

h3 {
position:relative;
display: inline-block;
color: <?php echo esc_attr( $base ); ?>;
font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;
font-size: 16px;
font-weight: bold;
line-height: 130%;
margin: 16px 0 20px;
text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
z-index: 9;
}

h4{
font-weight: 400;
font-size: 1.2em;
}

h3:before {
content: "";
position: absolute;
left: 0%;
bottom: -2px;
height: 10px;
width: 100%;
background-color: #e8e8e8;
z-index: -1;
}

a {
color: #be9e67;
font-weight: normal;
text-decoration: underline;
}

img {
border: none;
display: inline-block;
font-size: 14px;
font-weight: bold;
height: auto;
outline: none;
text-decoration: none;
text-transform: capitalize;
vertical-align: middle;
max-width: 100%;
}

#km-order-steps{
margin-bottom: 30px!important;
max-width: 94%;
margin: auto;
display: block;
}

.box{
border: 1px solid #C6C6C6;
padding: 30px 10%;
}

.box > :last-child{
margin-bottom:0 !important;
}

p {
margin-bottom: 10px !important;
}

.box p{
line-height: 1.8em;
margin-bottom: 0 !important;
}

table{
	border-collapse: collapse;
}

.order_item td{
border:1px solid #C6C6C6;
}

.order_item img {
border-radius:5px;
}

#km-totals {
padding-top:20px;
border-top:1px solid #C6C6C6;
border-bottom:1px solid #C6C6C6;
}

#km-totals table{
width:100%;
border-collapse: collapse;
}

#km-totals table td, #km-totals table th{
border: none!important;
padding-top:4px;
padding-bottom:4px;
}

#km-totals table td {
text-align:right!important;
}

#km-totals table > tr:last-child th,#km-totals table > tr:last-child td{
font-size: 1.25em;
}

#km-cta{
margin-top: 30px;
margin-bottom:35px;
display: flex;
padding: 7px 24px;
align-items: center;
border-radius: 5px;
background: #be9e67;
box-shadow: 0px 2px 6px 0px rgba(219,163,66,.5);
color: white;
font-size: 1.2em;
font-weight: 600;
width: fit-content;
margin-left: auto;
margin-right: auto;
text-decoration: none;
letter-spacing: 0.015em;
}

#km-social{
width: 100%;
background:#ECECEC;
padding:0;
}

#km-social h2{
margin-top: 25px;
}

#km-social-icons{
width: 100%;
display:flex;
justify-content:center;
align-items:center;
margin-bottom: 25px;
}

#km-social-icons > a{
margin:0 10px;
}

#km-legal-footer{
padding-top:24px;
text-align:center;
font-size:12px;
}

.km-info{
padding-top: 25px;
text-align:center;
font-size: 0.95em;
}

.km-info img{
margin-left:5px;
}

/**
* Media queries are not supported by all email clients, however they do work on modern mobile
* Gmail clients and can help us achieve better consistency there.
*/
@media screen and (max-width: 600px) {
#header_wrapper {
padding: 27px 36px !important;
font-size: 24px;
}

#body_content_inner {
font-size: 10px !important;
}
}

<?php
