<?
/*
* This class deals with the installing of packages
* Currently it extneds the installer so that it can do the download mettering
*
*/

class PEAR_Frontend_Gtk_Install {

    var $Frontend_Gtk; // main interface
    
    
    function init() {
        // connect buttons?
    
        $this->Frontend_Gtk->_widget_download_list->set_column_auto_resize(0,TRUE);
        $this->Frontend_Gtk->_widget_download_list->set_column_auto_resize(1,TRUE);
        $this->Frontend_Gtk->_widget_download_list->set_column_auto_resize(2,TRUE);
        $this->Frontend_Gtk->_widget_done_button->connect('pressed',array(&$this,'_callbackDone'));
    }
    
    function start($list) {
        
        $this->Frontend_Gtk->_widget_pages->set_page(1);
        $this->Frontend_Gtk->_widget_done_button->set_sensitive(0);
        // load up the list into the download list..
        $this->Frontend_Gtk->_widget_download_list->clear();
        $i=0;
        $install = array();
        if ($list) foreach ($list as $package) {
            print_r($package);
            $this->Frontend_Gtk->_widget_download_list->append(array('',$package['package'],$package['summary']));
            $this->Frontend_Gtk->_widget_download_list->set_pixmap($i,0,
                $this->Frontend_Gtk->_pixmaps['package.xpm'][0],
                $this->Frontend_Gtk->_pixmaps['package.xpm'][1]
            );
            $i++;
            $install[] = $package['package'];
        }
        while(gtk::events_pending()) gtk::main_iteration();
        $this->Frontend_Gtk->_downloadTotal = $i;
        
        $this->Frontend_Gtk->_downloadPos=0;        
        $cmd = PEAR_Command::factory('install',$this->Frontend_Gtk->config);
        $cmd->run('install' ,'', $install);
        $this->Frontend_Gtk->_widget_done_button->set_sensitive(1);
        
        
    }
    function _callbackDone() {
        $this->Frontend_Gtk->_package_list->loadList();
        $this->Frontend_Gtk->_widget_pages->set_page(0);
    }
   
    
}


?>