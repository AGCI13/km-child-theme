document.addEventListener('DOMContentLoaded', function () {
        const modal_pc_open_btns = document.querySelectorAll('.modal_pc_open_btn');
        const modal_postcode = document.querySelector('.modal-postcode');
        const modal_pc_close_btns = document.querySelectorAll(".modal-postcode-close");
        const form_modals = document.querySelectorAll('.form-postcode');

        if (modal_postcode.classList.contains('active')) {
            document.body.classList.add('modal-open');
        }

        modal_pc_open_btns.forEach(btn => {
            btn.addEventListener('click', function () {
                modal_postcode.classList.add('active');
                document.body.classList.add('modal-open');
            });
        });

        modal_pc_close_btns.forEach(btn => {
            btn.addEventListener('click', function () {
                modal_postcode.classList.remove('active');
                document.body.classList.remove('modal-open');
            });
        });

        form_modals.forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                e.stopPropagation();
                submitPostcode(e);
            });
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


        const submitPostcode = (event) => {
            let country_modal = event.target.querySelector('.country').value;
            let zip_modal = event.target.querySelector('.zip_code').value;
            let label_modal_postcode = event.target.querySelector('.zip_code_label');
            let nonce = event.target.querySelector('#nonce_postcode');

            //Front simple validation
            if (zip_modal.length < 5 && country_modal === 'FR' || zip_modal.length < 4 && country_modal === 'BE') {
                label_modal_postcode.textContent = 'Veuillez rentrez un code postal valide';
                return;
            }

            const data = {
                zip: zip_modal,
                country: country_modal,
                nonce_postcode: nonce.value,
            };

            handleLoading(event, true);

            kmAjaxCall('postcode_submission_handler', data)
                .then(response => {
                    if (response.success) {
                        //Traiement de la réponse
                        setCookie('zip_code', zip_modal + '-' + country_modal, 1);
                        setCookie('shipping_zone', response.data, 1);
                        modal_postcode.style.display = 'none';
                        location.reload();
                    } else {
                        // Gestion des erreurs
                        if (response.data && typeof response.data.message === 'string') {
                            label_modal_postcode.textContent = response.data.message;
                        } else {
                            label_modal_postcode.textContent = 'Une erreur inattendue est survenue.';
                        }
                    }
                    handleLoading(event, false);
                })
                .catch(error => {
                    label_modal_postcode.textContent = error.message;
                    handleLoading(event, false);
                });
        }
});
