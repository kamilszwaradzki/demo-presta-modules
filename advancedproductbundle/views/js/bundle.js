$(document).ready(function() {
    
    $('#add-bundle-product').on('click', function() {
        const productId = $('#bundle-product-select').val();
        const productName = $('#bundle-product-select option:selected').text();
        
        if (!productId) {
            alert('Wybierz produkt');
            return;
        }
        
        addProductToBundle(productId, productName);
    });
    
    $(document).on('click', '.remove-bundle-product', function() {
        $(this).closest('.bundle-product-item').remove();
        updateBundlePreview();
    });
    
    $('#discount_type').on('change', function() {
        const type = $(this).val();
        const suffix = type === 'percentage' ? '%' : Currency.sign;
        $('#discount-suffix').text(suffix);
        updateBundlePreview();
    });
    
    $('#discount_value').on('input', function() {
        updateBundlePreview();
    });
    
    $(document).on('input', '.bundle-product-quantity', function() {
        updateBundlePreview();
    });
    
    function addProductToBundle(productId, productName) {
        const html = `
            <div class="bundle-product-item" data-product-id="${productId}">
                <span class="remove-bundle-product">×</span>
                <strong>${productName}</strong>
                <input type="number" 
                       class="form-control bundle-product-quantity" 
                       value="1" 
                       min="1" 
                       style="width: 80px;"
                       name="bundle_products[${productId}][quantity]">
                <input type="hidden" 
                       name="bundle_products[${productId}][id_product]" 
                       value="${productId}">
            </div>
        `;
        
        $('#bundle-products-list').append(html);
        updateBundlePreview();
    }
    
    function updateBundlePreview() {
        let totalPrice = 0;
        
        $('.bundle-product-item').each(function() {
            const productId = $(this).data('product-id');
            const quantity = $(this).find('.bundle-product-quantity').val();
            const price = parseFloat($(this).data('price')) || 0;
            
            totalPrice += price * quantity;
        });
        
        const discountType = $('#discount_type').val();
        const discountValue = parseFloat($('#discount_value').val()) || 0;
        
        let finalPrice = totalPrice;
        let savedAmount = 0;
        
        if (discountType === 'percentage') {
            savedAmount = totalPrice * (discountValue / 100);
            finalPrice = totalPrice - savedAmount;
        } else {
            savedAmount = discountValue;
            finalPrice = totalPrice - discountValue;
        }
        
        $('#bundle-original-price').text(formatPrice(totalPrice));
        $('#bundle-final-price').text(formatPrice(finalPrice));
        $('#bundle-savings').text(formatPrice(savedAmount));
        
        if ($('.bundle-product-item').length > 0) {
            $('#bundle-preview').show();
        } else {
            $('#bundle-preview').hide();
        }
    }
    
    function formatPrice(price) {
        return price.toFixed(2) + ' ' + Currency.sign;
    }
    
    if (typeof $.fn.sortable !== 'undefined') {
        $('#bundle-products-list').sortable({
            handle: '.drag-handle',
            update: function() {
                updateProductPositions();
            }
        });
    }
    
    function updateProductPositions() {
        $('.bundle-product-item').each(function(index) {
            $(this).find('.product-position').val(index);
        });
    }
    
    $('#bundle-form').on('submit', function(e) {
        if ($('.bundle-product-item').length < 2) {
            e.preventDefault();
            alert('Bundle musi zawierać minimum 2 produkty');
            return false;
        }
        
        const discountValue = parseFloat($('#discount_value').val());
        if (discountValue <= 0) {
            e.preventDefault();
            alert('Wartość rabatu musi być większa od 0');
            return false;
        }
        
        return true;
    });
    
    if (typeof $.fn.autocomplete !== 'undefined') {
        $('#bundle-product-search').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: bundleAjaxUrl,
                    data: {
                        action: 'searchProducts',
                        query: request.term
                    },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            select: function(event, ui) {
                addProductToBundle(ui.item.id, ui.item.label);
                $(this).val('');
                return false;
            }
        });
    }
    
    updateBundlePreview();
});

function updateBundleQuantity(quantity) {
    const bundleProducts = document.querySelectorAll('.bundle-product-quantity');
    
    bundleProducts.forEach(function(input) {
        const baseQuantity = parseInt(input.dataset.baseQuantity);
        input.value = baseQuantity * quantity;
    });
}

unction addBundleToCart(bundleId) {
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