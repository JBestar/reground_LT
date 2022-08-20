<?php
	
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
	
	function curlProc(&$hMul, $fLog){
		
		$result = null;
		$logHead = "<MultiCurl>";

		curl_multi_exec($hMul, $running);
		curl_multi_select($hMul);

		if ($state = curl_multi_info_read($hMul)) {
			
			$result = curl_multi_getcontent($state['handle']);
			// writeLog($fLog, $logHead.json_encode($result));
			// $headerSize = curl_getinfo($state['handle'], CURLINFO_HEADER_SIZE);
			// writeLog($fLog, $logHead.$headerSize);
			// $result['header'] = substr($response, 0, $header_size);
			// $result = substr( $result, $headerSize );
			// writeLog($fLog, $logHead.$result);
			
			curl_multi_remove_handle($hMul, $state['handle']);
			curl_multi_close($hMul);
			$hMul = null;
		}

		// writeLog($fLog, $logHead.$hMul."=".$running);
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
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);

		return $curl;
	}
	
	function curlEosPball($roundMin){

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
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);

	}

	function curlEosPballs($roundMin){

		$milliSec = floor(microtime(true) * 1000);

		$url = "";
		if($roundMin == ROUND_5MIN){
			$url = "https://bepick.net/api/get_more/eosball5m/default/"; //2206090002
		} else if($roundMin == ROUND_3MIN){
			$url = "https://bepick.net/api/get_more/eosball3m/default/"; //2206090002
		}

		$arrRounds = getLastRoundInfos($roundMin);
		$strRoundInfo = roundInfoStr($arrRounds[2]);

		$url.= $strRoundInfo;	 
		$url.= "?_=".$milliSec;
		
		$header =  [
            'Host: bepick.net',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];
		return getCurl($url, $header);

	}

	
	function curlCoinPball($roundMin){
	
		if($roundMin == ROUND_5MIN)
			$url = "https://game.dr-score.com/api/coinpowerball5/get";
		else 
			$url = "https://game.dr-score.com/api/coinpowerball3/get";

		// $url.= "?t=".time();
		
		$header =  [
            'Host: game.dr-score.com',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);
	}

	function curlCoinPballs($roundMin){
		if($roundMin == ROUND_5MIN)
			$url = "https://game.dr-score.com/api/coinpowerball5/getsect?gamecount=2";
		else 
			$url = "https://game.dr-score.com/api/coinpowerball3/getsect?gamecount=2";

		// $url.= "?t=".time();
		
		$header =  [
            'Host: game.dr-score.com',
			'Connection: keep-alive',
			'Cache-Control: max-age=0',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
			'Accept: */*',
			'Accept-Encoding: ',
			'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
		];

		return getCurl($url, $header);
	}
	
	function curlBoglePball(){
	
		$url = "http://boglegames.com/game/powerball/ajax.get_live_data.php";
		$url.= "?t=".time();
		return getCurl($url);
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
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
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
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
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
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.0.0 Safari/537.36',
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














	/////////////////////////////////////////////////////
	//베틱 EOS5분파워볼 회차결과 얻어오기
	function fetchEosPballRound($strResult)
	{
		$nStartPos = strpos($strResult, "{\"");
		if($nStartPos === false )  
			return null;
		$strResult = trim(substr($strResult, $nStartPos));

		$arrResult = json_decode($strResult, true);
		
		return parseEosPballRound($arrResult);

	}

	//베틱 EOS5분파워볼 회차결과 얻어오기
	function fetchEosPballRounds($strResult)
	{
		$nStartPos = strpos($strResult, "[{\"");
		if($nStartPos === false )  
			return null;
		$strResult = trim(substr($strResult, $nStartPos));

		$arrResult = json_decode($strResult, true);
		
		$arrRounds = [ null, null];
		if(is_null($arrResult))
			return $arrRounds;

		if(count($arrResult) >= 2){
			$arrRounds[0] = parseEosPballRound($arrResult[0]);
			$arrRounds[1] = parseEosPballRound($arrResult[1]);
		}
		
		return $arrRounds;

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

	function parseEosPballRound($arrRoundInfo)
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

		if(!array_key_exists("round", $arrRoundInfo) || !array_key_exists("date", $arrRoundInfo) )
			return null;

		$arrRoundResult['date_round'] = $arrRoundInfo['round'];
		$arrRoundResult['r'] = $arrRoundInfo['round'];
		$arrRoundResult['times'] = $arrRoundInfo['rownump'];
		
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


	//드림스코 코인파워볼 회차결과 얻어오기
	function fetchScoreCoinRound($strResult)
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
			$arrRounds[0] = parseScoreCoinRound($arrResult[0]);
			$arrRounds[1] = parseScoreCoinRound($arrResult[1]);
		}

		return $arrRounds;
	}

	function parseScoreCoinRound($arrRoundInfo)
	{
		
		if(is_null($arrRoundInfo))
			return null;

		if(!array_key_exists("TIME", $arrRoundInfo) || !array_key_exists("DAYROUND", $arrRoundInfo)
			|| !array_key_exists("HASH", $arrRoundInfo))
			return null;

		$arrRoundResult = [];
		$arrRoundResult['date_round'] = $arrRoundInfo['DAYROUND'];
		$arrRoundResult['r'] = $arrRoundInfo['DAYROUND'];
		$arrRoundResult['times'] = $arrRoundInfo['HASH'];
		

		$strTime = $arrRoundInfo['TIME'];
		if(strlen($strTime) != 19)
			return null;
		$arrRoundResult['date'] = substr($strTime, 0, 10);

		if($arrRoundResult['date_round'] == 288){
			$tmRoundBetEnd = strtotime("-1 day", strtotime($arrRoundResult['date']));
		}
		//$strDate = $arrRoundInfo['DATENUM'];
		//if(strlen($strDate) != 8)
		//	return null;
		//$arrRoundResult['date'] = substr($strDate, 0, 4)."-".substr($strDate, 4, 2)."-".substr($strDate, 6, 2);

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

?>