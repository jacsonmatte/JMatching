<!DOCTYPE html>
<html lang="en">
  <head>
     <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="js/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="js/bootstrap.min.js"></script>
  
  <script type="text/javascript" src="js/vis.js"></script>


 

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JMATCHING</title>
    <link rel="icon" type="image/ico" href="favicon.ico">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/vis.css" rel="stylesheet" type="text/css"/>
    <link rel="stylesheet" type="text/css" href="css/jmapping.css">
  </head>
  <body>
  	<div style="text-align:center" class="panel panel-primary"><h1 class="panel-title">JMATCHING</h1></div>
   
  <div class="panel panel-success"> 
    <?php
    require_once dirname (__FILE__)."/pgEntrada.php";
    require_once dirname(__FILE__)."/exec_time/exec_time.php";
      //Tabela de Entrada
      startExec();
      pgEntrada();

    ?>
  </div>  
  </body>
</html>