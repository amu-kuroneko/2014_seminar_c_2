<?php

	if( $argc > 2 ){
		$aFileName = $argv[1];
		$aTable = $argv[2];
	}
	else{
		echo <<< EOF
usage: {$argv[0]} <Input> <Table>

Input: this is the input csv filename
Table: this is the create table name


EOF;
		exit();
	}

	$aUser = '';
	$aPassword = '';
	$aDatabase = '';
	$aHostName = '';

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

	function getCreateQuery( $aHost , $aTable , $parameters ){
		$query = array(
			"`id` int(11) not null auto_increment" ,
			"`state` varchar(4) default null"
		);
		$aStringCount = 0;
		$aNumberCount = 0;
		foreach( $parameters as $aKey => $aValue ){
			if( $aKey < 2 ){
				continue;
			}
			if( is_numeric( $aValue ) ){
				$query[] = "`N{$aNumberCount}` double not null";
				$aNumberCount++;
			}
			else{
				$query[] = "`S{$aStringCount}` varchar(16) default null";
				$aStringCount++;
			}
		}
		$query[] = "primary key (`id`)";
		return "create table if not exists `{$aTable}`(\n" .
			implode( ",\n" , $query ) . "\n" .
			") engine=innodb default charset=utf8 auto_increment=1 ;\n";
	}

	function getInsertQuery( $aHost , $aTable , $parameters ){
		$query = array( "`id`" , "state" );
		$aStringCount = 0;
		$aNumberCount = 0;
		foreach( $parameters as $aKey => $aValue ){
			$values[] = '?';
			if( $aKey < 2 ){
				continue;
			}
			if( is_numeric( $aValue ) ){
				$query[] = "`N{$aNumberCount}`";
				$aNumberCount++;
			}
			else{
				$query[] = "`S{$aStringCount}`";
				$aStringCount++;
			}
		}
		return "insert into `{$aTable}` (\n" .
			implode( ",\n" , $query ) . "\n" .
			")\n" .
			"values( " . implode( ' , ' , $values ) . " );";
	}


	if( $aFile = fopen( $aFileName , 'r' ) ){
		if( flock( $aFile , LOCK_SH ) ){
			$aHost = openDatabase( $aUser , $aPassword , $aDatabase , $aHostName );
			if( ! feof( $aFile ) ){
				$parameters = str_replace( array( "\r\n" , "\r" , "\n" ) , '' , fgets( $aFile ) );
				$sql = getCreateQuery( $aHost , $aTable , $parameters = explode( ',' , $parameters ) );
				echo "\n<--- exec query --->\n" . $sql;
				$aStatement = $aHost->prepare( $sql );
				if( ! $aStatement->execute() ){
					var_dump( $aStatement->errorInfo() );
					exit();
				}
				$aStatement = $aHost->prepare( $sql = getInsertQuery( $aHost , $aTable , $parameters ) );
				if( ! $aStatement->execute( $parameters ) ){
					var_dump( $aStatement->errorInfo() );
					exit();
				}
				$aCount = 1;
				while( ! feof( $aFile ) ){
					$parameters = str_replace( array( "\r\n" , "\r" , "\n" ) , '' , fgets( $aFile ) );
					if( strlen( $parameters ) == 0 ){
						break;
					}
					if( ! $aStatement->execute( explode( ',' , $parameters ) ) ){
						var_dump( $aStatement->errorInfo() );
						exit();
					}
					$aCount++;
				}
				echo "\n insert count : {$aCount}\n";
			}
			flock( $aFile , LOCK_UN );
		}
		fclose( $aFile );
	}

	/* End of file import.php */
	/* Location: ~seminar/program/import.php */
