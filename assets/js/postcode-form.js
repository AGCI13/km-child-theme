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
                    label_modal_postcode.textContent = '';
                    setCookie('zip_code', zip_modal + '-' + country_modal, 30);
                    setCookie('shipping_zone', response.data, 30);
                    setTimeout(() => {
                        modal_postcode.style.display = 'none';
                        location.reload();
                    }, 400);
                } else {
                    // Gestion des erreurs
                    if (response.data && typeof response.data.message === 'string') {
                        label_modal_postcode.textContent = response.data.message;
                    } else {
                        label_modal_postcode.textContent = 'Une erreur inattendue est survenue. Veuillez réessayer.';
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
