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
require('PEAR/Frontend/Gtk/PackageData.php');
/**
 * Gtk Frontend - Section that deals with package lists
 *
 * @author Alan Knowles <alan@akbkhome.com>
 */


class PEAR_Frontend_Gtk_Packages {

    var $ui; // main interface
    var $widget; // the list widget
    var $config; // reference to config;
    
    
    function PEAR_Frontend_Gtk_Packages(&$ui) {
        $this->ui = &$ui;
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
        $this->ui->_widget_package_list_holder->add($this->widget);
        
        $this->ui->_widget_packages_install->connect('pressed',array(&$this,'_callbackInstall'));
        
    }
    
    function _loadPackageData() {
        $reg = new PEAR_Registry($this->ui->config->get('php_dir'));
        $installed = $reg->packageInfo();
        
        foreach ($installed as $i=>$package) 
            $this->_package_status[$package['package']] = &$installed[$i];
        
        // get available
        if (!$this->_available_packages) {
            $r = new PEAR_Remote($this->ui->config);
            $this->_available_packages = $r->call('package.listAll', true);
            if (PEAR::isError($this->_available_packages)) {
                // whats the official way to hanlde this?
                echo $this->_available_packages->message . '\n';
                exit;
                
            }
        }
      
        // merge available
        foreach ($this->_available_packages as  $name => $info) {
            // installed already?
            $info['package'] = $name;
            if (@$this->_package_status[$name]) {
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
            if (@$info['version'] && (@$info['version'] != $info['stable']))
                $showversion = "New:".$info['stable'];
            //$showversion = "**".$info['version'] . "->".$info['stable']. "**";
            
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
        
        if (@$this->_pixmaps) return;
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
        $package = $this->widget->node_get_row_data($node);
        //if (!$package) return;
        
        switch ($col) {
            case 0:
            case 1:
            case 2: // install/ toggled
            //case 4:
                 
                $this->ui->_summary->hide();
                 
                if (!$package) return;
                $this->_setPackageStatus(&$node, !$this->_package_status[$package]['install']);
                
                break;
            case 3: // info selected
                if (!$package)  {
                    $this->ui->_summary->hide();
                    return;
                } 
                 
                if ($this->_moveto_flag)
                    $this->widget->node_moveto($node,0,0,0);
                $this->_moveto_flag=FALSE; 
                $this->ui->_summary->toggle($this->_package_status[$package]);
                break;
            case -1: // startup!
                return;
            default:
                $this->ui->_summary->hide();
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
                
        $this->ui->_install->start($install);
        
        
        
        
    }
    
    
    
    /*------------------------------------- New Data stuff.. ----------------------*/
    
    var $_remotePackageCache;  // array of remote packages.
    var $_localPackageCache;   // array of local packages
    var $packages;              // associative array of packagename : package
    
    
    function PEAR_Frontend_Gtk_PackageDataCollection(&$ui) {
        $this->ui = &$ui;
        $this->loadLocalPackages();
        $this->loadRemotePackages();
        $this->mergePackages();
    }
    
    function loadLocalPackages () {
        $reg = new PEAR_Registry($this->ui->config->get('php_dir'));
        $installed = $reg->packageInfo();
        $this->_localPackagesCache = array();
        foreach($installed as $packagear) {
            $package = PEAR_Frontend_Gtk_PackageData::staticNewFromArray($packagear);
            $package->name = $package->package;
            $this->_localPackagesCache[$package->name] = $package;
        }
    }
    
    function loadRemotePackages () {
        $r = new PEAR_Remote($this->ui->config);
        $remote = $r->call('package.listAll', true);
        if (PEAR::isError($packagear)) {
            // whats the official way to hanlde this?
            echo $this->_available_packages->message . '\n';
            exit;
            
        }
        foreach ($remote as as  $name => $packagear) {
            $package = PEAR_Frontend_Gtk_PackageData::staticNewFromArray($packagear);
            $package->name = $name;
            $this->_remotemotePackageCache[] = $package;
        }
    }
    
    function mergePackages () { // builds a mreged package list
        // start with remote list.
        $newpackages[];
        foreach ($this->_remotePackageCache as $package) 
            $newpackages[$package->name] = $package;
        
        // merge local.    
        foreach ($this->_localPackageCache as $package) {
            if (@$newpackages[$package->name]) {
                $newpackages[$package->name]->merge($package);
            } else {
                $newpackages[$package->name] = $package;
            }
            $newpackages[$name]->isInstalled = TRUE;
        }
        //merge existing status stuff..
        foreach ($this->packages as $name=>$package) {
            $newpackages[$name]->QueueInstall = $package->QueueInstall;
            $newpackages[$name]->QueueRemove = $package->QueueRemove;
        }
        $this->packages = $newpackages;
    }
    
    function resetQueue() {
        foreach(array_keys($this->packages) as $packagename) {
            $this->packages[$packagname]->QueueInstall = FALSE;
            $this->packages[$packagname]->QueueRemove = FALSE;
        }
    }
    
    function &getInstallQueue() {
        $ret = array()
        foreach(array_keys($this->packages) as $packagename) {
            if (!$this->packages[$packagname]->QueueInstall) continue;
            $ret[] = &$this->packages[$packagname];
        }
        return $ret;
    
    }
    
    function &getRemoveQueue() {
        $ret = array()
        foreach(array_keys($this->packages) as $packagename) {
            if (!$this->packages[$packagname]->QueueRemove) continue;
            $ret[] = &$this->packages[$packagname];
        }
        return $ret;
    }
    
    
    
}
?>