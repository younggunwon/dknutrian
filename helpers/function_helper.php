<?php
/**
 * @ purpose db connect
 * @ date 2019-02-12
 * @ author James
 */
if (!function_exists('db_connect')) {
	function db_connect()
	{
		$db_conn = mysqli_connect("localhost", DB_USER, DB_PASSWORD, DB_USER);
		if (mysqli_connect_errno()) {
			# 로그에 남기기
			//mysqli_connect_error();
		}
		return $db_conn;
	}
}

/**
 * PDO 연결 객체 반환
 */
if (!function_exists('pdo_connect')) {
	function pdo_connect()
	{
		try {
			$dsn = "mysql:host=localhost;port=3306;dbname=".DB_USER.";charset=utf8";
			$pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			//$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			return $pdo;
		} catch (PDOException $e) {
			# 로그에 남기기
			//$e->getMessage()
		}
	}
}

if (!function_exists('gd_debug'))
{
	function gd_debug($data)
	{
		if(in_array($_SERVER['REMOTE_ADDR'], ['58.124.137.93', '175.192.245.124']) || true) {
			// 출력 순서
			static $sequence = 1;
			// 데이터 출력
			$style = [
				'display:block',
				'font-family:Menlo, Courier New',
				'font-size:12px',
				'background:black',
				'color:white',
				'position:relative',
				'z-index:99999',
				'padding: 10px 3px 10px 40px',
				'border-bottom:1px dashed white',
				'word-break: break-all',
				'white-break: normal',
				'white-space: pre-wrap',
			];
			if (empty($data) !== false && $data !== 0) {
				$strData = '데이터가 없습니다.';
			} else {
				$strData = print_r($data, true);
			}
			$retData = '';
			if (strip_tags($strData) !== $strData) {
				$retData .= '<xmp style="' . implode(';', $style) . '">';
				$retData .= $strData;
				$retData .= '</xmp>';
			} else {
				$retData .= '<pre style="' . implode(';', $style) . '">';
				$retData .= '<strong style="color:rgb(0, 184, 214);position:absolute;top:10px;left:10px;">' . $sequence . '.</strong>';
				$retData .= trim(
					str_replace(
						[
							'Array',
							#'\'',
							' => ',
						],
						[
							'<b style="color:rgb(244, 229, 150)">Array</b>',
							#'`',
							chr(9) . '<b style="color:rgb(0, 128, 255)">=></b> ',
						],
						$strData
					)
				);
				$retData .= '</pre>';
			}

			$sequence++;
			echo $retData;

		}
	}
}

if (!function_exists('gd_isset'))
{
	function gd_isset(&$var, $value = null, $debug = false)
	{
		if (isset($var) === false) {
            $var = null;
        }
        if (($var === null || (is_string($var) && $var == '')) && $value !== null) {
            $var = $value;
        }
        if ($debug === true) {
            var_dump($var);
            var_dump($value);
        }
        return $var;
	}
}

if (!function_exists('gd_date_format'))
{
    /**
     * 날짜 변경
     * 현재 날짜를 원하는 format 형식으로 변경을 합니다.
     *
     * @param string $format   형식
     * @param string $thisDate 변경할 날짜
     *
     * @return string 변경된 날짜
     */
    function gd_date_format($format, $thisDate)
    {
        // 1970년 이전인경우 strtotime이 -값으로 나와 처리가 안됨
        if (strtotime($thisDate) < 0) {
            // 8자리이고 공백, -, :, 문자가없으면 8자리 생년월일로 간주하고 문자열 자름
            if (strlen($thisDate) == 8 && !preg_match('/\s|:|-/', $thisDate)) {
                if (strtolower($format) == 'y') {
                    return substr($thisDate, 0, 4);
                } elseif (strtolower($format) == 'm') {
                    return substr($thisDate, 4, 2);
                } elseif (strtolower($format) == 'd') {
                    return substr($thisDate, 6, 2);
                }
            } else {
                return '-';
            }
        // 32bit or 64bit 처리 방식이 달라 조건 추가
        } elseif (empty($thisDate) === true || strtotime($thisDate) === false || (PHP_INT_SIZE == 8 && strtotime($thisDate) < 0)) {
            return '-';
        } else {
            return date($format, strtotime($thisDate));
        }
    }
}

if (!function_exists('return_json'))
{
	function return_json($arr) {
		echo json_encode($arr, JSON_UNESCAPED_UNICODE);
		exit;
	}
}

if (!function_exists('location_url'))
{
	function location_url($url)
	{
		$url = str_replace("&amp;", "&", $url);
		//echo "<script> location.replace('$url'); </script>";

		if (!headers_sent())
			header('Location: '.$url);
		else {
			echo '<script>';
			echo 'location.replace("'.$url.'");';
			echo '</script>';
			echo '<noscript>';
			echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
			echo '</noscript>';
		}
		exit;
	}
}

if (!function_exists('alert_location_url'))
{
	function alert_location_url($message, $url, $location)
	{
		$url = str_replace("&amp;", "&", $url);
		
		echo '<script>';
		echo 'alert(\''.$message.'\');';
		if($location == 'parent') {
			echo 'window.parent.location.href="'.$url.'";';
		} else {
			echo 'location.href="'.$url.'";';
		}
		echo '</script>';
			
		exit;
	}
}

if (!function_exists('gd_money_format'))
{
	function gd_money_format($number, $isComma = true, $isRound = false)
	{
		if (empty($number) === true) {
			return $number;
		}

		$decimal = 0;

		// 소수점 처리
		$decimalPoint = '.';

		// 3자리 콤마 처리
		if ($isComma === true) {
			$thousandsSeperate = ',';
		} else {
			$thousandsSeperate = '';
		}

		// 반올림 처리
		if ($isRound === true) {
			$numberFormatter = number_format($number, $decimal, $decimalPoint, $thousandsSeperate);
		} else {
			$numberFormatter = number_format(preg_replace('/(\d+\.\d{' . $decimal . '})(\d*)/', '\\1', $number), $decimal, $decimalPoint, $thousandsSeperate);
		}

		return $numberFormatter;
	}
}
?>