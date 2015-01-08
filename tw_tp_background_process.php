<?php 
    class backgroundProcesses{
        function __construct(){
            
        }
        
        function background_process($cmd,$outputfile,$pidfile){
            exec(sprintf("%s > %s 2>&1 & echo $! >> %s", $cmd, $outputfile, $pidfile));
        }
        
        function isRunning($pid){
            try {
                $result = shell_exec(sprintf("ps %d", $pid));
                if( count(preg_split("/\n/", $result)) > 2){
                    return true;
                }
            } catch(Exception $e) {
                print_r($e);
            }
            return false;
        }
    }
    
    $background = new backgroundProcesses();
    $background->background_process();
?>