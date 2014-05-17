<?php

	$aUser = '';
	$aPassword = '';
	$aDatabase = '';
	$aHostName = '';
	$aDataTable = 'data2';
	$anAnswerTable = 'answer2';
	for( $anIndex = 0 ; $anIndex < 22 ; $anIndex++ ){
		$parameters[] = 'S' . $anIndex;
	}
	$characters = 'abcdefghijklmnopqrstuvwxyz';

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

	function getCountQuery( $aTable , $parameters , $characters ){
		for( $anIndex = 0 , $aLength = strlen( $characters ) ; $anIndex < $aLength ; $anIndex++ ){
			$select = array( "'{$characters[$anIndex]}' as `S`" , "`{$aTable}`.`state`" );
			foreach( $parameters as $aKey => $aValue ){
				$aCase = "case when `{$aTable}`.`{$aValue}` = '{$characters[$anIndex]}' then 1 else null end";
				$select[] = "count( {$aCase} ) as `{$aValue}`";
			}
			$query[] = "( select " . implode( ',' , $select ) . " " .
				"from `{$aTable}` " .
				"where `{$aTable}`.`state` != '?' " .
				"group by `{$aTable}`.`state` )";
		}
		return implode( ' union all ' , $query );
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
	$aStatement = $aHost->prepare( getCountQuery( $aDataTable , $parameters , $characters ) );
	if( $aStatement->execute() ){
		foreach( $aStatement as $row ){
			foreach( $parameters as $aValue ){
				$count[$row['.state']][$row['.S']][$aValue] = $row['.'.$aValue];
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
			$aTrueCount = 0;
			$aFalseCount = 0;
			foreach( $parameters as $aValue ){
				$aCharacter = $row[$aDataTable.'.'.$aValue];
				if( $aCharacter == '?' ){
					continue;
				}
				$aTrueNumber = $count['Y'][$aCharacter][$aValue];
				$aFalseNumber = $count['N'][$aCharacter][$aValue];
				if( $aFalseNumber < $aTrueNumber ){
					$aTrueCount++;
				}
				else if( $aTrueNumber < $aFalseNumber ){
					$aFalseCount++;
				}
			}
			$results[$row[$aDataTable.'.id']] = $aTrueCount < $aFalseCount ? 'N' : 'Y';
			// if( $results[$row[$aDataTable.'.id']] == 'Y' ){
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

	/* End of file analysis2.php */
	/* Location: ~seminar/program/anaylysis2.php */