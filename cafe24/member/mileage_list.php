<?php
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use Database\DB;
use Page\Page;

$db = new DB();

// 필터 파라미터 수집
$status = $_GET['status'] ?? '';
$keyword = $_GET['keyword'] ?? '';
$key = $_GET['key'] ?? '';

// 페이징
$getValue['page'] = gd_isset($_GET['page'], 1);
if(!$_GET['pageNum']) {
	$_GET['pageNum'] = '50';
}
$pageNum = $_GET['pageNum'];
$page = new Page($getValue['page']);
$page->page['list'] = $pageNum;
$page->block['cnt'] = 5;
$page->setPage();
$page->setUrl($_SERVER['QUERY_STRING']);

$limit = 'LIMIT '.$page->recode['start'] . ',' . $pageNum;

// SQL 쿼리 구성
$where = [];
if ($keyword) {
	$keywordEscaped = addslashes($keyword);
	if ($key === '' || $key === 'all') {
		// 전체 선택 시 OR 조건으로 세 개 모두 검사
		$where[] = "(gbh.member_id LIKE '%{$keywordEscaped}%' OR gbh.order_id LIKE '%{$keywordEscaped}%' OR gbh.product_code LIKE '%{$keywordEscaped}%')";
	} else if ($key === 'member_id') {
		$where[] = "gbh.member_id LIKE '%{$keywordEscaped}%'";
	} else if ($key === 'order_id') {
		$where[] = "gbh.order_id LIKE '%{$keywordEscaped}%'";
	} else if ($key === 'product_code') {
		$where[] = "gbh.product_code LIKE '%{$keywordEscaped}%'";
	}
}
if ($status && in_array($status, ['success','failed'], true)) {
	$successFlag = ($status === 'success') ? 'y' : 'n';
	$where[] = "gbh.success_flag='".addslashes($successFlag)."'";
}

$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : 'WHERE 1=1';

// 검색된 레코드 수
$sql = "
	SELECT COUNT(*) as cnt
	FROM wg_goods_benefit_history gbh
	LEFT JOIN wg_orderGoods og ON og.order_id = gbh.order_id COLLATE utf8mb4_unicode_ci AND og.order_item_code = gbh.order_item_code COLLATE utf8mb4_unicode_ci
	{$whereSql}
";
$searchCnt = $db->query_fetch($sql)[0];
$page->recode['total'] = $searchCnt['cnt'];
$page->setPage();

// 전체 갯수
$sql = "
	SELECT COUNT(*) as cnt
	FROM wg_goods_benefit_history gbh
";
$totalCnt = $db->query_fetch($sql)[0];

// 목록 조회
$sql = "
	SELECT gbh.*, 
		og.product_name,
		og.quantity,
		og.product_price,
		og.option_price
	FROM wg_goods_benefit_history gbh
	LEFT JOIN wg_orderGoods og ON og.order_id = gbh.order_id COLLATE utf8mb4_unicode_ci AND og.order_item_code = gbh.order_item_code COLLATE utf8mb4_unicode_ci
	{$whereSql}
	ORDER BY gbh.sno DESC
	{$limit}
";
$rows = $db->query_fetch($sql);

$select['status'][$status] = 'selected';

?>

<?php require_once './../header.php'; ?>

<div id="content" class="body">
	<link type="text/css" href="../css/cafe24.css" rel="stylesheet"/>
	<form id="frmSearchMileage" name="frmSearchMileage" method="get" class="js-form-enter-submit">
		<div class="page-header">
			<div class="table-title">
				마일리지 지급 이력
			</div>
			<div style="float:right; padding-right:20px;">
				<button type="button" class="btn btn-lg btn-black" onclick="manualAwardMileage()">마일리지 수동 지급</button>
			</div>
		</div>
		<div class="search-detail-box">
			<table class="table table-cols">
				<colgroup>
					<col class="width-md"/>
					<col>
					<col class="width-md"/>
					<col/>
				</colgroup>
				<tbody>
				<tr>
					<th>검색</th>
					<td colspan="3">
						<div class="form-inline">
							<select name="key" class="form-control" style="width:150px;">
								<option value="all" <?= ($key === '' || $key === 'all') ? 'selected' : '' ?>>전체</option>
								<option value="member_id" <?= $key === 'member_id' ? 'selected' : '' ?>>회원아이디</option>
								<option value="order_id" <?= $key === 'order_id' ? 'selected' : '' ?>>주문번호</option>
								<option value="product_code" <?= $key === 'product_code' ? 'selected' : '' ?>>상품코드</option>
							</select>
							<input type="text" name="keyword" class="form-control" value="<?= htmlspecialchars($keyword) ?>" placeholder="검색어를 입력하세요" style="margin-left: 10px; width:300px;">
						</div>
					</td>
				</tr>
				<tr>
					<th>상태</th>
					<td colspan="3">
						<select name="status" class="form-control" style="width:150px;">
							<option value="">전체</option>
							<option value="success" <?= $select['status']['success'] ?? '' ?>>성공</option>
							<option value="failed" <?= $select['status']['failed'] ?? '' ?>>실패</option>
						</select>
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
				검색 <strong><?= $searchCnt['cnt'] ?></strong>개 /
				전체 <strong><?= $totalCnt['cnt'] ?></strong>개
			</div>
			<div class="pull-right">
				<select id="pageNum" name="pageNum">
					<option value="10" <?= $_GET['pageNum'] == 10 ? 'selected' : '' ?>>10 개씩 보기</option>
					<option value="30" <?= $_GET['pageNum'] == 30 ? 'selected' : '' ?>>30 개씩 보기</option>
					<option value="50" <?= $_GET['pageNum'] == 50 || !$_GET['pageNum'] ? 'selected' : '' ?>>50 개씩 보기</option>
					<option value="100" <?= $_GET['pageNum'] == 100 ? 'selected' : '' ?>>100 개씩 보기</option>
					<option value="500" <?= $_GET['pageNum'] == 500 ? 'selected' : '' ?>>500 개씩 보기</option>
				</select>
			</div>
		</div>
	</form>

	<div class="table-responsive">
		<table class="table table-rows">
			<thead>
				<tr>
					<th>ID</th>
					<th>일시</th>
					<th>주문번호</th>
					<th>주문상품코드</th>
					<th>회원ID</th>
					<th>상품코드</th>
					<th>상품명</th>
					<th>수량</th>
					<th>상품가</th>
					<th>옵션가</th>
					<th>포인트</th>
					<th>지급구분</th>
					<th>상태</th>
					<th>재적용</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ((array)$rows as $r) { ?>
				<tr>
					<td class="center"><?= (int)$r['sno'] ?></td>
					<td class="center"><?= htmlspecialchars($r['reg_date'] ?? '') ?></td>
					<td class="center"><?= htmlspecialchars($r['order_id'] ?? '') ?></td>
					<td class="center"><?= htmlspecialchars($r['order_item_code'] ?? '') ?></td>
					<td class="center"><?= htmlspecialchars($r['member_id'] ?? '') ?></td>
					<td class="center"><?= htmlspecialchars($r['product_code'] ?? '') ?></td>
					<td class="center"><?= htmlspecialchars($r['product_name'] ?? '') ?></td>
					<td class="center"><?= htmlspecialchars($r['quantity'] ?? '') ?></td>
					<td class="center"><?= $r['product_price'] ? number_format((float)$r['product_price'], 0) : '' ?></td>
					<td class="center"><?= $r['option_price'] ? number_format((float)$r['option_price'], 0) : '' ?></td>
					<td class="center"><?= number_format((float)$r['benefit_value'], 0) ?></td>
					<td class="center">
						<?php
						$benefitTypeText = '';
						switch($r['benefit_type']) {
							case 'point': $benefitTypeText = '적립금'; break;
							case 'shopping': $benefitTypeText = '쇼핑지원금'; break;
							case 'coupon': $benefitTypeText = '쿠폰'; break;
							default: $benefitTypeText = $r['benefit_type'];
						}
						echo htmlspecialchars($benefitTypeText);
						?>
					</td>
					<td class="center">
						<?php if ($r['success_flag'] === 'y') { ?>
							<span style="color:green;">성공</span>
						<?php } else { ?>
							<span style="color:red;">실패</span>
						<?php } ?>
					</td>
					<td class="center">
						<?php if ($r['success_flag'] === 'n') { ?>
							<button type="button" class="btn btn-sm btn-black" onclick="reapplyBenefit('<?= $r['sno'] ?>')">재적용</button>
						<?php } else { ?>
							-
						<?php } ?>
					</td>
				</tr>
			<?php } ?>
			<?php if (empty($rows)) { ?>
				<tr><td colspan="14" class="center empty_table">자료가 없습니다.</td></tr>
			<?php } ?>
			</tbody>
		</table>
	</div>

	<div class="center">
		<?= $page->getPage(); ?>
	</div>
</div>

<script>
$(document).ready(function () {
	$('#pageNum').change(function() {
		$('#frmSearchMileage').submit();
	});
});

function reapplyBenefit(sno) {
	if(confirm('재적용 하시겠습니까?')) {
		$.ajax({
			url: 'mileage_ps.php',
			type: 'POST',
			data: {
				mode: 'reapplyGoodsBenefit',
				sno: sno
			},
			dataType: 'json',
			success: function(response) {
				if(response.success) {
					alert(response.message || '재적용이 완료되었습니다.');
					location.reload();
				} else {
					alert(response.message || '재적용 중 오류가 발생했습니다.');
				}
			},
			error: function(xhr, status, error) {
				alert('요청 처리 중 오류가 발생했습니다. 관리자에게 문의하세요.');
				console.error('AJAX 오류: ' + error);
			}
		});
	}
}

function manualAwardMileage() {
	if(confirm('마일리지를 수동으로 지급하시겠습니까?\n\n이 작업은 시간이 걸릴 수 있습니다.')) {
		// 버튼 비활성화
		var btn = event.target;
		btn.disabled = true;
		btn.innerHTML = '처리 중...';
		
		$.ajax({
			url: '../../api/order.php?mode=awardProductMileage',
			type: 'GET',
			dataType: 'json',
			timeout: 300000, // 5분 타임아웃
			success: function(response) {
				btn.disabled = false;
				btn.innerHTML = '마일리지 수동 지급';
				
				if(response.success !== undefined) {
					var message = response.message || '마일리지 지급이 완료되었습니다.';
					if(response.total !== undefined) {
						message += '\n총 ' + response.total + '건 중 성공: ' + response.success + '건, 실패: ' + response.failed + '건';
					}
					alert(message);
					location.reload();
				} else {
					alert('마일리지 지급이 완료되었습니다.');
					location.reload();
				}
			},
			error: function(xhr, status, error) {
				btn.disabled = false;
				btn.innerHTML = '마일리지 수동 지급';
				
				if(status === 'timeout') {
					alert('요청 시간이 초과되었습니다. 잠시 후 목록을 새로고침하여 결과를 확인해주세요.');
				} else {
					alert('마일리지 지급 중 오류가 발생했습니다. 관리자에게 문의하세요.');
					console.error('AJAX 오류: ' + error);
				}
			}
		});
	}
}
</script>

<?php require_once './../footer.php'; ?>
