jQuery(document).ready(function($){
    $('#packing-slip-add').on('click', function(){
        $('.form-packing-slip-add').addClass('packing-add-show');
    })
    $('.packing-popup-close').on('click', function(){
        $('.form-packing-slip-add').removeClass('packing-add-show');
    })
    $('#package_download_csv').on('click', function(e){
        if($('#package-template').val() === 'none') {
            e.preventDefault();
        }
    })
    let oldUrl = $('#package_download_csv').attr('href');
    $('#package-template').on('change', function(){
        let value = $(this).val();
        if(value != 'none'){
            $('#package_download_csv').attr('href', oldUrl+'&templateId='+value);
            $('#package_download_csv').removeClass('disabled');
        }else{
            $('#package_download_csv').attr('href', oldUrl);
            $('#package_download_csv').addClass('disabled');
        }
    })
})