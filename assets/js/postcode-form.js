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
        let country_value = event.target.querySelector('.country').value;
        let postcode_input = event.target.querySelector('.postcode');
        let postcode_value = postcode_input.value;
        let label_modal_postcode = event.target.querySelector('.postcode_label');
        let nonce = event.target.querySelector('#nonce_postcode');

        //Front simple validation.
        if (postcode_value.length < 5 && country_value === 'FR' || postcode_value.length < 4 && country_value === 'BE') {
            label_modal_postcode.textContent = 'Veuillez rentrez un code postal valide';
            return;
        }

        //Disable postcode_input.
        postcode_input.setAttribute('disabled', 'disabled');

        const data = {
            postcode: postcode_value,
            country: country_value,
            nonce_postcode: nonce.value,
        };

        handleLoading(event, true);

        kmAjaxCall('store_in_wc_session', data)
            .then(response => {
                if (response.success) {
                    // Logique après le stockage réussi
                    modal_postcode.style.display = 'none';
                    location.reload();
                } else {
                    // Gérer les erreurs
                    label_modal_postcode.textContent = response.data.message || 'Une erreur inattendue est survenue. Veuillez réessayer.';
                    postcode_input.removeAttribute('disabled');
                }
            })
            .catch(error => {
                console.log(error.message);
                postcode_input.removeAttribute('disabled');
            });
    }
});
