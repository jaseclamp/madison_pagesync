<?php 

if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Madison_pagessync_ext {

	var $name					= "Madison Pages Sync";
    var $description			= "When you save an entry, if it has a page's URI, it will copy the current site's pages URI's to other sites in MSM that you specify.";
    var $settings_exist			= 'n';
    var $docs_url				= '';
    var $version				= 1;
    var $settings				= array('sites_ids_to_update' => array( 3 ) );
    var $site_id				= 1;
    var $remove_deleted_entries = false;
    
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>
	//	C O N S T R U C T O R
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>

 	function __construct(){
		$this->EE 			=& get_instance();
		$this->site_id 		= $this->EE->config->item('site_id');

    }
    
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>
	//	A C T I V A T E   E X T E N S I O N
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>

	function activate_extension(){
		
		$hooks = array(
			'entry_submission_end'	=> 'entry_submission_end'
		);
		
		foreach($hooks as $k=>$v){
			$data = array(
				'class'     => __CLASS__,
				'method'    => $v,
				'hook'      => $v,
				'settings'  => serialize($this->settings),
				'priority'  => 10,
				'version'   => $this->version,
				'enabled'   => 'y'
			);
		
			$this->EE->db->insert('extensions', $data);
		}


	}
	
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>
	//	U P D A T E   E X T E N S I O N
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>
	
	function update_extension($current = ''){
		if ($current == '' OR $current == $this->version){
			return FALSE;
		}
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update(
			'extensions',
			array('version' => $this->version)
		);
	}
	
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>
	//	D I S A B L E   E X T E N S I O N
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>
	function disable_extension(){
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}
	
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>
	//	S E T T I N G S
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>
	
	function settings(){
		$settings = array();
		
		// No settings at this time
		
		return $settings;
	}
	
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>
	//	E N T R Y   S U B M I S S I O N   E N D
	//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~>>
	
	
	function entry_submission_end($id,$meta,$data){
		
		ee()->load->library('api'); 
		ee()->api->instantiate('channel_structure');
		
		$current_site	= ee()->config->item('site_short_name');

		// Fetch all pages

		ee()->db->select('site_pages, site_name, site_id');
		ee()->db->where('site_id', $this->site_id);
		$query = ee()->db->get('sites');



		if ($query->num_rows() > 0)
		{
			//get pages for current site
			$old_pages = $query->result_array();
			//extract
			$old_pages = unserialize(base64_decode($old_pages[0]['site_pages']));
			
			//go through each other site to update and merge in current site's pages into site-to-update's pages if any
			foreach($this->settings['sites_ids_to_update'] as $site_id_to_update)
			{
				ee()->db->select('site_pages, site_name, site_id');
				ee()->db->where('site_id', $site_id_to_update);
				$query = ee()->db->get('sites');
				if ($query->num_rows() > 0) {
					$new_pages = $query->result_array();
					$new_pages = unserialize(base64_decode($new_pages[0]['site_pages']));
					$new_pages[$site_id_to_update]['uris'] = array_merge($old_pages[$this->site_id]['uris'],$new_pages[$site_id_to_update]['uris']);
					$new_pages[$site_id_to_update]['templates'] = array_merge($old_pages[$this->site_id]['templates'],$new_pages[$site_id_to_update]['templates']);
				}
				
				ee()->db->update(
						'sites',
						array('site_pages' => base64_encode(serialize($new_pages))),
						array('site_id' => $site_id_to_update)
				);
				
			}
			
		}

		// Update config

		ee()->config->set_item('site_pages', $new_pages);

		return '';
		
		return true;
		
	}


	/*	END delete */	
	

}