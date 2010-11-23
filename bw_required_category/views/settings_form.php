<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=bw_required_category');?>

<?php 
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('channel'), 'style' => 'width:50%;'),
    lang('bw_enabled')
);

foreach ($channels as $channel)
{
	$this->table->add_row($channel['channel_title'], form_checkbox($channel['channel_id'], 'enabled', $channel['enabled']));
}

echo $this->table->generate();

?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>