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


	function __construct(){
		$this->mSnoopy = new Snoopy();

		$this->modelConfSite = new ConfSite_Model();

		$this->modelPballRound = new NpbRound_Model();
		$this->modelEos5Round = new PbRound_Model(GAME_EOS5_BALL);
		$this->modelCoin5Round = new PbRound_Model(GAME_COIN5_BALL);
	}

	//배팅사이트 정보얻기
	public function getSiteConf($dbConn, $confId)
	{		
		//게임배팅시간
		$objConfig = $this->modelConfSite->getById($dbConn, $confId);
		if(!is_null($objConfig) && $objConfig->conf_active > 0){
			return true;
		}
		return false;
		
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
	//파워볼 회차등록
	public function pbregister($dbConn, $arrRoundResult)
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
		
		$arrRoundInfo =  $arrRounds[0];
		$arrPbRoundInfo = $this->modelPballRound->registerEmptyRound($dbConn, $arrRoundInfo);
		if(!array_key_exists('date', $arrRoundResult)){
			$arrRoundResult['date'] = $arrRoundInfo['round_date'];
		}
		$nRegPbId = $this->modelPballRound->registerRound($dbConn, $arrPbRoundInfo, $arrRoundResult);

		if($nRegPbId > 0){
			
			$arrResult['status'] = "success";
			$arrResult['data'] = $arrRoundResult;
		}
		else $arrResult['status'] = "fail";

		return $arrResult;
	}
	//파워볼 회차등록
	public function pbregister_benz($dbConn, $arrRoundResult, $bLastRound=false)
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
			$arrResult['data'] = $arrRoundResults;
		}
		else $arrResult['status'] = "fail";

		return $arrResult;
	}

}


?>
