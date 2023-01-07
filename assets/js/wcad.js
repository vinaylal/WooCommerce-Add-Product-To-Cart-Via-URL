jQuery(document).ready(function($) {

    function wcad_produrlform_empty(variable){
        if( 
           variable == undefined || 
           variable == "undefined" || 
           variable == '' || 
           variable == null || 
           variable == 0 || 
           variable == "0" 
        ){
           return true;
        }else{
           return false;
        }
    }
    
    function wcadValidateProductRows(){

        productRowValidationContinue = false;
        
        $( ".prod_url_select" ).each(function( index ) {

            var product = $(this).val();
            if( wcad_produrlform_empty(product)){
                $('.wcad_error_message').text('');
                $('.wcad_error_message').append('Make sure to select a product for each product row before adding a new one. ');
                productRowValidationContinue = false;
            }else{
                productRowValidationContinue = true;
            }
        });

        if(productRowValidationContinue){
            return true;
        }else{
            return false;
        }
    }
    
    function wcadValidateFormInputs(){

        qtyValidationContinue = false;
        productValidationContinue = false;
        productNameValidationContinue = false;
        
        $( ".prod_url_select" ).each(function( index ) {

            // Make sure we have something in the product field
            var product = $(this).val();
            if( wcad_produrlform_empty(product)){
                $('.wcad_error_message').text('');
                $('.wcad_error_message').append('Make sure to select a product for each product row. ');
                productNameValidationContinue = false;
            }else{
                productNameValidationContinue = true;
            }

            // Make sure out product(s) are numeric
            if($.isNumeric(product)){
                productValidationContinue = true;
            }else{
                $('.wcad_error_message').text('');
                $('.wcad_error_message').append('The values for one or more of your products are not appropriate. Only numbers are allowed. ');
                productValidationContinue = false;
            }
        });

        // Make sure out quantities are numeric
        $( ".prod_url_qty" ).each(function( index ) {

            var qty = $(this).val();
            if($.isNumeric(qty) && qty >= 1 ){
               qtyValidationContinue = true;
            }else{
                $('.wcad_error_message').text('');
                $('.wcad_error_message').append('The values for one or more of your quantities is not numeric or is less than 1. You must have at least 1 for your quantity. ');
                qtyValidationContinue = false;
            }
        });

        // if all tests passed we return true so the ajax can continue
        if(qtyValidationContinue && productValidationContinue && productNameValidationContinue){
            return true;
        }else{
            return false;
        }
    }

    // some themes hide empty p tags so we make sure to show this - otherwise our error messages wont show to the user
    $('.wcad_error_message').show();
    
    // handle the form submission
    $( "#wcad_product_url_form" ).submit(function( event ) {
        
        event.preventDefault();
        $('.wcad_error_message').text('');
        $('#urlHolder').val('');
        var origin   = window.location.origin;
        var url = origin+'/checkout/?add-to-cart=';
        // /checkout/?add-to-cart=635:1,
        // /checkout/?add-to-cart=635:1,6452:2
        var product_segment = '';
        var qty_segment = '';
        var wcad_continue = wcadValidateFormInputs();
        
        if(wcad_continue){
            
            $( ".prod_qty_set" ).each(function( index ) {

                var product = $(this).children('.prod_url_select').val();
                var quantity = $(this).children('.prod_url_qty').val();
                url += product+':'+quantity+',';
            });

           // update the url for the user to copy
           $('#urlHolder').val(url);
        }else{
            $('#urlHolder').val('');
        }
    });

    // Add a new product set of inputs
    $( "#wcad_product_url_form #wcad_new_product" ).on( 'click', function( event ) {

        event.preventDefault();
        var original = $('#wcad_submit_product_url_list').val();
        $('#wcad_submit_product_url_list').val('Loading...');
        var wcadProductRows_continue = wcadValidateProductRows();
        
        if( wcadProductRows_continue ){
            
            var num_selects = 1;
            $( ".prod_url_select" ).each(function( index ) {
                num_selects++;
            });

            $.ajax({
                url: wcad_producturlform_ajax_obj.ajaxurl,
                data: {
                    'action': 'wcad_producturlform_ajax_request',
                    'num_selects': num_selects,
                    'nonce' : wcad_producturlform_ajax_obj.nonce
                },
                success:function(data) {

                    $(data).appendTo('.product_url_wrapper');
                    $( ".wcad_remove_input" ).on( 'click', function( event ) {
                        event.preventDefault();
                        var index = $(this).data('index');
                        $('#wcad_prod_qty_set_' + index).remove();
                    });
                    $('.wcad_error_message').html('');
                    $('#wcad_submit_product_url_list').val(original);
                },
                error: function(errorThrown){
                    console.log(errorThrown);
                }
            });
        }else{
            $('#wcad_submit_product_url_list').val(original);
            return;
        }
    });

      // Copy Button JS - to do
//    $( "#wcad_copy_url" ).on( 'click', function( event ) {
//        event.preventDefault();
//        var $temp = $("<input>");
//        $("body").append($temp);
//        $temp.val($('#urlHolder').val()).select();
//        document.execCommand("copy");
//        $temp.remove();
//        $('#wcad_copy_url').text('Copied!');
//    });
});