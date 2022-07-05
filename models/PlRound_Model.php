<?php

class PlRound_Model {

	private $mTableName = 'round_powerladder';
	
	function __construct($gameId)
	{
		switch($gameId){
			case GAME_POWER_LADDER: $this->mTableName = 'round_powerladder'; break;
			case GAME_BOGLE_LADDER: $this->mTableName = 'round_bogleladder'; break;
			default: break;
		}
		
	}


    public function getByDate($dbConn, $nRoundNo, $strDate){

    	$strSql = "SELECT * FROM ".$this->mTableName;
    	$strSql.= " WHERE round_num = '".$nRoundNo."' ";
    	$strSql.= " AND round_date = '".$strDate."' ";

    	$arrResult = null;
    	if($objResult = $dbConn->query($strSql)){
	    	if ($objResult->num_rows > 0) {
			  	while($arrRow = $objResult->fetch_assoc()) {
			    	$arrResult = $arrRow;
		  		}
			}
			$objResult->free();
		}
		return $arrResult;
    }


	public function registerEmptyRound($dbConn, $arrRoundInfo){
		//자료기지체크         
		$arrRound = $this->getByDate($dbConn, $arrRoundInfo['round_no'], $arrRoundInfo['round_date']);
        
        if(!is_null($arrRound))
        {
        	$arrRoundInfo['round_fid'] = $arrRound['round_fid'];
        	$arrRoundInfo['round_state'] = $arrRound['round_state'];
        	return $arrRoundInfo;
        }

        $arrRoundInfo['round_state'] = 0;

        $strSql = "INSERT INTO ".$this->mTableName." (round_date, round_num, round_time, round_state) ";
		$strSql .= " VALUES ('".$arrRoundInfo['round_date']."', '";
		$strSql .= $arrRoundInfo['round_no']."', NOW(), '".$arrRoundInfo['round_state']."' )";
		
		if ($dbConn->query($strSql) === TRUE) {
			$arrRoundInfo['round_fid'] = $dbConn->insert_id;
			return $arrRoundInfo;
		}

		return null; 

        
	}

	

	function registerRound($dbConn, $arrRoundInfo, $arrRoundResult)
	{
		if(is_null($arrRoundInfo) || is_null($arrRoundResult))
			return 0;
		//이미 등록되있으면 패스        
		if($arrRoundInfo['round_state'] == 1)
        {
        	return $arrRoundInfo['round_fid'];
        }

		if(!array_key_exists("date", $arrRoundResult) || !array_key_exists("r", $arrRoundResult))
			return 0;
		
		
		//날자읽기
		$strDate = $arrRoundResult['date'];
		if(empty($strDate) || $strDate !== $arrRoundInfo['round_date'])
			return 0;
		
		//회차번호 읽기
		$strRoundNo = $arrRoundResult['r'];
		if(empty($strRoundNo) || $strRoundNo != $arrRoundInfo['round_no'])
			return 0;	


        //배팅결과 좌우
		$strLR = $arrRoundResult['s'];
		if(empty($strLR))
			return null;

		//배팅결과 3줄4줄
		$str34 = $arrRoundResult['l'];
		if(empty($str34))
			return null;
		$str34 = (int)$str34;


		$strOE = $arrRoundResult['o'];
		if(empty($strOE))
			return null;

        //자료기지 등록

		$strSql = "UPDATE ".$this->mTableName." SET ";
		$strSql.= " round_state = '1', ";	
		
		//Round Result
		$strResult_1 = $strLR=="LEFT" ? 'P' : 'B';		//파워사다리 좌우
		$strSql.= " round_result_1 = '" .$strResult_1."', ";

		$strResult_2 = $str34==3 ? 'P' : 'B';			//파워사다리 3줄4줄
		$strSql.= " round_result_2 = '" .$strResult_2."', ";
		
		$strResult_3 = $strOE=="ODD" ? 'P' : 'B';		//파워사다리 홀짝
		$strSql.= " round_result_3 = '" .$strResult_3."' ";
		
		$strSql.= " WHERE round_fid = '".$arrRoundInfo['round_fid']."' ";

		//자료기지 등록
		if ($dbConn->query($strSql) === TRUE) {
			return $arrRoundInfo['round_fid'];
		}

        return 0;

	}



}


?>