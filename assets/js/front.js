let span = document.getElementsByClassName('uael-product-actions');
span.innerHTML += 'Voir le produit >';

document.addEventListener('DOMContentLoaded', () => {
	setTimeout(() => {
		const tidioIframe = document.getElementById('tidio-chat-iframe');
		if(tidioIframe){
			tidioIframe.style.zIndex = '98';
		}
	}, 1500);
	setDebugClosable(); 
});

const getCookie = (cname) => {
	let name = cname + "=";
	let decodedCookie = decodeURIComponent(document.cookie);
	let ca = decodedCookie.split(';');
	for (let i = 0; i < ca.length; i++) {
		let c = ca[i];
		while (c.charAt(0) === ' ') {
			c = c.substring(1);
		}
		if (c.indexOf(name) === 0) {
			return c.substring(name.length, c.length);
		}
	}
	return "";
}

const setCookie = (cname, cvalue, exdays) => {
	const d = new Date();
	d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
	let expires = "expires=" + d.toUTCString();
	document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

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

const setDebugClosable = () => {
	document.querySelectorAll('.modal-debug-close').forEach(closeBtn => {
		
        closeBtn.addEventListener('click', () => {
            document.querySelectorAll('.debug-content').forEach(element => {
                if (element.style.display === "none") {
                    element.style.display = "block";
                    element.parentElement.style.height = "calc(100% - 32px)";
                } else {
                    element.style.display = "none";
                    element.parentElement.style.height = "auto";
                }
            });
        });
    });
}

const showCouponForm = () => {

    couponLabels = document.querySelectorAll('.km-coupon-label');

    if (couponLabels) {
        couponLabels.forEach(couponLabel => {
            couponLabel.addEventListener('click', () => {
                couponLabel.classList.add('active');
                couponLabel.attributes['data-title'].value = 'Entrez votre code promo';
            });
        });
    }
}


jQuery(document).ready(function ($) {
    $(window).on('load', function() {
        // modal sous body, évite les problèmes de responsive avec le fait que le modal se trouve dans un header qui est caché en version mobile
        $('.km-modal').appendTo('body');
    });
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
	if ($('body').hasClass('single-post')) {
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
