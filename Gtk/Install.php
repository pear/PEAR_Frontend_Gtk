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
 * #TODO : Add remove methods, move to new 'InstallQueue/RemoveQueue API'
 *
 * @author Alan Knowles <alan@akbkhome.com>
 */


class PEAR_Frontend_Gtk_Install {
    /**
    * The Main User interface object
    * @var object PEAR_Frontend_Gtk
    */
    var $ui; // main interface
    
    /*
    * Gtk Installer Constructor
    *
    * #TODO: most of this can be moved to the glade file!?
    * @param object PEAR_Frontend_Gtk
    */
    
    function PEAR_Frontend_Gtk_Install(&$ui) {
        // connect buttons?
        $this->ui = &$ui;
        $this->ui->_widget_download_list->set_column_auto_resize(0,TRUE);
        $this->ui->_widget_download_list->set_column_auto_resize(1,TRUE);
        $this->ui->_widget_download_list->set_column_auto_resize(2,TRUE);
        $this->ui->_widget_done_button->connect('pressed',array(&$this,'_callbackDone'));
    }
    /* 
    * Start the download process (recievs a list of package 'associative arrays'
    * #TODO : recieve list of package objects to install/remove!
    *
    */
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
    /* 
    * GUI Callback - user presses the 'done button' 
    */
    function _callbackDone() {
        $this->ui->_package_list->loadList();
        $this->ui->_widget_pages->set_page(0);
    }
    /**
    * size of current file being downloaded
    * @var int
    * @access private
    */
    var $_activeDownloadSize =0;
    /**
    * Total number of files that are being downloaded
    * @var int
    * @access private
    */
    
    var $_downloadTotal=1;
    /**
    * How many files have been downloaded in this 'session'
    * @var int
    * @access private
    */
    var $_downloadPos=0;
    
    /*
    * PEAR_Command Callback (relayed) - used by downloader
    * @param string message type
    * @param string message data
    */
    
    function _downloadCallback($msg,  $params) {
        
        switch ($msg) {
            case 'setup':
                return;
                
            case 'saveas':
                $this->ui->_widget_downloading_filename->set_text("Downloading {$params}");
                $this->ui->_widget_downloading_total_progressbar->set_percentage(
                    (float) ($this->_downloadPos/$this->_downloadTotal));
                $this->_downloadPos++; 
                $this->ui->_widget_downloading_total->set_text("Total {$this->_downloadPos}/{$this->_downloadTotal}");
                while(gtk::events_pending()) gtk::main_iteration();
                return;
                
            case 'start':
                $this->_activeDownloadSize = $params;
                $this->ui->_widget_downloading_file_progressbar->set_percentage(0);
                while(gtk::events_pending()) gtk::main_iteration();
                return;
                
            case 'bytesread':
                $this->ui->_widget_downloading_file_progressbar->set_percentage(
                    (float) ($params / $this->_activeDownloadSize));
                while(gtk::events_pending()) gtk::main_iteration();
                return;
                
            case 'done':
                $this->ui->_widget_downloading_total_progressbar->set_percentage(
                    (float) ($this->_downloadPos/$this->_downloadTotal));
               
            default: // debug - what calls this?
                if (is_object($params)) $params="OBJECT";
                echo "MSG: $msg ". serialize($params) . "\n";
        }
    }
    

}


?>