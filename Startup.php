<?php

	include_once('libraries/Snoopy.php');
	include_once('helpers/Constant.php');
	include_once('helpers/Logic_Helper.php');
	include_once('helpers/MY_Helper.php');
	include_once('ServiceLogic.php');
	
	//서버가 기동할 동안 대기
	sleep(1);

	date_default_timezone_set('Asia/Seoul');

	$arrLionConf = parse_ini_file("config/config_lion.ini");
	$arrTigerConf = parse_ini_file("config/config_tiger.ini");
	
	//자료기지 접속
	$dbLionConn = connectDb($arrLionConf);

	if ($dbLionConn->connect_errno) {
	    $dbLionConn = null;
	}

	$bMultiReg = true;
	$dbTigerConn = connectDb($arrTigerConf);

	if ($dbTigerConn->connect_errno) {
	    $dbTigerConn = null;
		$bMultiReg = false;
	}

	$tRootDir = dirname(__FILE__);
	
	if(!is_dir($tRootDir."/log")){
		mkdir($tRootDir."/log");
	}
	
    $fName = date( 'Y-m-d', time());
	$fLog = fopen($tRootDir."/log/reg_".$fName, "a") ;

	sleep(1);

	
	//로직 생성
	$objServLogic = new ServiceLogic();

	$bPgEnable = true;
	$bEos5Enable = true;
	$bCoin5Enable = true;
	$bBgbEnable = true;
	$bBenzEnable = false;
	//PBG 회차등록상태 
	$bPgReg = false; 
	$bPgEmptyReg = false; 

	$bBenzLogin = false; //로그인상태
	$sessBenz = "";
	$roundPbg = null;
	$bLastRound = false;
	$benzInfo = null;
	$benzState = true;
	//EOS 회차등록상태 
	$bE5Reg = false; 
	$bE5EmptyReg = false; 
	
	//Coin 회차등록상태 
	$bC5Reg = false; 
	$bC5EmptyReg = false; 

	//보글 회차등록상태 
	$bBbReg = false; 
	$bBbEmptyReg = false; 
	
	$logHead = "";
	$orPbg = 0;
	$orE5 = 0;
	$orC5 = 0;

	$hPgball = null;
	$hE5ball = null;
	$hC5ball = null;
	$hBgball = null;

	while(true){
		$tmCurrent = time(); 
		$tmNow = $tmCurrent ;
		$nHour = date("G",$tmNow);
		$nMin = date("i",$tmNow);
		$nSec = date("s",$tmNow);
		
		//로그파일 
		if($nHour == 0 && $nMin == 0 && $nSec < 4){
			$strDate = date( 'Y-m-d', $tmNow );
			if($fName !== $strDate){
				if($fLog)
					fclose($fLog);
				$fName = $strDate;
				$fLog = fopen($tRootDir."/log/reg_".$fName, "a") ;
				writeLog($fLog, $logHead."Log File--".$fName);
			}
		}
		
		$benzState = true;
		if($bPgEnable){

			if($bBenzEnable) {
				if(!$bBenzLogin){
					// writeLog($fLog, $logHead."PBG-LOGIN-benz-");

					if($hPgball == null){
						$benzInfo = $objServLogic->getSiteInfo($dbLionConn, CONF_BENZ_ACC);

						if($benzInfo != null && strlen($benzInfo['site']) > 0 
							&& strlen($benzInfo['uid']) > 0 && strlen($benzInfo['pwd']) > 0){
							$hPgball = curl_multi_init();
							
							$tContent = "PBG-LOGIN-benz-".$hPgball;
							writeLog($fLog, $logHead.$tContent);
							
							$curl = curlLogin_benz($benzInfo);
							curl_multi_add_handle($hPgball, $curl);
						}
					}
					if($hPgball != null)
						$result = curlProc($hPgball, $fLog, 'PBG');

					$arrRegResult = null;
					if($result != null){
						$bBenzLogin = fetchLogin_benz($result, $sessBenz);
						// writeLog($fLog, $result);
						writeLog($fLog, $bBenzLogin?"PBG-Keep-".$sessBenz:"None-".$sessBenz);
					}		
				} else if($bBenzLogin && is_null($roundPbg)){
					if($hPgball == null){
						$hPgball = curl_multi_init();
						
						$tContent = "PBG-CURRENT-benz-".$hPgball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlPbg_benz($benzInfo, $sessBenz);
						curl_multi_add_handle($hPgball, $curl);
					}
					$result = curlProc($hPgball, $fLog, 'PBG');
					$arrRegResult = null;
					if($result != null){
						$roundPbg = fetchPbg_benz($result, $sessBenz, $bBenzLogin);
						// writeLog($fLog, $result);
						$lastRound = $objServLogic->getPbgRound($dbLionConn, $roundPbg, true);
						if(!is_null($lastRound) && $lastRound['round_state'] == 1)
							$bLastRound = false;
						else 
						$bLastRound = true;

						writeLog($fLog, $bBenzLogin?"PBG-Keep-".$sessBenz:"None-".$sessBenz);
					}		
				} else $benzState = false; 
			} else $benzState = false;
			
			
			if(!$bPgReg && $nMin%5 == 0 && ($nSec>=0 && $nSec <= 50 ) ){
						
				if($hPgball == null){
					$hPgball = curl_multi_init();
					
					// $orPbg = 0;
					$orPbg ++;
					if($orPbg > 5)
						$orPbg = 1;

					if($bBenzLogin && $orPbg % 5 < 4){
						$tContent = "PBG-REQ-benz-".$hPgball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlPbg_benz($benzInfo, $sessBenz, $roundPbg, $bLastRound);
						curl_multi_add_handle($hPgball, $curl);
					} else if($orPbg % 5 == 2 || $orPbg % 5 == 0 || $orPbg % 5 == 4){
						$tContent = "PBG-REQ-bpk-".$hPgball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlPbg_bpk(ROUND_5MIN);
						curl_multi_add_handle($hPgball, $curl);
					} else {
						$tContent = "PBG-REQ-bpk2-".$hPgball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlpbg_bpk2(ROUND_5MIN);
						curl_multi_add_handle($hPgball, $curl);
					}  				
				}
				$result = curlProc($hPgball, $fLog, 'PBG');
				$arrRegResult = null;
				if($result != null){
					if($bBenzLogin && $orPbg % 5 < 4){
						$roundResult = fetchPbg_benz($result, $sessBenz, $bBenzLogin);
						$arrRegResult = $objServLogic->pbgregister_benz($dbLionConn, $roundResult, $bLastRound);
						if($bLastRound){
							$arrRegResult = null;
							$bLastRound = false;
							$orPbg = 1;
						}
					} else if($orPbg % 5 == 2 || $orPbg % 5 == 0 || $orPbg % 5 == 4 ){
						$roundResult = fetchPball_bpk($result);
						$arrRegResult = $objServLogic->pbgregister($dbLionConn, $roundResult); 
					} else {
						$roundResult = fetchPball_bpk2($result);
						$arrRegResult = $objServLogic->pbgregister($dbLionConn, $roundResult);
					} 
				}

				if($arrRegResult != null && $arrRegResult['status'] == "success") {
					$bPgReg = true;
					$tContent = "PBG-".$arrRegResult['data']['times'];		
					writeLog($fLog, $logHead.$tContent);

					if($bMultiReg){
						if($bBenzLogin && $orPbg % 5 < 4)
							$arrRegResult = $objServLogic->pbgregister_benz($dbTigerConn, $arrRegResult['data']);
						else 
							$arrRegResult = $objServLogic->pbgregister($dbTigerConn, $arrRegResult['data']);
						writeLog($fLog, $logHead."PBG-tiger-".$arrRegResult['status']);
					}
					
				} else if(!$bPgEmptyReg) {	//빈회차등록
					$objServLogic->pbregister_empty($dbLionConn);
					if($bMultiReg){
						$objServLogic->pbregister_empty($dbTigerConn);
					}
					
					$bPgEmptyReg = true;
					$tContent = "PBG-empty";
					writeLog($fLog, $logHead.$tContent);

				}

			} 
			else if(($bPgReg || $bPgEmptyReg)  && $nMin%5 == 4 && ($nSec>=30 && $nSec <= 50 ) ){
				
				if($bBenzEnable){
					$dbInfo = $objServLogic->getSiteInfo($dbLionConn, CONF_BENZ_ACC);
					if($benzInfo['uid'] !== $dbInfo['uid']) {
						$bBenzLogin = false;
					}
				}

				if($bBenzLogin){
					if($hPgball == null){
						$hPgball = curl_multi_init();
						
						$tContent = "PBG-KEEP-benz-".$hPgball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlKeep_benz($benzInfo, $sessBenz);
						curl_multi_add_handle($hPgball, $curl);
					}
					$result = curlProc($hPgball, $fLog, 'PBG');
					$arrRegResult = null;
					if($result != null){
						$bBenzLogin = fetchKeep_benz($result, $sessBenz);
						// writeLog($fLog, $result);
						$roundPbg = null;
						$bPgReg = false;
						$bPgEmptyReg = false;
						$orPbg = 0;
						writeLog($fLog, $bBenzLogin?"PBG-Keep-".$sessBenz:"None-".$sessBenz);
					}		
				} else {
					$bPgReg = false;
					$bPgEmptyReg = false;
					$orPbg = 0;
				}
				
			} else if(!$benzState) $hPgball = null;

		}

		
		if($bEos5Enable){

			//EOS5분 파워볼 회차등록
			if(!$bE5Reg && $nMin%5 == 0 && ($nSec>=0 && $nSec <= 50 ) ){
						
				if($hE5ball == null){
					$hE5ball = curl_multi_init();

					$orE5 ++;
					if($orE5 > 3)
						$orE5 = 1;

					if($orE5 % 5 == 1 ){
						$tContent = "EOS5-REQ-bbj2-".$hE5ball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlEosPballs_bbj(ROUND_5MIN);
						curl_multi_add_handle($hE5ball, $curl);
					} 
					else if($orE5 % 5 == 3){
						$tContent = "EOS5-REQ-bbj1-".$hE5ball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlEosPball_bbj(ROUND_5MIN);
						curl_multi_add_handle($hE5ball, $curl);
					} else{
						$tContent = "EOS5-REQ-bpk2-".$hE5ball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlEosPball_bpk(ROUND_5MIN);
						curl_multi_add_handle($hE5ball, $curl);
					}
				}
				$result = curlProc($hE5ball, $fLog, 'EOS5');
				$arrRegResult = null;
				if($result != null){

					if($orE5 % 5 == 1 ){
						$roundResults = fetchPballs_bbj($result);
						$arrRegResult = $objServLogic->eos5registerlist($dbLionConn, $roundResults, $fLog);
					}
					else if($orE5 % 5 == 3) {
						$roundResult = fetchPball_bbj($result);
						$arrRegResult = $objServLogic->eos5register($dbLionConn, $roundResult, $fLog);
					} else {
						$roundResult = fetchPball_bpk($result);
						$arrRegResult = $objServLogic->eos5register($dbLionConn, $roundResult, $fLog);
					}
				}

				if($arrRegResult != null && $arrRegResult['status'] == "success") {
					$bE5Reg = true;
					
					$tContent = "EOS5-".$arrRegResult['data']['r'];		
					writeLog($fLog, $logHead.$tContent);

					if($bMultiReg){
						$arrRegResult = $objServLogic->eos5register($dbTigerConn, $arrRegResult['data'], $fLog);
						writeLog($fLog, $logHead."EOS5-tiger-".$arrRegResult['status']);
					}
					
				} else if(!$bE5EmptyReg) {	//빈회차등록
					
					$objServLogic->eos5register_empty($dbLionConn);
					if($bMultiReg){
						$objServLogic->eos5register_empty($dbTigerConn);
					}
					
					$bE5EmptyReg = true;
					$tContent = "EOS5-empty";
					writeLog($fLog, $logHead.$tContent);

				}

			} else if(($bE5Reg || $bE5EmptyReg)  && $nMin%5 == 4 && ($nSec>=30 && $nSec <= 50 ) ){
				$bE5Reg = false;
				$bE5EmptyReg = false;
				$orE5 = 0;
			}  else $hE5ball = null;

		}

		
		if($bCoin5Enable){

			//Coin5분 파워볼 회차등록
			if(!$bC5Reg && $nMin%5 == 0 && ($nSec>=3 && $nSec <= 50 ) ){
						
				if($hC5ball == null){
					$hC5ball = curl_multi_init();

					// $orC5 = 3;
					$orC5 ++;
					if($orC5 > 3)
						$orC5 = 1;

					if($orC5 % 4 == 2 ){
						$tContent = "Coin5-REQ-bpk2-".$hC5ball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlCoinPball_bpk2(ROUND_5MIN);
						curl_multi_add_handle($hC5ball, $curl);
					} else if($orC5 % 4 == 3 ){
						$tContent = "Coin5-REQ-down-".$hC5ball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlCoinPball_down(ROUND_5MIN);
						curl_multi_add_handle($hC5ball, $curl);
					} else{
						$tContent = "Coin5-REQ-bpk1-".$hC5ball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlCoinPball_bpk(ROUND_5MIN);
						curl_multi_add_handle($hC5ball, $curl);
					}
				}
				$result = curlProc($hC5ball, $fLog, 'Coin5');
				$arrRegResult = null;
				if($result != null){
					if($orC5 % 4 == 2 ){
						$roundResult = fetchPball_bpk2($result);
						$arrRegResult = $objServLogic->coin5register($dbLionConn, $roundResult, $fLog);
					} else if($orC5 % 4 == 3 ){
						$roundResult = fetchPball_down($result);
						$arrRegResult = $objServLogic->coin5register($dbLionConn, $roundResult, $fLog);
					} 
					else {
						$roundResult = fetchPball_bpk($result);
						$arrRegResult = $objServLogic->coin5register($dbLionConn, $roundResult, $fLog);
					}
				}

				if($arrRegResult != null && $arrRegResult['status'] == "success") {
					$bC5Reg = true;
					
					$tContent = "Coin5-".$arrRegResult['data']['r'];		
					writeLog($fLog, $logHead.$tContent);

					if($bMultiReg){
						$arrRegResult = $objServLogic->coin5register($dbTigerConn, $arrRegResult['data'], $fLog);
						writeLog($fLog, $logHead."Coin5-tiger-".$arrRegResult['status']);
					}
					
				} else if(!$bC5EmptyReg) {	//빈회차등록
					
					$objServLogic->coin5register_empty($dbLionConn);
					if($bMultiReg){
						$objServLogic->coin5register_empty($dbTigerConn);
					}
					
					$bC5EmptyReg = true;
					$tContent = "Coin5-empty";
					writeLog($fLog, $logHead.$tContent);

				}

			} else if(($bC5Reg || $bC5EmptyReg)  && $nMin%5 == 4 && ($nSec>=30 && $nSec <= 50 ) ){
				$bC5Reg = false;
				$bC5EmptyReg = false;
				$orC5 = 0;
			} else $hC5ball = null;

		}
		
		if($bBgbEnable) {
			//보글파워볼 회차등록
			if(!$bBbReg && $nMin%2 == 0 && ($nSec>=0 && $nSec <= 50 ) ){

				if($hBgball == null){
					$hBgball = curl_multi_init();	
					$tContent = "BGBALL-REQ-".$hBgball;
					writeLog($fLog, $logHead.$tContent);
					$curl = curlBoglePball();
					curl_multi_add_handle($hBgball, $curl);
				}
				$result = curlProc($hBgball, $fLog, 'BGBALL');
				$arrRegResult = null;
				if($result != null){
					$roundResult = fetchBoglePballRound($result);
					$arrRegResult = $objServLogic->bgbregister($dbTigerConn, $roundResult);
				}
				if(!is_null($arrRegResult) && $arrRegResult['status'] == "success"){
					$bBbReg = true;

					$tContent = "BGBALL-".$arrRegResult['data']['r'];		
					writeLog($fLog, $logHead.$tContent);

					// if($bMultiReg){
					// 	$arrRegResult = $objServLogic->bgbregister($dbLionConn, $arrRegResult['data']);
					// 	writeLog($fLog, $logHead."BGBALL-honey-".$arrRegResult['status']);
					// }
				} else if(!$bBbEmptyReg) {	//빈회차등록
					
					$objServLogic->bgbregister_empty($dbTigerConn);
					// if($bMultiReg){
					// 	$objServLogic->bgbregister_empty($dbLionConn);
					// }
					
					$bBbEmptyReg = true;
					$tContent = "BGBALL-empty";
					writeLog($fLog, $logHead.$tContent);

				}
			} else if(($bBbReg || $bBbEmptyReg)  && $nMin%2 == 1 && ($nSec>=30 && $nSec <= 50 ) ){
				$bBbReg = false;
				$bBbEmptyReg = false;
			} else $hBgball = null;
		}


		//END
		if( $hPgball == null && $hE5ball == null && $hC5ball == null && $hBgball == null){
			sleep(1);
		}
		// writeLog($fLog, "END");

	}
	
	
	sleep(100);
	
?>