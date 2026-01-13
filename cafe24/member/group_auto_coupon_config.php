<?php
include("../../helpers/common_helper.php");
include("../../helpers/function_helper.php");

use Database\DB;
use Coupon\Coupon;
use Member\Member;

$db = new DB();

$coupon = new Coupon();
$couponInfo = $coupon->getCouponInfo();

$member = new Member();
$groupAutoCouponConfig = $member->getGroupAutoCouponConfig();

?>

<?php require_once './../header.php'; ?>

<div id="content" class="body">
	<form id="frmGoods" action="./member_ps.php" method="post" target="ifrmProcess" name="frmGoods" onsubmit="return frmGoods_submit(this);" enctype="multipart/form-data">
		<input type="hidden" name="mode" value="saveGroupAutoCouponConfig">
		<div class="table-title">
			<span>회원등급관리</span>

			<div class="submit" style="float:right; padding-right:20px;">	
				<!-- <button type="button" value="목록" class="btn btn-red" onclick="location.href='../../cafe24/goods/goods_list.php'">목록</button> -->
				<input type="submit" value="저장" class="btn btn-red">
			</div>
		</div>
		<table class="table table-cols">
			<colgroup>
				<col class="width-md">
				<col>
				<col>
				<col>
			</colgroup>
			<tbody>
				<tr>
					<th>등급 변경시 지급 쿠폰</th>
					<td colspan="3">
						<select name="couponNo" class="form-control" style="width:200px;">
						<option value="">쿠폰 선택</option>
							<?php foreach($couponInfo as $val): ?>
								<option value="<?= $val['coupon_no'] ?>" <?php echo $groupAutoCouponConfig['couponNo'] == $val['coupon_no'] ? 'selected' : ''; ?>><?= $val['coupon_name'] ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
</div>

<script>
    function frmGoods_submit(f) {
		//if(f.uploadFile.value) {
			 //if (!f.uploadFile.value.match(/\.(pdf)$/i)) {
				//alert('pdf파일만 업로드가능합니다.');
				//return false;
			//}
		//}

        return true;
    }
</script>

<?php require_once './../footer.php'; ?>
