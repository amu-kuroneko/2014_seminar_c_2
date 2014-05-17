<?php

	$aUser = '';
	$aPassword = '';
	$aDatabase = '';
	$aHostName = '';
	$aDataTable = 'data1';
	$anAnswerTable = 'answer1';
	for( $anIndex = 0 ; $anIndex < 30 ; $anIndex++ ){
		$parameters[] = 'N' . $anIndex;
	}

	function openDatabase( $aUser , $aPassword , $aDatabase , $aHostName ){
		$data = "mysql:dbname={$aDatabase};host={$aHostName}";
		$options = array( PDO::ATTR_FETCH_TABLE_NAMES => true );
		try{
			return new PDO( $data , $aUser , $aPassword , $options );
		}
		catch( PDOException $anException ){
			echo "{$aUser} could not open {$aDatabase} database of mysql.\n";
			exit();
		}
		return null;
	}

	function getAverageQuery( $aTable , $parameters ){
		$query = array( "`{$aTable}`.`state`" );
		foreach( $parameters as $aKey => $aValue ){
			$query[] = "avg(`{$aTable}`.`{$aValue}`) as `{$aValue}`";
		}
		return "select " . implode( ',' , $query ) . " " .
			"from `{$aTable}` " .
			"where `{$aTable}`.`state` != '?' " .
			"group by `{$aTable}`.`state` ;";
	}

	function getDataQuery( $aTable , $parameters ){
		$query = array( "`{$aTable}`.`id`" , "`{$aTable}`.`state`" );
		foreach( $parameters as $aKey => $aValue ){
			$query[] = "`{$aTable}`.`{$aValue}`";
		}
		return "select " . implode( ',' , $query ) . " " .
			"from `{$aTable}` " .
			"where `{$aTable}`.`state` = ? ;";
	}

	function getUpdateQuery( $aTable , $parameters ){
		return "update `{$aTable}` set `{$aTable}`.`state` = ? " .
			"where `{$aTable}`.`id` = ? " .
			"limit 1 ;";
	}

	$aHost = openDatabase( $aUser , $aPassword , $aDatabase , $aHostName );
	$aStatement = $aHost->prepare( getAverageQuery( $aDataTable , $parameters ) );
	if( $aStatement->execute() ){
		foreach( $aStatement as $row ){
			foreach( $parameters as $aValue ){
				$averages[$row[$aDataTable.'.state']][$aValue] = $row['.'.$aValue];
			}
		}
	}
	else{
		echo "do not get averaeg data.\n";
		exit();
	}

	$aStatement = $aHost->prepare( getDataQuery( $aDataTable , $parameters ) );
	// $e = 0;
	// $a = 0;
	if( $aStatement->execute( array( '?' ) ) ){
		foreach( $aStatement as $row ){
			$aTrueData = 0;
			$aFalseData = 0;
			foreach( $parameters as $aValue ){
				$aTrueData += pow( $averages['Y'][$aValue] - $row[$aDataTable.'.'.$aValue]  , 2 );
				$aFalseData += pow( $averages['N'][$aValue] - $row[$aDataTable.'.'.$aValue]  , 2 );
			}
			$aTrueData = sqrt( $aTrueData );
			$aFalseData = sqrt( $aFalseData );
			$results[$row[$aDataTable.'.id']] = $aTrueData < $aFalseData ? 'Y' : 'N';
			// if( $results[$row[$aDataTable.'.id']] == 'N' ){
				// $e++;
			// }
			// $a++;
		}
		// echo "all : {$a}\n";
		// echo "error : {$e}\n";
	}
	else{
		echo "do not get data.\n";
		exit();
	}

	$aStatement = $aHost->prepare( getUpdateQuery( $anAnswerTable , $parameters ) );
	foreach( $results as $aKey => $aValue ){
		if( ! $aStatement->execute( array( $aValue , $aKey ) ) ){
			echo "update error.\n";
			exit();
		}
	}

	echo "success.\n";

	/* End of file analysis1.php */
	/* Location: ~seminar/program/anaylysis1.php */
