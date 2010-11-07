<?php

if (!isset($XMLFile_Included) || !$XMLFile_Included) {
$XMLFile_Included = 1;

class XMLTag
{
    var $cdata;
    var $attributes;
    var $name;
    var $tags;
    var $parent;

    var $curtag;

    function XMLTag(&$parent)
    {
        if (is_object( $parent ))
        {
            $this->parent = &$parent;
        }
        $this->_init();
    }

    function _init()
    {
        $this->attributes = array();
        $this->cdata = '';
        $this->name = '';
        $this->tags = array();
    }

    function add_subtag($name, $attributes=0)
    {
        $tag = new XMLTag( $this );
        $tag->set_name( $name );
        if (is_array($attributes)) {
            $tag->set_attributes( $attributes );
        }
        $this->tags[] = &$tag;
        $this->curtag = &$tag;
    }

    function find_subtags_by_name( $name )
    {
        $result = array();
        $found=false;
        for($i=0;$i<$this->num_subtags();$i++) {
            if(strtoupper($this->tags[$i]->name)==strtoupper($name)) {
                $found=true;
                $array2return[]=&$this->tags[$i];
            }
        }
        if($found) {
            return $array2return;
        }
        else {
            return false;
        }
    }

    function clear_subtags()
    {
        # Traverse the structure, removing the parent pointers
        $numtags = sizeof($this->tags);
        $keys = array_keys( $this->tags );
        foreach( $keys as $k ) {
            $this->tags[$k]->clear_subtags();
            unset($this->tags[$k]->parent);
        }

        # Clear the tags array
        $this->tags = array();
        unset( $this->curtag );
    }

    function remove_subtag($index)
    {
        if (is_object($this->tags[$index])) {
            unset($this->tags[$index]->parent);
            unset($this->tags[$index]);
        }
    }

    function num_subtags()
    {
        return sizeof( $this->tags );
    }

    function add_attribute( $name, $val )
    {
        $this->attributes[strtoupper($name)] = $val;
    }

    function clear_attributes()
    {
        $this->attributes = array();
    }

    function set_name( $name )
    {
        $this->name = strtoupper($name);
    }

    function set_attributes( $attributes )
    {
        $this->attributes = (is_array($attributes)) ? $attributes : array();
    }

    function add_cdata( $data )
    {
        $this->cdata .= $data;
    }

    function clear_cdata()
    {
        $this->cdata = "";
    }

    function write_file_handle( $fh, $prepend_str='' )
    {
        # Get the attribute string
        $attrs = array();
        $attr_str = '';
        foreach( $this->attributes as $key => $val )
        {
            $attrs[] = strtoupper($key) . "=\"$val\"";
        }
        if ($attrs) {
            $attr_str = join( " ", $attrs );
        }
        # Write out the start element
        $tagstr = "$prepend_str<{$this->name}";
        if ($attr_str) {
            $tagstr .= " $attr_str";
        }

        $keys = array_keys( $this->tags );
        $numtags = sizeof( $keys );
        # If there are subtags and no data (only whitespace),
        # then go ahead and add a carriage
        # return.  Otherwise the tag should be of this form:
        # <tag>val</tag>
        # If there are no subtags and no data, then the tag should be
        # closed: <tag attrib="val"/>
        $trimmeddata = trim( $this->cdata );
        if ($numtags && ($trimmeddata == "")) {
            $tagstr .= ">\n";
        }
        elseif (!$numtags && ($trimmeddata == "")) {
            $tagstr .= "/>\n";
        }
        else {
            $tagstr .= ">";
        }

        fwrite( $fh, $tagstr );

        # Write out the data if it is not purely whitespace
        if ($trimmeddata != "") {
            fwrite( $fh, $trimmeddata );
        }

        # Write out each subtag
        foreach( $keys as $k ) {
            $this->tags[$k]->write_file_handle( $fh, "$prepend_str\t" );
        }

        # Write out the end element if necessary
        if ($numtags || ($trimmeddata != "")) {
            $tagstr = "</{$this->name}>\n";
            if ($numtags) {
                $tagstr = "$prepend_str$tagstr";
            }
            fwrite( $fh, $tagstr );
        }
    }

}
###############################################################################
class XMLFile
{
    var $parser;
    var $roottag;
    var $curtag;

    function XMLFile()
    {
        $this->init();
    }

    # Until there is a suitable destructor mechanism, this needs to be
    # called when the file is no longer needed.  This calls the clear_subtags
    # method of the root node, which eliminates all circular references
    # in the xml tree.
    function cleanup()
    {
        if (is_object( $this->roottag )) {
            $this->roottag->clear_subtags();
        }
    }

    function init()
    {
        $this->roottag = "";
        $this->curtag = &$this->roottag;
    }

    function create_root()
    {
        $null = 0;
        $this->roottag = new XMLTag($null);
        $this->curtag = &$this->roottag;
    }

    # read_xml_string
    # Same as read_file_handle, but you pass it a string.  Note that
    # depending on the size of the XML, this could be rather memory intensive.
    # Contributed July 06, 2001 by Kevin Howe
    function read_xml_string( $str )
    {
        $this->init();
        $this->parser = xml_parser_create("UTF-8");
        xml_set_object( $this->parser, $this );
        xml_set_element_handler( $this->parser, "_tag_open", "_tag_close" );
        xml_set_character_data_handler( $this->parser, "_cdata" );
        xml_parse( $this->parser, $str );
        xml_parser_free( $this->parser );
    }

    function read_file_handle( $fh )
    {
        $this->init();
        $this->parser = xml_parser_create("UTF-8");
        xml_set_object( $this->parser, $this );
        xml_set_element_handler( $this->parser, "_tag_open", "_tag_close" );
        xml_set_character_data_handler( $this->parser, "_cdata" );

        while( $data = fread( $fh, 4096 )) {
            if (!xml_parse( $this->parser, $data, feof( $fh ) )) {
                die(sprintf("XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($this->parser)),
                    xml_get_current_line_number($this->parser)));
            }
        }

        xml_parser_free( $this->parser );
    }

    function write_file_handle( $fh, $write_header=1 )
    {
        if ($write_header) {
            fwrite( $fh, "<?xml version='1.0' encoding='UTF-8'?>\n" );
        }

        # Start at the root and write out all of the tags
        $this->roottag->write_file_handle( $fh );
    }

    ###### UTIL #######

    function _tag_open( $parser, $tag, $attributes )
    {
        #print "tag_open: $parser, $tag, $attributes\n";
        # If the current tag is not set, then we are at the root
        if (!is_object($this->curtag)) {
            $null = 0;
            $this->curtag = new XMLTag($null);
            $this->curtag->set_name( $tag );
            $this->curtag->set_attributes( $attributes );
        }
        else { # otherwise, add it to the tag list and move curtag
            $this->curtag->add_subtag( $tag, $attributes );
            $this->curtag = &$this->curtag->curtag;
        }
    }

    function _tag_close( $parser, $tag )
    {
        # Move the current pointer up a level
        $this->curtag = &$this->curtag->parent;
    }

    function _cdata( $parser, $data )
    {
        $this->curtag->add_cdata( $data );
    }
}
###############################################################################
} // included
###############################################################################
?>
