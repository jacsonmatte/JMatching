<?php


$banco = '';

if (isset($_POST) and !empty($_POST)) {
		
	require_once dirname(__FILE__)."/exec_time/exec_time.php";
	Global $banco;
	$banco = $_POST["banco"];
	
	//echo $banco;
	require_once dirname (__FILE__)."/dbi/dbconnector.php";
    require_once dirname (__FILE__)."/grafoEsquema.php";
    grammar($_POST);

}


function conect(){
    $graph  = array();
    $fund = array();
    
     //Estabelencendo conexão DB
    //echo "Conectando com a base de dados... </br>";
    $connect=dbConnect("mysql.hostinger.com.br", "u956250334_imdb", "imdb2016");

    //Lendo esquema
    //echo "Carregando o esquema </br>";
    leEsquema("information_schema", $connect, $graph);
    return $graph;
}


function in_multiarray($elem, $array)
    {
        $top = sizeof($array) - 1;
        $bottom = 0;
        while($bottom <= $top)
        {
            if($array[$bottom] == $elem)
                return true;
            else
                if(is_array($array[$bottom]))
                    if(in_multiarray($elem, ($array[$bottom])))
                        return true;
                   
            $bottom++;
        }       
        return false;
    }




//busca proximos caminhos em largura no grafo
function proxCaminho($graph, $temp, &$listAtual, &$caminhos, $prox){
	Global $grafoAux;

	
	for ($i=1; $i < count($grafoAux[0]); $i++) {

		
			if ($grafoAux[$temp][0]) { //antes era if ($grafoAux[$temp][$i])
				$listAtual[] = $i;
				$caminhos[] = $grafoAux[$temp][0];
				$caminhos[] = $grafoAux[$i][0];
				if ($grafoAux[$i][0] == $graph[$prox]['tabelaOrigem'][0]) {
					return 1;
				}
			}
		}
	return 0;
}

//cria os caminhos pares conforme o tamanho do NJ
function fundMapPar($caminhos){
	$k = 1;
	$pathAux = '';
	$i = count($caminhos)-1;
	$pathAux.=$caminhos[$i].'#';
	$path[] = $caminhos[$i];
	$i--;
	$pathAux.=$caminhos[$i].'#';
	$path[] = $caminhos[$i];
	$i--;
	while ($i >= 0) {

		if (fullUpper($caminhos[$i]) == fullUpper($path[$k])) {
			$pathAux.=$caminhos[$i-1].'#';
			$path[] = $caminhos[$i-1];
			$k++;
		};
		$i = $i - 2;
	} 
	return $pathAux;
}

function buscaMapeamento($graph, $NJ, $ant, $prox, $pos){
	Global $grafoAux, $fund;
	$caminhos = array();
	$listAtual = array();
	//encontra a nodo incial do caminho
	for ($i=0; $i < count($grafoAux[0]); $i++) { 
		if (fullUpper($grafoAux[$i][0]) == fullUpper($graph[$ant]['tabelaOrigem'][0])) {
			$listAtual[] = $i;
			break;
		}
	}
	$j = 0;
	while ($j < $NJ+1) {
		$temp = $listAtual[$j];
		$rt = proxCaminho($graph, $temp, $listAtual, $caminhos , $prox);
		if ($rt == 1) {
			//echo "FUND";
			$fund[$pos][] = fundMapPar($caminhos);
			break;
		}
		$j++;
	}
	
}

function buscaGrafo($nodoTabela, $graph){//busca uma nodo que contem a ocorrencia e retorna a posicao do nodo
	$cont = 0;
	while ($cont < count($graph)) {
		if ($graph[$cont]['tabelaOrigem'][0] == $nodoTabela[0]) {
			return $cont;
		}
		$cont++;
	}
}



//gera um grafo entre os resultados intermediários
function uniaoMap($camPar){
	Global $grafoAux;
	$lst = array();
	//busca a posição de cada elemento na matriz de adjacencia
	$j = 0;
	while ($j < count($camPar)) {
			for ($i=0; $i < count($grafoAux[0]); $i++) { 
			if (fullUpper($grafoAux[$i][0]) == fullUpper($camPar[$j])) {
				$lst[] = $i;
				break;
			}
		}
		$j++;	
	}

	//adiciona o caminho de mapeamento par individualmente
	for ($i=0; $i < count($camPar)-1; $i++) { 
		$grafoAux[$lst[$i]][$lst[$i+1]] = 1;
	}		
	
}

//reordena a array com os elementos a serem fundidos
function reordenaFund($trashDuplicate){
	$aux = array();
	$temp = array_keys($trashDuplicate);
	for ($i=0; $i < count($trashDuplicate); $i++) {
		foreach ($trashDuplicate[$temp[$i]] as $key => $value) {
			$aux[$i][] = $trashDuplicate[$temp[$i]][$key];
		}
	}
	return $aux;
}


function trocaValorIndice($array){
	$invert = array();
	foreach ($array as $key => $value) {
		$invert[$value] = $key;
	}
	return $invert;
}



function weave($NJ, $graph){
	Global $data, $listaInseridos, $grafoAux, $fund, $tabColVal, $banco;

	//echo "AQUI WEAVE";
	array_map('unlink', glob(getcwd()."*.csv")); //excluir arquivos antigos
	$queryTab =  array(); //guarda as tabelas para consulta
	$queryRelation =  array(); //guarda as relações para consulta
	$trashDuplicate = array();
	$fdcAnt =  array(); //verifica se os mapeamentos forem iguais a algum anterior
	
	foreach ($fund as $key => $value) {
		$trashDuplicate[$key] = array_unique($fund[$key]);  
	}

	$fund = reordenaFund($trashDuplicate);

	$i = 0;
	while ($i < count($fund[0])) {
		$tempCM = array();//guarda os caminhos par invertido chave/valor
		$consulta = array();//guarda os parametros para retorna o resultado das consultas
		$aux = explode('#', $fund[0][$i]);

		array_pop($aux);
		if (count(array_unique($aux)) != count($aux)) {//verifica se existe elementos duplicados
			for($xt = 0; $xt < count($aux); $xt++) {
				foreach ($aux as $key1 => $value1) {
					if ($aux[$xt] == $value1) {
						$tempCM[] = array($value1 => $xt);
						$xt = count($aux);
						break;
					}
				}
				
			}
		}else
		$tempCM[] =trocaValorIndice($aux);


		
		$j = 1;
		while ($j < count($fund)) {
			$k = 0;
			while ($k < count($fund[$j])) {
				$aux = explode('#', $fund[$j][$k]);
				array_pop($aux);
				if($aux[0]==$aux[count($aux)-1]){
					break;
				}
				$edges = array();
				if (count(array_unique($aux)) != count($aux)) {//verifica se existe elementos duplicados
					for($xt = 0; $xt < count($aux); $xt++) {
						foreach ($aux as $key1 => $value1) {
							if ($aux[$xt] == $value1) {
								$tempCM[][] = array($value1 => $xt);
								$xt = count($aux);
								break;
							}
						}
						
					}
				}else
				$tempCM[] =trocaValorIndice($aux);
				$k++;
			}
			$j++;
		}


		
		
		if (count($tempCM) != 1) {//se existir mais de um nodo como caminhoVERIFICAR SE O NODO Nao é o mesmo
			//echo count($tempCM);
		//faz o merge e intersecção entre os caminhos pares
			for ($p = 0; $p < count($tempCM) -1; $p++) {//aqui fazer pra mais de um merge

				if ($p == 0) {
					if (empty(array_intersect_key($tempCM[$p], $tempCM[$p+1]))) {
						$fdc = $ap =  $tempCM[$p];
						continue;
					}
					$fdc = array_merge($tempCM[$p], $tempCM[$p+1]);
					$ap = $tempCM[$p];					
				}else{
					if (empty(array_intersect_key($ap, $tempCM[$p+1]))) {
						continue;
					}
					$fdc = array_merge($fdc, $tempCM[$p+1]);
					

				}
			}
			$fdc = array_keys($fdc);
		}else{// caso exista somente um nodo como resposta
			$edges[$i] = array_flip($tempCM[0]);
			$fdc = array_flip($tempCM[0]);
			if(count($tempCM, 1) == 2){//VEM pra URGENTE
				$data[$i.'mp']['edges'][] = array('from' => $key.$i, 'to' => '' );
			}
		}

	

		//adiciona os nodos e seleciona as tabelas com os dados a serem inseridos
		

		$addCor = 0;
		 foreach ($fdc as $key => $value) {
		 	$data[$i.'mp']['nodes'][] = array('id' => $key.$i, 'label' => $fdc[$key]);
		 	foreach ($tabColVal as $key1 => $value1) {
		 		$value1 = explode('#', $value1);
		 		if ($value == $value1[0]) {
		 			if (!isset($consulta['tab'])) {
		 				$data[$i.'mp']['nodes'][$addCor]['color'] = 'green'; 
		 				$consulta['tab'][] = $value1[0];//tabela
		 				$consulta['col'][] = $value1[1];//coluna
		 				$consulta['ocrs'][] = $value1[2];//ocorencia
		 			}

		 			if (!in_array($value1[0],$consulta['tab']) || !in_array($value1[1],$consulta['col'])) {
		 				$data[$i.'mp']['nodes'][$addCor]['color'] = 'green'; 
		 				$consulta['tab'][] = $value1[0];//tabela
		 				$consulta['col'][] = $value1[1];//coluna
		 				$consulta['ocrs'][] = $value1[2];//ocorencia
		 			}
		 			
		 		}
		 	}
		 	$addCor++;

		}


		$data[$i.'mp']['tab'] = $consulta['tab'];
		$data[$i.'mp']['col'] = $consulta['col'];
		$ocr = $consulta['ocrs'];
		

		
		//liga as relações par a par precorendo todas as tabelas do caminho gerado
		$ltcompara = array();
		 for ($p=0; $p < count($fdc) -1 ; $p++) {
		 	$j = $p+1;
		 	for ($j; $j < count($fdc); $j++) { 
		 			for ($k=0; $k < count($grafoAux[0]); $k++) { 
		 				if (fullUpper($grafoAux[$k][0]) == fullUpper($fdc[$p])) {
							
		 					$ltcompara[] = $k;
		 				}
		 				if (fullUpper($grafoAux[$k][0]) == fullUpper($fdc[$j])) {
		 					$ltcompara[] = $k;
		 				}
		 			}
		 			if ($grafoAux[$ltcompara[0]][0] > 0) {
		 				$data[$i.'mp']['edges'][] = array('from' => $p.$i, 'to' => $j.$i );
		 			}
		 			unset($ltcompara);
		 			$ltcompara = array();
		 		}	
		 }
		unset($fdc);
		$queryRelation = array();
		//encontra a tabela/id
		for ($p=0; $p < count($data[$i.'mp']['nodes']); $p++) {
			$queryTab[] = $data[$i.'mp']['nodes'][$p]['label'].'#'.$data[$i.'mp']['nodes'][$p]['id'];
			if(isset($data[$i.'mp']['edges']) && $p < count($data[$i.'mp']['edges'])){
				$queryRelation[] = $data[$i.'mp']['edges'][$p]['from'].'#'.$data[$i.'mp']['edges'][$p]['to'];
			}
		}
		//print_r($data);
		//exit();
		$consulta = array();
		//echo $queryRelation.'|'.$queryTab.'|'.$data[$i.'mp']['tab'].'|'.$data[$i.'mp']['col'].'|'.$ocr.'|'.$graph.'|'.$i;
		$consulta = queryResult($queryRelation, $queryTab, $data[$i.'mp']['tab'], $data[$i.'mp']['col'], $ocr, $graph, $i);
		for ($l=0; $l < count($consulta); $l++) {
			if(isset($consulta[$l])){
				$data[$i.'mp']['exemplo'][] =  $consulta[$l];
			}
			
		}
		unset($queryTab);
		unset($queryRelation);
		$data[$i.'mp']['sql'] = $consulta['sql'];
		$data[$i.'mp']['linkbd'] = $consulta['linkbd'];
		$data[$i.'mp']['encontrada'] = $consulta['encontrada'];
		$data[$i.'mp']['joinNumber'] = count($ocr);
		$data[$i.'mp']['banco'] = $banco;
		
		$i++;
	}
	
	$result =  $i-1;
	for ($i=0; $i < count($data) ; $i++) { 
		$ordenar[$i] = count($data[$i.'mp']['nodes']);
	}

	asort($ordenar);

	$chaves = array_keys($ordenar);

	for ($i=0; $i < count($ordenar); $i++) { 
		$weave[$i.'mp'] = $data[$chaves[$i].'mp']; 
 	}
	
	unset($data);
	$data = $weave;
	$data['result'] =  $result;
	$timeMega = endExec();
	$timeMega = explode("#", $timeMega);
	$data['time'] = $timeMega[0];
	$data['mega'] = $timeMega[1];
	echo json_encode($data);

	
	
}


//gera dados para criação dos resultados das consultas
function dadosParaConsultas($ocorrencias){
	Global $tabColVal;
	for ($i=0; $i < count($ocorrencias); $i++) { 
		for ($j=0; $j < count($ocorrencias[$i]); $j++) { 
			$tabColVal[] = $ocorrencias[$i][$j];
		}
	}
}

//faz a distribuição para elaborar o caminho par entre os elementos do banco
function geraCaminhopar($ocorrencias, $graph, $rowColum, $NJ){//VER ISSO LOGO
	Global $fund;

	$key = array_keys($ocorrencias);
	$pri = $key[0];

	dadosParaConsultas($ocorrencias);
	

	if (sizeof($ocorrencias) > 0) {
		$k = 0;

		while ($k < count($ocorrencias[$pri], 1)) {

			$L = explode('#', $ocorrencias[$pri][$k]);//divide em tabela/coluna/valor
			$ant = buscaGrafo($L, $graph);//retorna posição do nodo no grafo
			//busca e combina as proxima ocorrencia e tenta relacionar coforme o NJ(number Join)
			$i = 1;
			//echo $ant.'#';
			while($i < count($ocorrencias)){
				$j = 0;
				while($j < count($ocorrencias[$key[$i]], 1)){
					$L = explode('#', $ocorrencias[$key[$i]][$j]);
					$prox = buscaGrafo($L, $graph);// proximo a ser verificado
					//echo $prox;
					buscaMapeamento($graph, $NJ, $ant, $prox, $i-1);

					$j++;
				}
				$i++;
			}
			$k++;
		}

	}

	if (!empty($fund)) {
		weave($NJ, $graph);
	}
}



//recebe os exemplos agrupados por coluna e seus tipos
function addOcorencias($ltOcorrencia, $tpOcorrencia, $graph, &$ocorrencias){
	foreach ($ltOcorrencia as $key => $value) {

		if ($tpOcorrencia[$key][0] == 'ALF' ) {

			$resp = buscaAlf($graph, $ltOcorrencia[$key]);
			if ($resp != 0) {
				$ocorrencias[] = $resp;
			}
		}elseif ($tpOcorrencia[$key][0] == 'DATE' or $tpOcorrencia[$key][0] == 'INT') {
			$resp = buscaDate($graph, $ltOcorrencia[$key]);
			if ($resp != 0) {
				$ocorrencias[] = $resp;
			}
			
		}
		if ($tpOcorrencia[$key][0] == 'INT') {
			$resp = buscaInt($graph, $ltOcorrencia[$key]);
			if ($resp != 0) {
				$ocorrencias[] = $resp;
			}
		}
		
	}
}

//busca as ocorrencias de instâncias por restrição de tipo de dado
function buscaOcorrencias($graph, $rowColum, $classInput){
	$i = 0;
	$restri = 0;//restrição para qque tenha mais de um dado de entrada
	$ocorrencias = array();
	$k = 0;
	foreach ($classInput[0]['valor'] as $key => $value) {
		if ($value !== 'NULLO') {
				$restri++;
			}	
	}


	if ($restri > 1) {//somente se existir mais de um elemento
		$ltOcorrencia =  array();//ocorrencia
		$tpOcorrencia = array(); //tipo ocorrencia
			while ( $k <= $rowColum[0]) {
				
				$l = 0;
				while ($l <= $rowColum[1]) {
					if ($classInput[0]['tipo'][$i] == "NULLO") {
						$i++;
						$l++;
						continue;

					}elseif ($classInput[0]['tipo'][$i] == "DATE") {
						$ltOcorrencia[$l][] =  $classInput[0]['valor'][$i];
						$tpOcorrencia[$l][] =  'DATE';
						$i++;

					}elseif ($classInput[0]['tipo'][$i] == "INT") {
						$ltOcorrencia[$l][] =  $classInput[0]['valor'][$i];
						$tpOcorrencia[$l][] =  'INT';
						$i++;
					}else{
						$ltOcorrencia[$l][] =  $classInput[0]['valor'][$i];
						$tpOcorrencia[$l][] =  'ALF';
						$i++;
					}
					$l++;
				}
			
			
			$k++;
		}
	}
	
	if (isset($ltOcorrencia) && isset($tpOcorrencia)) {

			addOcorencias($ltOcorrencia, $tpOcorrencia, $graph, $ocorrencias);
			
			geraCaminhopar($ocorrencias, $graph, $rowColum, 10);//definição dos joins
			
		}
	
}

//Encontra o numero de linhas e colunas da tabela de entrada
function searchRowColum($input){
	$indices = array_keys($input);
	$pos = count($indices);
	$aux = explode("t", $indices[$pos-1]);
	$rowColum = explode("#", $aux[1]);
	return $rowColum;
}

//separa as entrada em int/alfanumerico/date	
function grammar($input){
	$graph  = array();
	$graph = conect();// grafo do esquema
	
	$classInput = array();
	array_push($classInput, array('valor' => array(), 'tipo' => array()));//estrutura de classificação
	array_shift($input);
	foreach ($input as $key => $value) {
			//verifica o campo vazio
			if (empty($input[$key])) {
				array_push($classInput[0]["valor"], "NULLO");
				array_push($classInput[0]["tipo"], "NULLO");
				continue;
			}

			if(preg_match("/(\d{2})\/(\d{2})\/(\d{4})$/", $input[$key], $matches)){
	    		if (checkdate($matches[2], $matches[1], $matches[3])) { // testa se a data é válida
					array_push($classInput[0]["valor"], $input[$key]);
					array_push($classInput[0]["tipo"], "DATE");
				}
			}elseif(is_numeric($input[$key])){
	    		array_push($classInput[0]["valor"], $input[$key]);
				array_push($classInput[0]["tipo"], "INT");
			}else{
	    		array_push($classInput[0]["valor"], $input[$key]);
				array_push($classInput[0]["tipo"], "ALF");
			}
	}
	
	$rowColum = searchRowColum($input);//retorna a quantidade de linhas e colunas da tabela de entrada 
	
	buscaOcorrencias($graph,$rowColum, $classInput);

}
	



?>
