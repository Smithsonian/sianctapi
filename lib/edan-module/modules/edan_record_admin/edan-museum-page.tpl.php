<?php
dpm($content); // this will show all of the available fields for the unit/museum
dpm($exhibition_content);
dpm($imax_content);
//dpm($edan_record_id);
//$title = array_key_exists('title', $content) ? $content['title'] : '';

$edit_link_text = '';
if(user_access('Administer EDAN museum records')) {
  $edit_link_text = l(t('Edit') . ' ' . $title,
    $edit_link,
    array('attributes' => array('title' => t('edit') . ' ' . $title)));
}

$logo_html = '';
$logo_src = isset($content['logo']) ? $content['logo'] : '';
if(strlen($logo_src) > 0) {
  $logo_html = '<img src="' . $logo_src . '" alt="' . $title . ' icon" />';
}
$img_html = '';
$img_src = isset($content['image']) ? $content['image'] : '';
if(strlen($img_src) > 0) {
  $img_html = '<img src="' . $img_src . '" alt="' . $title . ' image" style="max-width:100%;" />';
}
?>
<div class="edan-record edan-record-museum">
  <div class="edit-link"><?php echo $edit_link_text;?></div>
  <div class="edan-record-detail museum-detail">
    <div class="museum-icon icon"><?php echo $logo_html; ?></div>
    <div class="museum-contact">
      <div class="museum-address"><?php echo(isset($content['streetAddress']) ? $content['streetAddress'] : ''); ?></div>
      <div class="museum-address2"><?php echo(isset($content['addressRegion']) ? $content['addressRegion'] : '')
          . ' ' . (isset($content['addressLocality']) ? $content['addressLocality'] : ''); ?></div>
      <div class="museum-phone"><?php echo(isset($content['phone']) ? $content['phone'] : ''); ?></div>
      <div class="museum-hours"><?php echo(isset($content['openingHours']) ? $content['openingHours'] : ''); ?></div>
    </div>
  </div>
  <div class="museum-image">
    <div class="museum-image image"><?php echo $img_html; ?></div>
  </div>

  <?php if(count($imax_content) > 0): ?>
    <div class="imaxmovies"><h2>IMAX Films</h2>
    <?php foreach($imax_content as $k => $event_record): ?>
      <?php
      $event = $event_record['content'];
      $logo_html = '';
      $title = $event['title']['content'];
      $title_link = l($title, $event_record['local_path']);
      $logo_src = isset($event['online_media'][0][0]['thumbnail'])  && (strlen($event['online_media'][0][0]['thumbnail']) > 0)
        ? 'http://si.edu' . $event['online_media'][0][0]['thumbnail'] : '';
      if(strlen($logo_src) > 0) {
        $logo_html = '<img src="' . $logo_src . '" alt="' . $title . ' icon" />';
      }

      $exhibition_dates = array_key_exists('Open', $event) ? $event['Open']['Opening'] : '';
      $exhibition_dates .= (strlen($exhibition_dates) > 0) ? ' - ' : '';
      $exhibition_dates .= array_key_exists('Close', $event) ? $event['Close']['Closing'] : '';

      ?>
      <div class="edan-record edan-record-event edan-record-event-imax">
        <div class="edan-record-detail event-detail">
          <div class="event-title"><h3><?php echo $title_link; ?></h3></div>
          <div class="event-icon icon"><?php echo $logo_html; ?></div>
          <div class="event-description"><?php print(text_summary(drupal_html_to_text($event['description']))); ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if(count($exhibition_content) > 0): ?>
    <div class="exhibitions"><h2>Exhibitions</h2>
    <?php foreach($exhibition_content as $k => $event_record): ?>
      <?php
      $event = $event_record['content'];
      $logo_html = '';
      $title = $event['title']['content'];
      $title_link = l($title, $event_record['local_path']);
      $logo_src = isset($event['online_media']['thumbnail'])  && (strlen($event['online_media']['thumbnail']) > 0)
        ? 'http://si.edu/Content/img/Exhibitions/db/' . $event['online_media']['thumbnail'] : '';
      if(strlen($logo_src) > 0) {
        $logo_html = '<img src="' . $logo_src . '" alt="' . $title . ' icon" />';
      }

      $exhibition_dates = array_key_exists('Open', $event) ? $event['Open']['Opening'] : '';
      $exhibition_dates .= (strlen($exhibition_dates) > 0) ? ' - ' : '';
      $exhibition_dates .= array_key_exists('Close', $event) ? $event['Close']['Closing'] : '';

      ?>
      <div class="edan-record edan-record-event edan-record-event-exhibition">
        <div class="edan-record-detail event-detail">
          <div class="event-title"><h3><?php echo $title_link; ?></h3></div>
          <div class="event-dates"><?php echo $exhibition_dates; ?></div>
          <div class="event-icon icon"><?php echo $logo_html; ?></div>
          <div class="event-description"><?php print(text_summary(drupal_html_to_text($event['description']))); ?></div>
          <div class="event-status">Status: <?php echo $event['status']; ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if(isset($content['objectGroup'])): ?>
  <div class="museum-object-group">
    <div class="ogmt-og-content">
      <?php if(isset($content['objectGroup']['objectGroupImageUri'])) {
        echo('<img src="'. $content['objectGroup']['objectGroupImageUri']) . '" style="max-width:100%;" />';
      } ?>
      <?php
      //print_r ($content['objectGroup']); ?>
    </div>

    <?php if(isset($content['objectGroup']['menu'])): ?>
      <div class="ogmt-og-menu"><?php //@todo not sure you want to show the object group pages here, might add confusion echo $content['objectGroup']['menu']; ?></div>
    <?php endif; ?>

    <?php if (isset($content['objectGroup']['search_results'])): ?>
      <div>
        <?php echo $content['objectGroup']['search_results']; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>
