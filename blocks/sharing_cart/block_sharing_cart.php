<?php // $Id: block_sharing_cart.php,v 1.7 2009/04/24 10:38:29 akiococom Exp $

require_once dirname(__FILE__).'/plugins.php';

class block_sharing_cart extends block_base {

    function init() {
        $this->title   = get_string('title', 'block_sharing_cart');
        $this->version = 2009040600;
        
        sharing_cart_plugins::load();
    }

    function applicable_formats() {
        return array('all' => true, 'tag' => false, 'mod-oublog' => false);
    }

    function has_config() {
        return (boolean)sharing_cart_plugins::enum();
    }

    function get_content() {
        global $CFG, $USER, $COURSE;

        //error_reporting(E_ALL);

        if ($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return $this->content = '';
        }

        if (empty($USER->id)) {
            return $this->content = '';
        }

        $course  = $COURSE->id == $this->instance->pageid
                 ? $COURSE
                 : get_record('course', 'id', $this->instance->pageid);

        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        $editing = isediting($this->instance->pageid) && has_capability('moodle/course:manageactivities', $context);

        if (!$editing) {
            return $this->content = '';
        }

        // DBから取得したアイテムを表示すべきものだけ抜き出し、木構造化
        // また、ここでドロップダウンリスト用にフォルダ名列挙もしておく
        $tree = array();
        $dirs = array();
        if ($shared_items = get_records('sharing_cart', 'user', $USER->id)) {
            foreach ($shared_items as $shared_item) {
                $node   =& self::path_to_node($tree, explode('/', $shared_item->tree));
                $node[] = array(
                    'id'   => $shared_item->id,
                    'path' => $shared_item->tree,
                    'icon' => empty($shared_item->icon)
                        ? $shared_item->name == 'label'
                            ? ''
                            : '<img src="'.$CFG->wwwroot.'/mod/'.$shared_item->name.'/icon.gif" alt="" class="icon" />'
                        : '<img src="'.$CFG->pixpath.'/'.$shared_item->icon.'" alt="" class="icon" />',
                    'text' => $shared_item->text,
                    'sort' => $shared_item->sort,
                    );
                $dirs[$shared_item->tree] = $shared_item->tree;
            }
            self::sort_tree($tree);
            unset($dirs['']);
            usort($dirs, 'strnatcasecmp');
        }

        // ツリーをHTMLにレンダリング
        $text = "<ul class=\"list\">\n".self::render_tree($tree).'</ul>';

        require_once dirname(__FILE__).'/shared/SharingCart_CourseScript.php';
        $courseScript = new SharingCart_CourseScript();

        $js_import = $courseScript->import($CFG->wwwroot.'/blocks/sharing_cart/sharing_cart.js', true);
        foreach (sharing_cart_plugins::get_imports() as $import) {
            $js_import .= $courseScript->import($CFG->wwwroot.'/blocks/sharing_cart/plugins/'.$import, true);
        }

        $js_pre = '
<script type="text/javascript">
//<![CDATA[
var sharing_cart = new sharing_cart_handler({
	wwwroot     : "'.$CFG->wwwroot.'",
	pixpath     : "'.$CFG->pixpath.'",
	instance_id : '.$this->instance->id.',
	course_id   : '.$course->id.',
	return_url  : "'.urlencode($_SERVER['REQUEST_URI']).'",
	directories : [
		'.implode(',
		', array_map(create_function('$dir', '
			return "\"".addslashes($dir)."\"";
		'), $dirs)).'
	],
	str : {
		rootdir        : "'.get_string('rootdir', 'block_sharing_cart').'",
		notarget       : "'.get_string('notarget', 'block_sharing_cart').'",
		copyhere       : "'.get_string('copyhere', 'block_sharing_cart').'",
		movehere       : "'.get_string('movehere').'",
		edit           : "'.get_string('edit').'",
		cancel         : "'.get_string('cancel').'",
		backup         : "'.get_string('backup', 'block_sharing_cart').'",
		clipboard      : "'.addslashes(get_string('clipboard', 'block_sharing_cart')).'",
		confirm_backup : "'.addslashes(get_string('confirm_backup', 'block_sharing_cart')).'",
		confirm_delete : "'.addslashes(get_string('confirm_delete', 'block_sharing_cart')).'"
	}
});
'. implode('
', sharing_cart_plugins::get_scripts()) .'
//]]>
</script>
';
        $js_post = $courseScript->addLoadEvent('sharing_cart.init();', true);

        $this->content         = new stdClass;
        $this->content->text   = $js_import
                               . $js_pre
                               . $text;
        $this->content->footer = '<div id="sharing_cart_header" style="text-align:right;">'
                               . implode('', sharing_cart_plugins::get_headers())
                               . helpbutton('sharing_cart', $this->title, 'block_sharing_cart', true, false, '', true)
                               . '</div>'
                               . implode('', sharing_cart_plugins::get_footers())
                               . $js_post;

        return $this->content;
    }
    
/** Internal **/
    
    /**
     *  path string ("foo/bar/baz") -> tree (["foo"]["bar"]["baz"])
     */
    private static function & path_to_node(&$tree, $path) {
        $i = array_shift($path);
        if (!isset($tree[$i]))
            $tree[$i] = array();
        if ($i == '')
            return $tree[$i];
        return self::path_to_node($tree[$i], $path);
    }
    /**
     * sort tree
     */
    private static function sort_tree(&$node) {
        foreach ($node as $k => &$v) {
            if (!is_numeric($k))
                self::sort_tree($v);
        }
        uksort($node, array(__CLASS__, 'sort_tree_cmp'));
    }
    private static function sort_tree_cmp($lhs, $rhs) {
        // directory first
        if ($lhs == '') return +1;
        if ($rhs == '') return -1;
        return strnatcasecmp($lhs, $rhs);
    }
    /**
     * sort item
     */
    private static function sort_item(&$node) {
        usort($node, array(__CLASS__, 'sort_item_cmp'));
    }
    private static function sort_item_cmp($lhs, $rhs) {
        // by sharing_cart->sort field
        if ($lhs['sort'] < $rhs['sort']) return -1;
        if ($lhs['sort'] > $rhs['sort']) return +1;
        // or by text
        return strnatcasecmp($lhs['text'], $rhs['text']);
    }
    /**
     * render tree as HTML
     */
    private static function render_tree($tree) {
        if (empty(self::$str_cache)) {
            self::$str_cache          = new stdClass;
            self::$str_cache->move    = get_string('move');
            self::$str_cache->delete  = get_string('delete');
            self::$str_cache->restore = get_string('restore', 'block_sharing_cart');
            self::$str_cache->movedir = get_string('movedir', 'block_sharing_cart');
        }
        $text = array();
        self::render_node($text, $tree);
        return implode('', $text);
    }
    private static function render_node(&$text, $node, $depth = 0, $id = 0, $dir = array()) {
        foreach ($node as $name => $leaf) {
            if ($name != '') {
                $path = array_merge($dir, array($name));
                self::render_diro($text, $depth, $name, $id, $path);
                $id = self::render_node($text, $leaf, $depth + 1, $id + 1, $path);
                self::render_dirc($text, $depth, $name, $id, $path);
            } else {
                self::sort_item($leaf);
                foreach ($leaf as $item) {
                    self::render_item($text, $depth, $item);
                }
            }
        }
        return $id;
    }
    private static function render_item(&$text, $depth, $item) {
        global $CFG;
        
        $text[] = str_repeat("\t", $depth + 1)
                . '<li class="r0" id="shared_item_'.$item['id'].'">';
        
        $text[] = '<div class="icon column c0">';
        if ($depth) {
            $text[] = print_spacer(10, $depth * 10, false, true);
        }
        if (!empty($item['icon'])) {
            $text[] = $item['icon'];
        }
        $text[] = '</div>';
        
        $text[] = '<div class="column c1" title="'.$item['text'].'">'.mb_substr($item['text'],0,20,"UTF-8").'</div>';
        
        $text[] = '<span class="commands">';
        {
            // ディレクトリ移動[→]
            $text[] = '<a title="'.self::$str_cache->movedir.'" href="javascript:void(0);"'
                    . ' onclick="return sharing_cart.movedir(this, '
                    . '\''.addslashes(htmlspecialchars($item['path'])).'\');">'
                    . '<img src="'.$CFG->pixpath.'/t/right.gif" class="iconsmall"'
                    . ' alt="'.self::$str_cache->movedir.'" />'
                    . '</a>';
            
            // 並べ替え[↓↑]
            $text[] = '<a title="'.self::$str_cache->move.'" href="javascript:void(0);"'
                    . ' onclick="return sharing_cart.move(this, '
                    . '\''.addslashes(htmlspecialchars($item['path'])).'\');">'
                    . '<img src="'.$CFG->pixpath.'/t/move.gif" class="iconsmall"'
                    . ' alt="'.self::$str_cache->move.'" />'
                    . '</a>';
            
            // 削除[×]
            $text[] = '<a title="'.self::$str_cache->delete.'" href="javascript:void(0);"'
                    . ' onclick="return sharing_cart.remove(this);">'
                    . '<img src="'.$CFG->pixpath.'/t/delete.gif" class="iconsmall"'
                    . ' alt="'.self::$str_cache->delete.'" />'
                    . '</a>';
            
            // コースへコピー[■→]
            $text[] = '<a title="'.self::$str_cache->restore.'" href="javascript:void(0);"'
                    . ' onclick="return sharing_cart.restore(this);">'
                    . '<img src="'.$CFG->pixpath.'/i/restore.gif" class="iconsmall"'
                    . ' alt="'.self::$str_cache->restore.'" />'
                    . '</a>';
            
            // plugins
            $text = array_merge($text, sharing_cart_plugins::get_commands());
        }
        $text[] = '</span>';
        
        $text[] = "</li>\n";
    }
    private static function render_diro(&$text, $depth, $name, $id, $path) {
        global $CFG;
        $text[] = str_repeat("\t", $depth + 1)
                . '<li class="r0">';
        $text[] = '<div title="'.htmlspecialchars(implode('/', $path)).'" style="cursor:pointer;"'
                . ' onclick="return sharing_cart.toggle(this, '.$id.');">';
        {
            $text[] = '<div class="column c0">';
            if ($depth) {
                $text[] = print_spacer(10, $depth * 10, false, true);
            }
            $text[] = '<img id="sharing_cart_'.$id.'_icon" src="'.$CFG->pixpath.'/i/open.gif" alt="" />'
                    . '</div>';
            $text[] = '<div class="column c1">'.htmlspecialchars($name).'</div>';
        }
        $text[] = '</div>';
        $text[] = '<ul id="sharing_cart_'.$id.'_item" class="list">'."\n";
    }
    private static function render_dirc(&$text, $depth, $name, $id, $path) {
        $text[] = str_repeat("\t", $depth + 1)."</ul></li>\n";
    }
    private static $str_cache;
}

?>