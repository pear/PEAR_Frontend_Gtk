<?
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
  | Author: Alan Knowles <alan@akbkhome.com>                             |
  +----------------------------------------------------------------------+

  $Id$
*/

/**
 * Gtk Frontend - Section that deals displaying summary details about a class
 * 
 * #TODO : make the textbox display more information in a 'friendlier way'
 *
 *
 * @author Alan Knowles <alan@akbkhome.com>
 */


class PEAR_Frontend_Gtk_Summary {

    var $ui; // main interface
    var $widget; // the list widget
    var $config; // reference to config;
    var $active_package=""; // currently selected package
    
    function PEAR_Frontend_Gtk_Summary(&$ui) {
        $this->ui = &$ui;
        $this->ui->_widget_close_details->connect_object('pressed',array(&$this,'hide'));
    }
    /*
    
    package info looks like this!
    
     [packageid] => 22
    [categoryid] => 22
    [category] => XML
    [license] => PHP License
    [summary] => RSS parser
    [description] => Parser for Resource Description Framework (RDF) Site Summary (RSS)
documents.
    [lead] => mj
    [stable] => 0.9.1

    
    */
    /*
    * is the details tab visable
    */
    
    var $_VisablePackageName = '';
    /*
    * show the details tab
    */
    function show(&$package_info) {
        $this->ui->_widget_details_area->show();
        $this->active_package = $package_info;
        //$this->ui->_widget_install->set_sensitive(1);
        foreach($package_info as $k=>$v)  {
            $v = str_replace("\r", '',$v);
            $var = "_widget_".strtolower($k);
            if (!is_object(@$this->ui->$var)) continue;
            $w = &$this->ui->$var;
            switch (get_class($w)) {
                case  'GtkLabel':
                case  'GtkEntry':
                    $w->set_text($v);
                    break;
                case 'GtkText':
                    $w->delete_text(0,-1);
                    $w->insert_text($v,0);
                    break;

            }
        }
        $this->_detailsVisableFlag = $package_info['package'];
    
    }
    
    function toggle($package_info) {
        if ($this->_detailsVisableFlag != $package_info['package']) {
            $this->show($package_info);
            return;
        }
        $this->hide();
    }
            
        
    
    
    function hide() {
        $this->ui->_widget_details_area->hide();
        $this->_detailsVisableFlag = '';
    
    }
    
    /*
    * Install callback
    */
    function _callbackInstall() {
        $ui = 'Gtk';
        $this->installer = &new PEAR_Installer($ui);
        $options = array();
        $info = $this->installer->install($this->active_package['package'], $options , $this->ui->config);
        // refresh list?
        $this->ui->_packages->loadList();
        $this->ui->_packages->selectPackage($this->active_package['package']);
    }
    
    
    
    
     
     
}
?>