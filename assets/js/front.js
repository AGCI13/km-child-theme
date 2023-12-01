let span = document.getElementsByClassName('uael-product-actions');
span.innerHTML += 'Voir le produit >';

document.addEventListener('DOMContentLoaded', () => {
	setTimeout(() => {
		const tidioIframe = document.getElementById('tidio-chat-iframe');
		if (tidioIframe) {
			tidioIframe.style.zIndex = '98';
		}
	}, 1500);
});

const handleLoading = (event, show = false) => {
	let container;
	// Si le bouton lui-même a été cliqué, remontez dans l'arbre DOM jusqu'au parent commun.
	if (event.target.closest('.modal-actions')) {
		container = event.target.closest('.modal-actions');
	} else {
		container = event.target;
	}

	// Maintenant, sélectionnez les éléments à partir du conteneur.
	const submit = container.querySelector('.btn-confirm');
	const submit_label = container.querySelector('.btn-confirm-label');
	const loader = container.querySelector('.btn-confirm-loader');

	if (show) {
		submit.disabled = true;
		submit_label.style.visibility = 'hidden';
		loader.style.display = 'block';
	}
	else {
		submit.disabled = false;
		loader.style.display = 'none';
		submit_label.style.visibility = 'visible';
	}
}

jQuery(document).ready(function ($) {
	/* catégorie cliquable en entier */
	$('.clickable-column').on('click', function () {
		var link = $(this).find('a').first().attr('href');
		if (link) {
			window.location = link;
		}
	});

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
	$(".footer .retractable").on("click", function () {
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

