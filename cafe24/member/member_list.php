<?php
//header("Access-Control-Allow-Origin: https://patra1985.cafe24.com");
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use Database\DB;
use Page\Page;
use Member\Member;

$member = new Member();
$memberListData = $member->getMemberList();

$select['searchDateFl'][$_GET['searchDateFl']] = 
$select['key'][$_GET['key']] =
$select['boardCategoryNo'][$_GET['boardCategoryNo']] = 'selected';

?>

<?php require_once './../header.php'; ?>

<div id="content" class="body">
	<form id="frmSearchMember" name="frmSearchMember" method="get" class="js-form-enter-submit">
		<input type="hidden" name="mode" value=""/>
		<input type="hidden" name="wgMode" value=""/>
		<div class="page-header">
			<div class="table-title gd-help-manual">
				회원 검색
			</div>
			<div class="btn-group">
				<button type="button" class="btn btn-red-line" onclick="location.href='/api/member.php?mode=update'">
					회원 수동 연동
				</button>
				<br>
				<br>
				<span style="color:red">* 30분에 한번씩 자동 연동</span>
			</div>
		</div>
		<div class="search-detail-box">
			<input type="hidden" name="detailSearch" value=""/>
			<input type="hidden" name="delFl" value=""/>
			<table class="table table-cols">
				<colgroup>
					<col class="width-md"/>
					<col>
					<col class="width-md"/>
					<col/>
				</colgroup>
				<tbody>
				<tr>
					<th>검색어</th>
					<td colspan="3" class="form-inline">
						<select class="form-control " id="key" name="key">
							<option value="" >전체</option>
							<option value="memId" <?=$select['key']['memId']?>>아이디</option>
							<option value="recommId" <?=$select['key']['recommId']?>>추천인 아이디</option>
							<option value="memNm" <?=$select['key']['memNm']?>>이름</option>
							<option value="recommNm" <?=$select['key']['recommNm']?>>추천인명</option>
						</select>
						<?php //gd_select_box('key', 'key', $search['combineSearch'], null, $search['key'], null); ?>
						<input type="text" name="keyword" value="<?= $_GET['keyword'] ?>" class="form-control"/>
	  
					</td>
				</tr>
				<!-- <tr> -->
					<!-- <th>카테고리</th> -->
					<!-- <td colspan="3" class="form-inline"> -->
						<!-- <select class="form-control " id="" name="boardCategoryNo"> -->
							<!-- <option value="" >전체</option> -->
							<!-- <option value="1" <?=$select['boardCategoryNo']['1']?>>의자</option> -->
							<!-- <option value="2" <?=$select['boardCategoryNo']['2']?>>테이블</option> -->
						<!-- </select> -->
					<!-- </td> -->
				<!-- </tr> -->
				<!-- <tr> -->
					<!-- <th>기간검색</th> -->
					<!-- <td colspan="3"> -->
						<!-- <div class="form-inline"> -->
							<!-- <select name="searchDateFl" class="form-control"> -->
								<!-- <option value="regDt" <?= $select['searchDateFl']['regDt'] ?>>등록일</option> -->
								<!-- <option value="modDt" <?= $select['searchDateFl']['modDt'] ?>>수정일</option> -->
							<!-- </select> -->

							<!-- <div class="input-group js-datepicker"> -->
								<!-- <input type="text" class="form-control width-xs" name="searchDate[]" value="<?=substr($search['searchDate'][0], 0, 10); ?>" /> -->
								<!-- <span class="input-group-addon"><span class="btn-icon-calendar"></span></span> -->
							<!-- </div> -->
							<!-- ~ -->
							<!-- <div class="input-group js-datepicker"> -->
								<!-- <input type="text" class="form-control width-xs" name="searchDate[]" value="<?=substr($search['searchDate'][1], 0, 10); ?>" /> -->
								<!-- <span class="input-group-addon"><span class="btn-icon-calendar"></span></span> -->
							<!-- </div> -->
							<!-- <?php // gd_search_date($search['searchPeriod']) ?> -->
						<!-- </div> -->
					<!-- </td> -->
				<!-- </tr> -->
				</tbody>
			</table>
		</div>

		<div class="table-btn">
			<input type="submit" value="검색" class="btn btn-lg btn-black">
		</div>

		<div class="table-header">
			<div class="pull-left">
				검색 <strong><?= $memberListData['searchCnt'] ?></strong>개 /
				전체 <strong><?= $memberListData['totalCnt'] ?></strong>개
			</div>
			<div class="pull-right">
				<!-- <button type="button" class="btn btn-white btn-icon-excel js-excel-download" data-target-form="frmSearchBase" style="background: url(../img/icon_excel_off.png) no-repeat 10px 50%; font-weight: normal;">
					엑셀다운로드
				</button> -->
				<select id="sort" name="sort">
					<option value="sno DESC" selected>등록순 ↓</option>
					<option value="sno ASC" <?= $_GET['sort'] == 'sno ASC' ? 'selected' : '' ?>>등록순 ↑</option>
					<option value="recommCnt DESC" <?= $_GET['sort'] == 'recommCnt DESC' ? 'selected' : '' ?>>추천인순 ↓</option>
					<option value="recommCnt ASC" <?= $_GET['sort'] == 'recommCnt ASC' ? 'selected' : '' ?>>추천인순 ↑</option>

				</select>
				<select id="pageNum" name="pageNum">
					<option value="10" <?= $_GET['pageNum'] == 10 ? 'selected' : '' ?>>10 개씩 보기</option>
					<option value="30" <?= $_GET['pageNum'] == 30 ? 'selected' : '' ?>>30 개씩 보기</option>
					<option value="50" <?= $_GET['pageNum'] == 50 ? 'selected' : '' ?>>50 개씩 보기</option>
					<option value="100" <?= $_GET['pageNum'] == 100 ? 'selected' : '' ?>>100 개씩 보기</option>
					<option value="500"<?= $_GET['pageNum'] == 500 ? 'selected' : '' ?>>500 개씩 보기</option>
				</select>
			</div>
		</div>
	</form>
	<form id="frmList" action="" method="get" target="ifrmProcess">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="modDtUse" value=""/>
		<div class="table-action" style="margin:0;">
			<div class="pull-left" style="width:100%; padding-top: 5px;">
				<!-- <button type="button" class="btn btn-white js-btn-display">노출함</button> -->
				<!-- <button type="button" class="btn btn-white js-btn-displaynone">노출 안함</button> -->
				<!-- <button type="button" class="btn btn-white js-btn-display2">영문몰 노출함</button> -->
				<!-- <button type="button" class="btn btn-white js-btn-displaynone2">영문몰 노출 안함</button> -->
			</div>
			
		</div>
		<div class="table-responsive">
			<table class="table table-rows">
				<thead>
					<tr>
						<!-- <th scope="col" rowspan="3"> -->
							<!-- <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)"> -->
						<!-- </th> -->
						<th style="min-width: 70px !important;">아이디</th>
						<th style="min-width: 70px !important;">이름</th>
						<th style="min-width: 70px !important;">추천인</th>
						<th style="min-width: 70px !important;">추천인명</th>
						<th style="min-width: 70px !important;">추천회원수</th>
					</tr>
				</thead>
				<tbody>

					<?php 
					foreach($memberListData['memberList'] as $row) {
					?>
					<tr>
					  <!-- <td class="center"> -->
						<!-- <input type="checkbox" name="product_no[]" value="<?= $row['product_no'] ?>"> -->
					  <!-- </td>	 -->
					  <td class="center">
					  	<?=$row['memId']?>
					  </td>
					  <td class="center">
					  	<?=$row['memNm']?>
					  </td>
					  <td class="center">
						<?=$row['recommId']?>
					  </td>
					  <td class="center">
						<?=$row['recommNm']?>
					  </td>
					  <td class="center">
						<?=$row['recommCnt']?>
					  </td>
				   </tr>
				   <?php }
					if (count($memberListData['memberList']) == 0) {
						echo '<tr id="empty_menu_list"><td colspan="5" class="empty_table center">자료가 없습니다.</td></tr>';
					}
					?>
			   </tbody>
			</table>
		</div>

		<div class="table-action">
			<div class="pull-left" style="width:100%; padding-top: 5px;">
				<!-- <button type="button" class="btn btn-white js-btn-display">노출함</button> -->
				<!-- <button type="button" class="btn btn-white js-btn-displaynone">노출 안함</button> -->
				<!-- <button type="button" class="btn btn-white js-btn-display2">영문몰 노출함</button> -->
				<!-- <button type="button" class="btn btn-white js-btn-displaynone2">영문몰 노출 안함</button> -->
			</div>
		</div>

		<div class="center">
			<?= $member->page->getPage(); ?>
		</div>
	</form>
</div>

<!-- <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script> -->

<!-- <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script> -->
<!-- <script src="../cafe24/js/bootstrap-dialog.js"></script> -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>

$('.js-register').click(function () {
	location.href = './board_register.php';
});
 <!--

$(document).ready(function () {
	$('.js-excel-download').click(function(){
		$('#frmSearchMember').attr('action', '../member/member_ps.php');
		$('#frmSearchMember').attr('target', 'ifrmProcess');
		$('input[name="wgMode"]').val('memberExcelDown');	
		$('#frmSearchMember').submit();
		
		$('#frmSearchMember').attr('action', '');
		$('#frmSearchMember').attr('target', '');
		$('input[name="wgMode"]').val('');
	});	

	$('.js-btn-group-dc-on').click(function() {
		var chkCnt = $('input[name*="product_no"]:checked').length;

		if (chkCnt == 0) {
			alert('선택된 회원이 없습니다.');
			return;
		}
		var isConfirmed = confirm(chkCnt+"개 회원을 등급할인 적용하시겠습니까?");
		if (isConfirmed) {
			$('#frmList input[name=\'mode\']').val('groupDcOn');
			$('#frmList').attr('method', 'post');
			$('#frmList').attr('action', './member_ps.php');
			$('#frmList').submit();
		}
	});

	$('.js-btn-group-dc-off').click(function() {
		var chkCnt = $('input[name*="product_no"]:checked').length;

		if (chkCnt == 0) {
			alert('선택된 회원이 없습니다.');
			return;
		}
		var isConfirmed = confirm(chkCnt+"개 회원을 등급할인 미적용하시겠습니까?");
		if (isConfirmed) {
			$('#frmList input[name=\'mode\']').val('groupDcOff');
			$('#frmList').attr('method', 'post');
			$('#frmList').attr('action', './member_ps.php');
			$('#frmList').submit();
		}
	});

	$('#pageNum').change(function() {
		$('#frmSearchMember').submit();
	});

	$('#sort').change(function() {
		$('#frmSearchMember').submit();
	});

	$('input[name="searchDate[]"], .btn-icon-calendar').click(function(){
		var index = $(this).closest('.js-datepicker').index() - 1;
		$('.js-datepicker:eq('+index+')').find('input[name="searchDate[]"]').datepicker({
			dateFormat: 'yy-mm-dd',	//날짜 포맷이다. 보통 yy-mm-dd 를 많이 사용하는것 같다.
			prevText: '이전 달',	// 마우스 오버시 이전달 텍스트
			nextText: '다음 달',	// 마우스 오버시 다음달 텍스트
			monthNames: ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'],	//한글 캘린더중 월 표시를 위한 부분
			monthNamesShort: ['1월', '2월', '3월', '4월', '5월', '6월', '7월', '8월', '9월', '10월', '11월', '12월'],	//한글 캘린더 중 월 표시를 위한 부분
			dayNames: ['일', '월', '화', '수', '목', '금', '토'],	//한글 캘린더 요일 표시 부분
			dayNamesShort: ['일', '월', '화', '수', '목', '금', '토'],	//한글 요일 표시 부분
			dayNamesMin: ['일', '월', '화', '수', '목', '금', '토'],	// 한글 요일 표시 부분
			showMonthAfterYear: true,	// true : 년 월  false : 월 년 순으로 보여줌
			yearSuffix: '년',	//
			showButtonPanel: true,	// 오늘로 가는 버튼과 달력 닫기 버튼 보기 옵션
		});
		 $('.js-datepicker:eq(' + index + ')').find('input[name="searchDate[]"]').datepicker('show');
	});
});

function check_all(f)
{
    var chk = document.getElementsByName("product_no[]");

    for (i=0; i<chk.length; i++)
        chk[i].checked = f.chkall.checked;
}


</script>

<?php require_once './../footer.php'; ?>