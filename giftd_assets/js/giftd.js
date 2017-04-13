
$(document).on('click', '.giftd-check-coupon', function() {
    var code = $('[name="coupon"]').val();

    $.ajax({
        type: 'post',
        url : '/giftd_assets/ajax/controller.php',
        data: {
            'coupon_code': code
        },
        success: function () {
            location.reload();
        }
    });
});