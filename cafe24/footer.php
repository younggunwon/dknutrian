<iframe name="ifrmProcess" src="/blank.php" width="100%" height="200" class="display-none"></iframe>

<script>
	$(function() {
		
		/**
		 * datetimepicker 초기화
		 *
		 */
		function init_datetimepicker() {
			// 날짜 픽커
			if ($('.js-datepicker').length) {
				var defaultOptions = {
					locale: 'ko',
					format: 'YYYY-MM-DD',
					dayViewHeaderFormat: 'YYYY년 MM월',
					viewMode: 'days',
					ignoreReadonly: true
				};
				var options = $('.js-datepicker').data('options');
				options = $.extend(true, {}, defaultOptions, options);
				$('.js-datepicker').datetimepicker(options);
				//날짜 체크 정규식 /^(19|20)\d{2}-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[0-1])$/;

				//이중 선택으로 변경
				$('.js-datepicker input').off('focus').click(function () {
					$(this).parent().data("DateTimePicker").show();
				});
			}

			// 시간 픽커
			if ($('.js-timepicker').length) {
				$('.js-timepicker').datetimepicker({
					locale: 'ko',
					format: 'HH:mm',
					dayViewHeaderFormat: 'YYYY년 MM월',
					ignoreReadonly: true
				});
			}

			// 날짜/시간 픽커 (분단위까지)
			if ($('.js-datetimepicker').length) {
				$('.js-datetimepicker').datetimepicker({
					locale: 'ko',
					format: 'YYYY-MM-DD HH:mm',
					dayViewHeaderFormat: 'YYYY년 MM월',
					ignoreReadonly: true
				});

				$('.js-datetimepicker input').off('focus').click(function () {
					$(this).parent().data("DateTimePicker").show();
				});
			}

			// 날짜/시간 픽커 (초단위까지)
			if ($('.js-fulltimepicker').length) {
				$('.js-fulltimepicker').datetimepicker({
					locale: 'ko',
					format: 'YYYY-MM-DD HH:mm:ss',
					dayViewHeaderFormat: 'YYYY년 MM월',
					ignoreReadonly: true
				});

				$('.js-fulltimepicker input').off('focus').click(function () {
					$(this).parent().data("DateTimePicker").show();
				});
			}

			// 기간설정 셀렉트 박스 액션
			if ($('.js-select-box-dateperiod').length) {
				$('.js-select-box-dateperiod').on('change', function(){
					var $startDate = '',
						$endDate = '',
						$period = $(this).val(),
						$elements = $('input[name*=\'' + $(this).data('target-name') + '\']'),
						$format = $(this).next().data('DateTimePicker').format();
					if(!$period) return false;
					$startDate = moment().hours(0).minutes(0).seconds(0).subtract($period, 'days').format($format);
					if($period == 1) $endDate = moment().hours(23).minutes(59).seconds(59).subtract($period, 'days').format($format);
					else $endDate = moment().hours(23).minutes(59).seconds(59).format($format);
					$($elements[0]).val($startDate);
					$($elements[1]).val($endDate);
				});

				// 버튼 활성 초기화
				$.each($('.js-select-box-dateperiod'), function (idx) {
					var $elements = $('input[name*=\'' + $(this).data('target-name') + '\']'),
						$format = $(this).next().data('DateTimePicker').format(),
						$endDate = moment().format($format),
						$yesterDay = moment().hours(-24).minutes(0).seconds(0).format($format);

					if ($elements.length && $elements.val() != '') {
						if (moment($($elements[1]).val()).format('YYYY-MM-DD') === moment($endDate).format('YYYY-MM-DD')) {
							var $interval = moment($($elements[1]).val()).diff(moment($($elements[0]).val()), 'days');
							if ($(this).find('option[value="' + $interval + '"]').length > 0) {
								$(this).find('option[value="' + $interval + '"]').prop('selected', 'true');
							} else {
								$(this).find('option[value=""]').prop('selected', 'true');
							}
						} else if(moment($($elements[0]).val()).format('YYYY-MM-DD') === moment($($elements[1]).val()).format('YYYY-MM-DD') &&  moment($($elements[1]).val()).format('YYYY-MM-DD') === moment($yesterDay).format('YYYY-MM-DD')) {
							$(this).find('option[value="1"]').prop('selected', 'true');
						} else {
							$(this).find('option[value=""]').prop('selected', 'true');
						}
					} else {
						$(this).find('option[value="7"]').trigger('click');
					}
				});
			}

			// 기간설정 버튼 액션
			if ($('.js-dateperiod').length) {
				$('.js-dateperiod label').click(function (e) {
					var $startDate = '',
						$endDate = '',
						$period = $(this).children('input[type="radio"]').val(),
						$elements = $('input[name*=\'' + $(this).closest('.js-dateperiod').data('target-name') + '\']'),
						$inverse = $('input[name*=\'' + $(this).closest('.js-dateperiod').data('target-inverse') + '\']'), $format = $($elements[0]).parent().data('DateTimePicker').format();

					if ($period >= 0) {
						// 달력 일 기준 변경(관리자로그)
						if ($(this).data('type') == 'calendar') {
							$startDate = $period.substring(0,4) + '-' + $period.substring(4,6) + '-' + $period.substring(6,8);
							$endDate = moment().format($format);
						} else {
						if ($inverse.length) {
							$period = '-' + $period;
						}
						if ($inverse.length) {
							$startDate = moment().hours(23).minutes(59).seconds(0).subtract($period, 'days').format($format);
						} else {
							$startDate = moment().hours(0).minutes(0).seconds(0).subtract($period, 'days').format($format);
						}

						// 주문/배송 > 송장일괄등록 등록일 검색시 현재시간까지 검색
						if ($('.js-datetimepicker').length && $('input[name="searchPeriod"]').length) {
							$endDate = moment().format($format);
						} else {
							$endDate = moment().hours(0).minutes(0).seconds(0).format($format);
						}
					}
					}

					if ($inverse.length) {
						$($elements[1]).val($startDate);
						$($elements[0]).val($endDate);
					} else {
						$($elements[0]).val($startDate);
						$($elements[1]).val($endDate);
					}

				});
				// 버튼 활성 초기화
				$.each($('.js-dateperiod'), function (idx) {
					var $elements = $('input[name*=\'' + $(this).data('target-name') + '\']'),
						$format = $($elements[0]).parent().data('DateTimePicker').format();
					if ($('.js-datetimepicker').length && $('input[name="searchPeriod"]').length) {
						var $endDate = moment().format($format);
					} else {
						var $endDate = moment().hours(0).minutes(0).seconds(0).format($format);
					}

					if ($elements.data('init') != 'n') {
						if ($elements.length && $elements.val() != '') {
							if (moment($($elements[1]).val())._f === 'YYYY-MM-DD') {
								if (moment($($elements[1]).val()).format('YYYY-MM-DD') === moment($endDate).format('YYYY-MM-DD')) {
									var $interval = moment($($elements[1]).val()).diff(moment($($elements[0]).val()), 'days');
									$(this).find('label input[type="radio"][value="' + $interval + '"]').trigger('click');
								}
							}
						} else {
							var $this = $(this);
							var $activeRadio = $this.find('label input[type="radio"][value="all"]');
							if ($activeRadio.length < 1) {
								$activeRadio = $this.find('label input[type="radio"][value="6"]');
							}
							$activeRadio.trigger('click');
						}
					}
				});
			}
			// 기간설정 통계용(오늘은 데이터가 없으므로 오늘은 나오지 않음) 버튼 액션
			if ($('.js-dateperiod-statistics').length) {
				$('.js-dateperiod-statistics label').click(function (e) {
					var $startDate = '',
						$endDate = '',
						$period = $(this).children('input[type="radio"]').val(),
						$elements = $('input[name*=\'' + $(this).closest('.js-dateperiod-statistics').data('target-name') + '\']'),
						$inverse = $('input[name*=\'' + $(this).closest('.js-dateperiod-statistics').data('target-inverse') + '\']'),
						$format = $($elements[0]).parent().data('DateTimePicker').format();
					if ($period >= 0) {
						if ($inverse.length) {
							$period = '-' + $period;
						}
						$startDate = moment().hours(0).minutes(0).seconds(0).subtract($period, 'days').format($format);
						$endDate = moment().hours(0).minutes(0).seconds(0).subtract(1, 'days').format($format);
					}

					if ($inverse.length) {
						$($elements[1]).val($startDate);
						$($elements[0]).val($endDate);
					} else {
						$($elements[0]).val($startDate);
						$($elements[1]).val($endDate);
					}
				});
				// 버튼 활성 초기화
				$.each($('.js-dateperiod-statistics'), function (idx) {
					var $elements = $('input[name*=\'' + $(this).data('target-name') + '\']'),
						$format = $($elements[0]).parent().data('DateTimePicker').format(),
						$endDate = moment().hours(-24).minutes(0).seconds(0).format($format);
					if ($elements.length && $elements.val() != '') {
						if (moment($($elements[1]).val()).format('YYYY-MM-DD') === moment($endDate).format('YYYY-MM-DD')) {
							var $interval = moment($($elements[1]).val()).diff(moment($($elements[0]).val()), 'days') + 1;
							$(this).find('label input[type="radio"][value="' + $interval + '"]').trigger('click');
						}
					} else {
						$(this).find('label input[type="radio"][value="7"]').trigger('click');
					}
				});
			}
		}
		init_datetimepicker();
	});
</script>