<?php
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use EventMileage\EventMileage;

$campaign = new EventMileage();
$campaignData = null;
$sno = !empty($_GET['sno']) ? intval($_GET['sno']) : 0;

if($sno > 0) {
	$campaignData = $campaign->getCampaign($sno);
}

?>

<?php require_once './../header.php'; ?>

<style>
.expiry_div{
	margin-top:10px;
}
.btn_box{
	text-align:center;
	margin-top:40px;
}
.btn_box input {
	width:70px;
}
.form-control{
	display:inline-block;
}
table td .red_text{
	color:red;
	margin-left:20px;
}
</style>
<div id="content" class="body">
  <div class="page-header js-affix">
  	<h3>쇼핑지원금 캠페인 <?= $sno > 0 ? '수정' : '등록' ?></h3>
  </div>

  <form id="frmCampaignRegist" action="./event_mileage_campaign_ps.php" method="post" novalidate="novalidate" target="ifrmProcess">
  	<input type="hidden" name="mode" value="campaignRegister">
  	<?php if($sno > 0): ?>
  	<input type="hidden" name="sno" value="<?= $sno ?>">
  	<?php endif; ?>
  	<div class="table-title">혜택 정보</div>
  	<table class="table table-cols">
  		<colgroup>
              <col class="width-sm" style="width: 159px !important; ">
              <col class="width-3xl">
              <col class="width-sm">
              <col class="">
          </colgroup>
  		
  		<tbody>
  			<tr>
  				<th class="require">캠페인명</th>
  				<td colspan="3"><input type="text" name="campaignNm" value="<?= htmlspecialchars($campaignData['campaignNm'] ?? '') ?>" class="form-control width-2xl"><span class="red_text">* 마이페이지&gt;쇼핑지원금 항에 표시됨</span></td>
  			</tr>
  			<tr>
  				<th class="require">캠페인 설명</th>
  				<td colspan="3"><input type="text" name="campaignDes" value="<?= htmlspecialchars($campaignData['campaignDes'] ?? '') ?>" class="form-control width-3xl"><span class="red_text">* 사용자에게 보이지 않습니다.</span></td>
  			</tr>
  			<tr>
  				<th>캠페인 코드</th>
  				<td colspan="3"><?= $sno > 0 && !empty($campaignData['campaignCode']) ? htmlspecialchars($campaignData['campaignCode']) : '등록시 자동생성' ?></td>
  			</tr>
  			<tr>
  				<th class="require">혜택금액</th>
  				<td><input type="text" name="eventMileage" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" value="<?= htmlspecialchars($campaignData['eventMileage'] ?? '') ?>" class="form-control width-sm" style="display:inline-block;"> <span>원</span></td>

  				<th class="require">혜택사용율</th>
  				<td>구매금액의 <input type="input" name="mileageLimit" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');" value="<?= htmlspecialchars($campaignData['mileageLimit'] ?? '') ?>" class="form-control width-sm" style="display:inline-block;"> <span>% 사용</span> <span class="red_text">* 주문서의 총 판매가 합계 기준.</span></td>
  			</tr>
  			<tr>
  				<th class="require">유효기간</th>
  				<td colspan="3">
  					<div class="form-inline expiry_div">
  						<label class="radio-inline">
  							<input type="radio" name="expiryDaysFl" value="y" <?= (!$campaignData || $campaignData['expiryDaysFl'] == 'y') ? 'checked="checked"' : '' ?>>
  							기간선택
  							<div class="input-group js-datepicker">
  								<input type="text" name="expiryStartDate" value="<?= htmlspecialchars($campaignData['expiryStartDate'] ?? '') ?>" class="form-control width-md" placeholder="">
  								<span class="input-group-addon">
  									<span class="btn-icon-calendar">
  									</span>
  								</span>
  							</div>
  							~
  							<div class="input-group js-datepicker">
  								<input type="text" name="expiryDate" value="<?= htmlspecialchars($campaignData['expiryDate'] ?? '') ?>" class="form-control width-md" placeholder="">
  								<span class="input-group-addon">
  									<span class="btn-icon-calendar">
  									</span>
  								</span>
  							</div>
  						</label>
  					</div>
  					<div class="form-inline expiry_div">
  						<label class="radio-inline">
  							<input type="radio" name="expiryDaysFl" value="dayDate" <?= ($campaignData && $campaignData['expiryDaysFl'] == 'dayDate') ? 'checked="checked"' : '' ?>>
  							발급일로부터
  							<input type="text" name="expiryDays" value="<?= htmlspecialchars($campaignData['expiryDays'] ?? '') ?>" class="form-control width-md" placeholder="" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');">
  							일까지<br>
  						</label>
  					</div>
  					<div class="form-inline expiry_div">
  						<label class="radio-inline">
  							<input type="radio" name="expiryDaysFl" value="monthEnd" <?= ($campaignData && $campaignData['expiryDaysFl'] == 'monthEnd') ? 'checked="checked"' : '' ?>>
  							발급월말 말일까지
  						</label>
  						<label class="radio-inline">
  							<input type="radio" name="expiryDaysFl" value="yearEnd" <?= ($campaignData && $campaignData['expiryDaysFl'] == 'yearEnd') ? 'checked="checked"' : '' ?>>
  							발급년도 말일까지
  						</label>
  					</div>
  				</td>
  			</tr>
  			<tr>
  				<th class="require">지급기간</th>
  				<td colspan="3">
  					<div class="form-inline">
  						<div class="input-group js-datepicker">
  							<input type="text" name="payStartDate" value="<?= htmlspecialchars($campaignData['payStartDate'] ?? '') ?>" class="form-control width-md" placeholder="">
  							<span class="input-group-addon">
  								<span class="btn-icon-calendar">
  								</span>
  							</span>
  						</div>
  						~
  						<div class="input-group js-datepicker">
  							<input type="text" name="payEndDate" value="<?= htmlspecialchars($campaignData['payEndDate'] ?? '') ?>" class="form-control width-md" placeholder="">
  							<span class="input-group-addon">
  								<span class="btn-icon-calendar">
  								</span>
  							</span>
  						</div>
  					</div>
  				</td>
  			</tr>
  			<tr>
  				<th class="require">지급방식</th>
  				<td colspan="3">
  					<div class="form-inline">
  						<label class="radio-inline">
  							<input type="radio" name="paymentFl" value="passivity" <?= ($campaignData && $campaignData['paymentFl'] == 'passivity') ? 'checked="checked"' : '' ?>>
  							수동발급
  						</label>
  						<label class="radio-inline">
  							<input type="radio" name="paymentFl" value="excelUpload" <?= ($campaignData && $campaignData['paymentFl'] == 'excelUpload') ? 'checked="checked"' : '' ?>>
  							엑셀업로드
  						</label>
  						<label class="radio-inline">
  							<input type="radio" name="paymentFl" value="auto" <?= ($campaignData && $campaignData['paymentFl'] == 'auto') ? 'checked="checked"' : '' ?>>
  							자동발급
  						</label>
  						<label class="radio-inline">
  							<input type="radio" name="paymentFl" value="down" <?= ($campaignData && $campaignData['paymentFl'] == 'down') ? 'checked="checked"' : '' ?>>
  							회원이 직접발급
  						</label>
  						<span class="red_text">* 수동발급/자동발급인 경우 캠페인 신규 등록 이후 대상자를 선택하고 발급함.</span>
  					</div>
  				</td>
  			</tr>

  			<tr>
  				<th>중복지급제한</th>
  				<td colspan="3">
  					<dl class="dl-horizontal">
  						<dt style="width:230px;">같은 아이디에 대해 같은 쇼핑지원금을</dt>
  						<dd style="margin-left:230px;">
  							<div class="radio mgt0">
  								<label class="radio-inline">
  									<input type="radio" name="memberAlwaysExceptFl" value="y" <?= ($campaignData && $campaignData['memberAlwaysExceptFl'] == 'y') ? 'checked="checked"' : '' ?>>재지급하지 않음 (유효기간 만료일 달라도 지급 제외)
  								</label>
  							</div>
  							<div class="radio mgb0 form-inline" style="position: relative;">
  								<label class="radio-inline">
  									<input type="radio" name="memberAlwaysExceptFl" value="n" <?= (!$campaignData || $campaignData['memberAlwaysExceptFl'] == 'n') ? 'checked="checked"' : '' ?>>재지급함
  								</label>
  								<label class="checkbox-inline mgl10">
  									<input type="checkbox" name="memberAlwaysExceptLimitType" value="y" <?= ($campaignData && $campaignData['memberAlwaysExceptLimitType'] == 'y') ? 'checked="checked"' : '' ?>>최대
  								</label>
  								<input type="text" name="memberAlwaysExceptLimit" value="<?= htmlspecialchars($campaignData['memberAlwaysExceptLimit'] ?? '') ?>" class="form-control js-number-only width-xs" maxlength="8">번
  							</div>
  						</dd>
  					</dl>
  				</td>
  			</tr>
        
  		</tbody>
  	</table>

  	<div class="btn_box">
  		<input type="button" value="<?= $sno > 0 ? '수정' : '등록' ?>" class="btn btn-register btn-gray">
  		<input type="button" value="취소" class="btn btn-white" onclick="parent.location.href='./event_mileage_campaign_list.php';" style="margin-left:10px;">
  	</div>
  </form>
</div>

<script type="text/javascript">
$(document).ready(function () {
	$('#frmCampaignRegist').validate({
		submitHandler: function(form) {
			//회원이 직접발급일때 발급일로부터 사용가능 선택불가
			if($('input[name="paymentFl"]:checked').val() == 'down' && $('input[name="expiryDaysFl"]:checked').val() == 'dayDate') {
				if(($('input[name="memberAlwaysExceptFl"]:checked').val() == 'n' && $('input[name="memberAlwaysExceptLimitType"]').is(":checked") && $('input[name="memberAlwaysExceptLimit"]').val() > 0) || ($('input[name="memberAlwaysExceptFl"]:checked').val() == 'y')) {

				} else {
					alert('회원이 직접발급하는 경우 지급기간동안 매일 계속 받을 수 있는 문제 발생함으로 다른 유효기간을 선택해주세요.');
					return false;
				}
			}

			//캠페인명 검사
			if(! $('input[name="campaignNm"]').val()) {
				alert('캠페인명을 입력해주세요.');
				return false;
			}

			//캠페인 설명 검사
			if(! $('input[name="campaignDes"]').val()) {
				alert('캠페인설명을 입력해주세요.');
				return false;
			}

			//유효기간검사
			var expiryDaysFl = $('input[name="expiryDaysFl"]:checked').val();
			
			// 기간선택(y)일 때: expiryStartDate와 expiryDate 필수
			if(expiryDaysFl == 'y') {
				if(!$('input[name="expiryStartDate"]').val() || !$('input[name="expiryDate"]').val()) {
					alert('기간선택의 시작일과 종료일을 입력해주세요.');
					return false;
				}
				if($('input[name="expiryStartDate"]').val() > $('input[name="expiryDate"]').val()) {
					alert('기간선택의 시작일과 종료일을 확인해주세요.');
					return false;
				}
			}
			
			// 발급일로부터(dayDate)일 때: expiryDays 필수
			if(expiryDaysFl == 'dayDate') {
				if(!$('input[name="expiryDays"]').val() || parseInt($('input[name="expiryDays"]').val()) <= 0) {
					alert('발급일로부터 일수를 입력해주세요.');
					return false;
				}
			}

			//혜택금액 검사
			if(! $('input[name="eventMileage"]').val()) {
				alert('혜택금액을 설정해주세요.');
				return false;
			}

			//혜택사용율 검사
			if(! $('input[name="mileageLimit"]').val()) {
				alert('혜택사용율을 설정해주세요.');
				return false;
			}

			//지급기간 검사
			if(! $('input[name="payStartDate"]').val() || ! $('input[name="payEndDate"]').val()) {
				alert('지급기간을 설정해주세요.');
				return false;
			}

			if($('input[name="payStartDate"]').val() > $('input[name="payEndDate"]').val()) {
				alert('지급기간을 확인해주세요.');
				return false;
			}

			//지급방식 검사
			if(!$('input[name="paymentFl"]:checked').val()) {
				alert('지급방식을 선택해주세요.');
				return false;
			}
      console.log(form);
			form.submit();
		}
	});

	$('.btn-register').click(function () {
		$("#frmCampaignRegist").submit();
	});

	// 유효기간 구분 변경 시 다른 필드 초기화
	$('input[name="expiryDaysFl"]').change(function(){
		var selectedExpiryDaysFl = $(this).val();
		
		if(selectedExpiryDaysFl == 'y') {
			// 기간선택 선택 시: expiryDays 초기화
			$('input[name="expiryDays"]').val('');
		} else if(selectedExpiryDaysFl == 'dayDate') {
			// 발급일로부터 선택 시: expiryStartDate, expiryDate 초기화
			$('input[name="expiryStartDate"]').val('');
			$('input[name="expiryDate"]').val('');
		} else {
			// 발급월말, 발급년도말 선택 시: expiryDays, expiryStartDate, expiryDate 초기화
			$('input[name="expiryDays"]').val('');
			$('input[name="expiryStartDate"]').val('');
			$('input[name="expiryDate"]').val('');
		}
	});

	$('input[name="paymentFl"]').change(function() {
		if($(this).val() == 'auto') {
			if($('input[name="expiryDaysFl"]:checked').val() == 'y') {
				$('input[name="expiryDaysFl"][value="dayDate"]').prop('checked', true);
			}
			$('input[name="expiryDaysFl"][value="y"]').closest('div').hide();
		} else {
			$('input[name="expiryDaysFl"][value="y"]').closest('div').show();
		}
	});
});

</script>

<?php require_once './../footer.php'; ?>
