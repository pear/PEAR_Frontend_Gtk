<?php
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
        echo "LOADED?";
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
    
    /*
    * add the callbacks 
    * - needs to be simplified later using callbacks in the glade file
    * - but for development get a better idea..
    */
    
    
    function _initInterface() {
        // must be a better way - still needs to read -c -C optss
        $this->config = &PEAR_Config::singleton('','');
         
        $this->_package_list = &new PEAR_Frontend_Gtk_Packages;
        $this->_package_list->Frontend_Gtk = &$this;
        $this->_package_list->init();
        $this->_summary = &new PEAR_Frontend_Gtk_Summary;
        $this->_summary->Frontend_Gtk = &$this;
        $this->_summary->init(); 
        
        
        $this->_widget_window->connect_after('realize',array(&$this,'_callbackWindowConfigure'));
        $this->_widget_window->connect_after('configure_event',array(&$this,'_callbackWindowConfigure'));
        
        $this->_widget_nav_bar->connect_after(
                'expose_event',array(&$this,   '_callbackNavBarExpose'));
        $this->_widget_nav_bar->connect_after(
                'configure_event',array(&$this,   '_callbackNavBarExpose'));
        
        
        $this->_widget_installer_logo = & new GtkDrawingArea();
        $this->_widget_installer_logo->size( 486, 48);
        
        $this->_widget_installer_logo->connect(
                'configure_event',array(&$this,'_callbackInstallerLogoConfigure'));        
                
        $this->_widget_installer_logo->connect(
                'expose_event',array(&$this,   '_callbackInstallerLogoExpose'));
        $this->_widget_installer_logo_holder->add($this->_widget_installer_logo);
        $this->_widget_installer_logo->show();
        
        $this->_widget_details_area->hide();
        $this->_widget_window->show();
    
    }
    /*
    * window realized - load pixmaps etc.
    *  
    */


    function _callbackWindowConfigure($window) {
        // must be a better way - still needs to read -c -C optss
        $this->_initPixmaps($window);
         
        $child = $this->_widget_pear_installer_button->child;
        
        
        $this->_loadNavBar();
        
        
        $this->_loadButton('pear_installer_button' , 'nav_installer.xpm');
        $this->_loadButton('config_button' , 'nav_configuration.xpm');
        $this->_loadButton('documentation_button' , 'nav_documentation.xpm');
        /*
        $widget= $this->_widget_nav_bar;
        $current_style=$widget->get_style();
        $new_style = $current_style->copy(); 
        $new_style->bg[GTK_STATE_NORMAL]=new GdkColor('#339900'); 
        $new_style->bg[GTK_STATE_NORMAL]=new GdkColor('#339900'); 
        $widget->set_style($new_style);
      
        gdk::draw_rectangle($widget->window,
            $widget->style->bg_gc[GTK_STATE_NORMAL],
            true, 0, 0,
            $widget->allocation->width,
            $widget->allocation->height);
         
        */
        
         
    }
    
    function _loadNavBar() {
    // this stuff only gets done once  
    
        $widget = $this->_widget_nav_bar;
        /*
        $child = $widget->child;
        if ($child)
            if (get_class($child) == "GtkVBox") return;
       
        $widget->remove($child);
        
        // remove the nave bar from it's parent
        $nav_bar = $this->_widget_nav_bar;
        $parent = $nav_bar->parent;
        $parent->remove($nav_bar);
       // $widget->pack_start( $nav_bar, true  , true  , 2);
        $widget->add($nav_bar);
        
        */
        // now change the style of the navbar!
        $newstyle = &new GtkStyle();
      
         
       
        $bg = &new GdkColor('#7b7d7a');
        $newstyle->bg[GTK_STATE_PRELIGHT] = $bg;
        $newstyle->bg[GTK_STATE_NORMAL] = $bg;
        $newstyle->bg[GTK_STATE_ACTIVE] = $bg;
        
        $newstyle->bg[GTK_STATE_SELECTED] = $bg;
        $newstyle->bg[GTK_STATE_INSENSITIVE] = $bg;
        $widget->set_style($newstyle);
         
        
        
        
        
    
    }
    function _callbackNavBarExpose($widget,$event) {
        /*
        $widget= $this->_widget_nav_bar;
        $current_style=$widget->get_style();
        $new_style = $current_style->copy(); 
        $new_style->bg[GTK_STATE_NORMAL]=new GdkColor('#339900'); 
        $new_style->bg[GTK_STATE_NORMAL]=new GdkColor('#339900'); 
        $widget->set_style($new_style);
        
        gdk::draw_rectangle($widget->window,
            $widget->style->bg_gc[GTK_STATE_NORMAL],
            true, 0, 0,
            $widget->allocation->width,
            $widget->allocation->height);
        */
    
    }
    
    function _loadButton($widgetname, $icon) {
        $widget_fullname = "_widget_". $widgetname;
        $widget = &$this->$widget_fullname;
        
        $child = $widget->child;
        if ($child)
            if (get_class($child) == "GtkVBox") return;
       
        $widget->set_relief(GTK_RELIEF_NONE);
        $oldstyle = $widget->style;
        $newstyle = $oldstyle->copy();;
        $fg = &new GdkColor('#000000');
        $newstyle->fg[GTK_STATE_PRELIGHT] = $fg;
        $newstyle->fg[GTK_STATE_NORMAL] = $fg;
        $newstyle->fg[GTK_STATE_ACTIVE] = $fg;
        
        $newstyle->fg[GTK_STATE_SELECTED] = $fg;
        $newstyle->fg[GTK_STATE_INSENSITIVE] = $fg;
        
        //$child->set_style($newstyle);
        $newstyle = &new GtkStyle();
        
        $bg = &new GdkColor('#7b7d7a');
        $newstyle->bg[GTK_STATE_PRELIGHT] = $bg;
        $newstyle->bg[GTK_STATE_NORMAL] = $bg;
        $newstyle->bg[GTK_STATE_ACTIVE] = $bg;
        
        $newstyle->bg[GTK_STATE_SELECTED] = $bg;
        $newstyle->bg[GTK_STATE_INSENSITIVE] = $bg;
        $widget->set_style($newstyle);
        
        
        
        //$widget->set_usize(150,100);
        $vbox = new GtkVBox; 
        
      // this stuff only gets done once
        $widget->remove($child);
        $pixmap = &new GtkPixmap($this->_pixmaps[$icon][0],$this->_pixmaps[$icon][1]);
        $vbox->pack_start( $pixmap, true  , true  , 2);
        $vbox->pack_end($child,true,true,2);
        $widget->add($vbox);
        //$widget->set_usize(150,100);
        $vbox->show();
        $pixmap->show();
     
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
            echo "loading {$dir}/{$file}";
            $this->_pixmaps[$file] =  
                Gdk::pixmap_create_from_xpm($window->window, NULL, "{$dir}/{$file}");
                
        }
    }
    
    var $_logo_pixmap; // the base to draw pixmaps onto...
    
    
    
    function _callbackInstallerLogoConfigure($widget,$event) {
        echo "Configure event";
        //if (@$this->_logo_pixmap) return;
        
        $this->_logo_pixmap = &new GdkPixmap($widget->window,
           $widget->allocation->width,
							$widget->allocation->height, 
            -1);
	 
        $current_style=$widget->get_style();
        $new_style = $current_style->copy(); 
        $new_style->bg[GTK_STATE_NORMAL]=new GdkColor('#339900'); 
        $new_style->bg[GTK_STATE_NORMAL]=new GdkColor('#339900'); 
        $widget->set_style($new_style);
        
        gdk::draw_rectangle($this->_logo_pixmap,
            $widget->style->bg_gc[GTK_STATE_NORMAL],
            true, 0, 0,
            $widget->allocation->width,
            $widget->allocation->height);
        // draw somethin on it.
        //$this->_initPixmaps($widget);
          
        //print_r(array($this->_logo_pixmap,$this->_pixmaps));
        gdk::draw_pixmap($this->_logo_pixmap,
            $widget->style->fg_gc[$widget->state],
            $this->_pixmaps['installer_logo.xpm'][0],
            0,0,
            0,0,
            486, 48);
            
        $widget->realize();  
        return true;
    }                  
    
        
    
    
    
    function _callbackInstallerLogoExpose($widget,$event) {
         echo "Expose event"; 
       
        gdk::draw_pixmap($widget->window,
            $widget->style->fg_gc[$widget->state],
            $this->_logo_pixmap,
            $event->area->x, $event->area->y,
            $event->area->x, $event->area->y,
            $event->area->width, $event->area->height);
        return false;
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
        echo "ERROR:";
        
    }

    // }}}
    // {{{ displayFatalError(eobj)

    function displayFatalError($eobj)
    {
        echo "FATAL ERROR";
        echo serialize($eobj);
        exit;
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
