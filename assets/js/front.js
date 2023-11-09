let span = document.getElementsByClassName('uael-product-actions');
span.innerHTML += 'Voir le produit >';

(function ($, root, undefined) {
	$(function () {
		'use strict';
		$(document).ready(function($) {
			if($('.home').length){
	
			}
			/* catégorie cliquable en entier */
			$('.clickable-column').on('click', function() {
				var link = $(this).find('a').first().attr('href');
				if (link) {
					window.location = link;
				}
			});
			/* grille produits bouton Voir le produit */
			/*
			function handleListItem($item) {
				const productLink = $item.find('.woocommerce-LoopProduct-link').attr('href');
				const newButton = $('<a>', {
					'href': productLink,
					'class': 'view-product-btn',
					'text': 'Voir le produit'
				});
				$item.find('.uael-product-actions').remove();
				$item.find('.uael-woo-products-thumbnail-wrap').append(newButton);
			}
			$('li').has('.uael-product-actions').each(function() {
				handleListItem($(this));
			});
			$('li').hover(
				function() {
					$(this).find('.view-product-btn').show();
				},
				function() {
					$(this).find('.view-product-btn').hide();
				}
			);
			*/
			//Page de connexion
			if ($('body').hasClass('page-id-563')) {
				$(".uael-google-text").text("Se connecter avec Google");
				//Changement de place du lien Mot de passe oublié
				$('.uael-login-form-wrapper .elementor-col-100.uael-login-form-footer').insertAfter($('.uael-login-form-wrapper .elementor-col-100.elementor-field-required').eq(1));
			}
			//Page single
			if ($('body').hasClass('single')) {
				/* Scroll du sommaire */
				var sommaire = document.querySelector('.sommaire');
				var contenu = document.querySelector('.contenu');
				var contenuOffsetTop = contenu.offsetTop;
				var contenuHeight = contenu.offsetHeight;
				var setSommairePosition = function () {
				  var scrollPosition = window.scrollY;
				  var sommaireHeight = sommaire.offsetHeight;
				  var contenuBottomPosition = contenuOffsetTop + contenuHeight;
				  var sommaireBottomPosition = scrollPosition + sommaireHeight;
				  sommaire.classList.remove("fixed", "fixedbottom");
				  if (scrollPosition > contenuOffsetTop && sommaireBottomPosition < contenuBottomPosition) {
					sommaire.classList.add("fixed");
				  } 
				  else if (sommaireBottomPosition >= contenuBottomPosition) {
					sommaire.classList.add("fixedbottom");
				  }
				};
				window.onscroll = setSommairePosition;
				window.onload = setSommairePosition;
			  }
			/* footer mobile */
			$(".footer .retractable").on("click", function() {
				if ($(window).width() < 768) {
					var nearestNav = $(this).closest('.elementor-widget-wrap').find('ul.elementor-nav-menu');
					var nearestIconList = $(this).closest('.elementor-widget-wrap').find('ul.elementor-icon-list-items');
					if (nearestNav.length) {
						nearestNav.slideToggle();
					} else {
						nearestIconList.slideToggle();
					}
					$(this).toggleClass('active');
				}
			});
		});
		$(window).load(function() {

		});
	});
})(jQuery, this);