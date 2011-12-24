<?php
/**
 * Depending on the PHP version, define the parse_ini_string function.
 * Source: a comment in the PHP manual, http://php.net/manual/en/function.parse-ini-string.php#97621
 * (Allow white-space?)
 */

# Define parse_ini_string if it doesn't exist.
# Does accept lines starting with ; as comments
# Does not accept comments after values
if( !function_exists('parse_ini_string') ){
    function parse_ini_string( $string ) {
        $array = Array();

        $lines = explode("\n", $string );
        
        foreach( $lines as $line ) {
            $statement = preg_match(
#"/^(?!;)(?P<key>[\w+\.\-]+?)\s*=\s*(?P<value>.+?)\s*$/"  //WAS.
"/^(?!;)\s*?(?P<key>[\w+\.\-]+?)\s*=\s*(?P<value>.+?)\s*$/", $line, $match );

            if( $statement ) {
                $key    = $match[ 'key' ];
                $value    = $match[ 'value' ];

                # Remove quote
                if( preg_match( "/^\".*\"$/", $value ) || preg_match( "/^'.*'$/", $value ) ) {
                    $value = mb_substr( $value, 1, mb_strlen( $value ) - 2 );
                }
                
                $array[ $key ] = $value;
            }
        }
        return $array;
    }
}

  function copyemz($file1, $file2){ 
      $contentx = file_get_contents($file1); 
                   $openedfile = fopen($file2, "w"); 
                   fwrite($openedfile, $contentx); 
                   fclose($openedfile); 
                    
      return $contentx!==FALSE; //$status; 
  }
