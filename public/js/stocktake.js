$(document).ready(function() {
    if ($('#search_product_for_stocktake').length > 0) {
        $('#search_product_for_stocktake')
            .autocomplete({
                source: function(request, response) {
                    $.getJSON(
                        '/stocktakes/search-products',
                        { location_id: $('#location_id').val(), term: request.term },
                        response
                    );
                },
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        $(this)
                            .data('ui-autocomplete')
                            ._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                select: function(event, ui) {
                    $(this).val(null);
                    fetch_stocktake_product_row(ui.item.variation_id);
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            let html = '<div>' + item.name;
            if (item.type === 'variable') {
                html += ' - ' + item.variation;
            }
            html += ' (' + item.sub_sku + ')';
            if (item.qty_available <= 0) {
                html += ' <span class="text-red-600">(Out of stock)</span>';
            }
            html += '</div>';
            return $('<li>').append(html).appendTo(ul);
        };
    }

    $('select#location_id').change(function() {
        $('table#stocktake_product_table tbody').html('');
        $('#product_row_index').val(0);
    });

    $(document).on('click', '.remove_product_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this).closest('tr').remove();
            }
        });
    });

    $('form#stocktake_form').validate();
});

function fetch_stocktake_product_row(variation_id) {
    const row_index = parseInt($('#product_row_index').val());
    const location_id = $('select#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/stocktakes/get-product-row',
        data: { row_index, variation_id, location_id },
        dataType: 'html',
        success: function(result) {
            $('table#stocktake_product_table tbody').append(result);
            $('#product_row_index').val(row_index + 1);
        },
    });
}
