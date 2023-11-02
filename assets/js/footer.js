jQuery(function ($) {
    // TD : Vider le panier et rediriger sur la page d'accueil au clic du CP
    /*$(function() {
        const postcodeField = $('#billing_postcode, #shipping_postcode');

        if(postcodeField.val() !== "") {
            postcodeField.attr('readonly', 'readonly');

            postcodeField.click(function() {
                if (confirm('La modification de votre code postal nécessite de vider votre panier et vous rediriger vers l\'accueil.')) {
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: '/wp-admin/admin-ajax.php?action=wc_woocommerce_clear_cart_url',
                        data: {
                            action : 'wc_woocommerce_clear_cart_url'
                        },
                        success: function (data) {
                            if (data.status == 'success') {
                                window.location.href = "/";
                            }
                        }
                    });
                }
            });
        }
    });*/

    // TD : au clic du bouton d'ajout au panier, lancer une seconde requête AJAX en 1er
     $(function() {
        $('.single_add_to_cart_button').click(function(e) {
            e.preventDefault();

            var btn = $(this);
            var productID = $('input[name="product_id"]').val();
            var variationID = $('input[name="variation_id"]').val();
            var qty = $('.qty').val();
            var form = $(this).closest('form');

            if(!productID) {
                productID = $('.single_add_to_cart_button').val();
            }

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: '/wp-admin/admin-ajax.php?action=wc_before_popup',
                data: {
                    action: 'wc_before_popup',
                    product_id: productID,
                    variation_id: variationID,
                    qty: qty
                },
                success: function (data) {
                    if (data.status === 'palette_popup') {
                        // Show palette popup
                        $('body').append(data.content);

                        $('#popup-btn button').click(function(){
                            form.submit();
                        });

                        $('#close-popup').click(function() {
                            document.getElementById('popup-shadow').style.display = "none";
                            document.getElementById('popup-content').style.display = "none";
                        });
                    } else if(data.status === 'vrac_popup') {
                        // Show vrac popup
                        $('body').append(data.content);

                        $('#popup-btn button, #close-popup').click(function() {
                            document.getElementById('popup-shadow').style.display = "none";
                            document.getElementById('popup-content').style.display = "none";
                        });
                    } else {
                        if(variationID) {

                        } else {
                            form.append("<input type='hidden' name='add-to-cart' value='" + btn.val() + "'>")
                        }

                        form.submit();
                    }
                },
                error: function() {
                    if(variationID) {
                        form.append("<input type='hidden' name='product_id' value='" + productID + "'>");
                        form.append("<input type='hidden' name='variation_id' value='" + variationID + "'>");
                    } else {
                        form.append("<input type='hidden' name='add-to-cart' value='" + btn.val() + "'>")
                    }

                    form.submit();
                }
            });
        });
    });
})