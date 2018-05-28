<?php	

$grafoAux = array();
$grafoAux[0][0] = '/'; 
$tabColVal = '';

function fullUpper($string){
  return strtr(strtoupper($string), array(
      "à" => "À",
      "è" => "È",
      "ì" => "Ì",
      "ò" => "Ò",
      "ù" => "Ù",
       "á" => "Á",
      "é" => "É",
      "í" => "Í",
      "ó" => "Ó",
      "ú" => "Ú",
      "â" => "Â",
      "ê" => "Ê",
      "î" => "Î",
      "ô" => "Ô",
      "û" => "Û",
      "ç" => "Ç",
    ));
} 

//cria grafo adjacente com valor 1 no caminho da PK pra FK e valor 2 no caminho da FK pra PK
function grafoAdjacente($for, $ref){
	Global $grafoAux;
	//encontra a linha de adjacencia 
	for ($i=1; $i < count($grafoAux[0])-1; $i++) { 
		if (fullUpper($grafoAux[$i][0]) == fullUpper($for)) {
			break;
		}
	}
	//encontra a linha de adjacencia 
	for ($j=1; $j < count($grafoAux[0])-1; $j++) { 
		if (fullUpper($grafoAux[0][$j]) == fullUpper($ref)) {
			break;
		}
	}
	$grafoAux[$j][$i] = 1;
	$grafoAux[$i][$j] = 2;
}

function zeraGrafo(){
	Global $grafoAux;
	for ($i=1; $i < count($grafoAux[0]); $i++) { 
		for ($j=1; $j < count($grafoAux[0]); $j++) { 
			$grafoAux[$i][$j] = 0;
		}
	}

}


function leEsquema($app, $connect, &$graph){
	Global $grafoAux, $banco;
	$tabelaOrigem = array();
	$tabelaDestino = array();
	$nameForeign = array();
	//consulta tabelas do esquema
	$sql = "SELECT `TABLE_NAME` FROM `TABLES` WHERE `TABLE_SCHEMA`=\"$banco\"  and `TABLE_TYPE`= \"BASE TABLE\" "; // retorna todas as tabelas do esquema selecinado
	$retorno=dbConsulta($sql, $app, $connect);


	//adiciona tabela->nodo e cria entrutura geral do grafo
	$cont=0;
	if (mysql_num_rows($retorno) > 0) {
		while($row = mysql_fetch_assoc($retorno)){
			array_push($graph, array('tabelaOrigem' => array(), 'colunaPK' => array(), 'tabelaDestino' => array(), 'colunaFor' => array(), 'colunaRef' => array(),  'colunaDate' => array(), 'colunaInt' => array(), 'colunaAlf' => array()));
			array_push($graph[$cont]['tabelaOrigem'], $row['TABLE_NAME']);
			$grafoAux[$cont+1][0] = $row['TABLE_NAME'];
			$grafoAux[0][$cont+1] = $row['TABLE_NAME'];
			$nomeTabela = $row['TABLE_NAME'];
			$sql = "SELECT COLUMN_NAME FROM `COLUMNS` WHERE TABLE_SCHEMA = \"$banco\" and TABLE_NAME = \"$nomeTabela\" and COLUMN_KEY = \"PRI\"";
			$rt=dbConsulta($sql, $app, $connect);
			if (mysql_num_rows($rt) > 0) {
				$rw = mysql_fetch_assoc($rt);
				array_push($graph[$cont]['colunaPK'], $rw['COLUMN_NAME']);
			}
			$cont++;	
		}
	}
	
	//consulta dos atributos e seus dominios e adiciona no grafo
	$cont=0;
	while ($cont < count($graph)) {
		$tabela=$graph[$cont]['tabelaOrigem'][0];
	 	$sql = "SELECT COLUMN_NAME, DATA_TYPE FROM COLUMNS WHERE TABLE_SCHEMA=\"$banco\" and TABLE_NAME=\"$tabela\""; // retorna os atributos e tipos de cada tabela
		$retorno=dbConsulta($sql, $app, $connect);
		while($row = mysql_fetch_assoc($retorno)){
			if($row['DATA_TYPE']=='date'){
				array_push($graph[$cont]['colunaDate'], $row['COLUMN_NAME']);	
			}
			if($row['DATA_TYPE']=='varchar' || $row['DATA_TYPE']=='char' || $row['DATA_TYPE']=='text'){
				array_push($graph[$cont]['colunaAlf'], $row['COLUMN_NAME']);	
			}
			if($row['DATA_TYPE']=='int'){
				array_push($graph[$cont]['colunaInt'], $row['COLUMN_NAME']);	
			}
			
		}
	 	$cont++;
	 }
	
	

	//consulta relações entre as tabelas
	/*$sql = "SELECT * FROM `INNODB_SYS_FOREIGN` WHERE `ID` like \"%$banco%\" ORDER BY `ID` ASC "; // retorna a relação entre as tabelas
	$retorno=dbConsulta($sql, $app, $connect);
	
	zeraGrafo();

	//cria lista de relações
	if (mysql_num_rows($retorno) > 0) {
		while($row = mysql_fetch_assoc($retorno)){
			array_push($nameForeign, $row['ID']);
			$aux=explode('/', $row['FOR_NAME']);
			array_push($tabelaDestino, $aux[1]);
			$aux2=explode('/', $row['REF_NAME']);
			array_push($tabelaOrigem, $aux2[1]);
			grafoAdjacente($aux[1], $aux2[1]);
		}
	}*/
	
	$cont=0;
	while ($cont < count($graph)) {
		$tableInformationSchema = $graph[$cont]['tabelaOrigem'][0];
		$sql = "select TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME from KEY_COLUMN_USAGE where TABLE_NAME = \"$tableInformationSchema\"
		and REFERENCED_COLUMN_NAME is not null";
		$retorno=dbConsulta($sql, $app, $connect);
		$row = mysql_fetch_assoc($retorno);
		if($row != false){
			array_push($graph[$cont]['colunaFor'], $row['COLUMN_NAME']);
			array_push($graph[$cont]['colunaRef'], $row['REFERENCED_COLUMN_NAME']);
			array_push($graph[$cont]['tabelaDestino'], $row['REFERENCED_TABLE_NAME']);
		}
		$cont++;
	}
	
	
	//adiciona as relações e seus atributos de relação
	/*foreach ($tabelaOrigem as $k => $v) {
		$cont=0;
		while ($cont < count($graph)) {
			if ($tabelaOrigem[$k]==$graph[$cont]['tabelaOrigem'][0]) {
				$idForeign = $nameForeign[$k];

				$sql = "SELECT `FOR_COL_NAME`, `REF_COL_NAME` FROM `INNODB_SYS_FOREIGN_COLS` WHERE ID=\"$idForeign\"";// retorna os atributos de relação
				$retorno=dbConsulta($sql, $app, $connect);
				$row = mysql_fetch_assoc($retorno);
				array_push($graph[$cont]['colunaFor'], $row['FOR_COL_NAME']);
				array_push($graph[$cont]['colunaRef'], $row['REF_COL_NAME']);
				array_push($graph[$cont]['tabelaDestino'], $tabelaDestino[$k]);
				break;
			}
			$cont++;
		}
	}*/
	//print_r($graph);
}

?>