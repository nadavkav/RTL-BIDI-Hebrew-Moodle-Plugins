<?php
/**
 * csv writer class
 *
 * @copyright &copy; 2008 The Open University
 * @author d.a.woolhead@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 *//** */
class csv_writer {

    // Define csv format dependant variables
    private $_excelcsv = false;
    private $_sep = ",";
    private $_line = "\n";

    /**
     * Constructor
     * Outputs relevant csv header.
     *
     * @param string $filename csv filename
     * @return none other than updated csv object 
     */
    public function __construct($filename, $format = false) {
    
        // Write common header
        header('Content-Disposition: attachment; filename='.
               preg_replace('/[^a-z0-9-]/','_',strtolower($filename)).'.csv');
    
        // Unicode byte-order mark for Excel
        if($format == 'excelcsv') {
    
            // Set Excel csv variables
            $this->_excelcsv = true;
            $this->_sep = "\t".chr(0);
            $this->_line = "\n".chr(0);        
    
            // Write Excel csv header
            header('Content-Type: text/csv; charset=UTF-16LE');
            print chr(0xFF).chr(0xFE);
    
        } else {
    
            // Set csv variables
            $this->_excelcsv = false;
            $this->_sep = ",";
            $this->_line = "\n";
    
            // Write cvs header
            header('Content-Type: text/csv; charset=UTF-8');
        }
    }
    
    /**
     * Gets quoted csv variable string.
     *
     * @param string $varstr csv variable string
     * @return quoted csv variable string 
     */
    public function quote($varstr) {
        if($this->_excelcsv) {
            $tl = textlib_get_instance();
            return $tl->convert('"'.str_replace('"',"'",$varstr).'"','UTF-8','UTF-16LE');
        } else {
            return '"'.str_replace('"',"'",$varstr).'"';
        }
    }
    
    /**
     * Gets csv variable separator.
     *
     * @param none
     * @return csv variable separator 
     */
    public function sep() {
        return $this->_sep;
    }
    
    /**
     * Gets csv line separator.
     *
     * @param none
     * @return csv line separator 
     */
    public function line() {
        return $this->_line;
    }
    
}
?>
