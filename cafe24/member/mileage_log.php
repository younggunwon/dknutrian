<?php
header("Access-Control-Allow-Origin: *");

include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use Mileage\Mileage;

$mileageSvc = new Mileage();

// POST 액션 처리: 재전송 및 취소
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['do'] ?? '';
	$logId = (int)($_POST['id'] ?? 0);
	$result = null;

	if ($logId > 0) {
		if ($action === 'retry') {
			$result = $mileageSvc->retryMileageLog($logId);
		} else if ($action === 'cancel') {
			$result = $mileageSvc->cancelMileageLog($logId);
		}
	}

	// 결과 메시지 처리 (선택사항)
	if ($result && !$result['success']) {
		echo "<script>alert('".$result['message']."'); history.back();</script>";
		exit;
	}

	// 처리 후 목록으로
	echo "<script>location.href='mileage_log.php?".http_build_query($_GET)."';</script>";
	exit;
}

// 필터 파라미터 수집
$filters = [];
$action = $_GET['action'] ?? '';
$status = $_GET['status'] ?? '';
if ($action) $filters['action'] = $action;
if ($status) $filters['status'] = $status;

// 목록 조회 (모델 사용)
$rows = $mileageSvc->getMileageLogList($filters);

?>

<?php require_once './../header.php'; ?>

<div id="content" class="body">
	<div class="table-title">마일리지 지급/취소 로그</div>
	<form method="get" class="form-inline" style="margin-bottom:10px;">
		<select name="action" class="form-control">
			<option value="">전체 액션</option>
			<option value="award" <?= $action==='award'?'selected':'' ?>>지급</option>
			<option value="resend" <?= $action==='resend'?'selected':'' ?>>재전송</option>
			<option value="cancel" <?= $action==='cancel'?'selected':'' ?>>취소</option>
		</select>
		<select name="status" class="form-control">
			<option value="">전체 상태</option>
			<option value="success" <?= $status==='success'?'selected':'' ?>>성공</option>
			<option value="failed" <?= $status==='failed'?'selected':'' ?>>실패</option>
		</select>
		<button type="submit" class="btn btn-black">검색</button>
	</form>

	<div class="table-responsive">
		<table class="table table-rows">
			<thead>
				<tr>
					<th>ID</th>
					<th>일시</th>
					<th>주문번호</th>
					<th>회원ID</th>
					<th>포인트</th>
					<th>액션</th>
					<th>상태</th>
					<th>사유</th>
					<th>처리</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ((array)$rows as $r) { ?>
				<tr>
					<td class="center"><?= (int)$r['id'] ?></td>
					<td class="center"><?= htmlspecialchars($r['regDt']) ?></td>
					<td class="center"><?= htmlspecialchars($r['order_id']) ?></td>
					<td class="center"><?= htmlspecialchars($r['member_id']) ?></td>
					<td class="center"><?= number_format((float)$r['points'], 2) ?></td>
					<td class="center"><?= htmlspecialchars($r['action']) ?></td>
					<td class="center"><?= htmlspecialchars($r['status']) ?></td>
					<td class="center"><?= htmlspecialchars($r['reason']) ?></td>
					<td class="center">
						<?php if ($r['status'] === 'failed' && in_array($r['action'], ['award','resend'], true)) { ?>
							<form method="post" style="display:inline;" onsubmit="return confirm('재전송 하시겠습니까?');">
								<input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
								<input type="hidden" name="do" value="retry" />
								<button type="submit" class="btn btn-sm btn-black">재전송</button>
							</form>
						<?php } ?>
						<?php if ($r['status'] === 'success' && in_array($r['action'], ['award','resend'], true)) { ?>
							<form method="post" style="display:inline;" onsubmit="return confirm('지급 취소(차감) 하시겠습니까?');">
								<input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
								<input type="hidden" name="do" value="cancel" />
								<button type="submit" class="btn btn-sm btn-gray">취소</button>
							</form>
						<?php } ?>
					</td>
				</tr>
			<?php } ?>
			<?php if (empty($rows)) { ?>
				<tr><td colspan="10" class="center">로그가 없습니다.</td></tr>
			<?php } ?>
			</tbody>
		</table>
	</div>
</div>

<?php require_once './../footer.php'; ?>


