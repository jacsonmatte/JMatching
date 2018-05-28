<script type="text/javascript">
    //adicona coluna na tabela de entrada
    column=2; //colunas tabela de entrada
    row=0;
    $(function(){
    	 //linhas tabela de entrada

    	//adiciona uma coluna na tabela de entrada
        $("#addColumn").click(function(){
        	var id="input"+row+"#"+column;
    		$(".addProx").replaceWith("<td><input class='form-control' id="+id+" name="+id+"></td><td class='addProx'></td>");
    		column=column+1;
		});

        //adiciona uma linha na tabela de entrada	
		$("#addRow").click(function(){
			row=row+1;
			var aux=0;
			var id="input"+row+"#"+aux;
			while (aux<column){
				var id="input"+row+"#"+aux;
				if (aux==column-1) {
    				$(".addProxAux").replaceWith("<td class='ult'><input class='form-control' id="+id+" name="+id+"></td></tr>");
				} else if (aux==0) {
    				$("#inputTable").append("<tr><td><input class='form-control' id="+id+" name="+id+"></td><td class='addProxAux'></td>");
				} else {
    				$(".addProxAux").replaceWith("<td><input class='form-control' id="+id+" name="+id+"></td><td class='addProxAux'></td>");
				}
				aux=aux+1;
			}	
		});

		$("#selectBanco").focusout(function(){
			$('.form-control').val("");
			$("#inputTable").replaceWith('<table id="inputTable" class="table table-striped"><tr> <td><input class="form-control" id="input00" name="input0#0"></td><td><input class="form-control" id="input01" name="input0#1"></td><td class="addProx"></td></table>');
			column=2; //colunas tabela de entrada
    		row=0;
		});

		//le o formulario e retorna uma array para o php
		$(document).keypress(function(e) {
			if(e.which == 13){
				var x = $("#formulario").serializeArray();
			//requisicao ajax
			alert("AQUI");
			
        	$.ajax({
 				url : "mapping.php", 
				type: "POST",
				dataType: 'json',
				data : x,
				success:function(data, textStatus, jqXHR){ //variavel data recebe os valores da tabela de entrada
					$("#results").html(data);
					//alert(data.nodes);

					console.log(data);
					
					//alert(data[0].nodes[0].id);
					if (data['0mp']!=undefined && data['0mp'].nodes!=undefined && data['0mp'].edges!=undefined && data['0mp'].nodes.length && data['0mp'].edges.length) {
						//$("#resultList").append("<a  href='javascript:void(0);' onclick='minhaFuncao();'>teste</a>");
						

						for (var i = 0; i <= data.result; i++) {
							$("#results").append("<div class = 'results2' id='"+i+"r2'></div>");

							var container = document.getElementById(i+"r2");
							var options = {
							        stabilize: false,
							        edges: {
							          width: 4,
							          color: 'red'
							        },
							        nodes: {
							          // default for all nodes
							          fontFace: 'times',
							          shape: 'circle',
							          fontSize: 18,
							          fontColor: 'white',
							          color: {
							            border: 'blue',
							            background: 'blue',
							          }
							        }
							    };
							  var network = new vis.Network(container, { nodes: data[i+'mp'].nodes, edges: data[i+'mp'].edges }, options);

							  $("#results").append("<div  class = 'results3' id='"+i+"c2'></div>");
							  var container = document.getElementById(i+"c2");
							  var add = "<table class='table'> <thead> <tr>";
							  var addAux;
							  for (var j = 0; j < data[i+'mp'].tab.length; j++){
							 		addAux = "<th>"+data[i+'mp'].tab[j].toUpperCase()+"->"+data[i+'mp'].col[j].toUpperCase()+"</th>";
							 		add = add + addAux;
							  }

							  addAux = "</tr> </thead><tbody>";
							  add = add + addAux;
							  var tam = new Array(j);

							  //pega os tamanhos de cada vetor de exemplos
							for (var ir = 0; ir < data[i+'mp'].exemplo.length ; ir++) {
							  	tam[ir] = data[i+'mp'].exemplo[ir].length;
							 };

							 var maior = Math.max.apply(null, tam );//encontra o maior valor da lista

							  var controla = 0;//controla o acesso pra criar as colunas
							  var k = 0;

							  addAux = "<tr>";
							  add = add + addAux;
							  //alert(data[i+'mp'].encontrada);
							  var encontrada=0;
							  while (k < data[i+'mp'].exemplo.length) {
								  	if (tam[k] <= controla) {//caso estoure o vetor vai pro proximo vetor e adiciona coluna em branco
								  		addAux = "<th>  </th>";
								  		add = add + addAux;
								  		k++;
								  		continue;
								  	};
								  	
								  	if (data[i+'mp'].exemplo[k][controla] == undefined) { 
								  		//alert(k);
								  		//alert(controla);	
								  		break;
								  	};

								  	if(encontrada<data[i+'mp'].joinNumber && data[i+'mp'].encontrada=='1'){
							  			addAux = "<th style='color:red;'>"+data[i+'mp'].exemplo[k][controla]+"</th>";
							  			add = add + addAux;
							  			encontrada = encontrada + 1; 
							  		}else{
							  			addAux = "<th>"+data[i+'mp'].exemplo[k][controla]+"</th>";
							  			add = add + addAux;
							  		}
							  		

								    k++;

								    if(k == data[i+'mp'].exemplo.length){
							  			 addAux = "</tr><tr>";
								 		 add = add + addAux;
							  			 k = 0;
							  			 controla ++;
							  			 continue;
							  		}

								    if (controla == maior) {//para de imprimir registros caso seja 10 ou o maior registro PRECISA VER SENAO ACABA AQUI A PORA TODA

							  			break;
							  		};
							  };


							  addAux = "</tbody> </table>";
							  add = add + addAux;
							  bt = "<button type='button' class='btn btn-primary' data-toggle='modal' data-target='.bs-example-modal-sm"+i+"'>VIEW SQL</button>";
							  btnlink = "<button type='button' class='btn btn-info' data-toggle='modal' data-target='.nomeBanco"+i+"'>Download Database</button>";
							  bt2 = "<div class='modal fade bs-example-modal-sm"+i+"' tabindex='-1' role='dialog' aria-labelledby='mySmallModalLabel' aria-hidden='true'>";
							  bt3 = "<div class='modal-dialog modal-sm'>";
							  bt4 = "<div class='modal-content'>";
							  bt5 =   "<div class='modal-header'>";
							  bt6 =       "<button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>×</span></button>";
							  bt7 =        "<h4 class='modal-title' id='mySmallModalLabel'>VIEW SQL<a class='anchorjs-link' href='#mySmallModalLabel'><span class='anchorjs-icon'></span></a></h4>";
							  bt8 =     "</div>";
							  bt9 =     "<div class='modal-body'>";
							  bt10 =       data[i+'mp'].sql;
							  bt11 =    "</div>";
							  bt12 =   "</div>";
							  bt13 =  "</div>";
							  bt14 = "</div>";
							  bt15 = "</div>";
							  
							  dl2 = "<div class='modal fade nomeBanco"+i+"' tabindex='-1' role='dialog' aria-labelledby='mySmallModalLabel' aria-hidden='true'>";
							  dl3 = "<div class='modal-dialog modal-sm'>";
							  dl4 = "<div class='modal-content'>";
							  dl5 =   "<div class='modal-header'>";
							  dl6 =       "<button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>×</span></button>";
							  dl7 =        "<h4 class='modal-title' id='mySmallModalLabel'>Download Database<a class='anchorjs-link' href='#mySmallModalLabel'><span class='anchorjs-icon'></span></a></h4>";
							  dl8 =     "</div>";
							  dl9 =     "<div class='modal-body'>";
							  dl10 =       "<form id='formularioNameDB' method='POST' action='dbi/dbconnector.php' target='_blank'><input class='form-control' id='database' name='database' placeholder='Digite o nome do arquivo'><input name='sql' style='display:none' value='"+data[i+'mp'].linkbd+"' ><input name='banco' style='display:none' value='"+data[i+'mp'].banco+"' ><input class='btn btn-success' type='submit' value='Gerar' style='margin-top: 5px;'></form>"
							  dl11 =    "</div>";
							  dl12 =   "</div>";
							  dl13 =  "</div>";
							  dl14 = "</div>";
							  dl15 = "</div>";
							  add = add + bt+ btnlink  + bt2 + bt3 + bt4 + bt5 + bt6 + bt7 + bt8  + bt9 + bt10 + bt11 + bt12 + bt13 + bt14 + bt15;
							  add = add + dl2 + dl3 + dl4 + dl5 + dl6 + dl7 + dl8  + dl9 + dl10 + dl11 + dl12 + dl13 + dl14 + dl15;
							  container.innerHTML = add;
							  

							};
							//alert(data['time']);
							// alert(data['mega']);
						
					}


					  

				}

			});

			}
			
		});


		
    })

</script>


<?php
//ler os banco de dados de entrada e eliminas as bases padros do Mysql
function loadBD(){
	require_once dirname (__FILE__)."/dbi/dbconnector.php";
	$sql = "SELECT `SCHEMA_NAME` FROM `SCHEMATA`";
	$connect=dbConnect("mysql.hostinger.com.br", "u956250334_imdb", "imdb2016");
	$retorno=dbConsulta($sql, 'information_schema', $connect);
    while($row = mysql_fetch_assoc($retorno)){
    	if ($row['SCHEMA_NAME'] != 'information_schema' && $row['SCHEMA_NAME'] != 'mysql' && $row['SCHEMA_NAME'] != 'performance_schema' && $row['SCHEMA_NAME'] != 'phpmyadmin' && $row['SCHEMA_NAME'] != 'test') {
    		 $resp[] = $row['SCHEMA_NAME'];
    	}
	}
	return $resp;
}

function pgEntrada(){
?>
  

<!-- Botao adiciona linha-->
<button type="button" class="btn btn-primary btn-xs" id="addRow">
	<span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Add row
</button>

<!-- Botao adiciona coluna-->
<button type="button" class="btn btn-primary btn-xs" id="addColumn">
	<span class="glyphicon glyphicon-plus" aria-hidden="true"></span> Add Column 
</button>	

<!-- Tabela de entrada inicial linha-->
<div style="width: auto">
	<form id="formulario">

		<div id="selectBD">
			<b>Select Database:</b>
			<select class="btn-success" name="banco" id="selectBanco">
				<?php 
				$resp = loadBD();
				
					for ($i=0; $i < count($resp) ; $i++) { 
					
						echo '<option value='.$resp[$i].'>'.$resp[$i].'</option>';
					}
				?>
	  		</select>
  		</div>
		<div style="text-align: center;"><h1>Tabela de Entrada</h1></div>
		<table id="inputTable" class="table table-striped">
			
		  <tr>
		    <td><input class="form-control" id="input00" name="input0#0"></td>
		    <td><input class="form-control" id="input01" name="input0#1"></td>
		    <td class="addProx"></td>
			
		</table>
	</form>


	<div id="results"></div> <!-- resultado dos campos de entrada -->
	
	</div>

	<!-- <div id = "resultList"><div>Resultado 1 </div>Resultado 2 Resultado # Resultado $</div> -->

<?php
}
?>
