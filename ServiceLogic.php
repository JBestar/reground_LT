<?php
include_once('models/ConfSite_Model.php');
include_once('models/PbRound_Model.php');
include_once('models/NpbRound_Model.php');

class ServiceLogic
{
	private $mSnoopy ;
	private $modelConfSite;

	private $modelPballRound;
	private $modelEos5Round;
	private $modelCoin5Round;
	private $modelBgballRound;


	function __construct(){
		$this->mSnoopy = new Snoopy();

		$this->modelConfSite = new ConfSite_Model();

		$this->modelPballRound = new NpbRound_Model();
		$this->modelEos5Round = new PbRound_Model(GAME_EOS5_BALL);
		$this->modelCoin5Round = new PbRound_Model(GAME_COIN5_BALL);
		$this->modelBgballRound = new PbRound_Model(GAME_BOGLE_BALL);
	}

	//배팅사이트 정보얻기
	public function getSiteConf($dbConn, $confId)
	{		
		//게임배팅시간
		$objConfig = $this->modelConfSite->getById($dbConn, $confId);
		if(!is_null($objConfig)){
			return trim($objConfig->conf_content);
		}
		return "";
		
	}
	
	//배팅사이트 정보얻기
	public function getSiteInfo($dbConn, $confId)
	{	
		$infos = ['site'=>'', 'uid'=>'', 'pwd'=>''];
		//게임배팅시간
		$sContent = $this->getSiteConf($dbConn, $confId);
		$data = explode("|", $sContent);
		if(count($data) > 2){
			$strSite = trim($data[0]); 
			$len = strlen($strSite);
			if($len > 8 && substr($strSite, $len-1, 1) === "/"){
				$strSite = substr($strSite, 0, $len-1);
			}
			$infos['site'] = $strSite;
			$infos['uid'] = trim($data[1]); 
			$infos['pwd'] = trim($data[2]); 
			$nLastPos = 0;
			$infos['domain'] = fetchStr($infos['site'], "//", "", $nLastPos);

		}
		
		return $infos;
		
	}

	//파워볼 빈회차등록
	public function pbregister_empty($dbConn)
	{		
		//자료기지 체크
		if(is_null($dbConn)){
			return ;
		}

		$arrRounds = getLastRoundInfos(ROUND_5MIN);

		$arrRoundInfo =  $arrRounds[0];
		$arrPbRoundInfo = $this->modelPballRound->registerEmptyRound($dbConn, $arrRoundInfo);
	}
	//EOS5분 파워볼 빈회차등록
	public function eos5register_empty($dbConn)
	{		
		//자료기지 체크
		if(is_null($dbConn)){
			return ;
		}

		$arrRounds = getLastRoundInfos(ROUND_5MIN);

		$arrRoundInfo =  $arrRounds[0];
		$arrEosRoundInfo = $this->modelEos5Round->registerEmptyRound($dbConn, $arrRoundInfo);
	}

	//Coin5분 파워볼 빈회차등록
	public function coin5register_empty($dbConn)
	{		
		//자료기지 체크
		if(is_null($dbConn)){
			return ;
		}

		$arrRounds = getLastRoundInfos(ROUND_5MIN);

		$arrRoundInfo =  $arrRounds[0];
		$arrEosRoundInfo = $this->modelCoin5Round->registerEmptyRound($dbConn, $arrRoundInfo);
	}

	//보글파워볼 빈회차등록
	public function bgbregister_empty($dbConn)
	{		
		//자료기지 체크
		if(is_null($dbConn)){
			return ;
		}

		$arrRoundInfo = getLastRoundInfo(ROUND_2MIN);
		$arrPbRoundInfo = $this->modelBgballRound->registerEmptyRound($dbConn, $arrRoundInfo);
		
	}

	//파워볼 회차등록
	public function pbgregister($dbConn, $arrRoundResult, $fLog = null)
	{
		$arrResult = array();

		//자료기지 체크
		if(is_null($dbConn)){
			$arrResult['status'] = "db_error";
			$this->pbgregisterDiagLog($fLog, 'PBG-pbgregister db_error conn=null', 'db_null');
			return $arrResult;
		}

		if(is_null($arrRoundResult)){
			$arrResult['status'] = "round_null";
			// 파싱 실패 상세는 Startup 의 PBG-diag( fetch )에 남김 — 중복 방지
			return $arrResult;
		}

		$arrRounds = getLastRoundInfos(ROUND_5MIN);
		
		$arrRoundInfo =  $arrRounds[0];
		$arrPbRoundInfo = $this->modelPballRound->registerEmptyRound($dbConn, $arrRoundInfo);

		if (is_null($arrPbRoundInfo)) {
			$arrResult['status'] = "fail";
			$emptyDiag = $this->modelPballRound->getLastEmptyInsertDiag();
			$apiBits = ' api_times=' . (isset($arrRoundResult['times']) ? $arrRoundResult['times'] : '');
			$apiBits .= ' api_date=' . (isset($arrRoundResult['date']) ? $arrRoundResult['date'] : '');
			$apiBits .= ' api_date_round=' . (isset($arrRoundResult['date_round']) ? $arrRoundResult['date_round'] : '');
			$apiBits .= ' local_date=' . (isset($arrRoundInfo['round_date']) ? $arrRoundInfo['round_date'] : '');
			$apiBits .= ' local_no=' . (isset($arrRoundInfo['round_no']) ? $arrRoundInfo['round_no'] : '');
			$this->pbgregisterDiagLog($fLog, 'PBG-pbgregister empty_round_insert_fail '.$emptyDiag.$apiBits, 'empty_ins');
			return $arrResult;
		}

		$nRegPbId = $this->modelPballRound->registerRound($dbConn, $arrPbRoundInfo, $arrRoundResult);

		if($nRegPbId > 0){
			
			$arrResult['status'] = "success";
			$arrResult['data'] = $arrRoundResult;
		}
		else {
			$arrResult['status'] = "fail";
			$diag = $this->modelPballRound->registerRoundDiagnose($arrPbRoundInfo, $arrRoundResult);
			$sqlDiag = $this->modelPballRound->getLastRegisterSqlDiag();
			$this->pbgregisterDiagLog($fLog, 'PBG-pbgregister regfail '.$diag.($sqlDiag !== '' ? ' '.$sqlDiag : ''), 'regfail');
		}

		return $arrResult;
	}

	/**
	 * 동일 5분 슬롯에서 같은 유형의 진단 로그는 한 번만 (로그 폭주 방지)
	 */
	private function pbgregisterDiagLog($fLog, $message, $tag)
	{
		if ($fLog === null || ! function_exists('writeLog')) {
			return;
		}
		static $pbgDiagLogged = array();
		$slot = intdiv(time(), 300);
		$key = $slot.'_'.$tag;
		if (isset($pbgDiagLogged[$key])) {
			return;
		}
		$pbgDiagLogged[$key] = true;
		if (count($pbgDiagLogged) > 64) {
			$pbgDiagLogged = array();
		}
		writeLog($fLog, $message);
	}
		
	//PBG 회차등록
	public function pbgregisterlist($dbConn, $arrRoundResults)
	{		
		$logHead = "";
		//자료기지 체크
		if(is_null($dbConn)){
			$arrResult['status'] = "db_error";
			return $arrResult;
		}

		if(is_null($arrRoundResults)){
			$arrResult['status'] = "round_null";
			return $arrResult;
		}

		$arrRounds = getLastRoundInfos(ROUND_5MIN);
		
		$arrRoundInfo =  $arrRounds[0];
		$arrPbgRoundInfo = $this->modelPballRound->registerEmptyRound($dbConn, $arrRoundInfo);
		
		$nRegPbId = $this->modelPballRound->registerRound($dbConn, $arrPbgRoundInfo, $arrRoundResults[0]);
		
		$arrPbgLastRoundInfo = $this->modelPballRound->registerEmptyRound($dbConn, $arrRounds[1]);
		if($nRegPbId > 0)
			$this->modelPballRound->registerRound($dbConn, $arrPbgLastRoundInfo, $arrRoundResults[1]);
		else 
			$this->modelPballRound->registerRound($dbConn, $arrPbgLastRoundInfo, $arrRoundResults[0]);

			
		if($nRegPbId > 0){
			$arrResult['status'] = "success";
			$arrResult['data'] = $arrRoundResults[0];
		}
		else $arrResult['status'] = "fail";

		return $arrResult;
	}
	//파워볼 회차등록
	public function pbgregister_benz($dbConn, $arrRoundResult, $bLastRound=false)
	{		
		//자료기지 체크
		if(is_null($dbConn)){
			$arrResult['status'] = "db_error";
			return $arrResult;
		}

		if(is_null($arrRoundResult)){
			$arrResult['status'] = "round_null";
			return $arrResult;
		}

		$arrRounds = getLastRoundInfos(ROUND_5MIN);
		
		if($bLastRound){
			$arrRoundInfo =  $arrRounds[1];
		} else 
			$arrRoundInfo =  $arrRounds[0];

		$arrPbRoundInfo = $this->modelPballRound->registerEmptyRound($dbConn, $arrRoundInfo);
		if(!array_key_exists('date', $arrRoundResult)){
			$arrRoundResult['date'] = $arrRoundInfo['round_date'];
		}
		
		$nRegPbId = $this->modelPballRound->registerPbgRound($dbConn, $arrPbRoundInfo, $arrRoundResult);

		if($nRegPbId > 0){
			
			$arrResult['status'] = "success";
			$arrResult['data'] = $arrRoundResult;
		}
		else $arrResult['status'] = "fail";

		return $arrResult;
	}
	public function getPbgRound($dbConn, $roundPbg, $bLastRound=false)
	{		
		//자료기지 체크
		if(is_null($dbConn)){
			$arrResult['status'] = "db_error";
			return null;
		}

		if(is_null($roundPbg)){
			return null;
		}

		$roundFid = 0;
		if($bLastRound){
			$roundFid = $roundPbg['times'] - 1;
		} else 
			$roundFid =  $roundPbg['times'];

		return $this->modelPballRound->getByFid($dbConn, $roundFid);
	
	}
	//EOS5분 파워볼 회차등록
	public function eos5register($dbConn, $arrRoundResult, $fLog)
	{		
		$logHead = "";
		//자료기지 체크
		if(is_null($dbConn)){
			$arrResult['status'] = "db_error";
			return $arrResult;
		}

		if(is_null($arrRoundResult)){
			$arrResult['status'] = "round_null";
			return $arrResult;
		}

		$arrRounds = getLastRoundInfos(ROUND_5MIN);
		
		$arrRoundInfo =  $arrRounds[0];
		$arrEosRoundInfo = $this->modelEos5Round->registerEmptyRound($dbConn, $arrRoundInfo);
		
		$nRegPbId = $this->modelEos5Round->registerRound($dbConn, $arrEosRoundInfo, $arrRoundResult);

		if($nRegPbId > 0){
			
			$arrResult['status'] = "success";
			$arrResult['data'] = $arrRoundResult;
		}
		else $arrResult['status'] = "fail";

		return $arrResult;
	}

	
	//EOS5분 파워볼 회차등록
	public function eos5registerlist($dbConn, $arrRoundResults, $fLog)
	{		
		$logHead = "";
		//자료기지 체크
		if(is_null($dbConn)){
			$arrResult['status'] = "db_error";
			return $arrResult;
		}

		if(is_null($arrRoundResults)){
			$arrResult['status'] = "round_null";
			return $arrResult;
		}

		$arrRounds = getLastRoundInfos(ROUND_5MIN);
		
		$arrRoundInfo =  $arrRounds[0];
		$arrEosRoundInfo = $this->modelEos5Round->registerEmptyRound($dbConn, $arrRoundInfo);
		
		$nRegPbId = $this->modelEos5Round->registerRound($dbConn, $arrEosRoundInfo, $arrRoundResults[0]);
		
		$arrEosLastRoundInfo = $this->modelEos5Round->registerEmptyRound($dbConn, $arrRounds[1]);
		if($nRegPbId > 0)
			$this->modelEos5Round->registerRound($dbConn, $arrEosLastRoundInfo, $arrRoundResults[1]);
		else 
			$this->modelEos5Round->registerRound($dbConn, $arrEosLastRoundInfo, $arrRoundResults[0]);

		if($nRegPbId > 0){
			$arrResult['status'] = "success";
			$arrResult['data'] = $arrRoundResults[0];
		}
		else $arrResult['status'] = "fail";

		return $arrResult;
	}
	
	//Coin5분 파워볼 회차등록
	public function coin5registerlist($dbConn, $arrRoundResults, $fLog)
	{		
		$logHead = "";
		//자료기지 체크
		if(is_null($dbConn)){
			$arrResult['status'] = "db_error";
			return $arrResult;
		}

		if(is_null($arrRoundResults)){
			$arrResult['status'] = "round_null";
			return $arrResult;
		}

		$arrRounds = getLastRoundInfos(ROUND_5MIN);
		
		$arrRoundInfo =  $arrRounds[0];
		$arrCoinRoundInfo = $this->modelCoin5Round->registerEmptyRound($dbConn, $arrRoundInfo);
		
		$nRegPbId = $this->modelCoin5Round->registerRound($dbConn, $arrCoinRoundInfo, $arrRoundResults[0]);
		
		$arrCoinLastRoundInfo = $this->modelCoin5Round->registerEmptyRound($dbConn, $arrRounds[1]);
		if($nRegPbId > 0)
			$this->modelCoin5Round->registerRound($dbConn, $arrCoinLastRoundInfo, $arrRoundResults[1]);
		else 
			$this->modelCoin5Round->registerRound($dbConn, $arrCoinLastRoundInfo, $arrRoundResults[0]);

		if($nRegPbId > 0){
			$arrResult['status'] = "success";
			$arrResult['data'] = $arrRoundResults[0];
		}
		else $arrResult['status'] = "fail";

		return $arrResult;
	}
	//COIN5분 파워볼 회차등록
	public function coin5register($dbConn, $arrRoundResult, $fLog)
	{		
		$logHead = "";
		//자료기지 체크
		if(is_null($dbConn)){
			$arrResult['status'] = "db_error";
			return $arrResult;
		}

		if(is_null($arrRoundResult)){
			$arrResult['status'] = "round_null";
			return $arrResult;
		}

		$arrRounds = getLastRoundInfos(ROUND_5MIN);
		
		$arrRoundInfo =  $arrRounds[0];
		$arrEosRoundInfo = $this->modelCoin5Round->registerEmptyRound($dbConn, $arrRoundInfo);
		
		$nRegPbId = $this->modelCoin5Round->registerRound($dbConn, $arrEosRoundInfo, $arrRoundResult);

		if($nRegPbId > 0){
			
			$arrResult['status'] = "success";
			$arrResult['data'] = $arrRoundResult;
		}
		else $arrResult['status'] = "fail";

		return $arrResult;
	}
	
	//보글 파워볼 회차등록
	public function bgbregister($dbConn, $arrRoundResult)
	{		
		//자료기지 체크
		if(is_null($dbConn)){
			$arrResult['status'] = "db_error";
			return $arrResult;
		}

		if(is_null($arrRoundResult)){
			$arrResult['status'] = "round_null";
			return $arrResult;
		}

		$arrRoundInfo = getLastRoundInfo(ROUND_2MIN);
		$arrPbRoundInfo = $this->modelBgballRound->registerEmptyRound($dbConn, $arrRoundInfo);
		
		$nRegPbId = $this->modelBgballRound->registerRound($dbConn, $arrPbRoundInfo, $arrRoundResult);

		if($nRegPbId > 0){
			$arrResult['status'] = "success";
			$arrResult['data'] = $arrRoundResult;
		}
		else $arrResult['status'] = "fail";

		return $arrResult;
	}

}


?>
