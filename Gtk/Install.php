<?
/*
* This class deals with the installing of packages
* 
*
*/

class PEAR_Frontend_Gtk_Install {

    var $Frontend_Gtk; // main interface
    
    
    function init() {
        // connect buttons?
    
        $this->Frontend_Gtk->_widget_download_list->set_column_auto_resize(0,TRUE);
        $this->Frontend_Gtk->_widget_download_list->set_column_auto_resize(1,TRUE);
        $this->Frontend_Gtk->_widget_download_list->set_column_auto_resize(2,TRUE);
    }
    
    function start($list) {
        $this->Frontend_Gtk->_widget_pages->set_page(1);
        // load up the list into the download list..
        $this->Frontend_Gtk->_widget_download_list->clear();
        $i=0;
        foreach ($list as $package) {
            $this->Frontend_Gtk->_widget_download_list->append(array('',$package['package'],$package['summary']));
            $this->Frontend_Gtk->_widget_download_list->set_pixmap($i,0,
                $this->Frontend_Gtk->_pixmaps['package.xpm'][0],
                $this->Frontend_Gtk->_pixmaps['package.xpm'][1]
            );
            $i++;
        }
        
    }
    
}


?>