<?



class PEAR_Frontend_Gtk_WidgetHTML {

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
        // print_r($this->_tokens);
        //exit;
    }
    var $Start = FALSE; // start rering (eg. ignore headers)
    function build($startpos,$endpos) {
        $this->layout->freeze();
        
        $this->_DrawingAreaClear();
        
        $this->_line_y = array();
        $this->_states = array();
        $this->states = array();
        $this->cur = array();
        $this->curid = array();
        // make a fake first line
        $this->_line =0;
        $this->_makeFont();
        $this->_lines[0]['top'] =0;
        $this->_lines[0]['ascent'] =0;
        $this->_lines[0]['descent'] =0;
        $this->_updateLine();
        $this->_lines[$this->_line]['bottom'] =0;
        $this->_lines[0]['left'] = 10;
        $this->_lines[0]['right'] = $this->_area_x;
       
       
        $this->_nextLine("BEGIN");
        
        $this->pushState(0);                 
        for($pos = $startpos;$pos < $endpos;$pos++) {
            $item = $this->_tokens[$pos]; 
            if (is_Array($item)) {
                $method = "push";
                if (!$item[0]) continue;
                //echo $pos;
                //echo "\nIN:".serialize($item)."\n";
                //$this->outputTAG($item[0]);
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
                            // move us down abit and start a new row
                            $this->_nextLine('CLEARPRETABLE'); // clear old line...
                            $this->_TDaddLine(); // add it to the table?
                            $this->tables[$pos]['from last']   = serialize($this->_lines[$this->_line]); 
                            
                            $this->tables[$pos]['pos'] = $pos;
                            $this->tables[$pos]['left']  =  $this->_lines[$this->_line]['left']; 
                            $this->tables[$pos]['right'] =  $this->_lines[$this->_line]['right']; 
                            $this->tables[$pos]['startright'] =  $this->_lines[$this->_line]['right']; 
                            $this->tables[$pos]['top']   = $this->_lines[$this->_line]['top']; 
                            $this->_nextLine('TABLE'); // new line object that contains the table information.
                            $this->_TDaddLine();
                            $this->_lines[$this->_line]['top'] = $this->tables[$pos]['top'];
                            
                            
                            $this->tables[$pos]['line'] = $this->_line;
                            
                            $this->_lines[$this->_line]['table'] = $pos;
                            $this->_lines[$this->_line]['top'] = $this->tables[$pos]['top'];
                            /*
                            if ($id = $this->inID('TD')) {
                                $this->td[$id]['lines'][] = $this->_line;
                                $this->td[$id]['line_items'][$this->_line] = &$this->_lines[$this->_line];
                            }
                            */
                            //$this->_updateLine("TABLE:{$pos}");// new line that is going to be the content.
                            
                            
                            
                            $this->_TABLEcalc($pos);
                             
                            $this->push($item[0],$pos,@$item[1]);
                            //$this->output("TABLE:$pos");
                            $this->_makeColors();
                            $this->tables[$pos]['gc'] = $this->_gc;
                            $this->tables[$pos]['bggc'] =$this->_bggc;
                                
                        } else {  //
                            
                             
                            
                            $table = $this->pop('TABLE',$pos);
                            
                             
                            $this->_TABLErecalc($table,$pos);
                            $this->_TABLEmovelines($table);
                            
                            
                            // update the container line
                            $line = $this->tables[$table]['line'];
                            $this->_lines[$line]['top'] = $this->tables[$table]['top']; 
                            $this->_lines[$line]['descent'] = $this->tables[$table]['height'];
                            $this->_lines[$line]['ascent'] = 0;
                            $this->_lines[$line]['height'] = $this->tables[$table]['height'];
                            $this->_lines[$line]['bottom'] = $this->tables[$table]['bottom'];
                            
                            //$this->_updateLine();
                            $this->_nextLine('ENDTABLE - clear'); // start a new line
                            $this->_TDaddLine();
                            
                            
                            
                            // move Y xursor.
                            $this->_lines[$this->_line]['top'] = $this->tables[$table]['bottom']; 
                            // move X xursor.
                            $this->_lines[$this->_line]['left'] = $this->tables[$table]['left']; 
                            
                            $this->_lines[$this->_line]['right'] = $this->tables[$table]['startright'];
                            
                            
                            $this->_lines[$this->_line]['x'] = $this->tables[$table]['left']; 
                            
                            
                        }
                        break;
                        
                    
                    //case 'TR':
                    case 'CAPTION':
                    case 'TH':
                        $item[0] = 'TD';
                    case 'TD':
                        
                        if ($method == 'push') { // start
                            if (!@$this->td[$pos]) {
                                $t = $this->inID('TABLE');
                                print_r($this->tables[$t]);
                                echo  "LOST TD?:$pos";
                                exit;
                            }
                            $this->td[$pos]['lines'] = array(); 
                            $this->td[$pos]['line_items'] = array();
                            //echo "TDPstart: $pos L:{$this->_left},{$this->_right}\n";
                            
                            $this->push($item[0],$pos,@$item[1]); 
                            
                            $this->_nextLine("TD - start");
                            $this->_TDaddLine();
                                                        
                            $this->_lines[$this->_line]['left'] = $this->td[$pos]['left'];
                            $this->_lines[$this->_line]['right'] = $this->td[$pos]['right'];
                            $this->_lines[$this->_line]['x'] = $this->td[$pos]['left'];
                            
                            // this doesnt matter -   gets changed later...
                            //$this->_lines[$this->_line]['top'] = $this->td[$pos]['top'];
                            
                            $this->_makeColors();
                             
                            $this->td[$pos]['gc'] = $this->_gc;
                            $this->td[$pos]['bggc'] =$this->_bggc;
                            
                        } else {
                            $this->_nextLine('TD - END');
                            $this->_TDaddLine();
                            $td = $this->pop('TD',$pos);
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
            if ($t = $this->inID('TABLE')) 
                if ($t > ($td = $this->inID('TD'))) {
                    if (trim($item))
                        echo "TABLE : $t, TD: $td skipping\n";
                    continue;
                }
            $this->output($item,$pos);
        }
        $this->linebr();   
        
        $this->_area_y = $this->_lines[$this->_line]['bottom'] ;
        $this->layout->set_size($this->_area_x , $this->_area_y );
        $this->layout->thaw();
        //return;
        //print_r($this->td);
        //if ($this->pass == 1) {
        //    $this->pass++;
        //    $this->build($startpos,$endpos);
        // }
        foreach(array_keys($this->tables) as $pos)
            $this->_drawBlock($this->tables[$pos]);
        foreach(array_keys($this->td) as $pos)
            $this->_drawBlock($this->td[$pos]);
        foreach(array_keys($this->_textParts) as $id)
            $this->_drawPart($id);
        
        print_r($this->tables);
        //print_r($this->_textParts);

    }
    
    /*-----------------------------STACK STUFF--------------------------------*/
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
            echo "\nPUSH:$what:";print_r($this->stack[$what]);
            echo "\nCUR:{$this->cur[$what]}";
        }
    }
    function pop($what,$pos=0,$attributes=array()) {
        if (!@$this->stack[$what]) return;
        list($id,$remove) = array_pop($this->stack[$what]);
        $this->cur[$what] = "";
        $this->curid[$what] = 0;
        if ($this->stack[$what])
            list($this->curid[$what],$this->cur[$what]) = $this->stack[$what][count($this->stack[$what])-1];
        $this->_stackAttributes($remove,$pos,'pop');
        /* debugging*/
        if ($this->check  && preg_match("/^(".$this->check.")$/",$what)) { 
            echo "\nPOP:$what:AT$pos:";print_r($this->stack[$what]);
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
        
        $colors = array(
            "yellow" => "#FFFF00",
            "blue" => "#0000FF"
        );
        
        $args = array();    
        if (preg_match("/\sbgcolor\=[\"\']?([a-z]+)[\"\']?/mi",' '.$attributes,$args))
            $this->$method("BGCOLOR",$pos,strtolower($args[1]));


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
    
    
    /*-----------------------------TEXT StuffSTUFF --------------------------------*/
    
    
    function linebr($item='') {
        if (($item == "P") && ($this->lastbr == "P")) return;
        //if ($item && $this->lastbr && ($this->lastbr != $item)) return;
        //$this->widget->insert($this->_font,$this->_fgcolor,$this->bg_color,":$item:\n");
        $this->_nextLine('LINEBREAK');
        $this->_updateLine('','LINEBR');
        $this->_TDaddLine();
        $this->lastbr = $item;
    }
    var $buffer="";
    var $_left = 10;
    var $_right  = 600;
    var $_x = 10;
    var $_y = 20;
    function output($string,$pos) {
        
        if (!$this->Start) return;
        $string = $this->unhtmlentities($string);
        if (!$this->inID('PRE')) 
            $string = trim(preg_replace("/[\n \t\r]+/m", ' ',$string)) . ' ';
        if (!trim($string)) return; // except PRE stuff!q
        
        // invisible stuff
        if ($this->inID('SELECT')) return;
        if ($this->inID('TEXTAREA')) return;
        
        $this->_makeFont();
        $this->_makeColors();
        //$this->_setStyle();
        if ($this->inID('PRE')) {
            $this->outputPRE($string,$pos);
            return;
        }
        $this->outputTEXT($string,$pos);
    
    }
    function outputTAG($tag) {
            
        $this->_makeFont('-adobe-helvetica-bold-r-normal-*-*-80-*-*-p-*-iso8859-1');
        $this->_makeColors("#000000","#FFFF00",FALSE);
        $this->outputTEXT("<{$tag}>");
    }
    var $line =0;
    function outputTEXT($string,$pos) {
        
        /*echo "outputTEXT ".
        
            "X:".$this->_lines[$this->_line]['x'] .
            "L:". $this->_lines[$this->_line]['left']. 
            "R:". $this->_lines[$this->_line]['right'].
            "\n";
        */
        $array = $this->_breakString(
            $this->_lines[$this->_line]['x'],
            $this->_lines[$this->_line]['left'],
            $this->_lines[$this->_line]['right'],
            $string);
        // array of lines (startpos,len,text)
        //echo serialize($array);
        $c = count($array) -1;
        foreach($array as $i=>$data) {

            if ($data[2] !== '') {
                //$widget = &new GtkLabel($line[2]);
                //$widget->set_style($this->_style);
                //if ($this->pass == 2) 
                     //echo "ADD: {$line[0]},{$this->_y} : {$line[2]}\n";
                //$this->layout->put($widget,$line[0],$this->_y);
                $this->_updateLine($data[2],'outputTEXT');
                
                $this->_textParts[] = array(
                    'string' => $data[2],
                    'line' =>   $this->_line,
                    'left' =>   $data[0],
                    'width' =>  $data[1],
                    'bggc' =>   $this->_bggc,
                    'gc'   =>   $this->_gc,
                    'font' =>   $this->_font,
                );
                //$widget->show();
                $this->_updateLine("POS:$pos", 'outputTEXT2');
                $this->_lines[$this->_line]['x'] = $data[0] + $data[1];   
                if ($c != $i) {
                    $this->_nextLine('TEXTout');
                    $this->_updateLine('','OutputTEXT NEWLINE');
                    $this->_TDaddLine();
                }
            }
        } 
        
    }
    function outputPRE($string,$pos) {
    
         
        if (!strpos($string,"\n")) {
            $this->outputTEXT($string,$pos);
            return;
        }
        
        $array = explode("\n", $string) ;
        // array of lines (startpos,len,text)
        $c = count($array) -1;
        foreach($array as $i=>$line) {
            $this->_updateLine($line, 'outputPRE');
            $this->outputTEXT($line,$pos);
            $this->_nextLine('PREout');
            $this->_TDaddLine();
        }
        
    }
    function _drawPart($id) {
        $part = $this->_textParts[$id];
        $line = $this->_lines[$part['line']];
        //print_r($part);
        //print_r($line);
        //exit;
        gdk::draw_rectangle($this->pixmap,
            $this->_gcs[$part['bggc']],true,    
            $part['left'],  $line['top'], 
            $part['width'], $line['height']
        );
                
        gdk::draw_text($this->pixmap, 
            $this->_fonts[$part['font']], $this->_gcs[$part['gc']] , 
            $part['left'],  $line['y'], 
            $part['string'], strlen($part['string'])
        );
        $this->drawing_area->draw(
            new GdkRectangle(
                $part['left'],  $line['bottom'], 
                $part['width'], $line['height']
            )
        );
    }
    function _drawBlock($ar) {
        if (!$ar['bggc']) { 
            print_r($ar); 
            echo 'NO BGGC';
            return;
        }
        gdk::draw_rectangle($this->pixmap,
            $this->_gcs[$ar['bggc']],true,    
            $ar['left'], $ar['top'], 
            $ar['right'], $ar['bottom'] -$ar['top']
        );
        $this->drawing_area->draw(
            new GdkRectangle(
                $ar['left'], $ar['top'], 
                $ar['right'], $ar['bottom'] - $ar['top']));
    }
    function _breakString($start,$left,$right,$string) {
         
        $l = $this->_fonts[$this->_font]->extents($string);
        //echo serialize($l);
        //echo "\nSTRINGLEN: $string =  {$l[2]} \n";
        if ($l[2] < ($right - $start)) {
            return array(array($start,$l[2],$string));
        }
        $ret = array();
        $buf = "";
        $words = explode(" ",$string);
        foreach ($words as $w) {
         
            $l = $this->_fonts[$this->_font]->extents($buf . " " . $w);
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
            $l = $this->_fonts[$this->_font]->extents($buf);
            $ret[] = array($start, $l[2] ,$buf);
            $buf = $w;
            $start = $left;
        }
        if ($buf) {
            $l = $this->_fonts[$this->_font]->extents($buf);
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
    
    /* -----------------------------LINE SUTFF -------------------*/
    
    var $_line;
    function _updateLine($string = '',$updateReason) {
        // store the line height of the current line..
        
        //if (!@$this->_lines[$this->_line]['ascent']) $this->_lines[$this->_line]['ascent']=1;
        //if (!@$this->_lines[$this->_line]['decent']) $this->_lines[$this->_line]['decent']=5;
         
        if (@$this->_lines[$this->_line]['ascent'] < $this->_fonts[$this->_font]->ascent ) 
            $this->_lines[$this->_line]['ascent'] = $this->_fonts[$this->_font]->ascent;
        if (@$this->_lines[$this->_line]['descent'] < $this->_fonts[$this->_font]->descent ) 
            $this->_lines[$this->_line]['descent'] = $this->_fonts[$this->_font]->descent;
        if (!isset($this->_lines[$this->_line]['descent'])) {
            echo "FAILED TO FIND DESCENT {$this->_line}\n";
            echo serialize($this->_fonts[$this->_font]->descent);
            exit;
        }
        if (!@$this->_lines[$this->_line]['updateReason'])
            $this->_lines[$this->_line]['updateReason'] = '';
        $this->_lines[$this->_line]['updateReason'] .= $updateReason;
        
        $this->_lines[$this->_line]['height'] = 
            $this->_lines[$this->_line]['descent'] + $this->_lines[$this->_line]['ascent'];
        
        $this->_calcLine($this->_line);
        
        // store the active block heights....
        
       
      
        if ($string)
            $this->_lines[$this->_line]['string'] .= $string;
         
    }
    
    
    function _calcLine($l) {
        $this->_lines[$l]['y'] = $this->_lines[$l]['top'] + $this->_lines[$l]['ascent'];
        $this->_lines[$l]['bottom'] = $this->_lines[$l]['top'] + $this->_lines[$l]['height'];
    }
    function _nextLine($reason) {    
        
        $this->_line++;
        if (!isset($this->_lines[$this->_line-1]['bottom'])) {
            print_r($this->_lines);
            echo "NO BOTTOM ON NEXT LINE";
            exit;
        }
        
        $this->_lines[$this->_line]['left'] = $this->_lines[$this->_line-1]['left'];
        $this->_lines[$this->_line]['right_from_last'] = $this->_lines[$this->_line-1]['right'];
        $this->_lines[$this->_line]['right'] = $this->_lines[$this->_line-1]['right'];
        $this->_lines[$this->_line]['x'] =$this->_lines[$this->_line]['left'];
        
        
        $this->_lines[$this->_line]['top'] = $this->_lines[$this->_line-1]['bottom'];
        $this->_lines[$this->_line]['y'] = $this->_lines[$this->_line-1]['bottom'];
        $this->_lines[$this->_line]['string'] = '';
        $this->_lines[$this->_line]['reason'] = $reason;
        $this->_lines[$this->_line]['ascent'] =0;
        $this->_lines[$this->_line]['descent'] =0;
        $this->_lines[$this->_line]['height'] =0;
        //$this->_updateLine();
        $this->_calcLine($this->_line);
    
    }
    
    function _TDaddLine() {
        if ($id = $this->inID('TD')) {
            $table = $this->inID('TABLE');
            if ($table > $id) {
                echo "TRIED TO ADD TD:$id to TABLE:$table";
                return;
            }
            if (!isset($this->td[$id]['lines'])) {
                print_r($this->td);
                echo "NO TD FOR $id\n";
                exit;
            }
            if (!in_array($this->_line,$this->td[$id]['lines'])) {
                $this->td[$id]['lines'][] = $this->_line;
                $this->td[$id]['line_items'][$this->_line] = &$this->_lines[$this->_line];
            }
        }
        
    
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
        if ($this->inID('PRE') || $this->inID('TT') || $this->inID('CODE') ) {
            //echo "IN PRE?" . $this->inID('PRE') . "\n";
            $font['family']  = 'courier';
            $font['space'] = 'm';
            $font['pointsize'] = 80;
        }
        if ($this->inID('B')) 
            $font['weight']  = 'bold';
        if ($this->inID('I')) 
            $font['slant']  = 'i';
            
        if ($v = $this->in('H')) {
            //&& is_int($v)
            // mapping 1=large = eg. 160 , 3=100 ok 5 = small
            // 20 * $v  would give 1=20, 2=40 .. 5=100
            // now 160-$v;; would give 1 = 140, 3=100 
            $font['weight']  = 'bold';
            $font['pointsize'] = 180 - ($v * 20);
            //echo "setting point size = {$font['pointsize']}";
        }
            
            
            
            
        $fontname = $this->_getFontString($font);
        //echo $fontname;
        if ($default) $fontname =  $default;
        
        if (@!$this->_fonts[$fontname])  
            $this->_fonts[$fontname] = gdk::font_load($fontname);
        if (!$this->_fonts[$fontname]) 
            echo "FAIL: $fontname\n";
        //echo "SET FONT: $fontname\n";
        $this->_font =  $fontname;
        
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
        $this->_gc = $id;
        $this->_bggc = $bgid;
        
        
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
        
        
        //if (!$this->Start) {
        
        //$this->build(0,count($this->_tokens),1);
        $this->Start = FALSE;
        $this->build(0,count($this->_tokens),1);
        //$this->Start = TRUE;
        // $this->build(85,117);
        //$this->build(77,141);
        //$this->build(FALSE);
        //}
        
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
        $pos++;
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
                array_pop($table);
                if (!$table) return $pos;
            }
            $pos++;
        }
        return $pos;
    }
    /* first version just divides available area! */
    var $td; // associative array of [posid]['width'|'start']
    function _TABLEcalc($pos) {
        $left = $this->tables[$pos]['left'];
        $right = $this->tables[$pos]['right'];

        $tableid = $pos;
        if (preg_match("/\swidth\=[\"\']?([0-9]+)([%]*)[\"\']?/mi",' '.$this->_tokens[$pos][1],$args)) {
            if ($args[2]) { 
                $right = $left + (int) (0.01 * $args[1]  * ($right - $left));
            } else {
                $right = $left + $args[1];
            }

        }
        
        $pos++;
        
        
        $table = array(); // table[row][col]
        $cells = array();
        $colsizes = array(); 
        $totalcols= 1;
        $totalrows = 1;
        $done = 0;
        $col =1;
        $row =0;
        $hasCaption = 0;
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
                    if ($row > $totalrows) $totalrows = $row;
                    $col = 1;
                    break;
                    
                case "CAPTION":
                    $hasCaption = $pos;
                    $table[$row][$col]['pos'] = $pos;
                    $table[$row][$col]['span'] = 1;
                    $table[$row][$col]['rowspan'] = 1;
                    $this->td[$pos]['pos'] = $pos;
                    $this->td[$pos]['row'] = $row;
                    $this->td[$pos]['col'] = $col;
                    $this->td[$pos]['colspan'] = 1;
                    $this->td[$pos]['rowspan'] = 1;
                    $this->td[$pos]['iscaption'] = 1;
                    $this->td[$pos]['table'] = $tableid;
                    $this->td[$pos]['tag'] =  $this->_tokens[$pos][0];
                    $cells[] = $pos;
                    $row++;
                    break;
                case "TD";
                case "TH";
                    
                    while (@isset($table[$row][$col]['pos'])) // find next empty col.
                        $col++;
                    $args = array();
                    $span =1;
                    $rowspan =1;
                    $args = array();
                    if (!@$colsizes[$col]  && preg_match("/\swidth\=[\"\']?([0-9]+)([%]*)[\"\']?/mi",' '.@$this->_tokens[$pos][1],$args)) {
                        if ($args[2]) { 
                            $colsizes[$col] = (int) (0.01 * $args[1]  * ($right - $left));
                        } else {
                            $colsizes[$col] = $args[1];
                        }
                    }
                    
                    
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
                    
                    
                    $this->td[$pos]['tag'] =  $this->_tokens[$pos][0];
                    $this->td[$pos]['row'] = $row;
                    $this->td[$pos]['col'] = $col;
                    $this->td[$pos]['colspan'] = $span;
                    $this->td[$pos]['rowspan'] = $rowspan;
                    $this->td[$pos]['table'] = $tableid;
                    $this->td[$pos]['colwidth'] = 0;
                    $cells[] = $pos;
                    
                    $col += $span;
                    break;
                case "/TR":
                    break;
                case "TABLE":
                    $spos = $pos;
                    $pos = $this->_findSubTables($pos); // skip sub tables
                    //echo "SKIPPED: $spos:$pos\n";
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
        //print_r($table); exit;
        // do do a guess on the stuff...
        
        if ($col > $totalcols) $totalcols = $col-1;
         
         
         
        if (!$totalcols) return;
        
        
        if ($hasCaption) {
            $pos = $hasCaption;
            $row = $this->td[$pos]['row'];
            $col = $this->td[$pos]['col'];
            $table[$row][$col]['span'] = $totalcols;
            $this->td[$pos]['colspan'] = $totalcols;
            $this->td[$pos]['table'] = $tableid;
            for ($i=1;$i<$totalcols;$i++) 
                $table[$row][$col+$i]['pos'] = $pos;
        }
        
        
        /* calculate the width */
        $colsizes = $this->_TABLEcalcWidth($colsizes, $right-$left, $totalcols);
        
        
        
        
        $x=$left;
        $row =0;
        for ($row =1; $row < ($totalrows + 1) ; $row++) {
            $cols = $table[$row];
            $x = $left;
            for ($col =1; $col < ($totalcols +1); $col++) {
                $data = $cols[$col];
                
                $td_id = $data['pos'];
                // if it's the first occurance set left and right
                if (($this->td[$td_id]['row'] == $row) 
                    && ($this->td[$td_id]['col'] == $col)) {
                    $this->td[$td_id]['left'] = $x;
                    $this->td[$td_id]['right'] = $x;
                }
                
                if ($this->td[$td_id]['row'] == $row) {
                    $this->td[$td_id]['colwidth'] += $colsizes[$col];
                    $this->td[$td_id]['right']    += $colsizes[$col];
                }
                $table[$row][$col]['width'] =  $colsizes[$col];
                //echo "R{$row}:C:{$col}:{$this->td[$td_id]['left']},{$this->td[$td_id]['right']}\n";
                $x +=  $colsizes[$col];
                
                /// for reference only
                $this->td[$td_id]['totalcols'] = $totalcols;
            }
            
        }
        //if ($tableid ==142 )  {
        //     print_r($this->td);
        //       exit;
        //  }
        $this->tables[$tableid]['table'] =$table;
        $this->tables[$tableid]['cells'] =$cells;
        $this->tables[$tableid]['colsizes'] =$colsizes;
        $this->tables[$tableid]['left'] =$left;
        $this->tables[$tableid]['right'] =$right;
        $this->tables[$tableid]['totalrows'] =$totalrows;
        $this->tables[$tableid]['totalcols'] =$totalcols;
        //print_r($this->tables); exit;
         
        
    }
    
    function _TABLEcalcWidth($cols,$width,$total) {
        $res = array();
        // add up the widths
        // and how many cells are used
        $sum =0;
        $empty =0;
        for ($i=1;$i<($total+1);$i++) {
            if (@$cols[$i]) {
                $sum += $cols[$i];
            } else {
                $empty++;
            }
        }
        $available = $width-$sum;
        $default =0;
        $factor = 1;
    
        if ($empty)  {
            $default = (int) ($available / $empty); 
        } else {
            $factor = $width/$sum;
        }
        for ($i=1;$i<($total+1);$i++) {
            if (@$cols[$i]) {
                $res[$i] = (int) ($cols[$i] * $factor);
            } else {
                $res[$i] = (int) ($default * $factor);
            }
        }
        /*
        print_r(
            array(
                'cols' =>$cols,
                'width' =>$width,
                'total' =>$total,
                'result' =>$res,
                'available' => $available,
                'empty' => $empty,
                'factor' => $factor,
                'sum' => $sum
                ));
        exit;
        */
        return $res;
    }
            
            
            
        
    
    
    function _TABLErecalc($id,$end) {
        //$rowx[$row]['top'] = 
        //$rowx[$row]['bottom'] = 
        
        $table = $this->tables[$id]['table'];
        $top = $this->tables[$id]['top'];
        $totalrows = $this->tables[$id]['totalrows'];
        $totalcols = $this->tables[$id]['totalcols'];
        
        $this->tables[$id]['end'] = $end;
        //if ($id == 85) echo "$top"; exit;
        $rows[1]['top'] = $top;
        for ($row =1; $row < ( $totalrows + 1) ; $row++) {
            $cols = $table[$row];
           
            // row
            $height  = 0;
            for ($col =1; $col < ($totalcols + 1) ; $col++) {
                $data = $cols[$col];
                $td_id = $data['pos'];
                $this->_TDcalcHeight($td_id);
                
                // top - is it the first row
                if (@$table[$row-1][$col]['pos'] != $td_id) 
                    $this->td[$td_id]['top'] = $top;
                    
                
                // bottom = ?
                if (@$table[$row+1][$col]['pos'] != $data['pos']) 
                    if ($height < @$this->td[$td_id]['height'])
                        $height = $this->td[$td_id]['height'];
                
                $bottom = $top + $height;
            }
            
            // set the bottom for all cols.
            for ($col =1; $col < ($totalcols+1); $col++) {
                $data = $cols[$col];
                $td_id = $data['pos'];
                
                if (@$table[$row+1][$col]['pos'] != $td_id) 
                    $this->td[$td_id]['bottom']  = $bottom;
                    
                $this->tables[$id]['table'][$row][$col]['td'] = &$this->td[$td_id];
               
          
            }
            //echo "ROW:$row:TOP:$top\n";
            $top = $bottom;
             
        }
        $this->tables[$id]['height'] = $bottom - $this->tables[$id]['top'];
        $this->tables[$id]['bottom'] = $bottom;
        //print_r($this->tables); exit;
        //if ($end > 160) {
         //    print_r($this->tables);
        //    echo "$id::$end\n";
            //exit;
       // }
    }
    function _TDcalcHeight($id) {
        //if ($this->td[$id]['height']) return;
        $h=0;
        foreach ($this->td[$id]['lines'] as $lineid) 
            $h += @$this->_lines[$lineid]['ascent'] + @$this->_lines[$lineid]['descent'];
        //if (!$h) $h=16;
        $this->td[$id]['height'] = $h;
    }
    function _TABLEmovelines($table) {
        
        $cells = $this->tables[$table]['cells'];
        foreach($cells as $td) {
            $lines = $this->td[$td]['lines'];
            $top = $this->td[$td]['top'];
            foreach($lines as $line) {
                $this->_lines[$line]['top'] = $top;
                $this->_calcLine($line);
                
                if (@$subtable = $this->_lines[$line]['table']) {
                    $this->tables[$subtable ]['top'] = $top;
                    $this->_TABLErecalc($subtable, $this->tables[$subtable ]['end']);
                    $this->_TABLEmovelines($subtable);
                }
                $top = $this->_lines[$line]['bottom'];
            }
        }
    }
    
}
    


dl('php_gtk.so');
error_reporting(E_ALL);
$t = new PEAR_Frontend_Gtk_WidgetHTML;
 //$t->test(dirname(__FILE__).'/tests/test3.html');
//$t->test(dirname(__FILE__).'/tests/packages.templates.it.html');
//$t->test('http://pear.php.net/manual/en/packages.templates.it.php');
//$t->test('http://docs.akbkhome.com/PHPcodedoc/PHP_CodeDoc.html');
$t->test('http://www.php.net/');
$t->tokenize();
$t->testInterface();
//$t->build();

gtk::main();

 
 
 
 

?>
    