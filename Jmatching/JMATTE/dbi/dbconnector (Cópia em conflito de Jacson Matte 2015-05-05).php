<?php

//Conecta a BD


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


function queryResult($queryRelation, $queryTab, $consultaTab, $consultaCol, $consultaOcrs, $graph){
	//monta projeção da consulta
	$select = "SELECT ";
	for ($i=0; $i < count($consultaTab); $i++) { 
		if ($i == count($consultaTab)) {
			$select = $select.$consultaTab[$i].'.'.$consultaCol[$i];
		}else
		$select = $select.$consultaTab[$i].'.'.$consultaCol[$i].', ';
	}
	//monta a estrutura das clausula from
	$from = " FROM ";
	for ($i=0; $i < count($queryTab); $i++) {
		$temp = explode('#', $queryTab[$i]); 
		if ($i == count($consultaTab)) {
			$from = $from.$temp[0];
		}else
		$from = $from.$temp[0].', '; 
	}

	//monta clausula where
	//print_r($queryRelation);
	for ($i=0; $i < count($queryRelation); $i++) { 
	/*	$tempR = explode('#', $queryRelation[$i]);
		for ($j=0; $j < count($queryTab); $j++) { 
			$tempT = explode('#', $queryTab[$j]);
			if ($tempT[1] == $tempR[0]) {
				$tabfrom = $tempT[0];
			}
			if ($tempT[1] == $tempR[1]) {
				$tabto = $tempT[0];
			}
		}

		echo $tabto;
		for ($j=0; $j < count($graph); $j++) { 
			for ($k=0; $k < count($graph[$j]['tabelaDestino']); $k++) { 
				if ($graph[$j]['tabelaDestino'][$k] == $tabfrom && $graph[$j]['tabelaOrigem'][0] == $tabto) {
					$idFk = $graph[$j]['colunaFor'][$k];
				}
				if ($graph[$j]['tabelaOrigem'][0] == $tabto) {
					$idPk = $graph[$j]['colunaPK'][0];
				}
			}				
		}

		if (isset($idFk)) {
			$where = " WHERE ";
			$where = $where.$tabfrom.$idFk.' = '.$tabto.$idPk.' AND '; 
		}*/

	}
	//print_r($where);
}

//consulta 10 registros de cada tabela
function consultaResult($tab, $col, $ocrs){
	global $banco;
	$connect=dbConnect("localhost", "root", "");
	for ($i=0; $i < count($tab); $i++) { 
		$sql = "SELECT $col[$i]  FROM $tab[$i] WHERE $col[$i] LIKE \"%$ocrs[$i]%\" LIMIT 1 \n"
            .   "UNION\n"
            . "SELECT $col[$i]  FROM $tab[$i]  LIMIT 10 ";
        $retorno=dbConsulta($sql, $banco, $connect);
        while($row = mysql_fetch_assoc($retorno)){
			$saida[$i][] = $row[$col[$i]];
		}

	}
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

//retorna a ordem pelo numero de colunas e de linhas
/*function prioryOrdenacao($graph, $connect, $tipo){
	global $banco;
	$ord = array();
	$contAtributo =  array();
	$ordValor = array();
	$result = array();
	$sql = "SELECT `TABLE_NAME`, `TABLE_ROWS` FROM information_schema.TABLES WHERE TABLE_SCHEMA = \"$banco\" ORDER BY `TABLE_ROWS` DESC ";
	$retorno=dbConsulta($sql, "information_schema", $connect);

	while($row = mysql_fetch_assoc($retorno)){
		array_push($ord, $row['TABLE_NAME']);
		array_push($ordValor, $row['TABLE_ROWS']);
	}


	//calcula o numero de atributos do tipo date de cada tabela
	$key = 0;
	while ($key < count($graph)) {
		array_push($contAtributo, array('nomeTabela' => array(), 'valorTabela' => array()));
		array_push($contAtributo[$key]['valorTabela'], count($graph[$key][$tipo]));
		array_push($contAtributo[$key]['nomeTabela'], $graph[$key]['tabelaOrigem'][0]);
		$key++;
	}

	//calcula os atributos multiplicado pelo número de tuplas
	foreach ($ord as $i => $value) {
		foreach ($contAtributo as $j => $value) {
			if ($ord[$i] == $graph[$j]['tabelaOrigem'][0]) {
				array_push($result, $ordValor[$i]*$contAtributo[$j]['valorTabela'][0]);
				break;
			}
		}
	}

	//reordena as tabelas por nome
	arsort($result);
	$rfinal = array();
	$ind = array_keys($result);
	foreach ($ind as $i => $value) {
		foreach ($ord as $j => $value) {
			if ($ind[$i] == $j) {
				array_push($rfinal, $ord[$j]);
			}
		}
	}
	//print_r($rfinal);
	return $rfinal;
}*/




function buscaAlf($graph, $instance){
	global $banco;
	$resp = '';
	$connect=dbConnect("localhost", "root", "");
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
		$temp = 0;
		if (count($graph[$ind]['colunaAlf'])) {
			foreach ($graph[$ind]['colunaAlf'] as $key => $value) {

				$coluna = $graph[$ind]['colunaAlf'][$key];
				$tabela = $graph[$ind]['tabelaOrigem'][0];
				foreach ($instance as $key1 => $value1) {
					$sql = "SELECT  $coluna FROM  $tabela WHERE $coluna LIKE \"%$instance[$key1]%\" LIMIT 1";

					$retorno=dbConsulta($sql, $banco, $connect);
					if (mysql_num_rows($retorno) > 0) {
						//echo $tabela.$coluna;
						$temp ++;
						while($row = mysql_fetch_assoc($retorno)){
							array_push($saidaAux, $tabela."#".$coluna."#".$row[$coluna]);
						}
					}
					
				}
			}
			if ($temp >= count($instance)) {
				$temp = 0;
				$saida[] = $saidaAux;
				
			}else{
				unset($saidaAux);
				$saidaAux = array();
			}
			
	
		}
		$cont++;
	}
	//print_r($saida);
	//print_r($saida);

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
	$connect=dbConnect("localhost", "root", "");
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
		$temp = 0;
		if (count($graph[$ind]['colunaInt'])) {
			foreach ($graph[$ind]['colunaInt'] as $key => $value) {

				$coluna = $graph[$ind]['colunaInt'][$key];
				$tabela = $graph[$ind]['tabelaOrigem'][0];
				foreach ($instance as $key1 => $value1) {
					$sql = "SELECT distinct $coluna FROM  $tabela WHERE $coluna LIKE \"%$instance[$key1]%\" LIMIT 1 ";
					$retorno=dbConsulta($sql, $banco, $connect);
					if (mysql_num_rows($retorno) > 0) {
						$temp ++;
						while($row = mysql_fetch_assoc($retorno)){
							array_push($saidaAux, $tabela."#".$coluna."#".$row[$coluna]);
						}
					}
					
				}
			}
			if ($temp >= count($instance)) {
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





function buscaDate($graph, $instance){
	global $banco;
	$connect=dbConnect("localhost", "root", "");
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
		$temp = 0;
		if (count($graph[$ind]['colunaDate'])) {
			foreach ($graph[$ind]['colunaDate'] as $key => $value) {

				$coluna = $graph[$ind]['colunaDate'][$key];
				$tabela = $graph[$ind]['tabelaOrigem'][0];

				foreach ($instance as $key1 => $value1) {

					$sql = "SELECT distinct $coluna FROM  $tabela WHERE $coluna LIKE \"%$instance[$key1]%\" LIMIT 1";
					$retorno=dbConsulta($sql, $banco, $connect);
					
					if (mysql_num_rows($retorno) > 0) {
						$temp ++;
						while($row = mysql_fetch_assoc($retorno)){
							array_push($saidaAux, $tabela."#".$coluna."#".$row[$coluna]);
						}
					}
					
				}
			}
			if ($temp >= count($instance)) {
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