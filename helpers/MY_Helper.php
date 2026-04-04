<?php

  


     //이전회차번호, 날자 계산하는 함수-파워볼, 파워사다리
    function getPbLastRoundInfo(){

      $tmNow = time()+TM_OFFSET;
      $nYear = date("Y",$tmNow);
      $nMonth = date("m",$tmNow);
      $nDay = date("d",$tmNow);

      $nHour = date("G",$tmNow);
      $nMin = date("i",$tmNow);
      
      $nSumMinutes = $nHour * 60 + $nMin;
      $nRoundNo = floor($nSumMinutes / 5) ;
      $nRoundNo = $nRoundNo % 288 ;
      if($nRoundNo == 0) {
        $nRoundNo = 288;
        $strDate = date('Y-m-d', strtotime("-1 day", $tmNow));
      }
      else $strDate = date( 'Y-m-d', $tmNow );
      
      $arrRoundInfo['round_no'] = $nRoundNo;
      $arrRoundInfo['round_date'] = $strDate;

      return $arrRoundInfo;
    }

     //이전회차번호, 날자 계산하는 함수-키노사다리
    function getKsLastRoundInfo(){

      $tmNow = time()+TM_OFFSET;
      $nYear = date("Y",$tmNow);
      $nMonth = date("m",$tmNow);
      $nDay = date("d",$tmNow);

      $nHour = date("G",$tmNow);
      $nMin = date("i",$tmNow);
      
      $nSumMinutes = $nHour * 60 + $nMin;
      $nRoundNo = floor($nSumMinutes / 5) ;
      $nRoundNo = $nRoundNo % 288 ;
      if($nRoundNo == 0) {
        $nRoundNo = 288;
        $strDate = date('Y-m-d', strtotime("-1 day", $tmNow));
      }
      else {
        $strDate = date( 'Y-m-d', $tmNow );
      }
      
      $arrRoundInfo['round_no'] = $nRoundNo;
      $arrRoundInfo['round_date'] = $strDate;

      return $arrRoundInfo;
    }

    //이전회차번호, 날자 계산하는 함수-일반
    function getLastRoundInfo($roundMin){

    $tmNow = time();

    $nHour = date("G",$tmNow);
    $nMin = date("i",$tmNow);
    
    $nSumMinutes = $nHour * 60 + $nMin;

    $nRoundNo = floor($nSumMinutes / $roundMin) ;
    $nRoundMax = floor(1440 / $roundMin);
    if($nRoundNo == 0) {
      $nRoundNo = $nRoundMax;
      $strDate = date('Y-m-d', strtotime("-1 day", $tmNow));
    }
    else $strDate = date( 'Y-m-d', $tmNow );
    
    $arrRoundInfo['round_no'] = $nRoundNo;
    $arrRoundInfo['round_date'] = $strDate;

    return $arrRoundInfo;
  }

    //이전회차번호, 날자 계산하는 함수-EOS
    function getLastRoundInfos($roundMin){

      $tmNow = time();
      
      $nHour = date("G",$tmNow);
      $nMin = date("i",$tmNow);
      
      $nSumMinutes = $nHour * 60 + $nMin;
      $nRoundNo = floor($nSumMinutes / $roundMin) ;
      
      $nRoundMax = floor(1440 / $roundMin);
      if($nRoundNo == 0) {
        $nRoundNo = $nRoundMax;
        $strDate = date('Y-m-d', strtotime("-1 day", $tmNow));
      }
      else $strDate = date( 'Y-m-d', $tmNow );
      
      $arrRounds = [null, null, null];

      $arrRoundInfo['round_no'] = $nRoundNo;
      $arrRoundInfo['round_date'] = $strDate;
      $arrRounds[0] = $arrRoundInfo;

      //전전회차번호, 날자
      $nRoundNo = $nRoundNo - 1;
      if($nRoundNo == 0) {
        $nRoundNo = $nRoundMax;
        $strDate = date('Y-m-d', strtotime("-1 day", $tmNow));
      } 
      $arrRoundInfo['round_no'] = $nRoundNo;
      $arrRoundInfo['round_date'] = $strDate;

      $arrRounds[1] = $arrRoundInfo;

      //현재회차번호, 날자
      $nRoundNo = $arrRounds[0]['round_no'] + 1;
      if($nRoundNo > $nRoundMax) {
        $nRoundNo = 1;
      } 
      $strDate = date('Y-m-d', $tmNow);

      $arrRoundInfo['round_no'] = $nRoundNo;
      $arrRoundInfo['round_date'] = $strDate;

      $arrRounds[2] = $arrRoundInfo;

      return $arrRounds;
    }

    function writeLog($fLog, $tContent, $bTm = true){
      
      if($bTm){
        $tmNow = time() ;
        $nHour = date("G",$tmNow);
        $nMin = date("i",$tmNow);
        $nSec = date("s",$tmNow);
        $tContent = "[".$nHour.":".$nMin.":".$nSec."] ".$tContent."\r\n";
      }
      
      echo $tContent;
      if($fLog)
       fputs($fLog, $tContent);
    }

?>