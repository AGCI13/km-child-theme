jQuery(document).ready(function ($) {
    // Function to toggle the visibility of custom zones fieldset and uncheck checkboxes
    function toggleCustomZones(variationId) {
        const customZonesFieldset = $('fieldset._custom_product_shipping_zones\\[' + variationId + '\\]');
        console.log('Variation ID:', variationId);
        console.log('Custom Zones Fieldset:', customZonesFieldset);

        const isCustomZonesChecked = $('input[name="_product_sales_area[' + variationId + ']"][value="custom_zones"]').is(':checked');
        console.log('Is Custom Zones Checked:', isCustomZonesChecked);

        if (isCustomZonesChecked) {
            customZonesFieldset.show();
        } else {
            customZonesFieldset.hide();
            customZonesFieldset.find('input[type="checkbox"]').prop('checked', false);
        }
    }

    // Function to handle variations
    function handleVariations() {
        $('.km-variation-custom-fields').each(function () {
            const variationId = $(this).find('input[name^="_product_sales_area"]').attr('name').match(/\d+/)[0];
            console.log('Handling variation:', variationId);
            toggleCustomZones(variationId);
        });

        // Event listener for changes on the sales area radio buttons
        $(document).on('change', 'input[name^="_product_sales_area"]', function () {
            const variationId = $(this).attr('name').match(/\d+/)[0];
            toggleCustomZones(variationId);
        });
    }

    // Wait for WooCommerce variations to be loaded
    $(document).on('woocommerce_variations_loaded', function () {
        handleVariations();
    });

    // Also run the function in case the event has already been triggered
    if ($('.variations_form').hasClass('variations-loaded')) {
        handleVariations();
    }
});
