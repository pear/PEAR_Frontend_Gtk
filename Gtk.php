e<?php
/*
  +----------------------------------------------------------------------+
  | PHP Version 4                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2002 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 2.02 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available at through the world-wide-web at                           |
  | http://www.php.net/license/2_02.txt.                                 |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Stig Sæther Bakken <ssb@fast.no>                             |
  +----------------------------------------------------------------------+

  $Id$
*/

require_once "PEAR.php";
require_once "PEAR/Frontend/Gtk/Packages.php";
require_once "PEAR/Frontend/Gtk/Summary.php";
require_once "PEAR/Frontend/Gtk/Install.php";

class PEAR_Frontend_Gtk extends PEAR
{
    // {{{ properties

    /**
     * What type of user interface this frontend is for.
     * @var string
     * @access public
     */
    var $type = 'Gtk';

    var $omode = 'plain';
    var $params = array();
    
     /**
     * glade object 
     * @var object GladeXML
     * @access private
     */
    var $_glade;
    
    var $config; // object PEAR_Config
    // }}}

    // {{{ constructor

    function PEAR_Frontend_Gtk()
    {
        parent::PEAR();
        if (!extension_loaded('php_gtk')) {
            dl('php_gtk.' . (OS_WINDOWS ? 'dll' : 'so'));
        }
        //echo "LOADED?";
        $this->_loadGlade();
        $this->_initInterface();
    }
    // }}}
    /*
    * load all the named widgets from the glade file into this class as _widget_NAME
    * 
    */
    
    
    function _loadGlade() {
        
        $file = dirname(__FILE__).'/Gtk/installer.glade';
        $this->_glade = &new GladeXML($file);
        $data = implode('',file($file));
        preg_match_all('/\<name\>([^\<]+)\<\/name\>/',$data,$items);
        foreach ($items[1] as $widgetname) {
            $varname = "_widget_".$widgetname;
            $this->$varname = $this->_glade->get_widget($widgetname);
        }
        $items = array();
        preg_match_all('/\<handler\>([^\<]+)\<\/handler\>/',$data,$items); 
        //print_r($items[1]);
        foreach ($items[1] as $handler)  
            if (method_exists($this,$handler))
                $this->_glade->signal_connect( $handler ,array(&$this,$handler));

        
        
    }
    /**
     * the class that manages the class list
     * @var object PEAR_Frontend_Gtk_Packages
     * @access private
     */
    var $_package_list; 
    
      /**
     * the class that manages the Package summary
     * @var object PEAR_Frontend_Gtk_Summray
     * @access private
     */
    var $_summary; 
      /**
     * the class manages package installation
     * @var object PEAR_Frontend_Gtk_Install
     * @access private
     */
    var $_install; 
    
    
    
    
    
    
    /*
    * add the callbacks 
    * - needs to be simplified later using callbacks in the glade file
    * - but for development get a better idea..
    */
    
    
    function _initInterface() {
        // must be a better way - still needs to read -c -C optss
        $this->config = &PEAR_Config::singleton('','');
        
        // initialize child objects
        
        $this->_package_list = &new PEAR_Frontend_Gtk_Packages;
        $this->_package_list->Frontend_Gtk = &$this;
        $this->_package_list->init();
        $this->_summary = &new PEAR_Frontend_Gtk_Summary;
        $this->_summary->Frontend_Gtk = &$this;
        $this->_summary->init(); 
        $this->_install = &new PEAR_Frontend_Gtk_Install;
        $this->_install->Frontend_Gtk = &$this;
        $this->_install->init(); 
        
        $this->_widget_window->connect_after('realize',array(&$this,'_callbackWindowConfigure'));
        $this->_widget_window->connect_after('configure_event',array(&$this,'_callbackWindowConfigure'));
        
       
        
        $this->_widget_details_area->hide();
        $this->_widget_window->show();
    
    }
    
    var $_windowConfiguredFlag = FALSE;
    /*
    * window realized - load pixmaps etc.
    *  
    */


    function _callbackWindowConfigure($window) {
        // must be a better way - still needs to read -c -C optss
        
        
        $this->_initPixmaps($window);
        
        if ($this->_windowConfiguredFlag) return;
        $this->_windowConfiguredFlag = TRUE;
        /* main package selection tab */
        $this->_setStyle('nav_bar','','#7b7d7a',FALSE);
    
        $this->_setStyle('pear_installer_button','#000000','#7b7d7a');
        $this->_setStyle('config_button','#000000','#7b7d7a');
        $this->_setStyle('documentation_button','#000000','#7b7d7a');
        
        $this->_setStyle('black_bg1','#FFFFFF','#000000',TRUE);
        $this->_setStyle('black_bg2','#FFFFFF','#000000',TRUE);
        $this->_setStyle('black_bg3','#FFFFFF','#000000',TRUE);
        $this->_setStyle('black_bg4','#FFFFFF','#000000',TRUE);
        
        $this->_setStyle('download_list','#000000','#FFFFFF',TRUE);
        $this->_setStyle('close_details','#FFFFFF','#000000',FALSE);
        
        // sort out the text.
        $this->_setStyle('summary'   ,'#FFFFFF','#000000',TRUE);
        $this->_setStyle('black_msg1','#FFFFFF','#000000',TRUE);
        
        $newstyle = &new GtkStyle();
        $this->_widget_packages_install->set_style($newstyle);
        
        $this->_loadButton('pear_installer_button' ,'nav_installer.xpm');
        $this->_loadButton('config_button' ,        'nav_configuration.xpm');
        $this->_loadButton('documentation_button' , 'nav_documentation.xpm');
        $this->_loadButton('close_details' ,        'black_close_icon.xpm');

        $this->_setStyle('package_logo','#000000','#339900',TRUE);
        $this->_setStyle('package_logo_text','#FFFFFF','#339900'); 
        
        $package_logo = &new GtkPixmap(
            $this->_pixmaps['pear.xpm'][0],
            $this->_pixmaps['pear.xpm'][1]);
        $this->_widget_package_logo->put($package_logo,0,0);
        $package_logo->show();
        
        
        /* downloding tab */
        $this->_setStyle('white_bg1','#000000','#FFFFFF',TRUE);
        $download_icon  = &new GtkPixmap(
            $this->_pixmaps['downloading_image.xpm'][0],
            $this->_pixmaps['downloading_image.xpm'][1]);
        $this->_widget_white_bg1->put($download_icon,0,0);
        $download_icon->show();
        $this->_setStyle('downloading_logo','#000000','#339900',TRUE);
        $this->_setStyle('downloading_logo_text','#FFFFFF','#339900'); 
        $this->_widget_downloading_logo_text->set_justify( GTK_JUSTIFY_LEFT );
        $installer_logo = &new GtkPixmap(
            $this->_pixmaps['pear.xpm'][0],
            $this->_pixmaps['pear.xpm'][1]);
        $this->_widget_downloading_logo->put($installer_logo,0,0);
        $installer_logo->show();
        
        /* configuration loading */
          
        $this->_clearConfig();
        $cmd = PEAR_Command::factory('config-show',$this->config);
        $cmd->ui = &$this;
        $cmd->run('config-show' ,'', array());
        
        $this->_setStyle('config_logo','#000000','#339900',TRUE);
        $this->_setStyle('config_logo_text','#FFFFFF','#339900'); 
        
        $config_logo = &new GtkPixmap(
            $this->_pixmaps['pear.xpm'][0],
            $this->_pixmaps['pear.xpm'][1]);
        $this->_widget_config_logo->put($config_logo,0,0);
        $config_logo->show();
         
    }
    /*
    * load the images onto the left navbar
    *  
    */
    
    function _loadButton($widgetname, $icon) {
        echo $widgetname;
        $widget_fullname = "_widget_". $widgetname;
        $widget = &$this->$widget_fullname;
        
        $child = $widget->child;
        if ($child)
            if (get_class($child) == "GtkVBox") return;
       
        $widget->set_relief(GTK_RELIEF_NONE);
          
        //$widget->set_usize(150,100);
        $vbox = new GtkVBox; 
        
      // this stuff only gets done once
        if ($child)
            $widget->remove($child);
        $pixmap = &new GtkPixmap($this->_pixmaps[$icon][0],$this->_pixmaps[$icon][1]);
        $vbox->pack_start( $pixmap, true  , true  , 2);
        if ($child)
            $vbox->pack_end($child,true,true,2);
        $widget->add($vbox);
        //$widget->set_usize(150,100);
        $vbox->show();
        $pixmap->show();
     
    }
    
    
    function _setStyle($widgetname,$fgcolor='',$bgcolor='',$copy=FALSE) {
        echo "SET: $widgetname: $fgcolor/$bgcolor ". ((int) $copy) . "\n";
        $widget_fullname = "_widget_". $widgetname;
        $widget = &$this->$widget_fullname;
        if ($copy) {
            $oldstyle = $widget->get_style();
            $newstyle = $oldstyle->copy();
        } else {
            $newstyle = &new GtkStyle();
        }
        if ($fgcolor) { // set foreground color
            $fg = &new GdkColor($fgcolor);
            $newstyle->fg[GTK_STATE_PRELIGHT] = $fg;
            $newstyle->fg[GTK_STATE_NORMAL] = $fg;
            $newstyle->fg[GTK_STATE_ACTIVE] = $fg;
            $newstyle->fg[GTK_STATE_SELECTED] = $fg;
            $newstyle->fg[GTK_STATE_INSENSITIVE] = $fg;
        }
        if ($bgcolor) { // set background color
            $bg = &new GdkColor($bgcolor);
            $newstyle->bg[GTK_STATE_PRELIGHT] = $bg;
            $newstyle->bg[GTK_STATE_NORMAL] = $bg;
            $newstyle->bg[GTK_STATE_ACTIVE] = $bg;
            $newstyle->bg[GTK_STATE_SELECTED] = $bg;
            $newstyle->bg[GTK_STATE_INSENSITIVE] = $bg;
        }
        $widget->set_style($newstyle);
    
    
    }
    
    var $_pixmaps = array(); // associative array of filename -> pixmaps|mask array objects used by application
    
    /*
    * initialize the pixmaps - load the into $_pixmaps[name] ->object
    *  
    */
    function _initPixmaps(&$window) {
        
        if ($this->_pixmaps) return;
        $dir = dirname(__FILE__).'/Gtk/xpm';
        $dh = opendir($dir);
        if (!$dh) return;
        while (($file = readdir($dh)) !== FALSE) {
            if (@$file{0} == '.') continue;
            if (!preg_match('/\.xpm$/',$file)) continue;
            //echo "loading {$dir}/{$file}";
            $this->_pixmaps[$file] =  
                Gdk::pixmap_create_from_xpm($window->window, NULL, "{$dir}/{$file}");
                
        }
    }
    
    var $_logo_pixmap; // the base to draw pixmaps onto...
    



    


    function _buildConfig(&$array) {
        if (!$array) return;
        foreach ($array as $v) 
            $this->_buildConfigItem($v[0],$v[1]);

    }
    
    function _buildConfigItem($k,$v) {
        echo "BUIDLING CONF ITME $k $v\n";
        $group = $this->config->getGroup($k);
        $gtktable =  $this->_getConfigTab($group);
        $prompt = $this->config->getPrompt($k);
        $gtklabel = &new GtkLabel();
        $gtklabel->set_text($prompt);
        $gtklabel->set_justify(GTK_JUSTIFY_LEFT);
        $gtklabel->set_alignment(0.0, 0.5);
        $gtklabel->show();
        $r = $gtktable->nrows;
        $gtktable->attach($gtklabel, 0, 1, $r, $r+1, GTK_FILL,GTK_FILL);
        if ($v == '<not set>') 
            $v = '';
        
        $type = $this->config->getType($k);
        switch ($type) {
            case 'string':
            case 'password':
            case 'int': // umask: should really be checkboxes..
                $gtkentry = &new GtkEntry();
                $gtkentry->set_text($v);
                
                $gtkentry->connect_object_after('enter_notify_event',
                    array(&$this,'_setConfigHelp'),$this->config->getDocs($k));
                
                if ($type == 'password')
                    $gtkentry->set_visibility(FALSE);
                $gtkentry->show();
                $gtktable->attach($gtkentry, 1, 2, $r, $r+1, GTK_FILL|GTK_EXPAND,GTK_FILL);
                break;
            case 'directory':    
                $gtkentry = &new GtkEntry();
                $gtkentry->set_text($v);
                $gtkentry->connect_object_after('enter_notify_event',
                    array(&$this,'_setConfigHelp'),$this->config->getDocs($k));
              
                $gtkentry->show();
                $gtktable->attach($gtkentry, 1, 2, $r, $r+1, GTK_FILL|GTK_EXPAND,GTK_FILL);
                $gtkbutton = &new GtkButton('...');
                $gtkbutton->show();
                $gtktable->attach($gtkbutton, 2, 3, $r, $r+1, GTK_SHRINK,GTK_SHRINK);
                break;
            case 'set':
                $options = $this->config->getSetValues($k);
                $gtkmenu = &new GtkMenu();
                $items = array();
                $sel = 0;
                foreach($options as $i=>$option) {
                    $items[$i] = &new GtkMenuItem($option);
                    //$items[$i]->connect('activate', 'echo_activated', $labels[$i], $pos[$i], 
                    $gtkmenu->append($items[$i]);
                    if ($option == $v) 
                        $sel = $i;
                }
                $gtkmenu->set_active($sel);
                $gtkmenu->show_all();
                $gtkoptionmenu = &new GtkOptionMenu();
                $gtkoptionmenu->set_menu($gtkmenu);
                $gtkoptionmenu->connect_object_after('enter_notify_event',
                    array(&$this,'_setConfigHelp'),$this->config->getDocs($k));
              
                $gtkoptionmenu->show();
                $gtktable->attach($gtkoptionmenu, 1, 2, $r, $r+1, GTK_FILL|GTK_EXPAND,GTK_FILL);
                break;
            // debug: shourd  really be 
            case 'integer': // debug : should really be a set?
                $gtkadj = &new GtkAdjustment($v, 1.0, 3.0, 1.0, 1.0, 0.0);
                $gtkspinbutton = &new GtkSpinButton($gtkadj);
                $gtkspinbutton->show();
                $gtkspinbutton->connect_object_after('enter_notify_event',
                    array(&$this,'_setConfigHelp'),$this->config->getDocs($k));
            
                $gtktable->attach($gtkspinbutton, 1, 2, $r, $r+1, GTK_FILL|GTK_EXPAND,GTK_FILL);
                break;
            default:
                echo "$prompt : ". $this->config->getType($k) . "\n";    
        }
        
    }
  
    function _setConfigHelp($event,$string) {
        $this->_widget_config_help->set_text($string);
    }
  
    var $_configTabs = array(); // associative array of configGroup -> GtkTable
 
    function &_getConfigTab($group) {
        if (@$this->_configTabs[$group]) 
            return $this->_configTabs[$group];
        $this->_configTabs[$group] = &new GtkTable();
        $this->_configTabs[$group]->set_row_spacings(10);
        $this->_configTabs[$group]->set_col_spacings(10);
        $this->_configTabs[$group]->set_border_width(15);
        $this->_configTabs[$group]->show();
        $gtklabel = &new GtkLabel($group);

        $gtklabel->show();
        $this->_widget_config_notebook->append_page($this->_configTabs[$group],$gtklabel);
        return $this->_configTabs[$group];
    }

    function _clearConfig() {
        if ($this->_configTabs) 
            foreach (array_keys($this->_configTabs) as $k) {
                $page = $this->_widget_config_notebook->page_num($this->_configTabs[$k]);
                $this->_widget_config_notebook->remove_page($page);
                $this->_configTabs[$k]->destroy();
            }
        
        // delete any other pages;
        if ($widget = $this->_widget_config_notebook->get_nth_page(0)) {
            $this->_widget_config_notebook->remove_page(0);
            $widget->destroy();
        }
    }
    function _callbackShowInstaller() {
        $this->_widget_pages->set_page(0);
    }
    function _callbackShowConfig() {
        $this->_widget_pages->set_page(2);
    }
    
    var $_activeDownloadSize =0;
    var $_downloadTotal=1;
    var $_downloadPos=0;
    function _downloadCallback($msg,  $params) {
         
        switch ($msg) {
            case 'setup':
                return;
            case 'saveas':
                $this->_widget_downloading_filename->set_text("Downloading {$params}");
                $this->_widget_downloading_total_progressbar->set_percentage($this->_downloadPos/$this->_downloadTotal);
                $this->_downloadPos++; 
                $this->_widget_downloading_total->set_text("Total {$this->_downloadPos}/{$this->_downloadTotal}");
                while(gtk::events_pending()) gtk::main_iteration();
       
                return;
            case 'start':
                $this->_activeDownloadSize = $params;
                $this->_widget_downloading_file_progressbar->set_percentage(0);
                while(gtk::events_pending()) gtk::main_iteration();
                return;
            case 'bytesread':
                $this->_widget_downloading_file_progressbar->set_percentage($params / $this->_activeDownloadSize);
                while(gtk::events_pending()) gtk::main_iteration();
                return;
            case 'done':
                
                $this->_widget_downloading_total_progressbar->set_percentage($this->_downloadPos/$this->_downloadTotal);
               
            default:
                if (is_object($params)) $params="OBJECT";
                echo "MSG: $msg ". serialize($params) . "\n";
        }
        
    }
    


    function on_expand_all_activate() {
        $this->_package_list->widget->expand_recursive();
    }

    function on_collapse_all_activate() {
        $this->_package_list->widget->collapse_recursive();
    }
    
    function on_quit() {
        gtk::main_quit();
        exit;
    }




    //-------------------------- BASE Installer methods --------------------------
    
    function outputData($data,$command ){
        switch ($command) {
            case 'config-show':
                $this->_buildConfig($data['data']);
                break;
            default:
                echo "COMMAND : $command\n";
                echo "DATA: ".serialize($data)."\n";
        }
    }

    function log($msg) {
        echo "LOG $msg"; 
    }

    // {{{ displayLine(text)

    function displayLine($text)
    {
        echo "$text\n";
    }

    function display($text)
    {
      echo "$text\n";
    }

    // }}}
    // {{{ displayError(eobj)

    function displayError($eobj)
    {
        return $this->displayLine($eobj->getMessage()); 
        
    }

    // }}}
    // {{{ displayFatalError(eobj)

    function displayFatalError($eobj)
    {
        $this->displayError($eobj);
        //exit(1);
    }

    // }}}
    // {{{ displayHeading(title)

    function displayHeading($title)
    {
    }

    // }}}
    // {{{ userDialog(prompt, [type], [default])

    function userDialog($prompt, $type = 'text', $default = '')
    {
         echo "Dialog?" . $prompt;
    }

    // }}}
    // {{{ userConfirm(prompt, [default])

    function userConfirm($prompt, $default = 'yes')
    {
          echo "Confirm?" . $prompt;
    }

    // }}}
    // {{{ startTable([params])

    function startTable($params = array())
    {
    }

    // }}}
    // {{{ tableRow(columns, [rowparams], [colparams])

    function tableRow($columns, $rowparams = array(), $colparams = array())
    {
    }

    // }}}
    // {{{ endTable()

    function endTable()
    {
    }

    // }}}
    // {{{ bold($text)

    function bold($text)
    {
    }

    // }}}
}

?>
