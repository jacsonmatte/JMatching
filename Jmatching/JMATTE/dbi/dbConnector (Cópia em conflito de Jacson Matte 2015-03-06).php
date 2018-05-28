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


//retorna a ordem pelo numero de colunas e de linhas
function prioryOrdenacao($graph, $connect){
	$ord = array();
	$sql = "SELECT `TABLE_NAME`, `TABLE_ROWS` FROM information_schema.TABLES WHERE TABLE_SCHEMA = \"academica\" ORDER BY `TABLE_ROWS` DESC ";
	$retorno=dbConsulta($sql, "information_schema", $connect);
	while($row = mysql_fetch_assoc($retorno)){
		array_push($ord, $row['TABLE_NAME']);
	}
	return $ord;
}


//busca atributos datas
function buscaDate($graph,$instance){
	$connect=dbConnect("localhost", "root", "");
	$cont = 0;
	$ord = prioryOrdenacao($graph, $connect);
	print_r($graph);
	print_r($ord);
	while ($cont < count($graph)) {


		$ind = 0;
		while ($ind < count($graph)) {
			//echo $graph[$key]['tabelaOrigem'][0];
			if ($graph[$ind]['tabelaOrigem'][0] == $ord[0]) {
				//echo $cont;
				//echo $ord[0];
				break;
			}
			$ind++;
		}		
		
		array_shift($ord);

		if (!count($graph[$ind]['tabelaDestino'])) { //tabelas que não referenciam não são vereficadas
			$cont++;
			continue;
		}
		echo $ind;
		//echo count($graph[$ind]['colunaDate']); 
		if (count($graph[$ind]['colunaDate'])) {
			foreach ($graph[$ind]['colunaDate'] as $key => $value) {
				$coluna = $graph[$ind]['colunaDate'][$key];
				$tabela = $graph[$ind]['tabelaOrigem'][0];
				print($tabela);
				$arr = explode('/', $instance);
				$newdata = $arr[2].'-'.$arr[1].'-'.$arr[0];
				$sql = "SELECT $coluna FROM  $tabela WHERE $coluna = \"$newdata\" ";
				$retorno=dbConsulta($sql, "academica", $connect);
				if (mysql_num_rows($retorno) > 0) {
					$row = mysql_fetch_assoc($retorno);
					echo $row[$coluna];
				}
			}
	
		}
		
		$cont++;

	}
}


//busca atributos inteiros
function buscaInt($graph,$instance){ 
	$connect=dbConnect("localhost", "root", "");
	$cont = 0;
	while ($cont < count($graph)) {
		if (!count($graph[$cont]['tabelaDestino'])) {
			$cont++;
			continue;
		}

		if (count($graph[$cont]['colunaInt'])) {
			foreach ($graph[$cont]['colunaInt'] as $key => $value) {
				$coluna = $graph[$cont]['colunaInt'][$key];
				$tabela = $graph[$cont]['tabelaOrigem'][0];
				$sql = "SELECT $coluna FROM  $tabela WHERE $coluna = \"$instance\" ";
				$retorno=dbConsulta($sql, "academica", $connect);
				if (mysql_num_rows($retorno) > 0) {
					$row = mysql_fetch_assoc($retorno);
					echo $row[$coluna];
				}
			}
	
		}
		$cont++;
	}
}



//Busca atributos alfanumericos
function buscaAlf($graph,$instance){ 
	$connect=dbConnect("localhost", "root", "");
	$cont = 0;
	while ($cont < count($graph)) {
		if (!count($graph[$cont]['tabelaDestino'])) {
			$cont++;
			continue;
		}
		if (count($graph[$cont]['colunaAlf'])) {
			foreach ($graph[$cont]['colunaAlf'] as $key => $value) {
				$coluna = $graph[$cont]['colunaAlf'][$key];
				$tabela = $graph[$cont]['tabelaOrigem'][0];
				$sql = "SELECT $coluna FROM  $tabela WHERE $coluna LIKE \"%$instance%\" ";
				$retorno=dbConsulta($sql, "academica", $connect);
				if (mysql_num_rows($retorno) > 0) {
					$row = mysql_fetch_assoc($retorno);
					//echo $row[$coluna];
				}
			}
			
	
		}
		$cont++;
	}
}

?>