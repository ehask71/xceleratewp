<?php
if( defined('SUNRISE') ) {

	//if domain mapping is on lets add the appropriate actions
	add_action('admin_init','wpe_api_domain_manage');

	/*
 	* Domain Mapping Integration
 	*/
	function wpe_api_domain_manage($domain) {

		//make sure we're on a domain mapping plugin
		if( $_GET['page'] == 'dm_domains_admin' OR $_GET['page'] == 'domainmapping') {
			
			//don't do anything if we're editing		
			if($_POST['action'] == 'edit') {
				
				//maybe oneday we'll do something
			
			//save or add the domain	
			} elseif(!empty($_POST) AND ( $_POST['action'] == 'save' OR $_POST['action'] == 'add') ) {

				//validate the referrer
				check_admin_referer('domain_mapping');
				
				//load the api class
				include_once(WPE_PLUGIN_DIR.'/class.wpeapi.php');
				$api = new WpeAPI();

				if( preg_match('/(\;|\,|\?)/',$_POST['domain']) OR !preg_match('/[A-z]|[1-9]|\./',$_POST['domain']) ) {

					$api->set_notice("The domain you entered was not valid.");
					if($api->is_error()) {
						unset($_POST);
					}
					
				} else {
					
					//set the method and domain
					$api->set_arg('method','domain');
					$api->set_arg('domain',$_POST['domain']);
					
					//do the api request and send the reponse to the admin screen
					
					$api->get()->set_notice();

					
				}
			
			//delete a domain
			} elseif( $_REQUEST['action'] == 'delete' OR $_REQUEST['action'] == 'del' ) {
				
				//check_admin_referer('domain_mapping');
				//load the api class
				include_once(WPE_PLUGIN_DIR.'/class.wpeapi.php');
				$api = new WpeAPI();
				
				//set the method and domain
				$api->set_arg('method','domain-remove');
				$api->set_arg('domain',$_REQUEST['domain']);
			
				//do the api request and send the reponse to the admin screen
				$api->get()->set_notice();
				error_log('del:'.var_export($api,true));
			} 
				
		}
	} 
}
?>
