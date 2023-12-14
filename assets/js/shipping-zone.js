jQuery(document).ready(function ($) {

    $('#km-shipping-zone-settings').on('submit', function (e) {
        e.preventDefault();

        submitBtn = $(this).find('button[type="submit"]');
        submitBtn.attr('disabled', true);
        submitBtn.siblings('.spinner').css("visibility", "visible");
        submitBtn.siblings('.km-success-message', '.km-error-message').css("display", "none");

        $('.km-success-message, .km-error-message').remove();

        const data = {
            'zone_id': $('#km-zone-id').val(),
            'min_shipping_days_hs': $('#min_shipping_days_hs').val(),
            'max_shipping_days_hs': $('#max_shipping_days_hs').val(),
            'min_shipping_days_ls': $('#min_shipping_days_ls').val(),
            'max_shipping_days_ls': $('#max_shipping_days_ls').val(),
            'shipping_nonce': $('#km_save_shipping_delay_nonce').val(),
        };

        kmAjaxCall('save_shipping_delays_handler', data)
            .then(response => {
                if (response.success) {
                    submitBtn.after('<p class="km-success-message">' + response.data.message + '</p>')
                }
                submitBtn.attr('disabled', false);
                submitBtn.siblings('.spinner').css("visibility", 'hidden')
                submitBtn.siblings('.km-success-message', '.km-error-message').css("display", "block");
                setTimeout(function () {
                    $('.km-success-message, .km-error-message').fadeOut('slow');
                }, 3000);
            })
            .catch(error => {
                submitBtn.after('<p class="km-error-message">Une erreur est survenue:' + error + '</p>');
            });
    });
});
