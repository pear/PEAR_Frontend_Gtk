<?

/*
* Experimental Package Data Class - used to manage state information 
* on the installer.
* 
*
*/


class PEAR_Frontend_Gtk_PackageData {

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
    var $gtkrow;         // gtk row that contains the row data..
    
    function staticNewFromArray($array) {
        $t = new PEAR_Frontend_Gtk_PackageData;
        foreach(array as $k=>$v)
            $t->$k = $v;
    }
}
?>