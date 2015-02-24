** Purpose **

In ExpressionEngine Admion, when you save an entry, it will copy the current site's pages URI's to other sites in MSM that you specify.
This will make the Page URIs from one site work on another site. 

** Instructions ** 

Drag and drop madison_pagessync folder into your third_party folder. 
Edit the file ext.madison_pagesync.php and change the following line: 

var $settings				= array('sites_ids_to_update' => array( 3 ) );

Include the IDs of the sites you want to update. You can find the IDs of the sites in MSM "Edit Sites" screen. So for example: 

var $settings				= array('sites_ids_to_update' => array( 2,3 ) );