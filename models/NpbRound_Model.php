<?php

class NpbRound_Model {

	private $mTableName = "round_powerball";
	

	function __construct()
	{
		
	}

	public function getByFid($dbConn, $nRoundFid){

    	$strSql = "SELECT * FROM ".$this->mTableName;
    	$strSql.= " WHERE round_fid = '".$nRoundFid."' ";
    	
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
	

	public function getLast($dbConn){
		$strSql = "SELECT * FROM ".$this->mTableName;
    	$strSql.= " ORDER BY round_fid DESC LIMIT 1"; 

    	$objResult = $dbConn->query($strSql);

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

	public function deleteByFid($dbConn, $nRoundFid) {

    	$strSql = "DELETE FROM ".$this->mTableName;
    	$strSql.= " WHERE round_fid = '".$nRoundFid."' ";
    	
    	if ($dbConn->query($strSql) === TRUE) {
			return true;
		}
		
		return false;
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
        $arrRound = $this->getLast($dbConn);        
        if(!is_null($arrRound))
        {
        	$arrRoundInfo['round_fid'] = $arrRound['round_fid'] + 1;
        } else {
        	$arrRoundInfo['round_fid'] = "10001";        
        }

        $strSql = "INSERT INTO ".$this->mTableName." (round_fid, round_date, round_num, round_time, round_state) ";
		$strSql .= " VALUES ('".$arrRoundInfo['round_fid']."', '".$arrRoundInfo['round_date']."', '";
		$strSql .= $arrRoundInfo['round_no']."', NOW(), '".$arrRoundInfo['round_state']."' )";
		
		if ($dbConn->query($strSql) === TRUE) {
			return $arrRoundInfo;
		}

		return null; 

        
	}

	
	public function registerRound($dbConn, $arrRoundInfo, $arrRoundResult)
	{


		if(is_null($arrRoundInfo) || is_null($arrRoundResult))
			return 0;
		//이미 등록되있으면 패스        
		if($arrRoundInfo['round_state'] == 1)
        {
        	return $arrRoundInfo['round_fid'];
        }

		if(!array_key_exists("date", $arrRoundResult) || !array_key_exists("date_round", $arrRoundResult))
			return 0;
		
		//날자읽기
		$strDate = $arrRoundResult['date'];
		if(empty($strDate) || $strDate !== $arrRoundInfo['round_date'])
			return 0;

		//일별회차번호 읽기
		$strRoundNo = $arrRoundResult['date_round'];
		if(empty($strRoundNo) || $strRoundNo != $arrRoundInfo['round_no'])
			return 0;

        //유일회차번호
    	$nRoundFid = $arrRoundResult['times'];
    	if(empty($nRoundFid) || $nRoundFid < 1)
			return 0;
		//유일회차번호 체크
		$bExistFid = false;
		if($arrRoundInfo['round_fid'] !=  $nRoundFid){
			
			$objRoundDb = $this->getByFid($dbConn, $nRoundFid);

			if(!is_null($objRoundDb)){
				if(!$this->deleteByFid($dbConn, $arrRoundInfo['round_fid']))	//유일번호가 이미 존재하면 등록된 빈회차 삭제
					return 0;
				$bExistFid = true;
			} 
			$arrRoundInfo['round_fid'] =  $nRoundFid;

		}


		//회차결과 수자들 따내기
		$arrRoundNumbers = $arrRoundResult['ball'];

		if(empty($arrRoundNumbers) || !is_array($arrRoundNumbers))
			return 0;
		
		$nCount = count($arrRoundNumbers);

		if($nCount != 6)
			return 0;

		//일반볼 문자열, 일반볼 합계산
		$nNorBallSum = 0;
		$strNorball = "";
		for ($i = 0 ; $i < $nCount-1 ; $i ++) 
		{
			if(is_numeric($arrRoundNumbers[$i]))
			{
				$nNorBallSum += $arrRoundNumbers[$i];
				$strNorball .= $arrRoundNumbers[$i].",";
			}
			else return 0;
		}

		$strNorball = substr($strNorball, 0, strlen($strNorball)-1);

		if(!is_numeric($arrRoundNumbers[5]))
			return 0;

		$nPowerball = (int)$arrRoundNumbers[5];

		$strSql = "UPDATE ".$this->mTableName." SET ";
		if(!$bExistFid)
			$strSql.= " round_fid = '" .$arrRoundInfo['round_fid']."', ";
		else{
			$strSql.= " round_date = '".$arrRoundInfo['round_date']."', ";
			$strSql.= " round_num = '".$arrRoundInfo['round_no']."', ";
			$strSql.= " round_time = NOW(), ";
		}
		
		$strSql.= " round_state = '1', ";		
		//Round Result
		$strResult1 = ($nPowerball % 2) ? 'P' : 'B';	//Powerball ODD or Even
		$strSql.= " round_result_1 = '" .$strResult1."', ";

		$strResult2 = ($nPowerball < 5) ? 'P' : 'B';	//Powerball UNDER or OVER				
		$strSql.= " round_result_2 = '" .$strResult2."', ";
		
		$strResult3 = ($nNorBallSum % 2) ? 'P' : 'B';	//Normalball ODD or Even
		$strSql.= " round_result_3 = '" .$strResult3."', ";

		$strResult4 = ($nNorBallSum <= 72) ? 'P' : 'B';	//Normalball UNDER or OVER
		$strSql.= " round_result_4 = '" .$strResult4."', ";

		//Large, Medium, Small
		$strResult5 = 'X';
		if($nNorBallSum >=15 && $nNorBallSum <= 64)
			$strResult5 = 'S';
		else if($nNorBallSum >=65 && $nNorBallSum <= 80)
			$strResult5 = 'M';
		else if($nNorBallSum >=81 && $nNorBallSum <= 130)
			$strResult5 = 'L';
		$strSql.= " round_result_5 = '" .$strResult5."', ";

		$strSql.= " round_power = '" .$nPowerball."', ";
		$strSql.= " round_normal = '" .$strNorball."' ";

		if($bExistFid)
			$strSql.= " WHERE round_fid = '".$arrRoundInfo['round_fid']."' ";
		else {
			$strSql.= " WHERE round_date = '".$arrRoundInfo['round_date']."' ";
			$strSql.= " AND round_num = '".$arrRoundInfo['round_no']."' ";
		}

		//자료기지 등록
		if ($dbConn->query($strSql) === TRUE) {
			return $arrRoundInfo['round_fid'];
		
		}
		
        return 0;

	}


	public function registerPbgRound($dbConn, $arrRoundInfo, $arrRoundResult)
	{

		if(is_null($arrRoundInfo) || is_null($arrRoundResult))
			return 0;
		//이미 등록되있으면 패스        
		if($arrRoundInfo['round_state'] == 1)
        {
        	return $arrRoundInfo['round_fid'];
        }

		if(!array_key_exists("date", $arrRoundResult) || !array_key_exists("date_round", $arrRoundResult))
			return 0;
		
		//날자읽기
		$strDate = $arrRoundResult['date'];
		if(empty($strDate) || $strDate !== $arrRoundInfo['round_date'])
			return 0;

		//일별회차번호 읽기
		$strRoundNo = $arrRoundResult['date_round'];
		if(empty($strRoundNo) || $strRoundNo != $arrRoundInfo['round_no'])
			return 0;

        //유일회차번호
    	$nRoundFid = $arrRoundResult['times'];
    	if(empty($nRoundFid) || $nRoundFid < 1)
			return 0;
		//유일회차번호 체크
		$bExistFid = false;
		if($arrRoundInfo['round_fid'] !=  $nRoundFid){
			
			$objRoundDb = $this->getByFid($dbConn, $nRoundFid);

			if(!is_null($objRoundDb)){
				if(!$this->deleteByFid($dbConn, $arrRoundInfo['round_fid']))	//유일번호가 이미 존재하면 등록된 빈회차 삭제
					return 0;
				$bExistFid = true;
			} 
			$arrRoundInfo['round_fid'] =  $nRoundFid;

		}
		
		$strSql = "UPDATE ".$this->mTableName." SET ";
		if(!$bExistFid)
			$strSql.= " round_fid = '" .$arrRoundInfo['round_fid']."', ";
		else{
			$strSql.= " round_date = '".$arrRoundInfo['round_date']."', ";
			$strSql.= " round_num = '".$arrRoundInfo['round_no']."', ";
			$strSql.= " round_time = NOW(), ";
		}
		
		$strSql.= " round_state = '1', ";		
		//Round Result
		if(!array_key_exists('result_1', $arrRoundResult))	//Powerball ODD or Even
			return 0;
		$strSql.= " round_result_1 = '" .$arrRoundResult['result_1']."', ";
		$strSql.= " round_result_2 = '" .$arrRoundResult['result_2']."', ";
		$strSql.= " round_result_3 = '" .$arrRoundResult['result_3']."', ";
		$strSql.= " round_result_4 = '" .$arrRoundResult['result_4']."', ";
		$strSql.= " round_result_5 = '" .$arrRoundResult['result_5']."', ";
		$strSql.= " round_normal = '" .$arrRoundResult['result_normal']."' ";

		if($bExistFid)
			$strSql.= " WHERE round_fid = '".$arrRoundInfo['round_fid']."' ";
		else {
			$strSql.= " WHERE round_date = '".$arrRoundInfo['round_date']."' ";
			$strSql.= " AND round_num = '".$arrRoundInfo['round_no']."' ";
		}

		//자료기지 등록
		if ($dbConn->query($strSql) === TRUE) {
			return $arrRoundInfo['round_fid'];
		}

        return 0;

	}

}


?>