<?php

/** PBG reground(bpk/bpk2) — 우리 서버 베이스(끝 슬래시 없음). */
if (! defined('PBG_REGROUND_BASE')) {
	define('PBG_REGROUND_BASE', 'https://pbg-2.com');
}
/** powerball .env REGROUND_COMPAT_KEY 와 같으면 ?key= 로 전달. */
if (! defined('PBG_REGROUND_COMPAT_KEY')) {
	define('PBG_REGROUND_COMPAT_KEY', '');
}
	
	  //자료기지 접속
	function connectDb($arrConfig){

	    $strDbHost="";
	    if(array_key_exists('db_host', $arrConfig)){
	        $strDbHost=$arrConfig['db_host'];
	    }
	      
	    $strDbUser="";
	    if(array_key_exists('db_user', $arrConfig)){
	        $strDbUser=$arrConfig['db_user'];
	    }
	      
	    $strDbPwd="";
	    if(array_key_exists('db_pwd', $arrConfig)){
	        $strDbPwd=$arrConfig['db_pwd'];
	    }

	    $strDbName="";
	    if(array_key_exists('db_name', $arrConfig)){
	        $strDbName=$arrConfig['db_name'];
	    }

		$mysqli= new mysqli($strDbHost, $strDbUser, $strDbPwd);
	    
		$dbConn = null;
		if(existDb($mysqli, $strDbName)){
			$dbConn= new mysqli($strDbHost, $strDbUser, $strDbPwd, $strDbName);
		} 

		return $dbConn;
	}
	
	function existDb($mysqli, $db_name)
    {
        $check_connection = mysqli_fetch_row($mysqli->query("SELECT SQL_NO_CACHE IF(EXISTS (SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'), 'Yes','No');"));
        if ($check_connection[0] === "Yes") {
            return true;
        } elseif ($check_connection[0] === "No") {
            return false;
        }
    }
	
	function curlProc(&$hMul, $fLog, $context = 'curl-multi'){
		
		$result = null;
		$running = null;

		// libcurl: CURLM_CALL_MULTI_PERFORM 일 때까지 exec 반복 후, running 동안 select+exec
		do {
			$mrc = curl_multi_exec($hMul, $running);
		} while ($mrc === CURLM_CALL_MULTI_PERFORM);

		while ($running > 0) {
			curl_multi_select($hMul, 1.0);
			do {
				$mrc = curl_multi_exec($hMul, $running);
			} while ($mrc === CURLM_CALL_MULTI_PERFORM);
		}

		if ($state = curl_multi_info_read($hMul)) {
			if (isset($state['handle'])) {
				$h = $state['handle'];
				$result = curl_multi_getcontent($h);
				curl_multi_remove_handle($hMul, $h);
				curl_close($h);
			}
			curl_multi_close($hMul);
			$hMul = null;
		} else {
			curl_multi_close($hMul);
			$hMul = null;
		}

		return $result;
	}

	function getCurl($url, $headers = null, $post = null){
    
		$curl = curl_init($url);
		// curl_setopt($curl, CURLOPT_URL, $url);
		if(substr($url, 0, 5) == 'https'){
			curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		}
		else
		{
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		}
		if (!is_null($post)) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		}
		if (!is_null($headers)) {
			curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt($curl, CURLOPT_TIMEOUT, 10);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, 1);

		return $curl;
	}
	
	function roundInfoStr($arrRoundInfo){
		if(is_null($arrRoundInfo))
			return "";

		if(strlen($arrRoundInfo['round_date']) < 1)
			return "";

		if($arrRoundInfo['round_no'] < 1)
			return "";

		$result = str_replace("-", "", $arrRoundInfo['round_date']);
		$result = substr($result, 2);
		
		$nDigit = strlen($arrRoundInfo['round_no']);
		
		$result.= str_repeat("0", 4-$nDigit);
		$result.= $arrRoundInfo['round_no'];
		return $result;
	}

	function curlPball_ep(){
		$url = "https://www.world35.net/api/v1/gameInfo";
		$url.= "?t=".time();
		
		$header =  [
            'Host: www.world35.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);
	}

	/******************************************** */
	/*====================EOS=====================*/
	/******************************************** */

	function curlEosPball_bpk($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://bepick.net/live/result/eosball5m";
		} else if($roundMin == ROUND_3MIN){
			$url = "https://bepick.net/live/result/eosball3m";
		} else return null;

		$url.= "?_=".$milliSec;

		
		$header =  [
            'Host: bepick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);

	}

	function curlEosPball_bpk2($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://bepick.net/api/get_pattern/eosball5m/daily/fd1/20/";
		} else if($roundMin == ROUND_3MIN){
			$url = "https://bepick.net/api/get_pattern/eosball3m/daily/fd1/20/";
		} else return null;
		
		$arrRoundInfo = getLastRoundInfo($roundMin);

		$url.= str_replace("-", "", $arrRoundInfo['round_date']);
		$url.= "?_=".$milliSec;
		
		$header =  [
            'Host: bepick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);

	}

	function curlEosPballs_bpk($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://bepick.net/live/eosball5m"; 
		} else if($roundMin == ROUND_3MIN){
			$url = "https://bepick.net/live/eosball3m"; 
		}
 
		$url.= "?_=".$milliSec;
		
		$header =  [
            'Host: bepick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];
		return getCurl($url, $header);

	}

	function curlEosPballs_pato($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "https://www.pato.co.kr/stats/nanum_eos5/_ajax_powerball_list.php"; //2206090002

		$arrRoundInfo = getLastRoundInfo(ROUND_5MIN);

		$post= "ajax_data=".$arrRoundInfo['round_date']."&ajax_type=date";
		// $url.= "?_=".$milliSec;
		
		$header =  [
            'Host: www.pato.co.kr',
			'Connection: keep-alive',
			'Content-Type: application/x-www-form-urlencoded',
			'Content-Length: ' . strlen($post),
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];
		//Cookie: PHPSESSID=741mqdp89i6jd8iabdfpje2hb3; 2a0d2363701f23f8a75028924a3af643=NTguMTM4LjIzMy43OA%3D%3D; _ga=GA1.3.2120859806.1662474048; _gid=GA1.3.231555844.1662474048
		return getCurl($url, $header, $post);

	}
	
	function curlEosPball_apk($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://www.all-pick.net/page/eospower5/api/current-result.php"; 
		} else if($roundMin == ROUND_3MIN){
			$url = "https://www.all-pick.net/page/eospower5/api/current-result.php"; 
		}
	 
		$url.= "?_=".$milliSec;
		
		$header =  [
            'Host: www.all-pick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];
		return getCurl($url, $header);

	}

	function curlEosPballs_apk($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://www.all-pick.net/page/eospower5/api/day-rounds.php"; 
		} else if($roundMin == ROUND_3MIN){
			$url = "https://www.all-pick.net/page/eospower3/api/day-rounds.php"; 
		}
	 
		$url.= "?_=".$milliSec;
		
		$header =  [
            'Host: www.all-pick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];
		return getCurl($url, $header);

	}

	function curlEosPballs_ep($roundMin){
		if($roundMin == ROUND_5MIN)
			$url = "https://www.world35.net/api/v1/eosball5?count=2";
		else 
			$url = "https://www.world35.net/api/v1/eosball3?count=2";
		$url.= "&t=".time();
		
		$header =  [
            'Host: www.world35.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);
	}

	function curlEosPball_bbj($roundMin){
		if($roundMin == ROUND_5MIN)
			$url = "http://ballballjoy.com/game/data/eos5/pb_json_latest";
		else 
			$url = "http://ballballjoy.com/game/data/eos3/pb_json_latest";
		$url.= "?t=".time();
		
		$header =  [
            'Host: ballballjoy.com',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);
	}

	function curlEosPballs_bbj($roundMin){
		if($roundMin == ROUND_5MIN)
			$url = "http://ballballjoy.com/game/data/eos5/pb_json";
		else 
			$url = "http://ballballjoy.com/game/data/eos3/pb_json";
		$url.= "?t=".time();
		
		$header =  [
            'Host: ballballjoy.com',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);
	}

	/******************************************** */
	/*====================COIN=====================*/
	/******************************************** */

	function curlCoinPballs_drs($roundMin){
	
		if($roundMin == ROUND_5MIN)
			$url = "https://game.dr-score.com/api/coinpowerball5/get";
		else 
			$url = "https://game.dr-score.com/api/coinpowerball3/get";

		$url.= "?t=".time();
		
		$header =  [
            'Host: game.dr-score.com',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);
	}

	function curlCoinPballs_drs2($roundMin){
		if($roundMin == ROUND_5MIN)
			$url = "https://game.dr-score.com/api/coinpowerball5/getsect?gamecount=2";
		else 
			$url = "https://game.dr-score.com/api/coinpowerball3/getsect?gamecount=2";

		$url.= "&t=".time();
		
		$header =  [
            'Host: game.dr-score.com',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);
	}
		
	function curlCoinPball_apk($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://www.all-pick.net/page/coin5/api/current-result.php"; 
		} else if($roundMin == ROUND_3MIN){
			$url = "https://www.all-pick.net/page/coin3/api/current-result.php"; 
		}
	 
		$url.= "?_=".$milliSec;
		
		$header =  [
            'Host: www.all-pick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];
		return getCurl($url, $header);

	}

	function curlCoinPballs_apk($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://www.all-pick.net/page/coin5/api/day-rounds.php"; 
		} else if($roundMin == ROUND_3MIN){
			$url = "https://www.all-pick.net/page/coin3/api/day-rounds.php"; 
		}
	 
		$url.= "?_=".$milliSec;
		
		$header =  [
            'Host: www.all-pick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];
		return getCurl($url, $header);

	}
	
	function curlCoinPballs_ep($roundMin){
		if($roundMin == ROUND_5MIN)
			$url = "https://www.world35.net/api/v1/cnball5?count=2";
		else 
			$url = "https://www.world35.net/api/v1/cnball3?count=2";
		$url.= "&t=".time();
		
		$header =  [
            'Host: www.world35.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);
	}

	function curlCoinPball_bpk($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://bepick.net/live/result/coinpower5";
		} else if($roundMin == ROUND_3MIN){
			$url = "https://bepick.net/live/result/coinpower3";
		} else return null;

		$url.= "?_=".$milliSec;

		
		$header =  [
            'Host: bepick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);

	}

	function curlCoinPball_bpk2($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://bepick.net/api/get_pattern/coinpower5/daily/fd1/20/";
		} else if($roundMin == ROUND_3MIN){
			$url = "https://bepick.net/api/get_pattern/coinpower3/daily/fd1/20/";
		} else return null;
		
		$arrRoundInfo = getLastRoundInfo($roundMin);

		$url.= str_replace("-", "", $arrRoundInfo['round_date']);
		$url.= "?_=".$milliSec;
		
		$header =  [
            'Host: bepick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);

	}

	function curlCoinPball_down($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://updownscore.com/api/last?g_type=coinpowerball5";
		} else if($roundMin == ROUND_3MIN){
			$url = "https://updownscore.com/api/last?g_type=coinpowerball3";
		} else return null;
		
		$url.= "&_=".$milliSec;
		
		$header =  [
            'Host: updownscore.com',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);

	}
	/******************************************** */
	/*====================BOGLE===================*/
	/******************************************** */
	function curlBoglePball(){
	
		$url = "http://boglegames.com/game/powerball/ajax.get_live_data.php";
		$url.= "?t=".time();
		return getCurl($url);
	}
	
	/******************************************** */
	/*====================PBG=====================*/
	/******************************************** */

	function curlPbg_ep(){
		
		$url = "https://www.world35.net/api/v1/pbgball?count=2";
		$url.= "&t=".time();
		
		$header =  [
            'Host: www.world35.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);
	}
		
	function curlPbg_apk(){

		$milliSec = floor(microtime(true) * 1000);

		$url = "https://www.all-pick.net/page/pbgpower5/api/current-result.php"; 
		$url.= "?_=".$milliSec;
		
		$header =  [
            'Host: www.all-pick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];
		return getCurl($url, $header);

	}

	function curlPbgs_apk(){

		$milliSec = floor(microtime(true) * 1000);

		$url = "https://www.all-pick.net/page/pbgpower5/api/day-rounds.php"; 
		$url.= "?_=".$milliSec;
		
		$header =  [
            'Host: www.all-pick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];
		return getCurl($url, $header);

	}

	function curlPbg_bpk($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = rtrim(PBG_REGROUND_BASE, '/')."/live/result/pbgpowerball";
		$url.= "?_=".$milliSec;
		if (PBG_REGROUND_COMPAT_KEY !== '') {
			$url .= "&key=".urlencode(PBG_REGROUND_COMPAT_KEY);
		}
		$host = parse_url(PBG_REGROUND_BASE, PHP_URL_HOST);
		if (! is_string($host) || $host === '') {
			$host = 'localhost';
		}
		
		$header =  [
            'Host: '.$host,
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);

	}

	function curlpbg_bpk2($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = rtrim(PBG_REGROUND_BASE, '/')."/api/get_pattern/pbgpowerball/daily/fd1/20/";
		
		$arrRoundInfo = getLastRoundInfo($roundMin);
		
		$url.= str_replace("-", "", $arrRoundInfo['round_date']);	 
		$url.= "?_=".$milliSec;
		if (PBG_REGROUND_COMPAT_KEY !== '') {
			$url .= "&key=".urlencode(PBG_REGROUND_COMPAT_KEY);
		}
		$host = parse_url(PBG_REGROUND_BASE, PHP_URL_HOST);
		if (! is_string($host) || $host === '') {
			$host = 'localhost';
		}
		
		$header =  [
            'Host: '.$host,
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);

	}

	/**
	 * 베픽(https://bepick.net) PBG — Tiger DB(round_pball) 전용.
	 * Lion은 curlPbg_bpk / curlpbg_bpk2(PBG_REGROUND_BASE, 예: pbg-2.com) 유지.
	 */
	function curlPbg_bepick($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "https://bepick.net/live/result/pbgpowerball";
		$url.= "?_=".$milliSec;

		$header =  [
            'Host: bepick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);

	}

	function curlpbg_bepick2($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "https://bepick.net/api/get_pattern/pbgpowerball/daily/fd1/20/";

		$arrRoundInfo = getLastRoundInfo($roundMin);

		$url.= str_replace("-", "", $arrRoundInfo['round_date']);
		$url.= "?_=".$milliSec;

		$header =  [
            'Host: bepick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);

	}

	/** 베픽 PBG HTTP 동기 수신(헤더 포함 raw). 실패 시 null */
	function pbgFetchBepickResponse($roundMin, $usePatternApi){
		$ch = $usePatternApi ? curlpbg_bepick2($roundMin) : curlPbg_bepick($roundMin);
		if ($ch === null) {
			return null;
		}
		$raw = curl_exec($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		if ($errno !== 0 || ! is_string($raw) || $raw === '') {
			return null;
		}
		return $raw;
	}

	function curlLogin_benz($benzInfo){

		$url = $benzInfo['site']."/login";

		$post = "username=".$benzInfo['uid']."&password=".$benzInfo['pwd'];
		
		$header =  [
            'Host: '.$benzInfo['domain'],
			'Connection: keep-alive',
			'Content-Length: '.strlen($post),
			'Cache-Control: max-age=0',
			'Upgrade-Insecure-Requests: 1',
			'Origin: '.$benzInfo['site'],
			'Content-Type: application/x-www-form-urlencoded',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Referer: '.$benzInfo['site'].'/',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7',
		];

		return getCurl($url, $header, $post);
	}
	
	function curlKeep_benz($benzInfo, $sessId){
		// $milliSec = floor(microtime(true) * 1000);

		$url = $benzInfo['site']."/ko/gameLive/powerball"; //ko/before
		// $url.= "?&_=".$milliSec;
		
		$header =  [
            'Host: '.$benzInfo['domain'],
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'Upgrade-Insecure-Requests: 1',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Sec-Fetch-Site: same-origin',
			'Sec-Fetch-Mode: navigate',
			'Sec-Fetch-User: ?1',
			'Sec-Fetch-Dest: document',
			'sec-ch-ua: ".Not/A)Brand";v="99", "Google Chrome";v="103", "Chromium";v="103"',
			'sec-ch-ua-mobile: ?0',
			'sec-ch-ua-platform: "Windows"',
			'Referer: '.$benzInfo['site'].'/',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7',
			'Cookie: JSESSIONID='.$sessId.'; loginUrl=/',
		];

		return getCurl($url, $header);
	}

	function curlPbg_benz($benzInfo, $sessId, $round=null, $bLast=false){
		$milliSec = floor(microtime(true) * 1000);

		$url = $benzInfo['site']."/ko/powerball/liveBetting?"; 
		if(!is_null($round)){

			$arrRounds = getLastRoundInfos(ROUND_5MIN);
			
			$arrRoundInfo = $arrRounds[0];

			$strRoundId = $round['times'];
			if($arrRoundInfo['round_no'] == $round['date_round']){
				$strRoundId = $round['times'];
			} else if($round['date_round'] > $arrRoundInfo['round_no']) {
				$strRoundId += $arrRoundInfo['round_no'] - $round['date_round'];
			} else if($round['date_round'] == 1){
				$strRoundId -= 1 ;
			} else $strRoundId += 1 ;

			if($bLast)
				$strRoundId -= 1 ;
				
			$strDate = str_replace("-", "", $arrRoundInfo['round_date']);

			$url.= "&round_dateidx=".$strDate;
			$url.= "&round_no=".$strRoundId;

			echo $url."\r\n";
		}
		$url.= "&_=".$milliSec;
		

		$header =  [
            'Host: '.$benzInfo['domain'],
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'Upgrade-Insecure-Requests: 1',
			'User-Agent: Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'X-Requested-With: XMLHttpRequest',
			'Sec-Fetch-Site: same-origin',
			'Sec-Fetch-Mode: cors',
			'Sec-Fetch-Dest: empty',
			'sec-ch-ua-platform: "Windows"',
			'Referer: '.$benzInfo['site'].'/ko/gameLive/powerball',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7',
			'Cookie: JSESSIONID='.$sessId.'; loginUrl=/',
		];

		return getCurl($url, $header);
	}














	/** curl 헤더+본문 응답에서 본문만 분리 (fetch 시 JSON 시작점 찾기용) */
	function pbgHttpResponseBody($raw)
	{
		if (! is_string($raw) || $raw === '') {
			return '';
		}
		$p = strpos($raw, "\r\n\r\n");
		if ($p !== false) {
			return substr($raw, $p + 4);
		}
		$p = strpos($raw, "\n\n");
		if ($p !== false) {
			return substr($raw, $p + 2);
		}
		return $raw;
	}

	/////////////////////////////////////////////////////
	//베틱 회차결과 얻어오기
	function fetchPball_bpk($strResult)
	{
		if (! is_string($strResult) || $strResult === '') {
			return null;
		}
		$body = pbgHttpResponseBody($strResult);
		$work = (strpos($body, '{"') !== false) ? $body : $strResult;
		$nStartPos = strpos($work, '{"');
		if ($nStartPos === false) {
			return null;
		}
		$jsonStr = trim(substr($work, $nStartPos));

		$arrResult = json_decode($jsonStr, true);
		if ($arrResult === null && json_last_error() !== JSON_ERROR_NONE) {
			return null;
		}
		if ($arrResult === null) {
			return null;
		}

		return parsePballRound_bpk($arrResult);
	}

	//베틱 회차결과 얻어오기
	function fetchPball_bpk2($strResult)
	{
		if (! is_string($strResult) || $strResult === '') {
			return null;
		}
		$body = pbgHttpResponseBody($strResult);
		$work = (strpos($body, '{"') !== false) ? $body : $strResult;
		$nStartPos = strpos($work, '{"');
		if ($nStartPos === false) {
			return null;
		}
		$jsonStr = trim(substr($work, $nStartPos));

		$arrResult = json_decode($jsonStr, true);
		if ($arrResult === null && json_last_error() !== JSON_ERROR_NONE) {
			return null;
		}
		if ($arrResult === null) {
			return null;
		}

		if (! array_key_exists('update', $arrResult)) {
			return null;
		}
		$arrResult = $arrResult['update'];

		return parsePballRound_bpk($arrResult);
	}

// 외부 응답에서 round_hash에 해당할 가능성이 높은 필드를 찾는다.
// 내부 계산은 하지 않고, 외부가 준 원본 필드 값만 사용한다.
function resolveExternalRoundHash($arrRoundInfo, &$usedKey = "")
{
	if(is_null($arrRoundInfo) || !is_array($arrRoundInfo))
		return null;

	$usedKey = "";
	$candidateKeys = array(
		'rownump',
		'idx',
		'round_hash',
		'hash',
		'HASH',
		'fixed_date_round',
		'pick_num',
		'GC',
		'full_round'
	);

	foreach($candidateKeys as $key){
		if(array_key_exists($key, $arrRoundInfo)){
			$v = trim((string)$arrRoundInfo[$key]);
			if($v !== ""){
				$usedKey = $key;
				return $v;
			}
		}
	}

	return null;
}

	function parsePballRound_bpk($arrRoundInfo)
	{
		/*
		{
			"fd1": "2",
			"fd2": "1",
			"fd3": "2",
			"fd4": "2",
			"fd5": "3",
			"b1": "3",
			"b2": "14",
			"b3": "28",
			"b4": "11",
			"b5": "26",
			"bsum": "82",
			"btype": "F",
			"pb": "0",
			"ptype": "A",
			"round": "141",
			"rownump": "249246084/17016",
			"date": "20220529",
			"rownum": "152461"
		}
		*/
		if(is_null($arrRoundInfo))
			return null;

	if(!array_key_exists("round", $arrRoundInfo) || !array_key_exists("date", $arrRoundInfo) ){
			return null;
	}

		$arrRoundResult['date_round'] = $arrRoundInfo['round'];
		$arrRoundResult['r'] = $arrRoundInfo['round'];
		$arrRoundResult['times'] = $arrRoundInfo['rownum'];
	$hashKey = "";
	$hashVal = resolveExternalRoundHash($arrRoundInfo, $hashKey);
	if(!is_null($hashVal)){
		$arrRoundResult['round_hash'] = $hashVal;
	}
		
		$strDate = $arrRoundInfo['date'];
		if(strlen($strDate) != 8)
			return null;
		$arrRoundResult['date'] = substr($strDate, 0, 4)."-".substr($strDate, 4, 2)."-".substr($strDate, 6, 2);

		$arrNorBall[0] = $arrRoundInfo['b1'];
		$arrNorBall[1] = $arrRoundInfo['b2'];
		$arrNorBall[2] = $arrRoundInfo['b3'];
		$arrNorBall[3] = $arrRoundInfo['b4'];
		$arrNorBall[4] = $arrRoundInfo['b5'];
		$arrNorBall[5] = $arrRoundInfo['pb'];
		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;
	}

	//베틱 회차결과 얻어오기
	function fetchPballs_bpk($strResult)
	{
		$arrRounds = [ null, null];
		
		$nLastPos = 0;
		$strResult = fetchStr($strResult, '<div class="scrollBox nResult"', "</ul>", $nLastPos);
		if(is_null($strResult)){
			return $arrRounds;
		}

		$strStart = '<li class="gmImg">';
		$strEnd = '</li>';

		$nLastPos = 0;
		$roundLi = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$arrRounds[0] = parsePballRound_bpkStr($roundLi);

		$roundLi = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$arrRounds[1] = parsePballRound_bpkStr($roundLi);
		
		return $arrRounds;

	}

	function parsePballRound_bpkStr($strResult){

		if(is_null($strResult))
			return null;

		$nLastPos = 0;

		$strStart = '<h3>';
		$strEnd = '</h3>';
		$roundId = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$arrInfo = explode('-', $roundId);
		if(count($arrInfo) < 2)
			return null;

		$arrRoundResult['date_round'] = trim($arrInfo[0]);
		$arrRoundResult['r'] = trim($arrInfo[0]);
		$arrRoundResult['round_hash'] = trim($arrInfo[1]);
		$arrRoundResult['date'] = date("Y-m-d");
		
		$strStart = 'class="bicon b';
		$strEnd = '">';
		$bn = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if( is_null($bn) || !is_numeric($bn))
			return null;
		$arrNorBall[0] = $bn;

		$bn = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if( is_null($bn) || !is_numeric($bn))
			return null;
		$arrNorBall[1] = $bn;

		$bn = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if( is_null($bn) || !is_numeric($bn))
			return null;
		$arrNorBall[2] = $bn;

		$bn = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if( is_null($bn) || !is_numeric($bn))
			return null;
		$arrNorBall[3] = $bn;

		$bn = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if( is_null($bn) || !is_numeric($bn))
			return null;
		$arrNorBall[4] = $bn;
		
		$strStart = 'class="bicon bp">';
		$strEnd = '</span>';
		$pb = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if( is_null($pb) || !is_numeric($pb) )
			return null;
		$arrNorBall[5] = $pb;
		
		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;

	}

	function fetchPball_apk($strResult)
	{
		$nStartPos = strpos($strResult, '{"');
		if($nStartPos === false )  
			return null;
		$strResult = trim(substr($strResult, $nStartPos));

		$arrResult = json_decode($strResult, true);
		
		return parsePballRound_apk($arrResult);

	}

	function parsePballRound_apk($arrRoundInfo)
	{
		/*
		{
			  	"ball_1": "24",
				"ball_2": "8",
				"ball_3": "3",
				"ball_4": "1",
				"ball_5": "20",
				"powerball": "1",
				"oe": "짝",
				"sec": "C",
				"size": "소",
				"sum": "56",
				"uo": "언더",
				"poe": "홀",
				"puo": "언더",
				"round": "269",
				"full_round": "266942350"
		}
		*/
		if(is_null($arrRoundInfo))
			return null;

		if(!array_key_exists("round", $arrRoundInfo))
			return null;

		$arrRoundResult['date_round'] = $arrRoundInfo['round'];
		$arrRoundResult['r'] = $arrRoundInfo['round'];
		$arrRoundResult['times'] = $arrRoundInfo['full_round'];
		$arrRoundResult['round_hash'] = $arrRoundInfo['full_round'];
		$arrRoundResult['date'] =  date("Y-m-d");

		$arrNorBall[0] = $arrRoundInfo['ball_1'];
		$arrNorBall[1] = $arrRoundInfo['ball_2'];
		$arrNorBall[2] = $arrRoundInfo['ball_3'];
		$arrNorBall[3] = $arrRoundInfo['ball_4'];
		$arrNorBall[4] = $arrRoundInfo['ball_5'];
		$arrNorBall[5] = $arrRoundInfo['powerball'];
		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;
	}

	function fetchPballs_apk($strResult)
	{
		$arrRounds = [ null, null];

		$nStartPos = strpos($strResult, '[{"');
		if($nStartPos === false )  
			return $arrRounds;
		$strResult = trim(substr($strResult, $nStartPos));

		$arrResult = json_decode($strResult, true);
		if(is_null($arrResult) || !is_array($arrResult) || count($arrResult) < 2)
			return $arrRounds;
		
		$cnt = count($arrResult);
		$arrRounds[0] = parsePballRound_apk2($arrResult[$cnt-1]); 
		$arrRounds[1] = parsePballRound_apk2($arrResult[$cnt-2]); 

		return $arrRounds;

	}

	function parsePballRound_apk2($arrRoundInfo)
	{
		/*
		{
			"id": "6045",
			"date": "2022-09-07",
			"lottery_time": "20:05:00",
			"ball_1": "28",
			"ball_2": "13",
			"ball_3": "20",
			"ball_4": "23",
			"ball_5": "18",
			"ball_powerball": "9",
			"def_ball_oe": "짝",
			"def_ball_section": "F",
			"def_ball_size": "대",
			"def_ball_sum": "102",
			"def_ball_unover": "오버",
			"pow_ball_oe": "홀",
			"pow_ball_unover": "오버",
			"date_round": "241",
			"fixed_date_round": "241",
			"times": "1200344"
		}
		*/
		if(is_null($arrRoundInfo))
			return null;

		if(!array_key_exists("date_round", $arrRoundInfo) || !array_key_exists("date", $arrRoundInfo) )
			return null;

		$arrRoundResult['date_round'] = $arrRoundInfo['date_round'];
		$arrRoundResult['r'] = $arrRoundInfo['date_round'];
		$arrRoundResult['times'] = $arrRoundInfo['times'];
		$arrRoundResult['round_hash'] = $arrRoundInfo['times'];
		$arrRoundResult['date'] = $arrRoundInfo['date'];

		$arrNorBall[0] = $arrRoundInfo['ball_1'];
		$arrNorBall[1] = $arrRoundInfo['ball_2'];
		$arrNorBall[2] = $arrRoundInfo['ball_3'];
		$arrNorBall[3] = $arrRoundInfo['ball_4'];
		$arrNorBall[4] = $arrRoundInfo['ball_5'];
		$arrNorBall[5] = $arrRoundInfo['ball_powerball'];
		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;
	}

	//베틱 EOS5분파워볼 회차결과 얻어오기
	function fetchEosPballs_pato($strResult)
	{
		
		$arrRounds = [ null, null];

		$strStart = '<tr>';
		$strEnd = '<\/tr>';
		$nLastPos = 0;
		$roundLi = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$arrRounds[0] = parseEosPballRound_pato($roundLi);

		$roundLi = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$arrRounds[1] = parseEosPballRound_pato($roundLi);
		
		return $arrRounds;

	}
	
	function parseEosPballRound_pato($strResult){

		if(is_null($strResult))
			return null;

		$nLastPos = 0;

		$strStart = '<td><font>';
		$strEnd = '<\/font>';
		$date_round = fetchStr($strResult, $strStart, $strEnd, $nLastPos);

		$arrRoundResult['date_round'] = $date_round;
		$arrRoundResult['r'] = $date_round;
		
		$arrRoundResult['date'] = date("Y-m-d");
		
		$strStart = "class='hidden-xs'>";
		$strEnd = '<\/td>';
		$normal = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$normal = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if( is_null($normal))
			return null;
		
		$arrball = explode(' ', $normal);
		if(count($arrball) < 5)
			return null;

		$arrNorBall[0] = $arrball[0];
		$arrNorBall[1] = $arrball[1];
		$arrNorBall[2] = $arrball[2];
		$arrNorBall[3] = $arrball[3];
		$arrNorBall[4] = $arrball[4];
		
		$nLastPos = 0;
		$strStart = "class='ball blank2'>";
		$strEnd = '<\/span>';
		$pb = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if( is_null($pb) || !is_numeric($pb) )
			return null;
		$arrNorBall[5] = $pb;
		
		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;

	}
	//ballballjoy EOS5분파워볼 회차결과 얻어오기
	function fetchPball_bbj($strResult)
	{
		$nStartPos = strpos($strResult, "{\"");
		if($nStartPos === false )  {
			// writeLog($fLog, $strResult, false);
			return null;
		}
		$strResult = trim(substr($strResult, $nStartPos));

		$arrResult = json_decode($strResult, true);
		
		return parsePballRound_bbj($arrResult);

	}

	//ballballjoy EOS5분파워볼 회차결과 얻어오기
	function fetchPballs_bbj($strResult)
	{
		
		$arrRounds = [ null, null];

		$nStartPos = strpos($strResult, '[{"');
		if($nStartPos === false )  
			return $arrRounds;
		$strResult = trim(substr($strResult, $nStartPos));

		$arrResult = json_decode($strResult, true);
		if(is_null($arrResult) || !is_array($arrResult) || count($arrResult) < 2)
			return $arrRounds;
		
		$cnt = count($arrResult);
		$arrRounds[0] = parsePballRound_bbj($arrResult[$cnt-1]); 
		$arrRounds[1] = parsePballRound_bbj($arrResult[$cnt-2]); 

		return $arrRounds;

	}

	function parsePballRound_bbj($arrRoundInfo)
	{
		/*
		{
			"pick_num": 267711277,
			"pick_num2": 111,
			"pick_date": "2022-09-13 09:15:00",
			"oe": "odd",
			"ou": "under",
			"poe": "even",
			"pou": "over",
			"wsum": "61",
			"wsize": "small",
			"powerball": 6,
			"numbers": "060810261106",
			"ball_section": "D",
			"pow_ball_section": "C",
			"wsize_ex": "15~64",
			"ball_section_ex": "58~65",
			"pow_ball_section_ex": "5~6",
			"fixed_date_round": "83B6C"
		}
		*/
		if(is_null($arrRoundInfo))
			return null;

		if(!array_key_exists("pick_num2", $arrRoundInfo) || !array_key_exists("pick_date", $arrRoundInfo) )
			return null;

		$arrRoundResult['date_round'] = $arrRoundInfo['pick_num2'];
		$arrRoundResult['r'] = $arrRoundInfo['pick_num2'];
		$arrRoundResult['round_hash'] = $arrRoundInfo['pick_num'];
		$arrRoundResult['times'] = $arrRoundInfo['pick_num'];
		
		$strDate = $arrRoundInfo['pick_date'];
		if(strlen($strDate) < 10)
			return null;
		$arrRoundResult['date'] = substr($strDate, 0, 10);

		$strNumbers = $arrRoundInfo['numbers'];
		if(strlen($strNumbers) < 12)
			return null;
		$arrNorBall[0] = substr($strNumbers, 0, 2);
		$arrNorBall[1] = substr($strNumbers, 2, 2);
		$arrNorBall[2] = substr($strNumbers, 4, 2);
		$arrNorBall[3] = substr($strNumbers, 6, 2);
		$arrNorBall[4] = substr($strNumbers, 8, 2);
		$arrNorBall[5] = substr($strNumbers, 10, 2);
		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;
	}


	//드림스코 코인파워볼 회차결과 얻어오기
	function fetchCoinPball_drs($strResult)
	{
		$arrRounds = [null, null];

		$nStartPos = strpos($strResult, "{\"");
		if($nStartPos === false )  
			return $arrRounds;
		$strResult = trim(substr($strResult, $nStartPos));

		$objResult = json_decode($strResult, true);
		
		$arrResult = null;
		if(!is_null($objResult)) {
			if(array_key_exists("data", $objResult) ) {
				if(array_key_exists("datas", $objResult["data"]))
					$arrResult = $objResult['data']['datas'];
			}  else if(array_key_exists("datas", $objResult)) {
				$arrResult = $objResult['datas'];
			}
		}
		

		if(is_null($arrResult))
			return $arrRounds;

		if(count($arrResult) >= 2){
			$arrRounds[0] = parseCoinPballRound_drs($arrResult[0]);
			$arrRounds[1] = parseCoinPballRound_drs($arrResult[1]);
		}

		return $arrRounds;
	}

	function parseCoinPballRound_drs($arrRoundInfo)
	{
		
		if(is_null($arrRoundInfo))
			return null;

		if(!array_key_exists("TIME", $arrRoundInfo) || !array_key_exists("DAYROUND", $arrRoundInfo)
			|| !array_key_exists("HASH", $arrRoundInfo))
			return null;

		$arrRoundResult = [];
		$arrRoundResult['date_round'] = $arrRoundInfo['DAYROUND'];
		$arrRoundResult['r'] = $arrRoundInfo['DAYROUND'];
		$arrRoundResult['round_hash'] = $arrRoundInfo['HASH'];
		

		$strTime = $arrRoundInfo['TIME'];
		if(strlen($strTime) != 19)
			return null;
		$arrRoundResult['date'] = substr($strTime, 0, 10);

		if(!array_key_exists("RESULTNUM", $arrRoundInfo) || !array_key_exists("PB", $arrRoundInfo))
			return null;

		$arrNorBall = explode(",", $arrRoundInfo['RESULTNUM']);

		if(count($arrNorBall) != 5)
			return null;

		$arrNorBall[5] = $arrRoundInfo['PB'];
		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;
	}

	function fetchLogin_benz($strResult, &$sessBenz)
	{
		//Session ID
		$strStart = "JSESSIONID=";
		$strEnd = ";";
		$nLastPos = 0;
		$sessBenz = fetchStr($strResult, $strStart, $strEnd, $nLastPos);

		if(is_null($sessBenz) ){
			$sessBenz = "";
			return false;
		}
		
		return true;
	}

	function fetchKeep_benz($strResult, &$sessBenz)
	{
		//Session ID
		$strStart = "JSESSIONID=";
		$strEnd = ";";
		$nLastPos = 0;
		$sessId = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if(!is_null($sessId) ){
			$sessBenz = $sessId;
		}

		$strStart = 'PBG파워볼';
		//Session ID
		$nStartPos = strpos($strResult, $strStart);
		if($nStartPos !== false )
			return true;
		else return false;
	}

	
	function fetchPbg_benz($strResult, &$sessBenz, &$bLogin)
	{
		//Session ID
		$strStart = "JSESSIONID=";
		$strEnd = ";";
		$nLastPos = 0;
		$sessId = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if(!is_null($sessId) ){
			$sessBenz = $sessId;
		}

		$strStart = 'PBG파워볼';
		//PBG파워볼
		$nStartPos = strpos($strResult, $strStart);
		if($nStartPos === false ){
			$bLogin = false;
			return null;
		}
		$nLastPos = $nStartPos; 
		//회차번호
		$strStart = 'id="betRound">';
		$strEnd = '</span>';
		$roundNo = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if(is_null($roundNo))
			return null;
		
		$roundNo = trim($roundNo);
		if(!is_numeric($roundNo))
			return null;
		$round['date_round'] = intval($roundNo);
		
		//회차아이디
		$strStart = 'color="blue">';
		$strEnd = '</font>';
		$roundId = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		if(is_null($roundId))
			return null;
		$roundId = trim($roundId);
		if( !is_numeric($roundId))
			return null;
		$round['times'] = intval($roundId);
		
		$strStart = 'class="lottery-result';
		//lottery-result
		$nStartPos = strpos($strResult, $strStart);
		if($nStartPos === false )
			return $round;
		$nLastPos = $nStartPos; 

		//일반볼 숫자합
		$strStart = 'class="el-button result el-button--primary is-circle"><span>';
		$strEnd = '</span>';
		$result_normal = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$result_normal = trim($result_normal);
		if(is_null($result_normal))
			return null;
		if(!is_numeric($result_normal))
			return null;
		$round['result_normal'] = $result_normal;

		//일반볼 홀짝
		$strStart = 'class="el-button result el-button--primary is-circle"><span>';
		$strEnd = '</span>';
		$result_3 = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$result_3 = trim($result_3);

		if(is_null($result_3))
			return null;
		else if($result_3 == "홀")
			$round['result_3'] = 'P';
		else if($result_3 == "짝")
			$round['result_3'] = 'B';
		else return null;
			
		//일반볼 언오버
		$strStart = 'class="el-icon-';
		$strEnd = '"></i>';
		$result_4 = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$result_4 = trim($result_4);
		if(is_null($result_4))
			return null;
		else if($result_4 == "bottom")
			$round['result_4'] = 'P';
		else if($result_4 == "top")
			$round['result_4'] = 'B';
		else return null;

		//일반볼 대중소
		$strStart = '<span>';
		$strEnd = '</span>';
		$result_5 = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$result_5 = trim($result_5);
		if(is_null($result_5))
			return null;
		else if($result_5 == "대")
			$round['result_5'] = 'L';
		else if($result_5 == "중")
			$round['result_5'] = 'M';
		else if($result_5 == "소")
			$round['result_5'] = 'S';
		else return null;

		//파워볼 홀짝
		$strStart = 'class="el-button result el-button--danger is-circle"><span>';
		$strEnd = '</span>';
		$result_1 = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$result_1 = trim($result_1);
		if(is_null($result_1))
			return null;
		else if($result_1 == "홀")
			$round['result_1'] = 'P';
		else if($result_1 == "짝")
			$round['result_1'] = 'B';
		else return null;

		//파워볼 언오버
		$strStart = 'class="el-icon-';
		$strEnd = '"></i>';
		$result_2 = fetchStr($strResult, $strStart, $strEnd, $nLastPos);
		$result_2 = trim($result_2);
		if(is_null($result_2))
			return null;
		else if($result_2 == "bottom")
			$round['result_2'] = 'P';
		else if($result_2 == "top")
			$round['result_2'] = 'B';
			
		return $round;
	}

	function fetchStr($strSource, $strStart, $strEnd, &$nLastPos){

		$nStartPos = strpos($strSource, $strStart, $nLastPos);
		if($nStartPos === false )
			return null;

		$nStartPos += strlen($strStart);
		if(is_null($strEnd) || strlen($strEnd) == 0){
			return substr($strSource, $nStartPos);
		}
		$nEndPos = strpos($strSource, $strEnd, $nStartPos);
		if($nEndPos === false )
			return null;
		
		$strResult = substr($strSource, $nStartPos, $nEndPos - $nStartPos);

		$nLastPos = $nEndPos;
		return trim($strResult);

	}

	//보글볼 회차결과
	function fetchBoglePballRound($strResult)
	{
		$arrResult = null; 
		$objResult = json_decode($strResult, true);
		
		if(!is_null($objResult) && array_key_exists("prevGame", $objResult)) {
			$arrResult = $objResult['prevGame'];
		}

		return parseBoglePballRound($arrResult);

	}

	function parseBoglePballRound($arrRoundInfo)
	{
		
		if(is_null($arrRoundInfo))
			return null;

		if(!array_key_exists("round", $arrRoundInfo) || !array_key_exists("date", $arrRoundInfo) )
			return null;

		$arrRoundResult['date_round'] = $arrRoundInfo['round'];
		$arrRoundResult['r'] = $arrRoundInfo['round'];
		
		$arrRoundResult['date'] = $arrRoundInfo['date'];
		if(strlen($arrRoundResult['date']) != 10)
			return null;

		$arrNorBall[0] = $arrRoundInfo['result_n1'];
		$arrNorBall[1] = $arrRoundInfo['result_n2'];
		$arrNorBall[2] = $arrRoundInfo['result_n3'];
		$arrNorBall[3] = $arrRoundInfo['result_n4'];
		$arrNorBall[4] = $arrRoundInfo['result_n5'];
		$arrNorBall[5] = $arrRoundInfo['result_pn'];
		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;
	}

	//e-pick 파워볼 회차결과 얻어오기
	function fetchPball_ep($strResult, $iGame)
	{
		$nStartPos = strpos($strResult, "{\"");
		if($nStartPos === false )  {
			return null;
		}
		$strResult = trim(substr($strResult, $nStartPos));

		$arrResult = json_decode($strResult, true);
		
		if(array_key_exists('data', $arrResult))
			$arrResult = $arrResult['data'];
		else return null;
		
		return parsePballRound_ep($arrResult, $iGame);

	}

	function parsePballRound_ep($arrRoundInfo, $iGame)
	{
		/*
		{
			"GAME_CNBALL5": 112,
			"NAME_PBGBALL": "파워볼게임",
			"GAME_PBGBALL": 1201943,
			"NEXTTIME_PBGBALL": "2022-09-13T09:25:02.000Z",
			"NUM1_PBGBALL": 28,
			"NUM2_PBGBALL": 15,
			"NUM3_PBGBALL": 1,
			"NUM4_PBGBALL": 7,
			"NUM5_PBGBALL": 5,
			"NUM6_PBGBALL": 9,
		}
		*/
		if(is_null($arrRoundInfo))
			return null;

		if(!array_key_exists("GAME_CNBALL5", $arrRoundInfo))
			return null;

		if($iGame == GAME_POWER_BALL){
			$arrRoundResult['date_round'] = $arrRoundInfo['GAME_CNBALL5'];
			$arrRoundResult['r'] = $arrRoundInfo['GAME_CNBALL5'];
			$arrRoundResult['round_hash'] = $arrRoundInfo['GAME_PBGBALL'];
			$arrRoundResult['times'] = $arrRoundInfo['GAME_PBGBALL'];
			
			$strDate = $arrRoundInfo['NEXTTIME_PBGBALL'];
			if(strlen($strDate) < 10)
				return null;
			$arrRoundResult['date'] = substr($strDate, 0, 10);
	
			$arrNorBall[0] = $arrRoundInfo['NUM1_PBGBALL'];
			$arrNorBall[1] = $arrRoundInfo['NUM2_PBGBALL'];
			$arrNorBall[2] = $arrRoundInfo['NUM3_PBGBALL'];
			$arrNorBall[3] = $arrRoundInfo['NUM4_PBGBALL'];
			$arrNorBall[4] = $arrRoundInfo['NUM5_PBGBALL'];
			$arrNorBall[5] = $arrRoundInfo['NUM6_PBGBALL'];
		} else if($iGame == GAME_EOS5_BALL){
			$arrRoundResult['date_round'] = $arrRoundInfo['GAME_CNBALL5'];
			$arrRoundResult['r'] = $arrRoundInfo['GAME_CNBALL5'];
			$arrRoundResult['round_hash'] = $arrRoundInfo['GAME_EOSBALL5'];
			
			$strDate = $arrRoundInfo['NEXTTIME_EOSBALL5'];
			if(strlen($strDate) < 10)
				return null;
			$arrRoundResult['date'] = substr($strDate, 0, 10);
	
			$arrNorBall[0] = $arrRoundInfo['NUM1_EOSBALL5'];
			$arrNorBall[1] = $arrRoundInfo['NUM2_EOSBALL5'];
			$arrNorBall[2] = $arrRoundInfo['NUM3_EOSBALL5'];
			$arrNorBall[3] = $arrRoundInfo['NUM4_EOSBALL5'];
			$arrNorBall[4] = $arrRoundInfo['NUM5_EOSBALL5'];
			$arrNorBall[5] = $arrRoundInfo['NUM6_EOSBALL5'];
		} else if($iGame == GAME_EOS3_BALL){
			$arrRoundResult['date_round'] = $arrRoundInfo['GAME_CNBALL3'];
			$arrRoundResult['r'] = $arrRoundInfo['GAME_CNBALL3'];
			$arrRoundResult['round_hash'] = $arrRoundInfo['GAME_EOSBALL3'];
			
			$strDate = $arrRoundInfo['NEXTTIME_EOSBALL3'];
			if(strlen($strDate) < 10)
				return null;
			$arrRoundResult['date'] = substr($strDate, 0, 10);
	
			$arrNorBall[0] = $arrRoundInfo['NUM1_EOSBALL3'];
			$arrNorBall[1] = $arrRoundInfo['NUM2_EOSBALL3'];
			$arrNorBall[2] = $arrRoundInfo['NUM3_EOSBALL3'];
			$arrNorBall[3] = $arrRoundInfo['NUM4_EOSBALL3'];
			$arrNorBall[4] = $arrRoundInfo['NUM5_EOSBALL3'];
			$arrNorBall[5] = $arrRoundInfo['NUM6_EOSBALL3'];
		} else if($iGame == GAME_COIN5_BALL){
			$arrRoundResult['date_round'] = $arrRoundInfo['GAME_CNBALL5'];
			$arrRoundResult['r'] = $arrRoundInfo['GAME_CNBALL5'];
			
			$strDate = $arrRoundInfo['NEXTTIME_CNBALL5'];
			if(strlen($strDate) < 10)
				return null;
			$arrRoundResult['date'] = substr($strDate, 0, 10);
	
			$arrNorBall[0] = $arrRoundInfo['NUM1_CNBALL5'];
			$arrNorBall[1] = $arrRoundInfo['NUM2_CNBALL5'];
			$arrNorBall[2] = $arrRoundInfo['NUM3_CNBALL5'];
			$arrNorBall[3] = $arrRoundInfo['NUM4_CNBALL5'];
			$arrNorBall[4] = $arrRoundInfo['NUM5_CNBALL5'];
			$arrNorBall[5] = $arrRoundInfo['NUM6_CNBALL5'];
		} else if($iGame == GAME_COIN3_BALL){
			$arrRoundResult['date_round'] = $arrRoundInfo['GAME_CNBALL3'];
			$arrRoundResult['r'] = $arrRoundInfo['GAME_CNBALL3'];
			
			$strDate = $arrRoundInfo['NEXTTIME_CNBALL3'];
			if(strlen($strDate) < 10)
				return null;
			$arrRoundResult['date'] = substr($strDate, 0, 10);
	
			$arrNorBall[0] = $arrRoundInfo['NUM1_CNBALL3'];
			$arrNorBall[1] = $arrRoundInfo['NUM2_CNBALL3'];
			$arrNorBall[2] = $arrRoundInfo['NUM3_CNBALL3'];
			$arrNorBall[3] = $arrRoundInfo['NUM4_CNBALL3'];
			$arrNorBall[4] = $arrRoundInfo['NUM5_CNBALL3'];
			$arrNorBall[5] = $arrRoundInfo['NUM6_CNBALL3'];
		} else return null;

		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;
	}

	function fetchPballs_ep($strResult)
	{
		$arrRounds = [ null, null];

		$nStartPos = strpos($strResult, '[{"');
		if($nStartPos === false )  
			return $arrRounds;
		$strResult = trim(substr($strResult, $nStartPos));

		$arrResult = json_decode($strResult, true);
		if(is_null($arrResult) || !is_array($arrResult))
			return $arrRounds;
		
		if(!array_key_exists('data', $arrResult))
			return $arrRounds;

		$arrResult = $arrResult['data'];
		if(count($arrResult) < 2)
			return $arrRounds;

		$cnt = count($arrResult);
		$arrRounds[0] = parsePballRound_ep2($arrResult[$cnt-1]); 
		$arrRounds[1] = parsePballRound_ep2($arrResult[$cnt-2]); 

		return $arrRounds;

	}

	function parsePballRound_ep2($arrRoundInfo)
	{
		/*
		{
			"DT": "2022-09-13T08:57:00.000Z",
			"GC": 405285,
			"PBALL": 9,
			"PRANGE": 4,
			"NUMS": "22,9,4,20,23",
			"NUMSUM": 78,
			"SRANGE": 5,
			"GC_TODAY": 179,
		}
		*/
		if(is_null($arrRoundInfo))
			return null;

		if(!array_key_exists("GC_TODAY", $arrRoundInfo) || !array_key_exists("DT", $arrRoundInfo) )
			return null;

		$arrRoundResult['date_round'] = $arrRoundInfo['GC_TODAY'];
		$arrRoundResult['r'] = $arrRoundInfo['GC_TODAY'];
		$arrRoundResult['times'] = $arrRoundInfo['GC'];
		$arrRoundResult['round_hash'] = $arrRoundInfo['GC'];
		
		$strDate = $arrRoundInfo['DT'];
		if(strlen($strDate) < 10)
			return null;
		$arrRoundResult['date'] = substr($strDate, 0, 10);

		$arrball = explode(',', $arrRoundInfo['NUMS']);
		if(count($arrball) < 5)
			return null;

		$arrNorBall[0] = $arrball[0];
		$arrNorBall[1] = $arrball[1];
		$arrNorBall[2] = $arrball[2];
		$arrNorBall[3] = $arrball[3];
		$arrNorBall[4] = $arrball[4];
		$arrNorBall[5] = $arrRoundInfo['PBALL'];
		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;
	}

	
	/////////////////////////////////////////////////////
	//UPDOWN 회차결과 얻어오기
	function fetchPball_down($strResult)
	{
		$nStartPos = strpos($strResult, "{\"");
		if($nStartPos === false )  
			return null;
		$strResult = trim(substr($strResult, $nStartPos));

		$arrResult = json_decode($strResult, true);
		
		if(array_key_exists('error', $arrResult) && $arrResult['error'] == true)
			return null;
		return parsePballRound_down($arrResult);

	}

	function parsePballRound_down($arrRoundInfo)
	{
		/*
		{
			"error": false,
			"msg": "성공",
			"g_date": "2023-10-31",
			"times": "848892816",
			"date_round": 463,
			"p_ball": 3,
			"p_section": "B",
			"p_oe": "홀",
			"p_uo": "언더",
			"n_ball": "9,2,15,13,21",
			"n_section": "D",
			"n_sum": 60,
			"n_oe": "짝",
			"n_uo": "언더",
			"n_bms": "소"
		}
		*/
		if(is_null($arrRoundInfo))
			return null;

		if(!array_key_exists("date_round", $arrRoundInfo) || !array_key_exists("g_date", $arrRoundInfo) )
			return null;

		$arrRoundResult['date_round'] = $arrRoundInfo['date_round'];
		$arrRoundResult['r'] = $arrRoundInfo['date_round'];
		$arrRoundResult['times'] = $arrRoundInfo['times'];
		$arrRoundResult['round_hash'] = $arrRoundInfo['times'];
		
		$strDate = $arrRoundInfo['g_date'];
		if(strlen($strDate) != 10)
			return null;
		$arrRoundResult['date'] = $arrRoundInfo['g_date'];

		
		$arrball = explode(',', $arrRoundInfo['n_ball']);
		if(count($arrball) < 5)
			return null;

		$arrNorBall[0] = intval($arrball[0]);
		$arrNorBall[1] = intval($arrball[1]);
		$arrNorBall[2] = intval($arrball[2]);
		$arrNorBall[3] = intval($arrball[3]);
		$arrNorBall[4] = intval($arrball[4]);
		$arrNorBall[5] = $arrRoundInfo['p_ball'];
		$arrRoundResult['ball'] = $arrNorBall;

		return $arrRoundResult;
	}




?>