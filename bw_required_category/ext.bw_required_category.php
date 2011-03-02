<?php

/**
* @package ExpressionEngine
* @author Wouter Vervloet
* @copyright  Copyright (c) 2010, Baseworks
* @license    http://creativecommons.org/licenses/by-sa/3.0/
* 
* This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
* To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/
* or send a letter to Creative Commons, 171 Second Street, Suite 300,
* San Francisco, California, 94105, USA.
* 
*/

if ( ! defined('EXT')) { exit('Invalid file request'); }

class Bw_required_category_ext
{
  public $settings             = array();
  
  public $name                 = 'BW Required Category';
  public $version              = '0.9.1';
  public $description          = "Makes categories required for specified channels";
  public $settings_exist       = 'y';
  public $docs_url             = 'http://www.baseworks.nl/';
  
  public $site_id              = 1;
  public $enabled_channels     = array();
			
	// -------------------------------
	// Constructor
	// -------------------------------
	function Bw_required_category_ext($settings=array())
	{
	  $this->__construct($settings);
	}
	
	function __construct($settings=array())
	{
	  
	  /** -------------------------------------
    /**  Get global instance
    /** -------------------------------------*/
    $this->EE =& get_instance();
	  
	  $this->site_id = $this->EE->config->item('site_id');
	  
		$this->settings = $settings;

		if( isset($this->settings[$this->site_id]) )
		{
  		foreach( $this->settings[$this->site_id] as $channel => $enabled )
  		{
  		  if( $enabled )
          $this->enabled_channels[] = $channel;  		    
  		}
		  
		}
		
	}
	// END Bw_required_category_ext
	
	
  /**
  * 
  */
  function check_category_presence($channel_id=0, $autosave=FALSE)
  {
          
    if( ! $channel_id || $autosave === TRUE ) return;

    if( ! in_array($channel_id, $this->enabled_channels) ) return;

  	$this->EE->lang->loadfile('bw_required_category');
        
    if( ! isset($this->EE->api_channel_entries->data['category']) )
    {
      $this->EE->javascript->output('$.ee_notice("'.$this->EE->lang->line('bw_forgot_category').'", {type : "error"})');
			$this->EE->api_channel_entries->_set_error('bw_forgot_category', 'category');
			$this->end_script = TRUE;
    }

  }
  // END check_category_presence    
  
  /**
  *
  */
  function settings_form($current)
  {
    
  	$this->EE->load->helper('form');
  	$this->EE->load->library('table');
  	
    $vars = array();

    $channels = $this->EE->db->get('channels');
    
    if($channels->num_rows() > 0)
    {
      foreach($channels->result() as $channel)
      {
        $vars['channels'][] = array(
          'channel_id' => $channel->channel_id,
          'channel_title' => $channel->channel_title,
          'enabled' => (isset($current[$this->site_id][$channel->channel_id])) ? $current[$this->site_id][$channel->channel_id] : FALSE
        );
      }
      
    }
        
    return $this->EE->load->view('settings_form', $vars, TRUE);
   
  }
  // END settings_form


  /**
  *
  */
  function save_settings()
  {
    
    if (empty($_POST))
  	{
  		show_error($this->EE->lang->line('unauthorized_access'));
  	}
  	
  	unset($_POST['submit']);

  	$channels = $this->EE->db->get('channels');

  	$current = unserialize($this->EE->db->select('settings')->where('class', __CLASS__)->get('extensions')->row('settings'));
    $settings = $current;

  	foreach($channels->result() as $channel)
  	{
  	  $settings[$this->site_id][$channel->channel_id] = (isset($_POST[$channel->channel_id])) ? TRUE : FALSE;
  	}
  	
    $this->EE->db->where('class', __CLASS__)->update('extensions', array('settings' => serialize($settings)));
    
    $this->EE->session->set_flashdata(
  		'message_success',
  	 	$this->EE->lang->line('preferences_updated')
  	);
   
  }
  // END settings_form

	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
	  
    $settings = array();
    
    $sites = $this->EE->db->get('sites');	  
	  $channels = $this->EE->db->get('channels');

	  if($channels->num_rows() > 0)
	  {
	    foreach($sites->result() as $site)
	    {
  	    foreach ($channels->result() as $channel)
  	    {
  	      $settings[$site->site_id][$channel->channel_id] = FALSE;
  	    }	      
	    }
	  }

    // data to insert
    $data = array(
      'class'		=> get_class($this),
      'method'	=> 'check_category_presence',
      'hook'		=> 'entry_submission_start',
      'priority'	=> 1,
      'version'	=> $this->version,
      'enabled'	=> 'y',
      'settings'	=> serialize($settings)
    );

    // insert in database
    $this->EE->db->insert('exp_extensions', $data);

    return TRUE;
	}
	// END activate_extension
	 
	 
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
  }
  // END update_extension

	// --------------------------------
	//  Disable Extension
	// --------------------------------
	function disable_extension()
	{		
    // Delete records
    $this->EE->db->where('class', get_class($this));
    $this->EE->db->delete('exp_extensions');
  }
  // END disable_extension

	 
}
// END CLASS
?>