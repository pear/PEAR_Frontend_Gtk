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
 * Gtk Frontend -This class deals with the installing of packages
 * 
 * #TODO : make the textbox display more information in a 'friendlier way'
 *
 *
 * @author Alan Knowles <alan@akbkhome.com>
 */


class PEAR_Frontend_Gtk_Install {

    var $ui; // main interface
    
    
    function PEAR_Frontend_Gtk_Install(&$ui) {
        // connect buttons?
        $this->ui = &$ui;
        $this->ui->_widget_download_list->set_column_auto_resize(0,TRUE);
        $this->ui->_widget_download_list->set_column_auto_resize(1,TRUE);
        $this->ui->_widget_download_list->set_column_auto_resize(2,TRUE);
        $this->ui->_widget_done_button->connect('pressed',array(&$this,'_callbackDone'));
    }
    
    function start($list) {
        
        $this->ui->_widget_pages->set_page(1);
        $this->ui->_widget_done_button->set_sensitive(0);
        // load up the list into the download list..
        $this->ui->_widget_download_list->clear();
        $i=0;
        $install = array();
        if ($list) foreach ($list as $package) {
            print_r($package);
            $this->ui->_widget_download_list->append(array('',$package['package'],$package['summary']));
            $this->ui->_widget_download_list->set_pixmap($i,0,
                $this->ui->_pixmaps['package.xpm'][0],
                $this->ui->_pixmaps['package.xpm'][1]
            );
            $i++;
            $install[] = $package['package'];
        }
        while(gtk::events_pending()) gtk::main_iteration();
        $this->ui->_downloadTotal = $i;
        
        $this->ui->_downloadPos=0;        
        $cmd = PEAR_Command::factory('install',$this->ui->config);
        $cmd->run('install' ,'', $install);
        $this->ui->_widget_done_button->set_sensitive(1);
        
        
    }
    function _callbackDone() {
        $this->ui->_package_list->loadList();
        $this->ui->_widget_pages->set_page(0);
    }
   
    
}


?>