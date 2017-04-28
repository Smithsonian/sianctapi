<div class="edan-records edan-records-block edan-records-museum">
<?php
//dpm($content);
foreach($content as $record_id => $record):
//dpm($record); // this will show all of the available records and their fields
$title = array_key_exists('unit', $record) ? $record['unit'] : '';

$logo_html = '';
$logo_src = array_key_exists('logo', $record) ? $record['logo'] : '';

// support legacy:
if(strlen($logo_src) == 0) {
	$logo_src = array_key_exists('image', $record) ? $record['image'] : '';
}
if(strlen($logo_src) > 0) {
  $logo_html = '<img src="' . $logo_src . '" alt="' . $title . ' icon" />';
}
$museum_link = l($title, $record['local_path']);

?>
	<div class="museum-phone"><?php echo(isset($content['phone']) ? $content['phone'] : ''); ?></div>

		<div class="edan-record edan-record-museum">
		<div class="museum-contact">
			<div class="museum-icon icon"><?php echo $logo_html; ?></div>
			<div class="museum-title"><?php echo($museum_link); ?></div>
	
		</div>
	</div>
<?php endforeach; ?>
</div>