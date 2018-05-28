<?php
    

   global $script_start;
    
   /* Get current time */
   function getTime(){
      return microtime(TRUE);
   }
    
   /* Calculate start time */
   function startExec(){
      global $script_start;
      // Iniciamos o "contador"
      list($usec, $sec) = explode(' ', microtime());
      $script_start = (float) $sec + (float) $usec;
   }
    
   /*
    * Calculate end time of the script,
    * execution time and returns results
    */
   function endExec(){
      global $script_start;
      list($usec, $sec) = explode(' ', microtime());
      $script_end = (float) $sec + (float) $usec;
      $elapsed_time = round($script_end - $script_start, 2);
      return number_format($elapsed_time, 3).'#'.((memory_get_peak_usage(true) / 1024) / 1024);
      //echo 'Elapsed time: ', number_format($elapsed_time, 3), ' secs. Memory usage: ', round(((memory_get_peak_usage(true) / 1024) / 1024), 2), 'Mb'; 
   }
    
?>