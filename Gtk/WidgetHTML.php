<?



class PEAR_Front_Gtk_WidgetHTML {

   var $pass=1;
    var $_source; // raw HTML

    function test($file) {
        $this->_source = implode('',file($file));
        //$fh = fopen('/tmp/test','w'); fwrite($fh,$this->_source ); fclose($fh);
    }
    
    var $_tokens = array(); // HTML Tokens
    
    function tokenize() {
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
                $this->_tokens[] = substr($b,strpos($b,'>')+1) ;
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
        //print_r($this->_tokens);
        //exit;
    }
    var $Start = FALSE; // start rering (eg. ignore headers)
    
    function build($startpos,$endpos) {
        $this->layout->freeze();
        $this->Start=FALSE;
        $this->_DrawingAreaClear();
        
        $this->_line_y = array();
        $this->_y =10;
        $this->_states = array();
        $this->states = array();
        $this->cur = array();
        $this->curid = array();
        // make a fake first line
        $this->_line =0;
        $this->_makeFont();
        $this->_lines[$this->_line]['top'] =0;
        $this->_updateLine();
        $this->_lines[$this->_line]['bottom'] =0;
        $this->_nextLine();
        
        $this->pushState(0);                 
        for($pos = $startpos;$pos < $endpos;$pos++) {
            $item = $this->_tokens[$pos]; 
            if (is_Array($item)) {
                $method = "push";
                if (!$item[0]) continue;
                //echo $pos;
                //echo "\nIN:".serialize($item)."\n";
                $this->outputTAG($item[0]);
                if ($item[0]{0} == '/') {
                    $method = "pop";
                    $item[0] = substr($item[0],1);
                    $item[1] = '/';
                }
                //if (!$draw) 
                  
                     
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
                            //$this->pushState($pos);
                            // move us down abit and start a new row
                            $this->_x = $this->_left;
                            $this->tables[$pos]['top']  = $this->_lines[$this->_line]['bottom']; 
                            $this->_nextLine();
                            $this->_lines[$this->_line]['top'] = $this->tables[$pos]['top'];
                            $this->_updateLine();
                            
                            if ($this->pass == 1) {
                                $this->calctable($pos,$this->_x ,$this->_right);
                            } 
                            $this->push($item[0],$pos,@$item[1]);
                            //$this->output("TABLE:$pos");
                            if ($this->pass == 2) 
                                $this->_drawBlock($this->tables[$pos]);
                        } else {  //
                        
                            $table = $this->pop('TABLE');
                            
                            if ($this->pass == 1) 
                                $this->_TABLErecalc($table);
                                
                            
                            //$this->popState($table);
                            $this->_x = $this->_left;
                           
                            $this->_nextLine();
                            $this->_lines[$this->_line]['top'] = $this->tables[$table]['bottom']; 
                            $this->_updateLine();
                           
                            
                            if ($this->pass == 2) { 
                                //print_r($this->tables);
                                //exit;
                            }
                                
                            //$this->clearStack('TD',$table);
                            //$this->clearStack('BGCOLOR',$table);
                            //$this->clearStack('FGCOLOR',$table);
                            //$this->clearStack('TR',$table);
                        }
                        break;
                        
                    
                    //case 'TR':
                    case 'TD':
                        
                        if ($method == 'push') { // start
                            if (!@$this->td[$pos]) break;
                            //echo "TDPstart: $pos L:{$this->_left},{$this->_right}\n";
                            $this->_nextLine();
                            
                            if (!@$this->td[$pos]['top']) 
                                $this->td[$pos]['top'] = $this->_lines[$this->_line]['top'];
                            
                            $this->_left = $this->td[$pos]['left'];
                            $this->_right = $this->td[$pos]['right'];
                            
                            $this->_lines[$this->_line]['top'] = $this->td[$pos]['top'];
                            $this->_updateLine();
                            
                            $this->_x = $this->_left;
                            $this->push($item[0],$pos,@$item[1]);
                            
                            if ($this->pass == 2) 
                                $this->_drawBlock($this->td[$pos]);
                        } else {
                            // pull back!
                            $td = $this->pop('TD');
                            //f ($this->pass == 2) 
                            //    $this->_y =$this->td[$td]['bottom']; 
                        }
                        
                        break;
                  
                        
                    case 'BODY':
                        $this->Start = TRUE;
                        break; 
                    case 'PRE':
                        //$this->output($item[0]);
                        $this->linebr($item[0]);
                        $this->$method($item[0],$pos);
                        
                    case 'SELECT': // hide this stuff
                    case 'TEXTAREA':
                    case 'T':
                    
                    case 'TT':
                    case 'CODE':
                    case 'B':
                    case 'I':
                        $this->$method($item[0],$pos);
                        break;
                        
                    case 'SMALL':
                        $item[0] = "H6";
                    case 'H1':
                    case 'H2':
                    case 'H3':
                    case 'H4':
                    case 'H5':
                    case 'H6':
                        //$this->output($item[0]);
                        $this->linebr();
                        $this->$method('H',$pos,$item[0]{1});
                        break;
                    case 'DIV': // ?? this stuf could contain formating?
                    case 'FONT':
                    case 'TR':
                    case 'A':
                        $this->$method($item[0],$pos,@$item[1]);
                        break;
                    
                    case 'BR':
                    case 'P': 
                        //$this->output($item[0]);
                        $this->linebr($item[0]);
                        break;
                    default:
                        echo "\nNOT HANDLED: -{$item[0]}-\n";
                        break;
                }
                
                continue;
            }
            //echo $pos;
            // strings only!
            $this->output($item);
        }
        $this->linebr();  
        //$this->_area_y = $this->_y+16;
        //$this->layout->set_size($this->_area_x , $this->_area_y );
        $this->layout->thaw();
        //return;
        //print_r($this->td);
        if ($this->pass == 1) {
            $this->pass++;
            $this->build($startpos,$endpos);
        }
        print_r($this->tables);

    }
    
    var $check = "";
    
    var $_states = array(); // array of pos'id => various data!!!
    function pushState($id) {
        foreach(array('_left','_right') as $k) 
            $this->_states[$id][$k] = $this->$k;
        
    }
        
    function popState($id) {
        foreach(array('_left','_right') as $k) 
            $this->$k = $this->_states[$id][$k];
        
    }
    var $stack;// array of item stack
    var $cur; // top of stack
    var $curid; // id of top of stack
    
    function push($what,$pos,$attributes='') {
        //echo "PUSH: $what, $attributes\n";
        if ($attributes && $attributes{strlen($attributes)-1} == '/') {
            //echo "SKIP";
            return;
        }
        if (!@$this->stack[$what])
            $this->stack[$what] = array();
        if (!$attributes) $attributes = ":";
        $this->stack[$what][] = array($pos,$attributes);
        $this->cur[$what] = $attributes;
        $this->curid[$what] = $pos;
        $this->_stackAttributes($attributes,$pos,'push');
            
        if ($this->check  && preg_match("/^(".$this->check.")$/",$what)) { 
            echo "\nPUSH:$what:".serialize($this->stack[$what]);
            echo "\nCUR:{$this->cur[$what]}";
        }
    }
    
    
    
    function pop($what,$pos=0,$attributes=array()) {
        if (!@$this->stack[$what]) return;
        list($id,$remove) = array_pop($this->stack[$what]);
        $this->cur[$what] = "";
        if ($this->stack[$what])
            list($this->curid[$what],$this->cur[$what]) = $this->stack[$what][count($this->stack[$what])-1];
        $this->_stackAttributes($remove,$pos,'pop');
        /* debugging*/
        if ($this->check  && preg_match("/^(".$this->check.")$/",$what)) { 
            echo "\nPOP:$what:".serialize($this->stack[$what]);
            echo "\nCUR:{$this->cur[$what]}";
        }
        return $id;
    }
    
    function clearStack($what,$pos) {
        //echo "CLEARING STACK OF $what for $pos\n ";
        if (!@$this->stack[$what]) return;
        $c = count(@$this->stack[$what]) -1;
        for($i=$c;$i>-1;$i--) {
            list($id,$attr) = $this->stack[$what][$i];
            if ($id >= $pos) $this->pop($what);
        }
    }
    
    
    
    function _stackAttributes($attributes,$pos,$method) {
        if ($attributes == ":") return;
        if ($attributes == '/') return;
        if ($attributes{0} == "#") return;
        
        $args = array();
     
        if (preg_match("/\scolor\=[\"\']?(\#[0-9A-F]+)[\"\']?/mi",' '.$attributes,$args)) 
            $this->$method("FGCOLOR",$pos,$args[1]);
          
        $args = array();    
        if (preg_match("/\sbgcolor\=[\"\']?(\#[0-9A-F]+)[\"\']?/mi",' '.$attributes,$args))
            $this->$method("BGCOLOR",$pos,$args[1]);

        $args = array();
        if (preg_match("/\stext\=[\"\']?(\#[0-9A-F]+)[\"\']?/mi",' '.$attributes,$args))
            $this->$method("FGCOLOR",$pos,$args[1]);
        
        if (preg_match("/\sclass\=[\"\']?([A-Z]+)[\"\']?/mi",' '.$attributes,$args))
            $this->_stackStyle($method,$pos,$args[1]);
        
    } 
    function _stackStyle($method,$pos,$class) {
        return;
        /* TODO?? */
        switch ($class) {
            case 'programlisting':
                $this->$method("BGCOLOR",'');
        }
        
    }
        
        
    
    
    function in($what) {
        return @$this->cur[$what];
    }
    function inID($what) {
        return @$this->curid[$what];
    }
    
    
    function linebr($item='') {
        if (($item == "P") && ($this->lastbr == "P")) return;
        //if ($item && $this->lastbr && ($this->lastbr != $item)) return;
        //$this->widget->insert($this->_font,$this->_fgcolor,$this->bg_color,":$item:\n");
        $this->_nextLine();
        $this->_updateLine();
        $this->_x = $this->_left;
    }
    
    
    var $buffer="";
    
 
    
    var $_left = 10;
    var $_right  = 600;
    var $_x = 10;
    var $_y = 20;
    
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
        //$this->_setStyle();
        if ($this->in('PRE')) {
            $this->outputPRE($string);
            return;
        }
        $this->outputTEXT($string);
    
    }
    
    function outputTAG($tag) {
            
        $this->_makeFont('-adobe-helvetica-bold-r-normal-*-*-80-*-*-p-*-iso8859-1');
        $this->_makeColors("#000000","#FFFF00",FALSE);
        //$x= $this->_x;
        //$y= $this->_y;
        $this->outputTEXT("<{$tag}>",TRUE);
        //$this->_x = $x;
        //$this->_y = $y;
    }
    
    var $line =0;
    
    
        
    function outputTEXT($string) {
    
        $array = $this->_breakString($this->_x,$this->_left,$this->_right,$string);
        // array of lines (startpos,len,text)
        $h = $this->_font->ascent + $this->_font->descent;
        $c = count($array) -1;
        foreach($array as $i=>$line) {

            if ($line[2]) {
                //$widget = &new GtkLabel($line[2]);
                //$widget->set_style($this->_style);
                //if ($this->pass == 2) 
                     //echo "ADD: {$line[0]},{$this->_y} : {$line[2]}\n";
                //$this->layout->put($widget,$line[0],$this->_y);
                $this->_updateLine($line[2]);
                gdk::draw_rectangle($this->pixmap,
                    $this->_bggc,true,    
                    $line[0], $this->_lines[$this->_line]['bottom'], 
                    $line[1], $this->_lines[$this->_line]['height']
                );
                
                gdk::draw_text($this->pixmap, 
                    $this->_font, 
                    $this->_gc  , 
                    $line[0], 
                    $this->_lines[$this->_line]['y'], 
                    $line[2], strlen($line[2])
                );
               
                $this->drawing_area->draw(
                    new GdkRectangle(
                        $line[0], $this->_lines[$this->_line]['bottom'], 
                        $line[1], $this->_lines[$this->_line]['height']));
                //$widget->show();
                $this->_updateLine();
                if ($c != $i)
                    $this->_nextLine();
                $this->_updateLine();
                    
            }
            
            
            
        } 
        
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
        $c = count($array) -1;
        foreach($array as $i=>$line) {
            $this->outputTEXT($line);
            $this->_updateLine();
            if ($i!= $c) {
                $this->_nextLine();
                $this->_x = $this->_left;
            }
            
        }

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
    
    
    function unhtmlentities ($string)  {
        $trans_tbl = get_html_translation_table (HTML_ENTITIES);
        $trans_tbl = array_flip ($trans_tbl);
        $ret = strtr ($string, $trans_tbl);
        return  preg_replace('/\&\#([0-9]+)\;/me',"chr('\\1')",$ret);
    }
    
    
    var $_line;
    function _updateLine($string = '') {
        // store the line height of the current line..
        
        //if (!@$this->_lines[$this->_line]['ascent']) $this->_lines[$this->_line]['ascent']=1;
        //if (!@$this->_lines[$this->_line]['decent']) $this->_lines[$this->_line]['decent']=5;
        
        if (@$this->_lines[$this->_line]['ascent'] < $this->_font->ascent ) 
            $this->_lines[$this->_line]['ascent'] = $this->_font->ascent;
        if (@$this->_lines[$this->_line]['descent'] < $this->_font->descent ) 
            $this->_lines[$this->_line]['descent'] = $this->_font->descent;
        if (!isset($this->_lines[$this->_line]['descent'])) {
            echo serialize($this->_font->descent);
            exit;
        }
        
        $this->_lines[$this->_line]['height'] = 
            $this->_lines[$this->_line]['descent'] + $this->_lines[$this->_line]['ascent'];
        
        $this->_lines[$this->_line]['y'] = $this->_lines[$this->_line]['top'] + $this->_lines[$this->_line]['ascent'];
        $this->_lines[$this->_line]['bottom'] = $this->_lines[$this->_line]['top'] + $this->_lines[$this->_line]['height'];
        
        // store the active block heights....
        
        if ($this->pass == 1) {
            if ($id = $this->inID('TD')) 
                $this->td[$id]['lines'][] = $this->_line;
            $this->_lines[$this->_line]['string'] .= $string;
        }
    }
    function _nextLine() {    
        
        $this->_line++;
        if (!isset($this->_lines[$this->_line-1]['bottom'])) {
            print_r($this->_lines);
            exit;
        }
        
        $this->_lines[$this->_line]['top'] = $this->_lines[$this->_line-1]['bottom'];
        $this->_lines[$this->_line]['y'] = $this->_lines[$this->_line-1]['bottom'] + 10;
        $this->_lines[$this->_line]['string'] = '';
        $this->_updateLine();
        // 
        
        
    
    }
    
    /*------------------------------FONTS AND COLORS --------------------------*/
    
    var $_fonts = array(); // associative array of fonts
    var $_font = NULL;
    function _makeFont($default='') {
        
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
        //echo $fontname;
        if ($default) $fontname =  $default;
        
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
            if (@$array[$i]) $a = $array[$i];
            $ret .= "-{$a}";
        }
        return $ret;
    }        
   
    var $_gcs = array(); // associative array fo fgcolor:bgcolor to GC object
    var $_gc = NULL; // link to current GtkGC
    
    var $_colors = array(); // associative array of colors
    var $_fgcolor = NULL;// link to current GdkColor
    var $_bgcolor = NULL;// link to current GdkColor

    function _makeColors($fgcolor = "#000000",$bgcolor = "#FFFFFF",$read=TRUE) {
        if ($read) {
            if ($c = $this->in('FGCOLOR')) 
                $fgcolor = $c;
            if ($c = $this->in('BGCOLOR')) 
                $bgcolor = $c;
        }
        //echo "$fgcolor:$bgcolor\n";
        
        if (!@$this->_colors[$fgcolor])
            $this->_colors[$fgcolor] = &new GdkColor($fgcolor);
        if (!@$this->_colors[$bgcolor])
            $this->_colors[$bgcolor] = &new GdkColor($bgcolor);
        
        $this->_fgcolor = &$this->_colors[$fgcolor];
        $this->_bgcolor = &$this->_colors[$bgcolor];
        /* GC STUFF */
        
        
        $id = $fgcolor . $bgcolor;
        
        if (!@$this->_gcs[$id]) {
            $window = $this->drawing_area->window;
            $this->_gcs[$id] = $window->new_gc();
            $cmap = $this->drawing_area->get_colormap();
            $this->_gcs[$id]->foreground =  $cmap->alloc($fgcolor);
            $this->_gcs[$id]->background =  $cmap->alloc($bgcolor);
        }
        /* REVERSED - for background rectangle*/
        $bgid = $bgcolor . $fgcolor;
        if (!@$this->_gcs[$bgid]) {
            $this->_gcs[$bgid] = $window->new_gc();
            $this->_gcs[$bgid]->foreground =  $this->_gcs[$id]->background ;
            $this->_gcs[$bgid]->background =  $this->_gcs[$id]->foreground;
        }
        $this->_gc = &$this->_gcs[$id];
        $this->_bggc = &$this->_gcs[$bgid];
        
        
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
     
    
 
   
    
    
    /* ------------------------------ BASIC WIDGET STUFF ------------------*/
    var $_area_x= 1000;
    var $_area_y= 10000;
    
    var $layout; // GtkLayout
    
    function testInterface() {
        $w = &new GtkWindow;
        $s  = &new GtkScrolledWindow();
        $hadj = $s->get_hadjustment();
        $vadj = $s->get_vadjustment();
        $this->layout =  &new GtkLayout($hadj,$vadj); 
        $this->layout->set_size($this->_area_x,$this->_area_y);
        $this->_DrawingArea();
        $s->add($this->layout);
        $w->add($s);
        $w->show_all();
    
    }
    
   
    var $drawing_area; // GtkDrawingArea
    function _DrawingArea() { // Drawing Area
        //echo "PAINTER";
        $this->drawing_area  = &new GtkDrawingArea();
        $this->drawing_area->size($this->_area_x,$this->_area_y);
        $this->drawing_area->set_events(  
              GDK_EXPOSURE_MASK
            | GDK_LEAVE_NOTIFY_MASK
            | GDK_BUTTON_PRESS_MASK
            | GDK_BUTTON_RELEASE_MASK
            // | GDK_BUTTON_MOTION_MASK
            | GDK_POINTER_MOTION_MASK
            | GDK_KEY_PRESS_MASK
            | GDK_POINTER_MOTION_HINT_MASK
        );

        //$this->drawing_area->set_events( GDK_ALL_EVENTS_MASK );
       // $this->layout->add_events(   GDK_KEY_PRESS_MASK   );
            
        $this->drawing_area->set_flags( GTK_CAN_FOCUS);
        
        
        
        $this->drawing_area->connect("configure_event",        array(&$this,"_DrawingAreaCallbackConfigure"));
        $this->drawing_area->connect("expose_event",           array(&$this,"_DrawingAreaCallbackExpose"));
        //$this->drawing_area->connect("key_press_event",        array(&$this,"_DrawingAreaCallbackKeyPress"));
        //$this->drawing_area->connect("button_release_event",   array(&$this,"_DrawingAreaCallbackBtnPress"));
        //$this->drawing_area->connect("button_press_event",     array(&$this,"_DrawingAreacallbackBtnPress"));
        //$this->html->drawing_area->connect_after("motion_notify_event",    array(&$this,"callback_pointer_motion_event"));
        //$this->html->drawing_area->connect("expose_event",
        //    array(&$this,"callback_expose_event"));
        $this->drawing_area->show();
        
        
        $this->layout->put($this->drawing_area,0,0);
        
    }                  
    
    var $pixmap;
    
    function _DrawingAreaCallbackConfigure($widget, $event) {
        if (@$this->pixmap) return;
        
        $this->pixmap = &new GdkPixmap($widget->window,
            $this->_area_x,$this->_area_y,
            -1);
        
        $this->_DrawingAreaClear();
        // draw somethin on it.
     
        //$this->drawing_area->realize();
        
        
        if (!$this->Start) {
            $this->build(0,count($this->_tokens),1);
            //$this->build(FALSE);
        }
        
        return true;
    }
    
    function _DrawingAreaClear() {
        gdk::draw_rectangle($this->pixmap,
            $this->drawing_area->style->white_gc,
            true, 0, 0,
            $this->_area_x,$this->_area_y);
        // draw somethin on it.
        $this->drawing_area->realize();
    
    }
    
    function _DrawingAreaCallbackExpose($widget,$event) {
         
        gdk::draw_pixmap($this->drawing_area->window,
            $widget->style->fg_gc[$widget->state],
            $this->pixmap,
            $event->area->x, $event->area->y,
            $event->area->x, $event->area->y,
            $event->area->width, $event->area->height);
        
    }                  
    
    
    
    
    
    
    
    /* ------------------------------ TABLE STUFF  ------------------*/
  
    
    
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
    
    
    function calctable($pos,$left,$right) {
       // if ($pos ==332 )  unset($this->td);
        //echo "START CALCTABLE $pos,$max\n";
        // read the width if there is one?
        $tableid = $pos;
        $attributes = $this->_tokens[$pos][1];
        if (preg_match("/ width\=[\"\']?(\[0-9]+)([%]*)[\"\']?/mi",' '.$attributes,$args)) {
            $right = $left + $args[1];
            if ($args[2]) $right = (int) (0.01 * $args[1]  * ($right - $left));
        }
        
        $pos++;
        
        
        $table = array(); // table[row][col]
        $totalcols= 1;
        $done = 0;
        $col =1;
        $row =0;
        $c = count($this->_tokens);
        while ($pos < $c) {
            if (!is_array($this->_tokens[$pos])) {
                $pos++;
                continue;
            }
            switch ($this->_tokens[$pos][0]) {
                case "TR":
                    $row++;
                    if ($col > $totalcols) $totalcols = $col-1;
                    $col = 1;
                    break;
                case "TD";
                    
                    while (@isset($table[$row][$col]['pos'])) // find next empty col.
                        $col++;
                    $args = array();
                    $span =1;
                    $rowspan =1;
                    if (preg_match("/\scolspan\=[\"\']?([0-9]+)[\"\']?/mi",' '.@$this->_tokens[$pos][1],$args))
                        $span = $args[1];
                    if (preg_match("/\srowspan\=[\"\']?([0-9]+)[\"\']?/mi",' '.@$this->_tokens[$pos][1],$args))
                        $rowspan = $args[1];
                    
                    $table[$row][$col]['pos'] = $pos;
                    $table[$row][$col]['span'] = $span;
                    $table[$row][$col]['rowspan'] = $rowspan;
                    
                    
                    for ($i=1;$i<$span;$i++) 
                        $table[$row][$col+$i]['pos'] = $pos;
                    for ($i=1;$i<$rowspan;$i++) 
                        for ($j=0;$j<$span;$j++) 
                        $table[$row+$i][$col+$j]['pos'] = $pos;
                    
                    
                    
                    $this->td[$pos]['row'] = $row;
                    $this->td[$pos]['col'] = $col;
                  
                    $this->td[$pos]['colspan'] = $span;
                    $this->td[$pos]['rowspan'] = $rowspan;
                    $this->td[$pos]['table'] = $tableid;
                    
                    $col += $span;
                    break;
                case "/TR":
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
        // I now have 2 arrays: $table[row][col][pos|span|rowspan] and $td[pos][col|row]
        // and totalcols;
        
        // do do a guess on the stuff...
         
         
         
         
        if (!$totalcols) return;
        $colwidth = (int) (($right-$left) / $totalcols);
        $x=$left;
        $row =0;
        foreach ($table as $row=>$cols) {
            $x = $left;
            foreach($cols as $col=>$data) {
                if (!isset($col_left[$col])) {
                    $col_left[$col] = $x;
                } else { 
                    $x=$col_left[$col] ;
                }
                
                $td_id = $data['pos'];
                // set left. if not set already
                if (!isset($this->td[$td_id]['left']))  
                    $this->td[$td_id]['left'] = $col_left[$col];
                
                
                if (!isset($this->td[$td_id]['right'])) 
                    $this->td[$td_id]['right'] = $col_left[$col];
                    
                 $this->td[$td_id]['colwidth'] = $colwidth;
                 $this->td[$td_id]['totalcols'] = $totalcols;
                if ($this->td[$td_id]['row'] == $row) 
                    $this->td[$td_id]['right'] += $colwidth;
                //echo "R{$row}:C:{$col}:{$this->td[$td_id]['left']},{$this->td[$td_id]['right']}\n";
                $x +=  $colwidth;
            }
            
        }
        //if ($tableid ==142 )  {
        //     print_r($this->td);
        //      exit;
        //  }
        $this->tables[$tableid]['cells'] =$table;
        $this->tables[$tableid]['left'] =$left;
        $this->tables[$tableid]['right'] =$right;
    }
    
    function _TABLErecalc($id) {
        //$rowx[$row]['top'] = 
        //$rowx[$row]['bottom'] = 
        
        $table = $this->tables[$id]['cells'];
        $top = $this->tables[$id]['top'];
        $rows[1]['top'] = $top;
        foreach ($table as $row=>$cols) {
            foreach($cols as $col=>$data) {
                $td_id = $data['pos'];
                $this->_TDcalcHeight($td_id);

                
                // bottom = ?
                if (@$table[$row+1][$col]['pos'] != $data['pos']) 
                    if (@$rows[$row]['height'] < @$this->td[$td_id]['height'])
                        $rows[$row]['height'] = $this->td[$td_id]['height'];
                
                $rows[$row]['bottom'] = $rows[$row]['top'] + $this->td[$td_id]['height'];
                $rows[$row+1]['top'] = $rows[$row]['bottom'];
                if (@$rows[$row]['bottom'] > @$this->tables[$id]['bottom'])
                    $this->tables[$id]['bottom'] =  $rows[$row]['bottom'];
            }
            // set the bottom for all cols.
            foreach($cols as $col=>$data) {
                $td_id = $data['pos'];
                $this->td[$td_id]['bottom']  = $rows[$row]['bottom'];
                if (@$table[$row-1][$col]['pos'] != $data['pos']) 
                    $this->td[$td_id]['top']  = $rows[$row]['top'];
            }
             
        }
        //print_r($rows);
        //exit;
    }
    
    
    
    function _TDcalcHeight($id) {
        if (@$this->td[$id]['height']) return;
        $h=0;
        foreach ($this->td[$id]['lines'] as $lineid) 
            $h += @$this->_lines[$lineid]['ascent'] + @$this->_lines[$lineid]['descent'];
        //if (!$h) $h=16;
        $this->td[$id]['height'] = $h;
    }
    
    function _drawBlock($ar) {
        $this->_makeColors();
        
        gdk::draw_rectangle($this->pixmap,
            $this->_bggc,true,    
            $ar['left'], $ar['top'], 
            $ar['right'], $ar['bottom']
        );
        $this->drawing_area->draw(
            new GdkRectangle(
                $ar['left'], $ar['top'], 
                $ar['right'], $ar['bottom']));
    }
     
    
}
    


dl('php_gtk.so');
error_reporting(E_ALL);
$t = new PEAR_Front_Gtk_WidgetHTML;
//$t->test(dirname(__FILE__).'/tests/test3.html');
$t->test('http://pear.php.net/manual/en/packages.templates.it.php');
$t->tokenize();
$t->testInterface();
//$t->build();

gtk::main();

 
 
 
 

?>
    