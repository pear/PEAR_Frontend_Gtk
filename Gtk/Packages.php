<?
/*
* This class deals with the list of packages 
* - select one and it passes on data to the summary class
*
*/

class PEAR_Frontend_Gtk_Packages {

    var $Frontend_Gtk; // main interface
    var $widget; // the list widget
    var $config; // reference to config;
    
    
    function init() {
        
        $this->_loadPackageData();
        $cols = array('Package','Version','','','Summary');
        $this->widget = &new GtkCTree(5,0,$cols);
        $this->widget->set_usize(300,20);  
        $this->widget->set_selection_mode(GTK_SELECTION_BROWSE);
        $this->widget->set_expander_style(GTK_CTREE_EXPANDER_TRIANGLE);
        $this->widget->set_line_style( GTK_CTREE_LINES_NONE);
        $this->widget->connect_after('realize', array(&$this, '_callbackRealize'));
        $this->widget->connect_object('tree-select-row', array(&$this, '_callbackSelectRow'));
        foreach($cols as $i=>$name) 
            $this->widget->set_column_auto_resize($i,TRUE);
        
        $this->widget->show();
        $this->Frontend_Gtk->_widget_package_list_holder->add($this->widget);
        
        $this->Frontend_Gtk->_widget_packages_install->connect('pressed',array(&$this,'_callbackInstall'));
        
    }
    
    function _loadPackageData() {
        $reg = new PEAR_Registry($this->Frontend_Gtk->config->get('php_dir'));
        $installed = $reg->packageInfo();
       
        
        
        foreach ($installed as $i=>$package) 
            $this->_package_status[$package['package']] = &$installed[$i];
            
        
        // get available
        if (!$this->_available_packages) {
            $r = new PEAR_Remote($this->Frontend_Gtk->config);
            $this->_available_packages = $r->call('package.listAll', true);
        }
        // merge available
        foreach ($this->_available_packages as  $name => $info) {
            // installed already?
            $info['package'] = $name;
            if ($this->_package_status[$name]) {
                // newer
                $v = $this->_package_status[$name]['version'];
                $this->_package_status[$name] = $info;
                $this->_package_status[$name]['version'] = $v; 
                $this->_package_status[$name]['installed'] = 1;    
                $this->_package_status[$name]['install'] = TRUE;
                if (@$this->_package_status[$name]['version'] < $info['stable']) {
                    // blank out the 'stable' column (as it is the same!
                    $this->_package_status[$name]['install'] = FALSE;
                }
            } else {
                $this->_package_status[$name] = $info;
            }
        }
        ksort($this->_package_status);
        echo "FINISHED LOADING DATA";
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
    
    
    var $_package_status; // associative array of packagename => package info
    var $_available_packages = array(); // result of rpc call
    
    function _callbackRealize($widget) {
        
        $this->_initPixmaps($widget);
        $this->loadList();
    }
        
        
    function loadList() {
        //while(gtk::events_pending()) gtk::main_iteration();
        $this->widget->clear();
        $this->widget->freeze();
        
        
        
         
        $this->_nodes = array();    
        
        foreach($this->_package_status as $name => $info) {
            
            $ar = explode('_',$name);
            $s = trim($ar[0]);
            $parent = NULL;
            if ($s && @$this->_nodes[$s]) 
                $parent =  $this->_nodes[$s];
                
            if ($s && !$parent && ($ar[0] != $name)) {
                // make a parent node
                
                echo "making node $s";
                $this->_nodes[$s] = $this->widget->insert_node(
                        NULL, NULL, //parent, sibling
                        array($s, '','','','',''),
                        5,    $this->_pixmaps['folder_closed.xpm'][0],
                              $this->_pixmaps['folder_closed.xpm'][1],  
                              $this->_pixmaps['folder_open.xpm'][0],
                              $this->_pixmaps['folder_open.xpm'][1],
                              false,true); 
                
                $parent = $this->_nodes[$s];
            } 
            
            $showversion = $info['stable'];
            if ($info['version'] && ($info['version'] != $info['stable']))
                $showversion = "New:".$info['stable'];
            //    $showversion = "**".$info['version'] . "->".$info['stable']. "**";
            
            $this->_nodes[$name] = $this->widget->insert_node(
                    $parent, NULL, //parent, sibling
                    array(
                        $name, 
                        $showversion,
                        '',
                        '',
                        $info['summary'] 
                      
                    ),
                    5,   
                    $this->_pixmaps['package.xpm'][0],
                    $this->_pixmaps['package.xpm'][1],  
                    $this->_pixmaps['package.xpm'][0],
                    $this->_pixmaps['package.xpm'][1],
                    false,true);
                    
          
                    
            $this->widget->node_set_pixtext(
                    $this->_nodes[$name], 3,'',0,
                    $this->_pixmaps['info_icon.xpm'][0],
                    $this->_pixmaps['info_icon.xpm'][1]
            );        
                    
                    
            $this->widget->node_set_row_data( $this->_nodes[$name], $name);
            $this->_setPackageStatus($this->_nodes[$name], @$this->_package_status[$name]['install']);
        }
        $this->widget->thaw();
      
    }
    
    /*
    * Array of pixmaps
    *   each pixmap object looks like
    *  $pixmap->closed,  $pixmap->mask_closed,
    *  $pixmap->open,    $pixmap->mask_open,
    *
    * @var array of StdClass objects
    *
    */
    var $pixmaps; 
     
    /*
    * initialize the pixmaps - load the into $_pixmaps[name] ->object
    *  
    */
    function _initPixmaps(&$window) {
        
        if ($this->_pixmaps) return;
        $dir = dirname(__FILE__).'/xpm';
        $dh = opendir($dir);
        if (!$dh) return;
        while (($file = readdir($dh)) !== FALSE) {
            if (@$file{0} == '.') continue;
            if (!preg_match('/\.xpm$/',$file)) continue;
            
            $this->_pixmaps[$file] =  
                Gdk::pixmap_create_from_xpm($window->window, NULL, "{$dir}/{$file}");
                
        }
 
    }
    /*
    * call back when a row is pressed - hide/show the details and check it's isntalled status
    *  
    */
    function _callbackSelectRow($node,$col) {
        print_r(array($node,$a,$b));
        $package = $this->widget->node_get_row_data($node);
        //if (!$package) return;
        
        switch ($col) {
            case 0:
            case 1:
            case 2: // install/ toggled
            //case 4:
                 
                $this->Frontend_Gtk->_summary->hide();
                 
                if (!$package) return;
                $this->_setPackageStatus(&$node, !$this->_package_status[$package]['install']);
                
                break;
            case 3: // info selected
                if (!$package)  {
                    $this->Frontend_Gtk->_summary->hide();
                    return;
                } 
                 
                if ($this->_moveto_flag)
                    $this->widget->node_moveto($node,0,0,0);
                $this->_moveto_flag=FALSE; 
                $this->Frontend_Gtk->_summary->toggle($this->_package_status[$package]);
                break;
            case -1: // startup!
                return;
            default:
                $this->Frontend_Gtk->_summary->hide();
                break;
        }
        
        
    }
    var $_moveto_flag = FALSE;
    function selectPackage($package) {
        if (!$this->_nodes[$package]) return;
        $this->_moveto_flag=TRUE;
        $this->widget->select($this->_nodes[$package]);
        
        
    
    
    }
    
    function _setPackageStatus(&$node, $status=FALSE) {
        $status_icon = 'check_no.xpm';
        if ($status) $status_icon = 'check_yes.xpm';
        
        $this->widget->node_set_pixtext(
                    $node, 2,'',0,
                    $this->_pixmaps[$status_icon][0],
                    $this->_pixmaps[$status_icon][1]
            );  
        $package = $this->widget->node_get_row_data($node);
        $this->_package_status[$package]['install'] = $status;
    }
    
    
    
    function _callbackInstall() {
        // send the installer a list of packages to install
        // probably the command stuff eventually...
        // this method is very chessy - need to work out a more 'consistent way to handle, remove,add etc'
        $install = array();
        foreach($this->_package_status as $package) 
            if ($package['install'] & !$package['installed'])
                $install[] = $package;
                
        $this->Frontend_Gtk->_install->start($install);
        
        
        
        
    }
    
}
?>
    