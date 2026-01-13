<?php
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use EventMileage\EventMileage;

$campaign = new EventMileage();

// 검색 조건 수집
$searchParams = [
	'campaignNm' => $_GET['campaignNm'] ?? '',
	'searchDate' => $_GET['searchDate'] ?? [],
	'eventMileage' => $_GET['eventMileage'] ?? [],
	'expiryDate' => $_GET['expiryDate'] ?? [],
	'payDate' => $_GET['payDate'] ?? [],
	'paymentFl' => $_GET['paymentFl'] ?? '',
	'progressFl' => $_GET['progressFl'] ?? 'all'
];

// 캠페인 목록 조회
$campaignList = $campaign->getCampaignList($searchParams);

// 전체 개수
$totalCount = count($campaign->getCampaignList([]));
// 검색 결과 개수 (진행상태 필터링 전)
$rawSearchCount = count($campaignList);

// 진행상태 필터링 후 개수
$statusMap = [
  'scheduled' => '진행예정',
  'proceeding' => '진행 중',
  'end' => '종료'
];
$filteredCount = 0;
if(!empty($campaignList)) {
  foreach($campaignList as $c) {
    $progressStatus = getProgressStatus($c);
    if($searchParams['progressFl'] == 'all' || $progressStatus == ($statusMap[$searchParams['progressFl']] ?? '')) {
      $filteredCount++;
    }
  }
}
$searchCount = $filteredCount;

// 진행상태 계산 함수
function getProgressStatus($campaign) {
	$today = date('Y-m-d');
	$payStartDate = $campaign['payStartDate'] ?? '';
	$payEndDate = $campaign['payEndDate'] ?? '';
	
	if(empty($payStartDate) || empty($payEndDate)) {
		return '종료';
	}
	
	if($today < $payStartDate) {
		return '진행예정';
	} else if($today >= $payStartDate && $today <= $payEndDate) {
		return '진행 중';
	} else {
		return '종료';
	}
}

// 지급방식 한글 변환
function getPaymentFlText($paymentFl) {
	$texts = [
		'passivity' => '수동발급',
		'excelUpload' => '엑셀업로드',
		'auto' => '자동발급',
		'down' => '회원이 직접발급'
	];
	return $texts[$paymentFl] ?? $paymentFl;
}

// 유효기간 표시
function getExpiryDateText($campaign) {
	$expiryDaysFl = $campaign['expiryDaysFl'] ?? '';
	
	switch($expiryDaysFl) {
		case 'y':
			$startDate = $campaign['expiryStartDate'] ?? '';
			$endDate = $campaign['expiryDate'] ?? '';
			return ($startDate ? date('Y-m-d', strtotime($startDate)) : '') . ' ~ ' . ($endDate ? date('Y-m-d', strtotime($endDate)) : '');
		case 'dayDate':
			$days = $campaign['expiryDays'] ?? '';
			return '발급일로부터 ' . $days . '일';
		case 'monthEnd':
			return '발급월말 말일까지';
		case 'yearEnd':
			return '발급년도 말일까지';
		default:
			return '';
	}
}

?>

<?php require_once './../header.php'; ?>

<div id="content" class="body">
  <div class="page-header" style="position: relative;">
    <h3 style="position: relative;">쇼핑지원금 캠페인 
		<button type="button" class="btn btn-gray js-register" style="position: absolute; top: 0; right: 0; z-index: 100;">등록</button>
	</h3>
  </div>
  <form id="frmSearchBase" name="frmSearchBase" method="get" class="js-form-enter-submit">
    <div class="table-title gd-help-manual">
      쇼핑지원금 캠페인 검색
    </div>
    <div class="search-detail-box">
      <table class="table table-cols">
        <colgroup>
          <col class="width-md">
          <col>
          <col class="width-md">
          <col>
        </colgroup>
        <tbody>
          <tr>
            <th>캠페인명</th>
            <td>
              <div class="form-inline">
                <input type="text" name="campaignNm" value="<?= htmlspecialchars($searchParams['campaignNm']) ?>" class="form-control">
              </div>
            </td>
          </tr>
          <tr>
            <th>캠페인 등록일</th>
            <td>
              <div class="form-inline">
                <div class="input-group js-datepicker">
                  <input type="text" class="form-control width-xs" name="searchDate[]" value="<?= htmlspecialchars($searchParams['searchDate'][0] ?? '') ?>">
                  <span class="input-group-addon">
                    <span class="btn-icon-calendar">
                    </span>
                  </span>
                </div>
                ~
                <div class="input-group js-datepicker">
                  <input type="text" class="form-control width-xs" name="searchDate[]" value="<?= htmlspecialchars($searchParams['searchDate'][1] ?? '') ?>">
                  <span class="input-group-addon">
                    <span class="btn-icon-calendar">
                    </span>
                  </span>
                </div>
                <div class="btn-group js-dateperiod" data-toggle="buttons" data-target-name="searchDate">
                  <label class="btn btn-white btn-sm hand "><input type="radio" name="searchPeriod" value="0">오늘</label>
                  <label class="btn btn-white btn-sm hand  active"><input type="radio" name="searchPeriod" value="6">7일</label>
                  <label class="btn btn-white btn-sm hand "><input type="radio" name="searchPeriod" value="14">15일</label>
                  <label class="btn btn-white btn-sm hand "><input type="radio" name="searchPeriod" value="29">1개월</label>
                  <label class="btn btn-white btn-sm hand "><input type="radio" name="searchPeriod" value="89">3개월</label>
                  <label class="btn btn-white btn-sm hand "><input type="radio" name="searchPeriod" value="364">1년</label>
                </div>
              </div>
            </td>
          </tr>
          <tr>
            <th>혜택금액</th>
            <td>
              <div class="form-inline">
                <input type="text" name="eventMileage[]" value="<?= htmlspecialchars($searchParams['eventMileage'][0] ?? '') ?>" class="form-control width-xs">원
                ~
                <input type="text" name="eventMileage[]" value="<?= htmlspecialchars($searchParams['eventMileage'][1] ?? '') ?>" class="form-control width-xs">원
              </div>
            </td>
          </tr>
          <tr>
            <th>유효기간</th>
            <td>
              <div class="form-inline">
                <div class="input-group js-datepicker">
                  <input type="text" class="form-control width-xs" name="expiryDate[]" value="<?= htmlspecialchars($searchParams['expiryDate'][0] ?? '') ?>">
                  <span class="input-group-addon">
                    <span class="btn-icon-calendar">
                    </span>
                  </span>
                </div>
                ~
                <div class="input-group js-datepicker">
                  <input type="text" class="form-control width-xs" name="expiryDate[]" value="<?= htmlspecialchars($searchParams['expiryDate'][1] ?? '') ?>">
                  <span class="input-group-addon">
                    <span class="btn-icon-calendar">
                    </span>
                  </span>
                </div>
                <div class="btn-group js-dateperiod2" data-toggle="buttons" data-target-name="expiryDate">
                  <label class="btn btn-white btn-sm hand active">
                    <input type="radio" name="searchExpiryPeriod" value="">전체
                  </label>
                  <label class="btn btn-white btn-sm hand">
                    <input type="radio" name="searchExpiryPeriod" value="0">오늘
                  </label>
                  <label class="btn btn-white btn-sm hand">
                    <input type="radio" name="searchExpiryPeriod" value="6">7일
                  </label>
                  <label class="btn btn-white btn-sm hand">
                    <input type="radio" name="searchExpiryPeriod" value="14">15일
                  </label>
                  <label class="btn btn-white btn-sm hand ">
                    <input type="radio" name="searchExpiryPeriod" value="29">1개월
                  </label>
                  <label class="btn btn-white btn-sm hand ">
                    <input type="radio" name="searchExpiryPeriod" value="89">3개월
                  </label>
                  <label class="btn btn-white btn-sm hand ">
                    <input type="radio" name="searchExpiryPeriod" value="364">1년
                  </label>
                </div>
              </div>
            </td>
          </tr>
          <tr>
            <th>지급기간</th>
            <td>
              <div class="form-inline">
                <div class="input-group js-datepicker">
                  <input type="text" class="form-control width-xs" name="payDate[]" value="<?= htmlspecialchars($searchParams['payDate'][0] ?? '') ?>">
                  <span class="input-group-addon">
                    <span class="btn-icon-calendar">
                    </span>
                  </span>
                </div>
                ~
                <div class="input-group js-datepicker">
                  <input type="text" class="form-control width-xs" name="payDate[]" value="<?= htmlspecialchars($searchParams['payDate'][1] ?? '') ?>">
                  <span class="input-group-addon">
                    <span class="btn-icon-calendar">
                    </span>
                  </span>
                </div>
                <div class="btn-group js-dateperiod2" data-toggle="buttons" data-target-name="payDate">
                  <label class="btn btn-white btn-sm hand active">
                    <input type="radio" name="searchPayPeriod" value="">전체
                  </label>
                  <label class="btn btn-white btn-sm hand ">
                    <input type="radio" name="searchPayPeriod" value="0">오늘
                  </label>
                  <label class="btn btn-white btn-sm hand">
                    <input type="radio" name="searchPayPeriod" value="6">7일
                  </label>
                  <label class="btn btn-white btn-sm hand ">
                    <input type="radio" name="searchPayPeriod" value="14">15일
                  </label>
                  <label class="btn btn-white btn-sm hand ">
                    <input type="radio" name="searchPayPeriod" value="29">1개월
                  </label>
                  <label class="btn btn-white btn-sm hand ">
                    <input type="radio" name="searchPayPeriod" value="89">3개월
                  </label>
                  <label class="btn btn-white btn-sm hand ">
                    <input type="radio" name="searchPayPeriod" value="364">1년
                  </label>
                </div>
              </div>
            </td>
          </tr>
          <tr>
            <th>지급방식</th>
            <td>
              <div class="form-inline">
                <label class="radio-inline">
                  <input type="radio" name="paymentFl" value="all" <?= (!$searchParams['paymentFl'] || $searchParams['paymentFl'] == 'all') ? 'checked' : '' ?>>전체
                </label>
                <label class="radio-inline">
                  <input type="radio" name="paymentFl" value="passivity" <?= $searchParams['paymentFl'] == 'passivity' ? 'checked' : '' ?>>수동발급
                </label>
                <label class="radio-inline">
                  <input type="radio" name="paymentFl" value="excelUpload" <?= $searchParams['paymentFl'] == 'excelUpload' ? 'checked' : '' ?>>엑셀업로드
                </label>
                <label class="radio-inline">
                  <input type="radio" name="paymentFl" value="auto" <?= $searchParams['paymentFl'] == 'auto' ? 'checked' : '' ?>>자동발급
                </label>
                <label class="radio-inline">
                  <input type="radio" name="paymentFl" value="down" <?= $searchParams['paymentFl'] == 'down' ? 'checked' : '' ?>>회원이 직접발급
                </label>
              </div>
            </td>
          </tr>
          <tr>
            <th>진행상태</th>
            <td>
              <div class="form-inline">
                <label class="radio-inline">
                  <input type="radio" name="progressFl" value="all" <?= (!$searchParams['progressFl'] || $searchParams['progressFl'] == 'all') ? 'checked' : '' ?>>전체
                </label>
                <label class="radio-inline">
                  <input type="radio" name="progressFl" value="scheduled" <?= $searchParams['progressFl'] == 'scheduled' ? 'checked' : '' ?>>진행예정
                </label>
                <label class="radio-inline">
                  <input type="radio" name="progressFl" value="proceeding" <?= $searchParams['progressFl'] == 'proceeding' ? 'checked' : '' ?>>진행 중
                </label>
                <label class="radio-inline">
                  <input type="radio" name="progressFl" value="end" <?= $searchParams['progressFl'] == 'end' ? 'checked' : '' ?>>종료
                </label>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="table-btn">
      <input type="submit" value="검색" class="btn btn-lg btn-black">
    </div>

    <div class="table-header">
      <div class="pull-left">
          검색
          <strong class="text-danger"><?= number_format($searchCount) ?></strong>개
          / 전체 <strong class="text-danger"><?= number_format($totalCount) ?></strong>개
      </div>
    </div>
  </form>

  <form id="frmList" action="./event_mileage_campaign_ps.php" method="post" target="ifrmProcess" novalidate="novalidate">
    <input type="hidden" name="mode" value="">

    <table class="table table-rows">
      <thead>
        <tr>
          <th class="width2p">
            <input type="checkbox" class="js-checkall" data-target-name="campaignSno">
          </th>
          <th class="width3p">캠페인등록일</th>
          <th class="width12p">캠페인명</th>
          <th class="width7p">캠페인코드</th>
          <th class="width5p">혜택금액</th>
          <th class="width5p">유효기간</th>
          <th class="width7p">지급방식</th>
          <th class="width5p">지급기간</th>
          <th class="width5p">진행상태</th>
          <th class="width5p">내역</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        // 진행상태 필터링
        $filteredList = [];
        if(!empty($campaignList)) {
          $statusMap = [
            'scheduled' => '진행예정',
            'proceeding' => '진행 중',
            'end' => '종료'
          ];
          foreach($campaignList as $campaign) {
            $progressStatus = getProgressStatus($campaign);
            if($searchParams['progressFl'] == 'all' || $progressStatus == ($statusMap[$searchParams['progressFl']] ?? '')) {
              $filteredList[] = $campaign;
            }
          }
        }
        ?>
        <?php if(!empty($filteredList)): ?>
          <?php foreach($filteredList as $campaign): ?>
            <?php 
              $progressStatus = getProgressStatus($campaign);
              $regDate = !empty($campaign['regDt']) ? date('Y-m-d', strtotime($campaign['regDt'])) : '';
              $payStartDate = !empty($campaign['payStartDate']) ? date('Y-m-d', strtotime($campaign['payStartDate'])) : '';
              $payEndDate = !empty($campaign['payEndDate']) ? date('Y-m-d', strtotime($campaign['payEndDate'])) : '';
              $expiryDateText = getExpiryDateText($campaign);
            ?>
            <tr>
              <td class="center">
                <input name="campaignSno[]" type="checkbox" value="<?= $campaign['sno'] ?>">
              </td>
              <td class="center"><?= htmlspecialchars($regDate) ?></td>
              <td class="center">
                <a href="./event_mileage_campaign_view.php?sno=<?= $campaign['sno'] ?>" style="text-decoration: underline;">
                  <?= htmlspecialchars($campaign['campaignNm']) ?>
                </a>
              </td>
              <td class="center"><?= htmlspecialchars($campaign['campaignCode'] ?? '') ?></td>
              <td class="center"><?= number_format($campaign['eventMileage'] ?? 0) ?></td>
              <td class="center"><?= htmlspecialchars($expiryDateText) ?></td>
              <td class="center"><?= htmlspecialchars(getPaymentFlText($campaign['paymentFl'] ?? '')) ?></td>
              <td class="center"><?= htmlspecialchars($payStartDate) ?> ~ <br><?= htmlspecialchars($payEndDate) ?></td>
              <td class="center"><?= htmlspecialchars($progressStatus) ?></td>
              <td class="center">
                <a href="./event_mileage_campaign_log.php?campaignSno=<?= $campaign['sno'] ?>" style="text-decoration: underline;">
                  지급내역
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="10" class="center empty_table">자료가 없습니다.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="table-action">
      <div class="pull-left">
        <button type="submit" class="btn btn-white">선택 삭제</button>
      </div>
    </div>

    <div class="center"></div>
  </form>
</div>

<script>
$(document).ready(function () {
  //루딕스-brown 자동캠페인 실행버튼
  $('.js-auto-pay').click(function(){
    $.ajax({
      method: 'POST',
      cache: false,
      url: '/member/event_mileage_campaign_list.php',
      async: true,
      data: {mode:"autoCampaignPay"},
      dataType: 'json',
      success: function (data) {
        if(data) {
          alert('자동캠페인 실행하였습니다.');
        }
      },
      error: function (data, text) {

      }
    });
  });

  $("#frmList").validate({
    dialog : false,
    submitHandler: function (form) {
      if (confirm('선택한 항목을 삭제하시겠습니까?\n삭제된 항목은 복구하실 수 없습니다.')) {
        $('#frmList input[name=\'mode\']').val('event_delete');
        form.submit();
      }
    },
    rules: {
      "campaignSno[]": 'required'
    },
    messages: {
      "campaignSno[]": '선택된 항목이 없습니다.'
    }
  });

  // 등록
  $('.js-register').click(function () {
    location.href = './event_mileage_campaign_register.php';
  });
  
  if ($('.js-dateperiod2').length) {
    $('.js-dateperiod2 label').click(function (e) {
      var $startDate = '',
      $endDate = '',
      $period = $(this).children('input[type="radio"]').val(),
      $elements = $('input[name*=\'' + $(this).closest('.js-dateperiod2').data('target-name') + '\']'),
      $inverse = $('input[name*=\'' + $(this).closest('.js-dateperiod2').data('target-inverse') + '\']'), 
      $format = 'YYYY-MM-DD';

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

      //전체
      if($(this).find('input').val() == ''){
        $startDate = '';
        $endDate = '';
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
    $.each($('.js-dateperiod2'), function (idx) {
      var $elements = $('input[name*=\'' + $(this).data('target-name') + '\']'),
      $format = 'YYYY-MM-DD';
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
          var $activeRadio = $this.find('label input[type="radio"][value="-1"]');
          if ($activeRadio.length < 1) {
            $activeRadio = $this.find('label input[type="radio"][value=""]');
          }
          $activeRadio.trigger('click');
        }
      }
    });
  }
});
</script>

<?php require_once './../footer.php'; ?>
