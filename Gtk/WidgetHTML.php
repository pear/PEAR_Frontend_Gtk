<?



class PEAR_Frontend_Gtk_WidgetHTML {


    var $_source; // raw HTML

    function test() {
        $this->_source = implode('',file('http://pear.php.net/manual/en/packages.templates.it.php'));
        $fh = fopen('/tmp/test','w'); fwrite($fh,$this->_source ); fclose($fh);
    }
    
    var $_tokens = array(); // HTML Tokens
    
    function  tokenize() {
        $tok = strtok($this->_source,'<');
        $a[0] = $tok;
        while( $tok !== FALSE ) {
            $tok = strtok('<');
            $a[] = $tok;
        }
        
        $this->_tokens = array();
        foreach($a as $i=>$b) {
            if (trim($b) === '') continue;
            $l = strlen($b)-1;
            if ($b{$l} == '>') {
                if (($s = strcspn($b," \n")) == strlen($b)) {
                    $this->_tokens[] = array(strtoupper(substr($b,0,-1)));
                    continue;
                }
                $tag = strtoupper(substr($b,0,strcspn($b," \n")));
                $attribs = substr($b,strcspn($b," \n"),-1);
                $this->_tokens[] = array($tag,trim($attribs));
                continue;
            }
            if (strcspn($b," \n") == $l+1) {
                $this->_tokens[] = array(strtoupper(substr($b,0,strpos($b,'>'))));
                continue;
            }
            if (strcspn($b," \n") > strpos($b,'>')) {
                $tag = substr($b,0,strpos($b,'>'));
                $this->_tokens[] = array(strtoupper($tag));
                $this->_tokens[] = substr($b,strpos($b,'>')+1);
                continue;
            }
            $tag = strtoupper(substr($b,0,strcspn($b," \n")));
            $attribs = substr($b,strcspn($b," \n")+1,strpos($b,'>')-strlen($tag)-1);
            $this->_tokens[] = array($tag,$attribs);
            $this->_tokens[] = substr($b,strpos($b,'>')+1);
        }
    }
  
    function build() {
        $this->widget->freeze();
        $this->Start=FALSE;
        foreach($this->_tokens as $pos=>$item) {
            if (is_Array($item)) {
                $method = "push";
                if (!$item[0]) continue;
                //echo "\nIN:".serialize($item)."\n";
                if ($item[0]{0} == '/') {
                    $method = "pop";
                    $item[0] = substr($item[0],1);
                    $item[1] = '/';
                }
                
                switch (trim($item[0])) {
                    case '!DOCTYPE':
                    case 'HTML':

                    case 'META':                    
                    case 'LINK':
                    case 'HEAD':
                    case 'SCRIPT':
                    case 'STYLE':
                    case 'TITLE':
                    
                    case 'FORM':
                    
                    case 'INPUT':
                    case 'OPTION':
                    case 'TBODY':
                    
                    case 'LI':
                    case 'UL':
                    
                    case 'IMG':
                    
                    case '!--':
                        break;
                        
                        
                        
                    case 'TABLE':               // unhandled stauff
                        if ($method == 'push') { // start
                            // move us down abit and start a new row
                            $this->_x = $this->_left;
                            $this->_y +=16; 
                            $this->calctable($pos,$this->_right- $this->_x);
                            //$this->output("TABLE:$pos");
                        } else {  //end
                            $this->_x = $this->_left;
                            $this->_y +=16; 
                        }
                        break;
                        
                    
                    //case 'TR':
                    case 'TD':
                        if (!$this->td[$pos]) break;
                        if ($method == 'push') { // start
                            echo "TDPstart: $pos L:{$this->_left},{$this->_right}\n";
                            $this->push('right',$this->td[$pos]['right']);
                            $this->push('left',$this->td[$pos]['left']);
                            $this->_left = $this->td[$pos]['left'];
                            $this->_right = $this->td[$pos]['right'];
                            $row =  $this->td[$pos]['row'];
                            if (!$this->tr[$row])
                                $this->tr[$row] = $this->_y+16;
                            $this->_x = $this->_left;
                                
                            $this->_y =$this->tr[$row] ;
                            //$this->output("TD:$pos");
                            //echo "TDstart: $pos L:{$this->_left},{$this->_right}\n";
                        } else {
                            // pull back!
                            $this->pop('right');
                            $this->pop('left');
                            $this->_left = 10;
                            if ($s = $this->in('left'))
                                $this->_left = $s;
                            $this->_right = 600;
                            if ($r = $this->in('right'))
                                $this->_right = $r;
                            echo "endtd: $pos L:{$this->_left},{$this->_right}\n";
                        }
                    
                        break;
                  
                        
                    case 'BODY':
                        $this->Start = TRUE;
                        break; 
                    case 'PRE':
                        $this->linebr($item[0]);
                        $this->$method($item[0]);
                        
                    case 'SELECT': // hide this stuff
                    case 'TEXTAREA':
                    case 'T':
                    
                    case 'TT':
                    case 'CODE':
                    case 'B':
                    case 'I':
                        $this->$method($item[0]);
                        break;
                        
                    case 'SMALL':
                        $item[0] = "H6";
                    case 'H1':
                    case 'H2':
                    case 'H3':
                    case 'H4':
                    case 'H5':
                    case 'H6':
                        $this->linebr();
                        $this->$method('H',$item[0]{1});
                        break;
                     case 'DIV': // ?? this stuf could contain formating?
                     case 'FONT':
                    case 'A':
                        $this->$method($item[0],@$item[1]);
                        break;
                    //case 'TR':
                    case 'BR':
                    case 'P':
                        $this->linebr($item[0]);
                        break;
                    default:
                        echo "\nNOT HANDLED: -{$item[0]}-\n";
                        break;
                }
                
                continue;
            }
            // strings only!
            $this->output($item);
        }
        $this->linebr();  
        $this->widget->set_size(600, $this->_y+16);
        $this->widget->thaw();
    }
    
    var $check = "xxxx";
    
    function push($what,$attributes='') {
        //echo "PUSH: $what, $attributes\n";
        if ($attributes && $attributes{strlen($attributes)-1} == '/') {
            //echo "SKIP";
            return;
        }
        if (!@$this->stack[$what])
            $this->stack[$what] = array();
        if (!$attributes) $attributes = ":";
        $this->stack[$what][] = $attributes;
        $this->cur[$what] = $attributes;
        $this->_stackAttributes($attributes,'push');
            
        if ($what != $this->check) return;
        echo "\nPUSH:$what:".serialize($this->stack[$what]);
        echo "\nCUR:{$this->cur[$what]}";
    }
    
    
    
    function pop($what,$attributes=array()) {
        if (!@$this->stack[$what]) return;
        $remove = array_pop($this->stack[$what]);
        $this->cur[$what] = "";
        if ($this->stack[$what])
            $this->cur[$what] = $this->stack[$what][count($this->stack[$what])-1];
        $this->_stackAttributes($remove,'pop');
        if ($what != $this->check) return;            
        echo "\nPOP:$what:".serialize($this->stack[$what]);
        echo "\nCUR:{$this->cur[$what]}";
    }
    
    function _stackAttributes($attributes,$method) {
        if ($attributes == ":") return;
        if ($attributes == '/') return;
        if ($attributes{0} == "#") return;
        
        $args = array();
     
        if (preg_match("/ color\=[\"\']?(\#[0-9A-F]+)[\"\']?/mi",' '.$attributes,$args)) 
            $this->$method("FGCOLOR",$args[1]);
          
        $args = array();    
        if (preg_match("/ bgcolor\=[\"\']?(\#[0-9A-F]+)[\"\']?/i",' '.$attributes,$args))
            $this->$method("BGCOLOR",$args[1]);

        $args = array();
        if (preg_match("/ text\=[\"\']?(\#[0-9A-F]+)[\"\']?/i",' '.$attributes,$args))
            $this->$method("FGCOLOR",$args[1]);
            
    } 
    
    
    function in($what) {
        return @$this->cur[$what];
    }

    
    
    function linebr($item='') {
        if (($item == "P") && ($this->lastbr == "P")) return;
        //if ($item && $this->lastbr && ($this->lastbr != $item)) return;
        //$this->widget->insert($this->_font,$this->_fgcolor,$this->bg_color,":$item:\n");
        
        $this->_y += 14;
        $this->_x = $this->_left;
    }
    
    
    var $buffer="";
    
    var $_fonts = array(); // associative array of fonts
    var $_colors = array(); // associative array of colors
    
    var $_font = NULL;
    var $_fgcolor = NULL;
    var $_bgcolor = NULL;
    
    var $_left = 10;
    var $_right  = 600;
    var $_x = 10;
    var $_y = 0;
    
    function output($string) {
        if (!$this->Start) return;
        $string = $this->unhtmlentities($string);
        if (!$this->in('PRE')) 
            $string = trim(preg_replace("/[\n \t\r]+/m", ' ',$string)) . ' ';
        if (!trim($string)) return; // except PRE stuff!q
        
        // invisible stuff
        if ($this->in('SELECT')) return;
        if ($this->in('TEXTAREA')) return;
        
        $this->_makeFont();
        $this->_makeColors();
        $this->_setStyle();
        if ($this->in('PRE')) {
            $this->outputPRE($string);
            return;
        }
        $this->outputTEXT($string);
    
    }
        
        
    function outputTEXT($string) {
    
        $array = $this->_breakString($this->_x,$this->_left,$this->_right,$string);
        // array of lines (startpos,len,text)
        
        foreach($array as $line) {
            if ($line[2]) {
                $widget = &new GtkLabel($line[2]);
                $widget->set_style($this->_style);
                //echo "ADD: {$line[0]},{$this->_y} : {$line[2]}\n";
                $this->widget->put($widget,$line[0],$this->_y);
                $widget->show();
            }
            $this->_y += 14;
            
        } 
        $this->_y -= 14;
        $this->_x = $line[0] + $line[1];
        $this->lastbr = ''; 
    }
    
    
    function outputPRE($string) {
    
         
        if (!strpos($string,"\n")) {
            $this->outputTEXT($string);
            return;
        }
        
        $array = explode("\n", $string) ;
        // array of lines (startpos,len,text)
        
        foreach($array as $line) {
            $this->outputTEXT($line);
            $this->_y += 14;
            $this->_x = $this->_left;
        }
        
        $this->_y -= 14;

        $this->lastbr = ''; 
    }
    
    
    
    
    
    function _breakString($start,$left,$right,$string) {
         
        $l = $this->_font->extents($string);
        //echo serialize($l);
        //echo "\nSTRINGLEN: $string =  {$l[2]} \n";
        if ($l[2] < ($right - $start)) {
            return array(array($start,$l[2],$string));
        }
        $ret = array();
        $buf = "";
        $words = explode(" ",$string);
        foreach ($words as $w) {
         
            $l = $this->_font->extents($buf . " " . $w);
            if ($l[2]< ($right - $start)) {
                $buf .= " " . $w;
                continue;
            }
            // its longer! and buffer is empty.. (and it's the first line!
            if ($buf == "" && ($start != $left)) {
                $ret[] = array($start,0,'');
                $start = $left;
                if ($l[2] < ($right - $start)) {
                    $buf =  $w;
                    continue;
                }
                // it's longer and - just add it as a new line
                // even though it's too big!
                $ret[] = array($start,$l[2],$w);
                continue;
            }
            // its longer, add the buffer to stack, clear buffer
            $l = $this->_font->extents($buf);
            $ret[] = array($start, $l[2] ,$buf);
            $buf = $w;
            $start = $left;
        }
        if ($buf) {
            $l = $this->_font->extents($buf);
            $ret[] = array($start, $l[2] ,$buf);
        }
        return $ret;
    
    
    
    }
    
    
    
    
    
    
    
    function _makeFont() {
        $font['pointsize'] = 100;
        $font['space'] = 'p';  // or 'm' for monospaced
        $font['family'] = 'times'; // or helvetica, courier
        $font['weight'] = 'medium'; // or bold
        $font['slant'] = 'r'; // or o
        
        /* not used? */
        $font['setwidth'] = 'normal'; 
        
        //PRE:
        if ($this->in('PRE') || $this->in('TT') || $this->in('CODE') ) {
            $font['family']  = 'courier';
            $font['space'] = 'm';
            $font['pointsize'] = 80;
        }
        if ($this->in('B')) 
            $font['weight']  = 'bold';
        if ($this->in('I')) 
            $font['slant']  = 'i';
            
        if ($v = $this->in('H')) {
            //&& is_int($v)
            // mapping 1=large = eg. 160 , 3=100 ok 5 = small
            // 20 * $v  would give 1=20, 2=40 .. 5=100
            // now 160-$v;; would give 1 = 140, 3=100 
            $font['weight']  = 'bold';
            $font['pointsize'] = 145 - ($v * 15);
            //echo "setting point size = {$font['pointsize']}";
        }
            
            
            
            
        $fontname = $this->_getFontString($font);
        if (@!$this->_fonts[$fontname])  
            $this->_fonts[$fontname] = gdk::font_load($fontname);
        if (!$this->_fonts[$fontname]) 
            echo "FAIL: $fontname\n";
       
        $this->_font = &$this->_fonts[$fontname];
    }
    
    
    function _getFontString($array) {
        $ret = "";
        foreach(array(
            'foundary','family','weight','slant','setwidth',
            'addedstyle', 'pixelsize','pointsize','resx','resy',
            'space','averagewidth','registry','encoding') as $i) {
            $a = '*';
            if ($array[$i]) $a = $array[$i];
            $ret .= "-{$a}";
        }
        return $ret;
    }        
    
    function _makeColors() {
        $fgcolor = "#000000";
        $bgcolor = "#FFFFFF";
         
        if ($c = $this->in('FGCOLOR')) 
            $fgcolor = $c;
        if ($c = $this->in('BGCOLOR')) 
            $bgcolor = $c;
         
        //echo "$fgcolor:$bgcolor\n";
        
        if (!$this->_colors[$fgcolor])
            $this->_colors[$fgcolor] = &new GdkColor($fgcolor);
        if (!$this->_colors[$bgcolor])
            $this->_colors[$bgcolor] = &new GdkColor($bgcolor);
        
        $this->_fgcolor = &$this->_colors[$fgcolor];
        $this->_bgcolor = &$this->_colors[$bgcolor];
        
    }
    
    
 
    function unhtmlentities ($string)  {
        $trans_tbl = get_html_translation_table (HTML_ENTITIES);
        $trans_tbl = array_flip ($trans_tbl);
        $ret = strtr ($string, $trans_tbl);
        return  preg_replace('/\&\#([0-9]+)\;/me',"chr('\\1')",$ret);
    }
    
    function testInterface() {
        $w = &new GtkWindow;
        $s  = &new GtkScrolledWindow();
        $hadj = $s->get_hadjustment();
        $vadj = $s->get_vadjustment();
        $this->widget =  &new GtkLayout($hadj,$vadj); 
        $this->widget->set_size(600, 12000);
        $s->add($this->widget);
        $w->add($s);
        $w->show_all();
    
    }
    var $_style = NULL;
    function _setStyle() {
        //echo "SET: $widgetname: $fgcolor/$bgcolor ". ((int) $copy) . "\n";
        
        
        $style = &new GtkStyle();
        
        if ($this->_fgcolor) { // set foreground color
            $fg = &$this->_fgcolor;
            
            $style->fg[GTK_STATE_PRELIGHT] = $fg;
            $style->fg[GTK_STATE_NORMAL] = $fg;
            $style->fg[GTK_STATE_ACTIVE] = $fg;
            $style->fg[GTK_STATE_SELECTED] = $fg;
            $style->fg[GTK_STATE_INSENSITIVE] = $fg;
        }
        if ($this->_bgcolor) { // set background color
            $bg = $this->_bgcolor;
            $style->bg[GTK_STATE_PRELIGHT] = $bg;
            $style->bg[GTK_STATE_NORMAL] = $bg;
            $style->bg[GTK_STATE_ACTIVE] = $bg;
            $style->bg[GTK_STATE_SELECTED] = $bg;
            $style->bg[GTK_STATE_INSENSITIVE] = $bg;
        }
        if ($this->_font)
            $style->font = $this->_font;
        $this->_style = &$style;
        
    }
    
    
    
    
    /*
    
    
    tables : complex stuff:
    got a table : look ahead to find
    tr = number of rows
    td = no of colums and how big they are...
    
    */
    
    function _findNextTag($pos,$tag) {
        $c = count($this->_tokens);
        while ($pos < $c) {
            if (!is_array($this->_tokens[$pos])) {
                $pos++;
                continue;
            }
            if ($this->_tokens[$pos][0] == $tag) return $pos;
            $pos++;
        }
        return $pos;
    }
    
    function _findSubTables($pos) {
        $table[] = array();
        $c = count($this->_tokens);
        while ($pos < $c) {
            if (!is_array($this->_tokens[$pos])) {
                $pos++;
                continue;
            }
            if ($this->_tokens[$pos][0] == "TABLE") 
                $table[] = $pos;
            if ($this->_tokens[$pos][0] == "/TABLE") {
                if (!$table) return $pos;
                array_pop($table);
            }
            $pos++;
        }
        return $pos;
    }
    
    /* first version just divides available area! */
    var $td; // associative array of [posid]['width'|'start']
    
    function calctable($pos,$max) {
        
        echo "START CALCTABLE $pos,$max\n";
        // read the width if there is one?
        
        $attributes = $this->tokens[$pos][1];
        if (preg_match("/ width\=[\"\']?(\[0-9]+)([%]*)[\"\']?/mi",' '.$attributes,$args)) {
            $max = $args[1];
            if ($args[2]) $max = (int) (0.01 * $args[1]  * $max);
        }
        
        $pos++;
        $rows = 0;
        $rowmax = 600;
        $cols =0;
        $totalcols = 0;
        $done =0;
        $c = count($this->_tokens);
        while ($pos < $c) {
            if (!is_array($this->_tokens[$pos])) {
                $pos++;
                continue;
            }
            switch ($this->_tokens[$pos][0]) {
                case "TR":
                    $rows++;
                    $rowid = $pos;
                    break;
                case "TD";
                
                    $args = array();
                    $span =1;
                    if (preg_match("/ colspan\=[\"\']?([0-9]+)[\"\']?/mi",' '.$this->_tokens[$pos][1],$args))
                        $span = $args[1];
                    echo "SPAN $span\n";
                    $td[$pos]['col'] = $cols;
                    $td[$pos]['row'] = $rowid;
                    $td[$pos]['span'] = $span;
                    $cols+=$span;
                    break;
                case "/TR":
                    if ($cols > $totalcols) $totalcols = $cols;
                    //if ($rowsize > $rowmax) $rowmax= $rowsize;
                    $cols = 0;
                    //$rowsize = 0;
                    break;
                case "TABLE":
                    $pos = $this->_findSubTables($pos); // skip sub tables
                    break;
                case "/TABLE":
                    $done = 1;
                    break;
            }
            //echo "$pos\n";
            if ($done) break;
            $pos++;
        }
        $endoftable = $pos;
        echo "ENDTABLE $pos : W $totalcols\n";
        
        if (!$totalcols) return;
        $colwidth = $max / $totalcols;
        $x=$this->_left;
        $row =0;
        foreach ($td as $pos=>$array) {
            if ($array['row'] != $row) $x=$this->_left;
            $row = $array['row'];
            
            $this->td[$pos]['right'] = $x+($colwidth * $array['span']);
            $this->td[$pos]['left'] = $x;
            $this->td[$pos]['row'] = $array['row'];
            $this->td[$pos]['span'] = $array['span'];
            
            echo "SET TD:$pos: L$x, R".($x+$colwidth). "\n";  
            
            $x += ($colwidth * $array['span']);
        }
        echo "END SETTABLE\n";
        return $endoftable;
    }
}
    


dl('php_gtk.so');

$t = new PEAR_Frontend_Gtk_WidgetHTML;
$t->test();
$t->tokenize();
$t->testInterface();
$t->build();

gtk::main();

 
 
 
 

?>
    