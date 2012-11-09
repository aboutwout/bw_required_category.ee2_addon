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
  public $version              = 1.1;
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
  function entry_submission_start($channel_id=0, $autosave=FALSE)
  {
    if ( ! $channel_id OR $autosave === TRUE) return;

    if ( ! in_array($channel_id, $this->enabled_channels)) return;

    // Instantiate the channel_categories API
    $this->EE->load->library('api');
    $this->EE->api->instantiate('channel_entries');

    if ($this->_check_category_presence($channel_id, $this->EE->input->post('category')) === FALSE)
    {
      // Load the bw_required_category language file
      $this->EE->lang->loadfile('bw_required_category');



      $this->EE->javascript->output('$.ee_notice("'.$this->EE->lang->line('bw_forgot_category').'", {type : "error"})');
      $this->EE->api_channel_entries->_set_error('bw_forgot_category', 'category');
      $this->end_script = TRUE;
    }

  }
  // END check_category_presence

  function safecracker_submit_entry_start($obj=NULL)
  {
    $channel_id = $obj->channel['channel_id'];
    $categories = $this->EE->input->post('category');

    if ( ! in_array($channel_id, $this->enabled_channels)) return;

    if ($this->_check_category_presence($channel_id, $categories) === FALSE)
    {
      // Load the bw_required_category language file
      $this->EE->lang->loadfile('bw_required_category');

      $obj->errors[] = $this->EE->lang->line('bw_forgot_category');
    }

  }

  function _check_category_presence($channel_id=0, $categories=array())
  {
//    debug($this->EE->api_channel_entries->data['category']);

    if ( ! $channel_id) return TRUE;

    // If channel doesn't have to be checked for categories, skip the check
    if ( ! in_array($channel_id, $this->enabled_channels)) return TRUE;


    // No way! No category selected at all
    if ( ! is_array($categories) OR count($categories) === 0)
    {
      return FALSE;
    }

    $this->EE->load->model('category_model');
    $selected_cat_groups = array();

    // For each category, get the group
    foreach ($categories as $cat) {
      $qry = $this->EE->category_model->get_category_name_group($cat);
      if ($qry->num_rows() > 0)
      {
        $group = $qry->result();
        $selected_cat_groups[] = $group[0]->group_id;
      }
    }

    $required_cat_groups = $this->settings[$this->site_id][$channel_id];

    foreach ($required_cat_groups as $req_cat_group) {
      if (! in_array($req_cat_group, $selected_cat_groups))
      {
        return FALSE;
      }
    }

    return TRUE;

  }

  /**
  *
  */
  function settings_form($current)
  {

    $this->EE->load->helper('form');
    $this->EE->load->library('table');
    $this->EE->load->model('category_model');

    $vars = array();

    $channels = $this->EE->db->where('site_id', $this->site_id)->get('channels');
    $qry = $this->EE->category_model->get_category_groups();
    $category_groups = $qry->result();

    if($channels->num_rows() > 0)
    {
      foreach($channels->result() as $channel)
      {
        // Init cat. group id and name
        $channel_cat_groups = array();

        // Get channel category groups
        $cat_groups = explode('|', $channel->cat_group);

        // For each group, get name and id
        foreach ($cat_groups as $cat_group_id)
        {
          foreach ($category_groups as $cat_group)
          {
            if ($cat_group->group_id === $cat_group_id)
            {
              $channel_cat_groups[] = array('group_id' => $cat_group->group_id, 'group_name' => $cat_group->group_name);
            }
          }
        }

        $vars['channels'][] = array(
          'channel_id' => $channel->channel_id,
          'channel_title' => $channel->channel_title,
          'enabled' => (isset($current[$this->site_id][$channel->channel_id])) ? $current[$this->site_id][$channel->channel_id] : FALSE,
          'category_groups' => $channel_cat_groups
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
      // Reset settings for the channel
      $settings[$this->site_id][$channel->channel_id] = array();

      if (isset($_POST['cat_group'][$channel->channel_id]) && $_POST['cat_group'][$channel->channel_id] !== NULL)
      {
        foreach ($_POST['cat_group'][$channel->channel_id] as $cat_group)
        {
          $settings[$this->site_id][$channel->channel_id][] = $cat_group;
        }
      }
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

    $hooks = array(
      'entry_submission_start' => 'entry_submission_start',
      'safecracker_submit_entry_start' => 'safecracker_submit_entry_start'
    );

    $this->_add_hooks($hooks, $settings);

    return TRUE;
  }
  // END activate_extension


  // --------------------------------
  //  Update Extension
  // --------------------------------
  function update_extension($current='')
  {
    if ($current < 1.0)
    {
      $where = array('class' => get_class($this), 'method' => 'check_category_presence');
      $hooks = $this->EE->db->where($where)->get('extensions');

      if ($hooks->num_rows() > 0)
      {
        // Remove the old hooks from the database just to be safe
        $this->EE->db->where('class', get_class($this))->delete('extensions');

        $settings = unserialize($hooks->row('settings'));

        $hooks = array(
          'entry_submission_start' => 'entry_submission_start',
          'safecracker_submit_entry_start' => 'safecracker_submit_entry_start'
        );


        $this->_add_hooks($hooks, $settings);

      }

    }
  }
  // END update_extension

  function _add_hooks($hooks=array(), $settings=array())
  {
    foreach ($hooks as $hook => $method)
    {
      // data to insert
      $data = array(
        'class'   => get_class($this),
        'method'  => $method,
        'hook'    => $hook,
        'priority'  => 1,
        'version' => $this->version,
        'enabled' => 'y',
        'settings'  => serialize($settings)
      );

      // insert in database
      $this->EE->db->insert('exp_extensions', $data);

    }
  }

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