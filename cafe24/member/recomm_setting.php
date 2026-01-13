<?php
header("Access-Control-Allow-Origin: *");

include_once('../../common.php');
include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use Database\DB;
use Page\Page;
use Member\Member;

//session_start();
//if($_SESSION['cafe24adminFl'] != 'y') {
//	if($_SERVER['HTTP_REFERER'] == 'https://medicals709.cafe24.com/') {
//		$_SESSION['cafe24adminFl'] = 'y';
//	} else {
//		echo '잘못된 접근입니다. cafe24 관리자 페이지 > 앱 > 관리하기 버튼을 통해 접속해주세요.';
//		exit;
//	}
//}

$member = new Member();
$db = new DB();

$data = $member->getRecommSetting();

?>
<style>
.body {padding: 20px;}
.search-detail-box {width: 900px;}
.btn_center_box {text-align: center;}

.update_board_btn {
    height: 38px;
    padding: 0 20px 0 20px;
    font-size: 14px;
    font-weight: bold;
    background: 0;
    border: 1px solid #CA1717;
    color: #CA1717;
}
</style>

<div class="body">
	<link type="text/css" href="../css/cafe24.css" rel="stylesheet"/>
	<form id="frmReward" name="frmReward" method="GET" class="js-form-enter-submit" action="" target="ifrmProcess">
		<input name="mode" type="hidden" value="">
		<div style="text-align:right; width:900px;">
			<button type="submit" class="update_board_btn recomm_btn">
				추천인 적립금 버튼
			</button>
			<button type="submit" class="update_board_btn pay_board_reward">
				게시글 리워드 버튼
			</button>
		</div>
	</form>
	<form id="frmRecommSetting" name="frmRecommSetting" method="GET" class="js-form-enter-submit" action="./member_ps.php">
		<input type="hidden" name="mode" value="saveRecommSetting" />

		<div class="table-title">
			추천인/적립금 설정
		</div>
		<div class="search-detail-box">
			<table class="table table-cols" style="width: 900px;">
				<colgroup>
					<col class="width-md">
					<col/>
				</colgroup>

				<tbody>
				<!-- 리워드 절사 -->
				<tr>
					<th scope="row">리워드 절사</th>
					<td>
						<div class="form-inline">
							적립금을 %단위로 입력하는 경우에
							<select name="unitPrecision" class="form-control">                                             
								<option value="1" <?php if($data['unitPrecision'] == '1') {echo 'selected';} ?>>1</option> 
								<option value="10" <?php if($data['unitPrecision'] == '10') {echo 'selected';} ?>>10</option>
								<option value="100" <?php if($data['unitPrecision'] == '100') {echo 'selected';} ?>>100</option>
								<option value="1000" <?php if($data['unitPrecision'] == '1000') {echo 'selected';} ?>>1000</option>
							</select>
							<span id="set_mileage_type_use">
							원 단위에서
							<select name="unitRound" class="form-control">
								<option value="A" <?php if($data['unitRound'] == 'A') {echo 'selected';} ?>>내림</option>
								<option value="B" <?php if($data['unitRound'] == 'B') {echo 'selected';} ?>>반올림</option>
								<option value="C" <?php if($data['unitRound'] == 'C') {echo 'selected';} ?>>올림</option>
							</select>
							하여 지급
							</span>
						</div>
					</td>
				</tr>
				</tbody>
			</table>

			<div class="table-title">
				월간 결제 금액별 커미션 설정
			</div>
			<table class="table table-cols" style="width: 900px;">
				<colgroup>
					<col class="width-md">
					<col/>
				</colgroup>

				<tbody>
				<tr>
					<th>사용여부</th>
					<td>
						<div class="form-inline">
							<label><input type="radio" name="useFl" value="y" <?php if($data['useFl'] == 'y') echo 'checked'; ?>/>사용함</label> &nbsp;
							<label><input type="radio" name="useFl" value="n" <?php if($data['useFl'] == 'n') echo 'checked'; ?>/>사용안함</label>
							
						</div>
					</td>
				</tr>
				</tbody>
			</table>

			<div class="table-title">
				일반 월간 결제 금액별 커미션
			</div>
			<table id="normalCommissionTable" class="table table-cols" style="width: 900px;">
				<colgroup>
					<col class="width-xl2"/>
					<!-- <col class="width-md"> -->
					<col class="width-md"/>
					<col class="width-xl"/>
					<col class="width-2xs">
				</colgroup>
				<thead>
					<tr>
						<th>금액 구간</th>
						<!-- <th>혜택 대상</th> -->
						<th>커미션 비율 (%)</th>
						<th>커미션 지급한도</th>
						<th>추가/삭제</th>
					</tr>
				</thead>

				<?php foreach($data['normal'] as $key => $val) { ?>
				<tbody>
					<tr>
						<td>
							<div class="form-inline">
								<input type="text" class="form-control width-sm" name="priceRangeStart[normal][]" value="<?= $val['priceRangeStart'] ?>"/> 만 이상 ~ <input type="text" class="form-control width-sm" name="priceRangeEnd[normal][]" value="<?= $val['priceRangeEnd'] ?>"/> 만 미만
							</div>
						</td>
						<!-- <td class="text-center">일반</td> -->
						<td class="text-center">
							<div class="form-inline">
								<input type="number" class="form-control width-2xs" name="commissionRate[normal][]" min="0" step="0.1" value="<?= $val['commissionRate'] ?>" > %
							</div>
						</td>
						<td class="text-center">
							<div class="form-inline">
								<select name="limitPeriodMonth[normal][]" class="form-control">
									<option value="1" <?php if($val['limitPeriodMonth'] == 1) {echo 'selected';} ?>>1</option>
									<option value="2" <?php if($val['limitPeriodMonth'] == 2) {echo 'selected';} ?>>2</option>
									<option value="3" <?php if($val['limitPeriodMonth'] == 3 || !$val['limitPeriodMonth']) {echo 'selected';} ?>>3</option>
									<option value="6" <?php if($val['limitPeriodMonth'] == 6) {echo 'selected';} ?>>6</option>
									<option value="12" <?php if($val['limitPeriodMonth'] == 12) {echo 'selected';} ?>>12</option>
								</select> 개월 기준 
								<input type="number" class="form-control  width-sm" name="commissionLimit[normal][]" value="<?= $val['commissionLimit'] ?>"> 원
							</div>
						</td>

						<?php if($key == 0) { ?>
						<td rowspan="3" class="text-center">
							<button type="button" class="btn btn-sm btn-white btn-icon-plus js-commission-add">추가</button>
						</td>
						<?php } else { ?>
						<td rowspan="3" class="text-center">
							<button type="button" class="btn btn-sm btn-white btn-icon-plus js-commission-remove">삭제</button>
						</td>
						<?php } ?>
					</tr>

				</tbody>
				<?php } ?>
			</table>

			<div class="table-title">
				사업자 월간 결제 금액별 커미션
			</div>
			<table id="businessCommissionTable" class="table table-cols" style="width: 900px;">
				<colgroup>
					<col class="width-xl2"/>
					<!-- <col class="width-md"> -->
					<col class="width-md"/>
					<col class="width-xl"/>
					<col class="width-2xs">
				</colgroup>
				<thead>
					<tr>
						<th>금액 구간</th>
						<!-- <th>혜택 대상</th> -->
						<th>커미션 비율 (%)</th>
						<th>커미션 지급한도</th>
						<th>추가/삭제</th>
					</tr>
				</thead>

				<?php foreach($data['business'] as $key => $val) { ?>
				<tbody>
					<tr>
						<td>
							<div class="form-inline">
								<input type="text" class="form-control width-sm" name="priceRangeStart[business][]" value="<?= $val['priceRangeStart'] ?>"/> 만 이상 ~ <input type="text" class="form-control width-sm" name="priceRangeEnd[business][]" value="<?= $val['priceRangeEnd'] ?>"/> 만 미만
							</div>
						</td>
						<!-- <td class="text-center">사업자</td> -->
						<td class="text-center">
							<div class="form-inline">
								<input type="number" class="form-control width-2xs" name="commissionRate[business][]" min="0" step="0.1" value="<?= $val['commissionRate'] ?>" > %
							</div>
						</td>
						<td class="text-center">
							<div class="form-inline">
								<select name="limitPeriodMonth[business][]" class="form-control">
									<option value="1" <?php if($val['limitPeriodMonth'] == 1) {echo 'selected';} ?>>1</option>
									<option value="2" <?php if($val['limitPeriodMonth'] == 2) {echo 'selected';} ?>>2</option>
									<option value="3" <?php if($val['limitPeriodMonth'] == 3 || !$val['limitPeriodMonth']) {echo 'selected';} ?>>3</option>
									<option value="6" <?php if($val['limitPeriodMonth'] == 6) {echo 'selected';} ?>>6</option>
									<option value="12" <?php if($val['limitPeriodMonth'] == 12) {echo 'selected';} ?>>12</option>
								</select> 개월 기준 
								<input type="number" class="form-control  width-sm" name="commissionLimit[business][]" value="<?= $val['commissionLimit'] ?>"> 원
							</div>
						</td>

						<?php if($key == 0) { ?>
						<td rowspan="3" class="text-center">
							<button type="button" class="btn btn-sm btn-white btn-icon-plus js-commission-add">추가</button>
						</td>
						<?php } else { ?>
						<td rowspan="3" class="text-center">
							<button type="button" class="btn btn-sm btn-white btn-icon-plus js-commission-remove">삭제</button>
						</td>
						<?php } ?>
					</tr>

				</tbody>
				<?php } ?>
			</table>

			<div class="table-title">
				게시글 리워드
			</div>
			<table id="" class="table table-cols" style="width: 900px;">
				<colgroup>
					<col class="width-md">
					<col>
				</colgroup>
				<tr>
					<th>사용여부</th>
					<td>
						<div class="form-inline">
							<label><input type="radio" name="boardRewardUseFl" value="y" <?php if($data['boardRewardUseFl'] == 'y') echo 'checked'; ?>/>사용함</label> &nbsp;
							<label><input type="radio" name="boardRewardUseFl" value="n" <?php if($data['boardRewardUseFl'] == 'n') echo 'checked'; ?>/>사용안함</label>
						</div>
					</td>
				</tr>
				<tr>
					<th>적립금 지급 비율</th>
					<td>
						<div class="form-inline">
							<input type="number" class="form-control width-2xs" name="boardRewardRate" min="0" step="0.1" value="<?= $data['boardRewardRate'] ?>" > %
						</div>
					</td>
				</tr>
				<tr>
					<th>리워드 적용 회원등급</th>
					<td>
						<div class="form-inline">
							<?php foreach($data['customergroups'] as $key => $val) { ?>
								<label><input type="checkbox" name="boardRewardGroupNo[]" value="<?=$val['group_no']?>" <?php if(in_array($val['group_no'], $data['boardRewardGroupNo'])) { echo 'checked'; } ?> /><?=$val['group_name']?></label> &nbsp;
							<?php } ?>
						</div>
					</td>
				</tr>
				<tr>
					<th>지급 한도</th>
					<td class="">
						<div class="form-inline">
							<select name="boardRewardlimitPeriodMonth" class="form-control">
								<option value="1" <?php if($data['boardRewardlimitPeriodMonth'] == 1) {echo 'selected';} ?>>1</option>
								<option value="2" <?php if($data['boardRewardlimitPeriodMonth'] == 2) {echo 'selected';} ?>>2</option>
								<option value="3" <?php if($data['boardRewardlimitPeriodMonth'] == 3 || !$data['boardRewardlimitPeriodMonth']) {echo 'selected';} ?>>3</option>
								<option value="6" <?php if($data['boardRewardlimitPeriodMonth'] == 6) {echo 'selected';} ?>>6</option>
								<option value="12" <?php if($data['boardRewardlimitPeriodMonth'] == 12) {echo 'selected';} ?>>12</option>
							</select> 개월 기준 
							<input type="number" class="form-control  width-sm" name="boardRewardCommissionLimit" value="<?= $data['boardRewardCommissionLimit'] ?>"> 원
						</div>
					</td>
				</tr>
				<tr>
					<th>지급 조건</th>
					<td>
						<div class="form-inline">
						<ul class="mForm typeVer">
							<li>
								<span class="gWidth">
									<span class="icoDot"> </span> 주문상태 :
								</span>
								<select name="boardRewardStatus" id="eMileageStandard" class="form-control" data-before_value="C">
									<option value="C" <?php if($data['boardRewardStatus'] == 'C') {echo 'selected';} ?>>배송완료 후</option>
									<option value="P" <?php if($data['boardRewardStatus'] == 'P') {echo 'selected';} ?>>구매확정 후</option>
								</select>                                        
							</li>
							<li>
								<span class="gWidth">
									<span class="icoDot"></span> 적립시점 :
								</span>
								<select name="boardRewardPeriod" id="eMileagePeriod" class="form-control">
									<option value="0" <?php if($data['boardRewardPeriod'] == '0') {echo 'selected';} ?>>당일</option>
									<option value="1" <?php if($data['boardRewardPeriod'] == '1') {echo 'selected';} ?>>익일</option>
									<option value="3" <?php if($data['boardRewardPeriod'] == '3') {echo 'selected';} ?>>3일후</option>
									<option value="7" <?php if($data['boardRewardPeriod'] == '7') {echo 'selected';} ?>>7일후</option>
									<option value="14" <?php if($data['boardRewardPeriod'] == '14') {echo 'selected';} ?>>14일후</option>
									<option value="20" <?php if($data['boardRewardPeriod'] == '20') {echo 'selected';} ?>>20일후</option>
								</select>                                        
							</li>
						</ul>
						</div>
					</td>
				</tr>
			</table>
			
			<div class="btn_center_box">
				<button type="submit" class="btn btn-red ">저장</button>
			</div>
		</div>
	</form>
</div>

<script id="normalCommissionRowTemplate" type="text/html">
	<tbody>
		<tr>
			<td>
				<div class="form-inline">
					<input type="text" class="form-control width-sm" name="priceRangeStart[normal][]" value=""/> 만 이상 ~ <input type="text" class="form-control width-sm" name="priceRangeEnd[normal][]" value=""/> 만 미만
				</div>
			</td>
			<!--<td class="text-center">일반</td>-->
			<td class="text-center">
				<div class="form-inline">
					<input type="number" class="form-control width-2xs" name="commissionRate[normal][]" min="0" step="0.1" value="" > %
				</div>
			</td>
			<td class="text-center">
				<div class="form-inline">
					<select name="limitPeriodMonth[normal][]" class="form-control">
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3" selected>3</option>
						<option value="6">6</option>
						<option value="12">12</option>
					</select> 개월 기준 
					<input type="number" class="form-control width-sm" name="commissionLimit[normal][]" value=""> 원
				</div>
			</td>
			<td rowspan="3" class="text-center">
				<button type="button" class="btn btn-sm btn-white btn-icon-plus js-commission-remove">삭제</button>
			</td>
		</tr>
	</tbody>
</script>
<script id="businessCommissionRowTemplate" type="text/html">
	<tbody>
		<tr>
			<td>
				<div class="form-inline">
					<input type="text" class="form-control width-sm" name="priceRangeStart[business][]" value=""/> 만 이상 ~ <input type="text" class="form-control width-sm" name="priceRangeEnd[business][]" value=""/> 만 미만
				</div>
			</td>
			<!--<td class="text-center">사업자</td>-->
			<td class="text-center">
				<div class="form-inline">
					<input type="number" class="form-control width-2xs" name="commissionRate[business][]" min="0" step="0.1" value="" > %
				</div>
			</td>
			<td class="text-center">
				<div class="form-inline">
					<select name="limitPeriodMonth[business][]" class="form-control">
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3" selected>3</option>
						<option value="6">6</option>
						<option value="12">12</option>
					</select> 개월 기준 
					<input type="number" class="form-control  width-sm" name="commissionLimit[business][]" value=""> 원
				</div>
			</td>
			<td rowspan="3" class="text-center">
				<button type="button" class="btn btn-sm btn-white btn-icon-plus js-commission-remove">삭제</button>
			</td>
		</tr>
	</tbody>
</script>

<!-- <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script> -->
<!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script> -->
<!-- <script src="../cafe24/js/bootstrap-dialog.js"></script> -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/underscore.js/1.13.6/underscore-min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js" integrity="sha512-rstIgDs0xPgmG6RX1Aba4KV5cWJbAMcvRCVmglpam9SoHZiUCyQVDdH2LPlxoHtrv17XWblE/V/PP+Tr04hbtA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
	$(document).ready(function () {
		$('#frmRecommSetting').validate({
			submitHandler: function (form) {
				form.submit();
			},
		});

		$(document).on('click', '#normalCommissionTable .js-commission-add', function() {
			var compiled = _.template($('#normalCommissionRowTemplate').html());
            $('#normalCommissionTable').append(compiled);

		});
		$(document).on('click', '#businessCommissionTable .js-commission-add', function() {
			var compiled = _.template($('#businessCommissionRowTemplate').html());
            //$('#codeListTbl tbody').append(compiled({num: num, itemCd: itemCd, itemCdNumber: itemCdNumber}));

            $('#businessCommissionTable').append(compiled);

		});
		$(document).on('click', '.js-commission-remove', function() {
			$(this).closest('tbody').remove();
		});
	});
</script>
<script>
$('#frmRecommSetting').validate({
			submitHandler: function (form) {
				form.submit();
			},
		});
$(document).ready(function() {
	var clickedButton = null; // 클릭된 버튼을 저장할 변수

    // 버튼 클릭 시 해당 버튼을 저장
    $('#frmReward button[type="submit"]').on('click', function(e) {
        clickedButton = $(this); // 어떤 버튼이 눌렸는지 저장
    });

    $('#frmReward').validate({
		submitHandler: function (form) {
			var msg = '연동이 완료되었습니다.';
			if (clickedButton) {
                if (clickedButton.hasClass('recomm_btn')) {
                    $('#frmReward input[name="mode"]').val('update');
					$('#frmReward input[name="wgMode"]').val('update');
					$('#frmReward').attr('action', '/api/order.php');
					msg = '추천인 적립금 연동이 완료되었습니다.';
                }
                if (clickedButton.hasClass('pay_board_reward')) {
                    $('#frmReward input[name="mode"]').val('boardReward');
					$('#frmReward input[name="wgMode"]').val('boardReward');
					$('#frmReward').attr('action', '/api/member.php');
					msg = '게시글 리워드 연동이 완료되었습니다.';
                }
            }

			form.submit();
			setTimeout(function() {
				alert(msg);
				location.reload(); // 폼 제출 후 새로고침
			}, 500); 
		}
	});

});
</script>
<?php require_once './../footer.php'; ?>