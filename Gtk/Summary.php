<?
/*
* This class deals with the list of packages 
* - select one and it passes on data to the summary class
*
*/

class PEAR_Frontend_Gtk_Summary {

    var $Frontend_Gtk; // main interface
    var $widget; // the list widget
    var $config; // reference to config;
    var $active_package=""; // currently selected package
    
    function init() {
        
        $this->Frontend_Gtk->_widget_close_details->connect_object('pressed',array(&$this,'hide'));
   
          
        
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
        $this->Frontend_Gtk->_widget_details_area->show();
        $this->active_package = $package_info;
        //$this->Frontend_Gtk->_widget_install->set_sensitive(1);
        foreach($package_info as $k=>$v)  {
            $v = str_replace("\r", '',$v);
            $var = "_widget_".strtolower($k);
            if (!is_object(@$this->Frontend_Gtk->$var)) continue;
            $w = &$this->Frontend_Gtk->$var;
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
        $this->Frontend_Gtk->_widget_details_area->hide();
        $this->_detailsVisableFlag = '';
    
    }
    
    /*
    * Install callback
    */
    function _callbackInstall() {
        $ui = 'Gtk';
        $this->installer = &new PEAR_Installer($ui);
        $options = array();
        $info = $this->installer->install($this->active_package['package'], $options , $this->Frontend_Gtk->config);
        // refresh list?
        $this->Frontend_Gtk->_package_list->loadList();
        $this->Frontend_Gtk->_package_list->selectPackage($this->active_package['package']);
    }
    
    
    
    
     
     
}
?>
