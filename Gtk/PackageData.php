<?php

/*
* Experimental Package Data Class - used to manage state information 
* on the installer.
* 
*
*/


class PEAR_Frontend_Gtk_PackageData {
    
    var $ui; // the User interface object
    /* data from remote-list */
    var $name;               // name  -- wel actually not there, but lets use it anyway
    var $category = "";      // eg. XML
    var $license = "";       //eg.  PHP License
    var $summary = "";       // eg. RSS parser
    var $description = "";   // eg. Parser for Resource Description ....
    var $lead  =  "";        // alan_k
    var $stable =  "";  // eg. 0.9.1 version on the main server!

    /* data from local list */
    
    var $filelist;       // File Objects:
                            // name
                            // role
                            // md5sum
                            // installed_as
                            
    var $maintainers;   // Maintainer Objects
                            //handle
                            //name
                            //email
                            //role
    var $version;       // Installed version eg. 1.1
    var $release_date;  // eg. 2002-05-16
    var $release_licence; //eg. PHP 2.0.2
    var $release_state; // eg. stable
    var $release_notes; // 
    var $changelog;     // Changelog Objects
                            // version
                            // release_date
                            // release_state
                            // release_notes
    var $_lastmodified; //
                            

    /* data from installer */
    var $isInstalled;    // is it installed
    var $QueueInstall = FALSE;
    var $QueueRemove = FALSE; 
    
    /* data not available yet!!! */
    var $dependancies;   // list of packages that this depends on.
    var $gtknode;         // gtk node for this package
    
    function &staticNewFromArray($array) {
        
        $t = new PEAR_Frontend_Gtk_PackageData;
        foreach($array as $k=>$v) {
             
             //echo "SETTING $k to $v\n";
            $t->$k = $v;
        }
        return $t;
    }
    
    
    function merge($object) {
        foreach(get_object_vars($object) as $k=>$v) {
            if (!$v) continue;
            //echo "SET $k -> $v\n";
            $this->$k = $v;
        }
    }
    /*
    * create Node
    *
    * creates a node  - current format is:
    * Name (Tree), Trash, Installed Version, Latest Version, Add, Info Icon, Summary
    *
    *
    * @params object GtkNode Parent in tree
    */
    
    function createNode(&$parent) {
        
        $this->gtknode = $this->ui->_packages->widget->insert_node(
            $parent, NULL, //parent, sibling
            array($this->name, '','','' ),
            5,   
            $this->ui->_pixmaps['package.xpm'][0],
            $this->ui->_pixmaps['package.xpm'][1],  
            $this->ui->_pixmaps['package.xpm'][0],
            $this->ui->_pixmaps['package.xpm'][1],
            false,true
        );
        $this->_setIcon(3,'info_icon.xpm',$this->summary);
        $this->ui->_packages->widget->node_set_row_data( $this->gtknode, $this->name);
        $this->_showDelete();
        $this->_showInstall();
    }
    
    function _setIcon($col,$name,$string='') {
        $ui = PEAR_Command::getFrontendObject();
        $this->ui->_packages->widget->node_set_pixtext(
            $this->gtknode, $col,$string,0,
            $this->ui->_pixmaps[$name][0],
            $this->ui->_pixmaps[$name][1]
        );        
    }
    
    function _showDelete() {
        if (!$this->version) return;
        
        $icon = "stock_delete-outline-16.xpm";
        if ($this->QueueRemove)
            $icon = "stock_delete-16.xpm";
        $this->_setIcon(1,$icon,' '.$this->version);   
    }
    
    function _showInstall() {
        if ($this->version == $this->stable) return;
     
        //foreach(get_object_vars($this) as $k=>$v) echo "$k=>$v\n";
        $icon = "check_no.xpm";
        if ($this->QueueInstall)
            $icon = "check_yes.xpm";
        $this->_setIcon(2,$icon, ' '.$this->stable);   
    }
    function toggleRemove() {
        if (!$this->version) return;

        $this->QueueRemove = !$this->QueueRemove;
        if ($this->QueueRemove) 
            $this->QueueInstall = FALSE;
        
        $this->_showDelete();
        $this->_showInstall();
        
    }
    function toggleInstall() {
        if ($this->version == $this->stable) return;
        
        $this->QueueInstall = !$this->QueueInstall;
        if ($this->QueueInstall)
            $this->QueueRemove = FALSE;
        $this->_showInstall();
        $this->_showDelete();
    }
    
    function doQueue() {
        $cmd = PEAR_Command::factory('install',$this->ui->config);
        if ($this->QueueInstall && !$this->isInstalled)  {
            $cmd->run('install' ,'', array($this->name));
            return;
        } else if ($this->QueueInstall) {
            $cmd->run('upgrade' ,'', array($this->name));
            return;
        }
        if (!$this->QueueRemove) return;
        $cmd->run('uninstall' ,'', array($this->name));
        
    }
    
}
?>