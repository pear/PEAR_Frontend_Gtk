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
         
        //$this->Frontend_Gtk->_widget_package_list_holder->add($this->widget);
        //$this->Frontend_Gtk->_widget_install->set_sensitive(0);
        //$this->Frontend_Gtk->_widget_remove->set_sensitive(0);
        //$this->Frontend_Gtk->_widget_getcvs->set_sensitive(0);
        //$this->Frontend_Gtk->_widget_install->connect_object('pressed',array(&$this,'_callbackInstall'));
        //$this->Frontend_Gtk->_widget_remove->connect_object('pressed',array(&$this,'_callbackRemove'));
        //$this->Frontend_Gtk->_widget_getcvs->connect_object('pressed',array(&$this,'_callbackGetCvs'));
          
        
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
    function show(&$package_info) {
        $this->Frontend_Gtk->_widget_details_area->show();
        $this->active_package = $package_info;
        //$this->Frontend_Gtk->_widget_install->set_sensitive(1);
        foreach($package_info as $k=>$v)  {
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
    
    }
    
    function hide() {
         $this->Frontend_Gtk->_widget_details_area->hide();
    
    
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
