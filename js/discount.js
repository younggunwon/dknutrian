var cafe24_discount_sample_app = (function () {
    'use strict';
    //TODO : 운영시 discount_url, client_id 수정
    const discount_url = "https://dknutri02.mycafe24.com/api/discount.php";//<-------------------------------- [Discount url] 수정
    const max_event_mileage_url = "https://dknutri02.mycafe24.com/api/event_mileage.php";//<-------------------------------- [Max event mileage url] 수정
    const client_id = "rQfkxGAOEKTaTGbA4Q2MTG";        //<-------------------------------- [App key] 수정

    // 마지막으로 성공한 할인 금액을 저장하는 변수
    var last_success_mileage = 0;

    return { 
        discount_do: function (params) {            //App의 할인 로직 호출
            var app_discount_order = {};

            app_discount_order.mall_id = params.ec_mall_id;
            app_discount_order.shop_no = params.shop_no;
            app_discount_order.member_id = params.member_id;
            app_discount_order.guest_key = params.guest_key;
            app_discount_order.member_group_no = params.group_no;
            app_discount_order.product = params.products;
            app_discount_order.time = Math.ceil(new Date().getTime() / 1000);
            let use_event_mileage = $('input[name="useEventMileage"]').val() || 0;
            app_discount_order.use_event_mileage = (Number(use_event_mileage) === 0) ? parseFloat("0.0000000000000000001") : use_event_mileage;

            $.ajax({
                url: discount_url,
                type: 'POST',
                cache: false,
                data: JSON.stringify(app_discount_order),
				dataType: 'json',
                contentType: 'application/json; charset=utf-8',
                success: function (result) {
                    console.log('DM_discount Success!');
                    if (result.code === 200) {
                        // 성공 시에만 성공값 업데이트 및 추가입력 필드 반영
                        last_success_mileage = use_event_mileage;
                        $('input[fw-label="쇼핑지원금"]').val(Number(use_event_mileage));

                        AppCallback.setDiscountPrice(JSON.stringify(result.data));
                    } else {
                        // 실패 시 (사용가능 금액 초과 등) 이전 성공값으로 복구
                        alert(result.message || '할인 적용에 실패했습니다.');
                        $('input[name="useEventMileage"]').val(last_success_mileage);
                        // 복구 후 다시 초기화하여 Cafe24 엔진과 동기화
                        setTimeout(function() {
                            cafe24_discount_sample_app.discount_init();
                        }, 1000);
                    }
                },
                error: function (request, status, error) {
                    console.log('DM_discount Error!');
                    // 네트워크 에러 시에도 이전 값으로 복구
                    $('input[name="useEventMileage"]').val(last_success_mileage);
                    setTimeout(function() {
                        cafe24_discount_sample_app.discount_init();
                    }, 1000);
                }
            });
        },
        discount_init: function () {        //기본 정보 및 상품정보 세팅
            var app_discount_req_params = {};

            //장바구니 정보 조회
            CAFE24API.getCartItemList(function (err, res) {
                if (err) {
                    console.log(err);
                } else {
                    if (res.items.length > 0) {
                        app_discount_req_params.products  = res.items;
                    } else {
                        console.log("There is no product in the basket.");
                    }
                }
            });

            //CAFE24FrontAPI 활용 기본정보 조회 
            (function (CAFE24API) {
                app_discount_req_params.ec_mall_id = CAFE24API.MALL_ID;
                app_discount_req_params.shop_no = CAFE24API.SHOP_NO;

                // 회원정보 조회
                CAFE24API.getMemberInfo(function (res) {
                    app_discount_req_params.group_no = Number(res.id.group_no);

                    if (res.id.member_id == null) {
                        app_discount_req_params.member_id = null;
                        app_discount_req_params.guest_key = res.id.guest_id;
                    } else {
                        app_discount_req_params.member_id = res.id.member_id;
                    }

                    cafe24_discount_sample_app.discount_do(app_discount_req_params);
                });
            })(CAFE24API.init(client_id));
        }
    }
})();

//window.onload 확인 후 이벤트 리스너에 등록
if (document.readyState == 'complete') {
    $("body").bind("EC_ORDER_ORDERFORM_CHANGE", function (e, oParam) {
        if (oParam.event_type !== 'product_change') {
            return;
        }
        cafe24_discount_sample_app.discount_init();
    });

    cafe24_discount_sample_app.discount_init();
} else {
    window.addEventListener('load', cafe24_discount_sample_app.discount_init);
}

$(function() {
    $('input[fw-label="쇼핑지원금"], input[fw-label="총상품금액"]').closest('tr').hide();
    
    const max_event_mileage_url = "https://dknutri02.mycafe24.com/api/eventMileage.php";//<-------------------------------- [Max event mileage url] 수정
    const client_id = "rQfkxGAOEKTaTGbA4Q2MTG";        //<-------------------------------- [App key] 수정

    (function (CAFE24API) {
        var totalCartPrice = 0;
        CAFE24API.getCartItemList(function (err, res) {
            if (err) {
                console.log(err);
            } else {
                // res.items 배열에서 각 상품의 가격을 합산하여 장바구니의 상품가격 총합을 계산
                if (res.items && Array.isArray(res.items)) {
                    totalCartPrice = res.items.reduce(function(acc, item) {
                        var price = Number(item.discount_price * item.quantity || 0);
                        return acc + price;
                    }, 0);

                    // 추가 입력 영역의 "총상품금액" 필드에 값 입력 (필드명 기반)
                    $('input[fw-label="총상품금액"]').val(totalCartPrice);
                }
            }
        });

        // 회원정보 조회
        CAFE24API.getMemberInfo(function (res) {
            if(res.id.member_id) {
                $('#ec-jigsaw-title-discount').next().append(`
                    <div class="discountDetail  mDiscountcodeSelect">
                        <div class="displayblock">
                            <strong class="heading">쇼핑지원금</strong>
                            <div class="control">
                                <input type="text" name="useEventMileage" class="inputTypeText">
                                (최대 사용 가능금액 : <span id="maxEventMileage">0</span>원)
                                <a href="#none" id="useAllEventMileage" class="btnNormal">전액 사용</a>
                            </div>
                        </div>
                    </div>
                `);
                $.ajax({
                    url: max_event_mileage_url,
                    type: 'POST',
                    cache: false,
                    data: {member_id : res.id.member_id, mode : 'useableEventMileage', totalOrderPrice : totalCartPrice},
                    dataType: 'json',
                    success: function (result) {
                        if (result.code === 200) {
                            $('#maxEventMileage').text(Number(result.data.useableEventMileage).toLocaleString());
                        }
                    },
                    error: function (request, status, error) {
                    }
                });
            }
        });
    })(CAFE24API.init(client_id));

    // 쇼핑지원금 금액 변경시 재적용
    $('input[name="useEventMileage"]').on('change', function() {
        cafe24_discount_sample_app.discount_init();
    });

    $('#useAllEventMileage').on('click', function() {
        $('input[name="useEventMileage"]').val($('#maxEventMileage').text().replace(',', ''));
        $('input[name="useEventMileage"]').change();
    });
});
