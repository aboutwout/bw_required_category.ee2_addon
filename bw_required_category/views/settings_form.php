<?=form_open('C=addons_extensions'.AMP.'M=save_extension_settings'.AMP.'file=bw_required_category');?>

<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('channel'), 'style' => 'width:50%;'),
    lang('bw_enabled')
);


foreach ($channels as $channel)
{
	$category_groups = '';
	foreach ($channel['category_groups'] as $cat_group) {
		if (isset ($channel['enabled']) && in_array($cat_group['group_id'], $channel['enabled']))
		{
			$enabled = 'checked="checked"';
		} else {
			$enabled = '';
		}

		$category_groups .= '<p>
		<input type="checkbox" name="cat_group['.$channel['channel_id'].'][]" id="cat_group_'.$channel['channel_id'].'_'.$cat_group['group_id'].'" value="'.$cat_group['group_id'].'" '.$enabled.'" />
		&nbsp;<label for="cat_group_'.$channel['channel_id'].'_'.$cat_group['group_id'].'">'.$cat_group['group_name'].'</label>
		</p>';
	}

	$this->table->add_row(
							$channel['channel_title'],
							$category_groups

	);
}

echo $this->table->generate();

?>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
<?php $this->table->clear()?>
<?=form_close()?>