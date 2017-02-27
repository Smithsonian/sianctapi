<div class="edan-records edan-records-event">
<?php
$event_count = count($content);
?>
<div class="event_count"><?php echo $event_count; ?> exhibitions</div>
<?php
foreach($content as $record_id => $record):
//dpm($record); // this will show all of the available records and their fields

$exhibition_link = l($title, '/event/' . $record_id);
?>
  <div class="event-status"><?php echo(isset($content['status_text']) ? $content['status_text'] : ''); ?></div>

  <div class="edan-record edan-record-event">
  <div class="event-contact">
    <div class="event-title"><?php echo($exhibition_link); ?></div>
  </div>
</div>
<?php endforeach; ?>
</div>