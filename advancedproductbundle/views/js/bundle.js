$(document).ready(function () {
    $('.bundle-products-ajax-input').select2({
        placeholder: 'Wybierz produkt',
        minimumInputLength: 2,
        ajax: {
            url: bundle_ajax_url,
            dataType: 'json',
            quietMillis: 250,
            data: function (term, page) {
                return { q: term };
            },
            results: function (data, page) {
                return { results: data };
            }
        },
        formatResult: function (item) {
            return item.text;
        },
        formatSelection: function (item) {
            return item.text;
        },
        dropdownCssClass: 'bigdrop',
        escapeMarkup: function (m) { return m; },
        multiple: true,
        tags: true, // To pozwala na wiele wartości
        width: '100%'
    }).on('change', function (e) {
        addProductToBundle(e.added.id, e.added.text);
        // $(this).val(null).trigger('change');
    });
    // Funkcja dodająca produkt do listy
    function addProductToBundle(productId, productName) {
        // Sprawdź czy produkt już istnieje
        if ($('#bundle-products-list').find('[data-product-id="' + productId + '"]').length > 0) {
            alert('Product is already in the bundle!');
            return;
        }

        var html = `
        <div class="bundle-product-item well" data-product-id="${productId}">
            <div class="row">
                <div class="col-md-6">
                    <strong>${productName}</strong>
                </div>
                <div class="col-md-3">
                    <input type="number" 
                           name="bundle_products[${productId}][quantity]" 
                           value="1" 
                           min="1" 
                           class="form-control product-quantity"
                           placeholder="Quantity">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-danger remove-product">
                        <i class="icon-trash"></i> Remove
                    </button>
                </div>
            </div>
            <input type="hidden" name="bundle_products[${productId}][id_product]" value="${productId}">
        </div>
        `;
        
        $('#bundle-products-list').append(html);
    }

    // Usuwanie produktu
    $(document).on('click', '.remove-product', function() {
        $(this).closest('.bundle-product-item').remove();
    });
});

function updateBundleQuantity(quantity) {
    const bundleProducts = document.querySelectorAll('.bundle-product-quantity');
    
    bundleProducts.forEach(function(input) {
        const baseQuantity = parseInt(input.dataset.baseQuantity);
        input.value = baseQuantity * quantity;
    });
}

function addBundleToCart(bundleId) {
    const quantity = parseInt($('#quantity_wanted').val()) || 1;
    
    $.ajax({
        url: bundleAjaxUrl,
        method: 'POST',
        data: {
            action: 'addBundleToCart',
            id_bundle: bundleId,
            quantity: quantity
        },
        success: function(response) {
            if (response.success) {
                prestashop.emit('updateCart', {
                    reason: {
                        linkAction: 'add-to-cart'
                    }
                });
                
                showNotification('Bundle dodany do koszyka!');
            } else {
                alert(response.error || 'Błąd dodawania do koszyka');
            }
        }
    });
}

function showNotification(message) {
    const notification = $('<div class="bundle-notification">' + message + '</div>');
    $('body').append(notification);
    
    setTimeout(function() {
        notification.fadeOut(function() {
            $(this).remove();
        });
    }, 3000);
}