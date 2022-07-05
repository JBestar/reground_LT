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



?>