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
	
	//PBG 회차등록상태 
	$bPgReg = false; 
	$bPgEmptyReg = false; 

	$bBenzLogin = false; //로그인상태
	$sessBenz = "";
	$roundPbg = null;
	$bLastRound = true;
	$benzInfo = null;
	
	//EOS 회차등록상태 
	$bE5Reg = false; 
	$bE5EmptyReg = false; 
	
	//Coin 회차등록상태 
	$bC5Reg = false; 
	$bC5EmptyReg = false; 

	$logHead = "";
	$orE5 = 0;
	$orC5 = 0;

	$hPgball = null;
	$hE5ball = null;
	$hC5ball = null;

	while(true){
		$tmCurrent = time(); 
		$tmNow = $tmCurrent + TM_OFFSET;
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
		
		$nHour = date("G",$tmCurrent);
		$nMin = date("i",$tmCurrent);
		$nSec = date("s",$tmCurrent);
		
		if($bPgEnable){
			if(!$bBenzLogin){
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
					$result = curlProc($hPgball, $fLog );

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
				$result = curlProc($hPgball, $fLog );
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
			} 
			else if($bBenzLogin && !$bPgReg && $nMin%5 == 0 && ($nSec>=0 && $nSec <= 50 ) ){
						
				if($hPgball == null){
					$hPgball = curl_multi_init();
					
					$tContent = "PBG-REQ-benz-".$hPgball;
					writeLog($fLog, $logHead.$tContent);
					$curl = curlPbg_benz($benzInfo, $sessBenz, $roundPbg, $bLastRound);
					curl_multi_add_handle($hPgball, $curl);
				}
				$result = curlProc($hPgball, $fLog );
				$arrRegResult = null;
				if($result != null){
					$roundResult = fetchPbg_benz($result, $sessBenz, $bBenzLogin);
					$arrRegResult = $objServLogic->pbregister_benz($dbLionConn, $roundResult, $bLastRound);
					if($bLastRound){
						$arrRegResult = null;
						$bLastRound = false;
					}
				}

				if($arrRegResult != null && $arrRegResult['status'] == "success") {
					$bPgReg = true;
					$tContent = "PBG-".$arrRegResult['data']['times'];		
					writeLog($fLog, $logHead.$tContent);

					if($bMultiReg){
						
						$arrRegResult = $objServLogic->pbregister_benz($dbTigerConn, $arrRegResult['data']);
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
			else if($bBenzLogin && ($bPgReg || $bPgEmptyReg)  && $nMin%5 == 4 && ($nSec>=30 && $nSec <= 50 ) ){
				
				if($hPgball == null){
					$hPgball = curl_multi_init();
					
					$tContent = "PBG-KEEP-benz-".$hPgball;
					writeLog($fLog, $logHead.$tContent);
					$curl = curlKeep_benz($benzInfo, $sessBenz);
					curl_multi_add_handle($hPgball, $curl);
				}
				$result = curlProc($hPgball, $fLog );
				$arrRegResult = null;
				if($result != null){
					$bBenzLogin = fetchKeep_benz($result, $sessBenz);
					// writeLog($fLog, $result);
					$bPgReg = false;
					$bPgEmptyReg = false;
					$roundPbg = null;
					writeLog($fLog, $bBenzLogin?"PBG-Keep-".$sessBenz:"None-".$sessBenz);
				}		
			} else $hPgball = null;

		}

		
		if($bEos5Enable){

			//EOS5분 파워볼 회차등록
			if(!$bE5Reg && $nMin%5 == 0 && ($nSec>=0 && $nSec <= 50 ) ){
						
				if($hE5ball == null){
					$hE5ball = curl_multi_init();

					// $orE5 = 1;
					$orE5 ++;
					if($orE5 > 9)
						$orE5 = 0;

					if($orE5 % 2 == 1 ){
						$tContent = "EOS5-REQ-bepicklist-".$hE5ball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlEosPballs(ROUND_5MIN);
						curl_multi_add_handle($hE5ball, $curl);
					}
					else{
						$tContent = "EOS5-REQ-bepick-".$hE5ball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlEosPball(ROUND_5MIN);
						curl_multi_add_handle($hE5ball, $curl);
					}
				}
				$result = curlProc($hE5ball, $fLog );
				$arrRegResult = null;
				if($result != null){
					if($orE5 % 2 == 1 ){
						$roundResults = fetchEosPballRounds($result);
						$arrRegResult = $objServLogic->eos5registerlist($dbLionConn, $roundResults, $fLog);
						
					}
					else {
						$roundResult = fetchEosPballRound($result);
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
			if(!$bC5Reg && $nMin%5 == 0 && ($nSec>=0 && $nSec <= 50 ) ){
						
				if($hC5ball == null){
					$hC5ball = curl_multi_init();

					// $orC5 = 1;
					$orC5 ++;
					if($orC5 > 9)
						$orC5 = 0;

					if($orC5 % 2 == 1 ){
						$tContent = "Coin5-REQ-drscorelist-".$hC5ball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlCoinPballs(ROUND_5MIN);
						curl_multi_add_handle($hC5ball, $curl);
					}
					else{
						$tContent = "Coin5-REQ-drscore-".$hC5ball;
						writeLog($fLog, $logHead.$tContent);
						$curl = curlCoinPball(ROUND_5MIN);
						curl_multi_add_handle($hC5ball, $curl);
					}
				}
				$result = curlProc($hC5ball, $fLog );
				$arrRegResult = null;
				if($result != null){
					// writeLog($fLog, $result);
					$roundResults = fetchScoreCoinRound($result);
					$arrRegResult = $objServLogic->coin5registerlist($dbLionConn, $roundResults, $fLog);
				}

				if($arrRegResult != null && $arrRegResult['status'] == "success") {
					$bC5Reg = true;
					
					$tContent = "Coin5-".$arrRegResult['data'][0]['r'];		
					writeLog($fLog, $logHead.$tContent);

					if($bMultiReg){
						$arrRegResult = $objServLogic->coin5registerlist($dbTigerConn, $arrRegResult['data'], $fLog);
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
		
		//END
		if( $hPgball == null && $hE5ball == null && $hC5ball == null ){
			sleep(3);
		}
		// writeLog($fLog, "END");

	}
	
	
	sleep(100);
	
?>