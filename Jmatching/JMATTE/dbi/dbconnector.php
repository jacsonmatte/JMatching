<?php

//Conecta a BD
if (isset($_POST["database"]) and !empty($_POST["database"])) {
	$dtaba = @$_POST["database"];
	$geracsv = ' INTO OUTFILE "/Users/jacsonmatte/Dropbox/Jacson/TCC\ II/web-content/resultsCsv/'.$dtaba.'.csv" FIELDS TERMINATED BY "," OPTIONALLY ENCLOSED BY "" LINES TERMINATED BY "\n" ';
	$sqlbd = @$_POST["sql"].$geracsv;
	$connect=dbConnect("mysql.hostinger.com.br", "u956250334_imdb", "imdb2016");
	$bd = @$_POST["banco"];
	$retorno=dbConsulta($sqlbd, $bd, $connect);
	

	$file = "../resultsCsv/".$dtaba.".csv";
	// Quick check to verify that the file exists
	if( !file_exists($file) ) die("File not found");
	// Force the download
	header("Content-Disposition: attachment; filename=".$dtaba.".csv ");
	header("Content-Length: " . filesize($file));
	header("Content-Type: application/octet-stream;");
	readfile($file);
}

function dbConnect($host, $user, $pass){
	$connect = @mysql_connect($host, $user, $pass);
	return $connect;
}


//Conecta na BD e executa uma consulta
function dbConsulta($sql, $app, $connect){
	mysql_select_db($app, $connect);
	mysql_set_charset('UTF8',$connect);
	($a = mysql_query($sql)) or (die ("error: ".mysql_error()));
	return $a;
}


function queryResult($queryRelation, $queryTab, $consultaTab, $consultaCol, $consultaOcrs, $graph, $resp){
	//monta projeção da consulta
	
	global $banco;
	$connect=dbConnect("mysql.hostinger.com.br", "u956250334_imdb", "imdb2016");
	$projecao = array(); 
	$select = " SELECT ";
	for ($i=0; $i < count($consultaTab); $i++) { 
		if ($i == count($consultaTab) - 1) {
			$select = $select.$consultaTab[$i].'.'.$consultaCol[$i].' AS '.$consultaTab[$i].$consultaCol[$i];
			$projecao[] = $consultaTab[$i].$consultaCol[$i];
		}else{
			$select = $select.$consultaTab[$i].'.'.$consultaCol[$i].' AS '.$consultaTab[$i].$consultaCol[$i].', ';
			$projecao[] = $consultaTab[$i].$consultaCol[$i];
		}
	}
	//monta a estrutura das clausula from
	$from = " FROM ";

	for ($i=0; $i < count($queryTab); $i++) {
		$temp = explode('#', $queryTab[$i]); 
		if ($i == count($queryTab) - 1) {
			$from = $from.$temp[0];
		}else
		$from = $from.$temp[0].', '; 
	}

	//monta clausula where
	if(count(array_unique($consultaTab)) > 1){
		$where = " WHERE ";
		for ($i=0; $i < count($queryRelation); $i++) { 
			$tempR = explode('#', $queryRelation[$i]);
			for ($j=0; $j < count($queryTab); $j++) { 
				$tempT = explode('#', $queryTab[$j]);
				if ($tempT[1] == $tempR[0]) {
					$tabfrom = $tempT[0];
				}
				if ($tempT[1] == $tempR[1]) {
					$tabto = $tempT[0];
				}
			}
			//echo $tabto.'#'.$tabfrom;
			for ($j=0; $j < count($graph); $j++) {
				if (fullUpper($graph[$j]['tabelaOrigem'][0]) == fullUpper($tabfrom) && isset($graph[$j]['colunaPK'][0])) {
					
					$idPk = $graph[$j]['colunaPK'][0];
				}
				for ($k=0; $k < count($graph[$j]['tabelaDestino']); $k++) {
					
					if (fullUpper($graph[$j]['tabelaDestino'][$k]) == fullUpper($tabto) && fullUpper($graph[$j]['tabelaOrigem'][0]) == fullUpper($tabfrom)) {
						$idFk = $graph[$j]['colunaFor'][$k];
					}
					
					
				}				
			}
			//echo $idPk.'#';
			//echo $idFk;
			
			if (isset($idFk) && isset($idPk)) {
				if ($i == count($queryRelation) - 1) {
					$where = $where.$tabfrom.'.'.$idPk.' = '.$tabto.'.'.$idFk;
				}else
				$where = $where.$tabfrom.'.'.$idPk.' = '.$tabto.'.'.$idFk.' AND '; 
			}
			
			if ((!isset($idFk) && !isset($idPk)) || (!isset($idFk) && isset($idPk)) || (isset($idFk) && !isset($idPk))) {
				for ($j=0; $j < count($graph); $j++) {
					if (fullUpper($graph[$j]['tabelaOrigem'][0]) == fullUpper($tabto) && isset($graph[$j]['colunaPK'][0])) {
						$idPk = $graph[$j]['colunaPK'][0];
						//echo $idPk."#".$graph[$j]['tabelaOrigem'][0];
					}
					for ($k=0; $k < count($graph[$j]['tabelaDestino']); $k++) {
						
						if (fullUpper($graph[$j]['tabelaDestino'][$k]) == fullUpper($tabfrom) && fullUpper($graph[$j]['tabelaOrigem'][0]) == fullUpper($tabto)) {
							$idFk = $graph[$j]['colunaFor'][$k];

						}
					}				
				}

				

				if (isset($idFk) && isset($idPk)) {
				if ($i == count($queryRelation) - 1) {
					$where = $where.$tabfrom.'.'.$idFk.' = '.$tabto.'.'.$idPk;
				}else
					$where = $where.$tabfrom.'.'.$idFk.' = '.$tabto.'.'.$idPk.' AND '; 
				}

			}
			unset($idPk);
			unset($idFk);

		}
		
	}else{
		$like = '';
		foreach ($consultaOcrs as $key => $value) {
			if($key == 0){
				$consultaOcrs[$key] =  substr(str_replace('"',"",$consultaOcrs[$key]),0, strlen($consultaOcrs[$key])*0.5);
				$like = $like.$consultaTab[$key].'.'.$consultaCol[$key].' LIKE "%'.$consultaOcrs[$key].'%"';
			}else
				$consultaOcrs[$key] = substr(str_replace('"',"",$consultaOcrs[$key]),0, strlen($consultaOcrs[$key])*0.5);//retorna 50% do texto buscado
				$like = $like.' AND '.$consultaTab[$key].'.'.$consultaCol[$key].' LIKE "%'.$consultaOcrs[$key].'%"';	
		}
		
		$sql = $select.$from.' WHERE '.$like.' LIMIT 1';
		$retorno=dbConsulta($sql, $banco, $connect);
		if(mysql_num_rows($retorno)){
			$saida['encontrada']=1;
		}else
		$saida['encontrada']=0;
		while($row = mysql_fetch_assoc($retorno)){
			for ($i=0; $i < count($projecao); $i++) { 
	    		$saidaTemp[][$projecao[$i]] = $row[$projecao[$i]];
	    	}
	    	//$saida['encontrada'] = 1;
		}


		$sql = $select.$from.' LIMIT 5';
		
		$retorno=dbConsulta($sql, $banco, $connect);
		while($row = mysql_fetch_assoc($retorno)){
			for ($i=0; $i < count($projecao); $i++) { 
				$saidaTemp[][$projecao[$i]] = $row[$projecao[$i]];
			}
		}
		for ($i=0; $i < count($projecao); $i++) { 
			for ($j=0; $j < count($saidaTemp); $j++) {
				if(isset($saidaTemp[$j][$projecao[$i]])){
					$saida[$i][] = $saidaTemp[$j][$projecao[$i]];
				}
			}
		}
		$sqlv = "CREATE OR REPLACE VIEW ".$banco.$resp." AS " .$select.$from;
		//dbConsulta($sqlv, $banco, $connect);
		$view = "SELECT * FROM ".$banco.$resp;
		$saida['sql'] = $sqlv;
		$column = '';
		foreach ($consultaCol as $key => $value) {
			if ($key == count($consultaCol)-1) {
				$column = $column.'"'.$consultaTab[$key].'.'.$consultaCol[$key].'"';
			}else
			$column = $column.'"'.$consultaTab[$key].'.'.$consultaCol[$key].'", ';
		}
		$column = fullUpper($column);
		//$sql = "SELECT $column union all $select $from INTO OUTFILE '/Users/jacsonmatte/Dropbox/Jacson/TCC\ II/web-content/resultsCsv/$banco$resp.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '' LINES TERMINATED BY '\n' ";
		$saida['linkbd'] = "SELECT ".$column." union all".$select.$from;
		return $saida;
	}
	$like = '';
	foreach ($consultaOcrs as $key => $value) {
		$consultaOcrs[$key] = substr(str_replace('"',"",$consultaOcrs[$key]),0, strlen($consultaOcrs[$key])*0.5);
		if($like == ''){
			$like = $consultaTab[$key].'.'.$consultaCol[$key].' LIKE "%'.$consultaOcrs[$key].'%"';
		}else
		$like = $like.' AND '.$consultaTab[$key].'.'.$consultaCol[$key].' LIKE "%'.$consultaOcrs[$key].'%"';
	}
	
	$sql = $select.$from.$where.$like.' LIMIT 1';
	//print_r($sql);
	$retorno=dbConsulta($sql, $banco, $connect);
	if(mysql_num_rows($retorno)){
		$saida['encontrada']=1;
	}else
	$saida['encontrada']=0;
	while($row = mysql_fetch_assoc($retorno)){
		for ($i=0; $i < count($projecao); $i++) { 
    		$saidaTemp[][$projecao[$i]] = $row[$projecao[$i]];
    	}
    	//$saida['encontrada'] = 1;
	}


	$sql = $select.$from.$where.$like.' LIMIT 5';
	$sqlv = "CREATE OR REPLACE VIEW ".$banco.$resp." AS " .$select.$from.$where;

	$retorno=dbConsulta($sql, $banco, $connect);
	
	$saidaTemp = array();
	while($row = mysql_fetch_assoc($retorno)){
		for ($i=0; $i < count($projecao); $i++) { 
    		$saidaTemp[][$projecao[$i]] = $row[$projecao[$i]];
    	}
	}


	for ($i=0; $i < count($projecao); $i++) { 
		for ($j=0; $j < count($saidaTemp); $j++) {
			if(isset($saidaTemp[$j][$projecao[$i]])){
				$saida[$i][] = $saidaTemp[$j][$projecao[$i]];
			}
		}
	}
	$view = "SELECT * FROM ".$banco.$resp;
	$saida['sql'] = $sqlv;
	
	$column='';
	foreach ($consultaCol as $key => $value) {
		if ($key == count($consultaCol)-1) {
			$column = $column.'"'.$consultaTab[$key].'.'.$consultaCol[$key].'"';
		}else
		$column = $column.'"'.$consultaTab[$key].'.'.$consultaCol[$key].'", ';
	}
	
	$column = fullUpper($column);
	//$sql = "SELECT $column union all $select $from $where INTO OUTFILE '/Users/jacsonmatte/Dropbox/Jacson/TCC\ II/web-content/resultsCsv/$banco$resp.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '' LINES TERMINATED BY '\n' ";
   	$saida['linkbd'] = "SELECT ".$column." union all ".$select.$from.$where;
    return $saida;
}

//retorna a posição que será inserido na lista por ordem de prioridade fk/numero de tuplas/numero de atributos
function searchPos($class, $tabela, $nFk, $nAtributo, $nLinhas){
	for ($i=0; $i < count($class); $i++) { 
				if ($class[$i]['atributos'] < $nAtributo) {
					$equal = -1;
					$pos = $i;
					break;

				}

				if ($class[$i]['atributos'] > $nAtributo) {
					$equal = 0;
					$pos = $i;
				}

				if ($class[$i]['atributos'] == $nAtributo) {
					$equal = 1;
					$pos = $i;
					break;
				}
			}

			if ($equal == -1) {
				return $pos - 1 ;
			}
			if ($equal == 0) {
				return $pos;
		}else{
			$equal = '';
			//retorna a posição somente dos que contém o mesmo número de chave estrangeira e  registros
			for ($i = $pos; $i < count($class); $i++) {

				if ($class[$i]['atributos'] == $nAtributo && $class[$i]['linhas'] < $nLinhas) {
					
					$equal = -1;
					$pos = $i;
					break;

				}
				if ($class[$i]['atributos'] == $nAtributo && $class[$i]['linhas'] > $nLinhas) {
					$equal = 0;
					$pos = $i;

				}

				if ($class[$i]['atributos'] == $nAtributo && $class[$i]['linhas'] == $nLinhas) {
					$equal = 1;
					$pos = $i;
					break;
				}
			}

			if ($equal == -1) {
				return $pos-1;
			}
			if ($equal == 0) {
				return $pos;
			}else{
				$equal = '';
				//retorna a posição que contém a mesma chave estrangeira, registro e atributos
				for ($i=$pos; $i < count($class); $i++) { 
					if ($class[$i]['linhas'] == $nLinhas && $class[$i]['nForeignKey'] < $nFk) {
						$equal = -1;
						$pos = $i;

					}

					if ($class[$i]['linhas'] == $nLinhas && $class[$i]['nForeignKey'] > $nFk) {
						$equal = 0;
						$pos = $i;
					}

					if ($class[$i]['linhas'] == $nLinhas && $class[$i]['nForeignKey'] == $nFk) {
						$equal = 1;
						$pos = $i;
						break;
					}
				}

				if ($equal == -1) {
					return $pos-1;
				}
				if ($equal == 0) {
					return $pos;
				}else return $pos;
			}
		}
}

//AQUI FUNCIONA AGORA VER SE ORDENA DEPOIS POR NUMERO DE LINHA OU POR NUMERO DE ATRIBUTOS
function classMedal($graph, $connect, $tipo){
	Global $banco;
	$aux = array();
	$ord = array();
	$ordValor = array();
	$class = array();
	//retorna as tabelas ordenadas pelo maior para o menor número de registros
	$sql = "SELECT `TABLE_NAME`, `TABLE_ROWS` FROM information_schema.TABLES WHERE TABLE_SCHEMA = \"$banco\" ORDER BY `TABLE_ROWS` DESC ";
	$retorno=dbConsulta($sql, "information_schema", $connect);

	while($row = mysql_fetch_assoc($retorno)){
		array_push($ord, $row['TABLE_NAME']);
		array_push($ordValor, $row['TABLE_ROWS']);
	}

	for ($i=0; $i < count($graph); $i++) {
		$tabela = $graph[$i]['tabelaOrigem'][0];
		$sql = "SELECT count(*) as \"number\" FROM `TABLE_CONSTRAINTS` WHERE TABLE_SCHEMA = \"$banco\" and TABLE_NAME = \"$tabela\" and CONSTRAINT_TYPE = \"FOREIGN KEY\"";
		$retorno=dbConsulta($sql, "information_schema", $connect);
		$row = mysql_fetch_assoc($retorno);
		$nFk = $row['number'];
		$nAtributo = count($graph[$i][$tipo]);
		for ($j=0; $j < count($ord); $j++) { 
			if (fullUpper($ord[$j]) == fullUpper($tabela)) {
				$nLinhas = $ordValor[$j];
				break;
			}
		}
		if ($i == 0) {
			array_push($class, array('nomeTabela' => $tabela, 'nForeignKey' => $nFk ,'atributos' => $nAtributo, 'linhas' => $nLinhas));
			continue;
		}else{
			$ps = searchPos($class, $tabela, $nFk, $nAtributo, $nLinhas);
			$first = 0;
			if ($ps == -1) {
				array_push($aux, array('nomeTabela' => $tabela, 'nForeignKey' => $nFk ,'atributos' => $nAtributo, 'linhas' => $nLinhas));
				$aux[] = $class[0];
				$first = 1;
			}

			for ($p = $first; $p < count($class); $p++) { 
				if ($ps == $p) {
					//echo 'jacson';
					$aux[] = $class[$p];
					array_push($aux, array('nomeTabela' => $tabela, 'nForeignKey' => $nFk ,'atributos' => $nAtributo, 'linhas' => $nLinhas));
					
				}else
				$aux[] = $class[$p]; 
			}

			$class =  $aux;
			unset($aux);
			$aux = array();
			
			
		}

	}
	$rfinal = array();
	for ($j=0; $j < count($class); $j++) { 
		$rfinal[] = $class[$j]['nomeTabela']; 
	}
	return $rfinal;
}





function buscaAlf($graph, $instance){
	global $banco;
	$resp = '';
	$connect=dbConnect("mysql.hostinger.com.br", "u956250334_imdb", "imdb2016");
	$cont = 0;
	$saida = array();
	$saidaAux = array();
	//$ord = prioryOrdenacao($graph, $connect, 'colunaAlf');
	$ord = classMedal($graph, $connect, 'colunaAlf');

	while ($cont < count($graph)) {

		$ind = 0;
		while ($ind < count($graph)) {
			if ($graph[$ind]['tabelaOrigem'][0] == $ord[0]) {
				//echo $ord[0].'<br>';
				break;
			}
			$ind++;
		}		
		
		array_shift($ord);// tira o primeiro elemento
		$temp = array();
		if (count($graph[$ind]['colunaAlf'])) {
			foreach ($graph[$ind]['colunaAlf'] as $key => $value) {

				$coluna = $graph[$ind]['colunaAlf'][$key];
				$tabela = $graph[$ind]['tabelaOrigem'][0];

				foreach ($instance as $key1 => $value1) {
					$sql = "SELECT  $coluna FROM  $tabela WHERE $coluna LIKE \"%$instance[$key1]%\" LIMIT 1";
					$retorno=dbConsulta($sql, $banco, $connect);
					if (mysql_num_rows($retorno) > 0) {
						//echo $tabela.$coluna;
						foreach ($instance as $key2 => $value2) {
							if($value2 == $instance[$key1]){
								$temp[$value2] = 1;
							}
							if($value2 == $instance[$key1]){
								$temp[$value2] = 1;
							}
							
						}
						
						while($row = mysql_fetch_assoc($retorno)){
							array_push($saidaAux, $tabela."#".$coluna."#".$row[$coluna]);
						}
					}
					
				}
			}
			if (count($temp) == count($instance)) {
				
				$saida[] = $saidaAux;
				
			}else{
				unset($saidaAux);
				$saidaAux = array();
			}
			
	
		}
		$cont++;
	}

	foreach ($saida as $key => $value) {
		foreach ($saida[$key] as $key1 => $value1) {
			$resp[] = $saida[$key][$key1];
		}
		
	}
	if (isset($resp)) {
		return $resp;
	}
	return 0;

}



function buscaInt($graph, $instance){
	global $banco;
	$connect=dbConnect("mysql.hostinger.com.br", "u956250334_imdb", "imdb2016");
	$cont = 0;
	$saida = array();
	$saidaAux = array();
	//$ord = prioryOrdenacao($graph, $connect, 'colunaInt');
	$ord = classMedal($graph, $connect, 'colunaInt');
	while ($cont < count($graph)) {

		$ind = 0;
		while ($ind < count($graph)) {
			if ($graph[$ind]['tabelaOrigem'][0] == $ord[0]) {
				//echo $ord[0].'<br>';
				break;
			}
			$ind++;
		}		
		
		array_shift($ord);// tira o primeiro elemento
		$temp = array();
		if (count($graph[$ind]['colunaInt'])) {
			foreach ($graph[$ind]['colunaInt'] as $key => $value) {

				$coluna = $graph[$ind]['colunaInt'][$key];
				$tabela = $graph[$ind]['tabelaOrigem'][0];
				foreach ($instance as $key1 => $value1) {
					$sql = "SELECT  $coluna FROM  $tabela WHERE $coluna LIKE \"%$instance[$key1]%\" LIMIT 1 ";
					$retorno=dbConsulta($sql, $banco, $connect);
					if (mysql_num_rows($retorno) > 0) {
						foreach ($instance as $key2 => $value2) {
							if($value2 == $instance[$key1]){
								$temp[$value2] = 1;
							}
							if($value2 == $instance[$key1]){
								$temp[$value2] = 1;
							}
							
						}
						while($row = mysql_fetch_assoc($retorno)){
							array_push($saidaAux, $tabela."#".$coluna."#".$row[$coluna]);
						}
					}
					
				}
			}
			if (count($temp) == count($instance)) {
				$saida []= $saidaAux;
			}else{
				unset($saidaAux);
				$saidaAux = array();
			}
			
	
		}
		$cont++;
	}
	foreach ($saida as $key => $value) {
		foreach ($saida[$key] as $key1 => $value1) {
			$resp[] = $saida[$key][$key1];
		}
		
	}
	if (isset($resp)) {
		return $resp;
	}
	return 0;

}





function buscaDate($graph, $instance){
	global $banco;
	$connect=dbConnect("mysql.hostinger.com.br", "u956250334_imdb", "imdb2016");
	$cont = 0;
	$saida = array();
	$saidaAux = array();
	//$ord = prioryOrdenacao($graph, $connect, 'colunaDate');
	$ord = classMedal($graph, $connect, 'colunaDate');
	$temp = implode("-",array_reverse(explode("/",$instance[0])));//converte para o padrão do BD
	unset($instance);
	$instance[] = $temp;

	while ($cont < count($graph)) {

		$ind = 0;
		while ($ind < count($graph)) {
			if ($graph[$ind]['tabelaOrigem'][0] == $ord[0]) {
				//echo $ord[0].'<br>';
				break;
			}
			$ind++;
		}		
		
		array_shift($ord);// tira o primeiro elemento
		$temp = array();
		if (count($graph[$ind]['colunaDate'])) {
			foreach ($graph[$ind]['colunaDate'] as $key => $value) {

				$coluna = $graph[$ind]['colunaDate'][$key];
				$tabela = $graph[$ind]['tabelaOrigem'][0];

				foreach ($instance as $key1 => $value1) {

					$sql = "SELECT  $coluna FROM  $tabela WHERE $coluna LIKE \"%$instance[$key1]%\" LIMIT 1";
					$retorno=dbConsulta($sql, $banco, $connect);
					
					if (mysql_num_rows($retorno) > 0) {
						foreach ($instance as $key2 => $value2) {
							if($value2 == $instance[$key1]){
								$temp[$value2] = 1;
							}
							if($value2 == $instance[$key1]){
								$temp[$value2] = 1;
							}
							
						}
						while($row = mysql_fetch_assoc($retorno)){
							array_push($saidaAux, $tabela."#".$coluna."#".$row[$coluna]);
						}
					}
					
				}
			}
			if (count($temp) == count($instance)) {
				$temp = 0;
				$saida []= $saidaAux;
			}else{
				unset($saidaAux);
				$saidaAux = array();
			}
			
	
		}
		$cont++;
	}
	foreach ($saida as $key => $value) {
		foreach ($saida[$key] as $key1 => $value1) {
			$resp[] = $saida[$key][$key1];
		}
		
	}
	if (isset($resp)) {
		return $resp;
	}
	return 0;

}



?>