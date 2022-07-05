<?php

class ConfSite_Model {

	private $mTableName ;
	
	function __construct()
	{
		$this->mTableName = "conf_site";
	}

	public function getById($dbConn, $conf_id){
		
        $strSql = "SELECT * FROM ".$this->mTableName;
    	$strSql.= " WHERE conf_id = '".$conf_id."' ";
    	
    	$objConfig = null;
    	if($objResult = $dbConn->query($strSql)){
	    	if ($objResult->num_rows > 0) {
			  	while($arrRow = $objResult->fetch_assoc()) {
			    	$objConfig = (object)$arrRow;
			    	break;
		  		}
			}
			$objResult->free();
		}
		return $objConfig;
    }

	
}

?>