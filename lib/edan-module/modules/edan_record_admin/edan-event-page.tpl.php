<?php
dpm($content); // this will show all of the available fields for the exhibition
dpm($facility_content);

if(isset($facility_content['logo'])) {
    echo('<div class="facility-logo"><img style="width:240px;" src="' . $facility_content['logo'] . '" alt="facility logo"></div>');
}

$edit_link_text = '';
if(user_access('edit edan event content')) {
  $edit_link_text = l(t('Edit') . ' ' . $title,
    $edit_link,
    array('attributes' => array('title' => t('edit') . ' ' . $title)));
}

$logo_html = '';
$logo_src = isset($content['online_media'][0]['thumbnail'])  && (strlen($content['online_media'][0]['thumbnail']) > 0)
  ?  $content['online_media'][0]['thumbnail'] : '';
if(strlen($logo_src) > 0) {
  $logo_html = '<img src="' . $logo_src . '" alt="' . $title . ' icon" />';
}

$exhibition_dates = array_key_exists('Open', $content) ? $content['Open']['Opening'] : '';
$exhibition_dates .= (strlen($exhibition_dates) > 0) ? ' - ' : '';
$exhibition_dates .= array_key_exists('Close', $content) ? $content['Close']['Closing'] : '';

$address1 = isset($facility_content['streetAddress']) ? $facility_content['streetAddress'] : '';
$address2 = isset($facility_content['addressRegion']) && (strlen(trim($facility_content['addressRegion'])) > 0 )
? $facility_content['addressRegion'] . ',' : '';
$address2 .= isset($facility_content['addressLocality']) ? $facility_content['addressLocality'] : '';
$address2 .= isset($facility_content['postalCode']) ? $facility_content['postalCode'] : '';
$address1 .= strlen($address1) > 0 ? '<br />'  : '';
$address2 .= strlen($address2) > 0 ? '<br />'  : '';

$facility_link = isset($facility_content['id'])
  ? '<a href="/' . $facility_content['local_path'] . '">' . $facility_content['title'] . '</a>'
: '';

?>
<div class="edan-record edan-record-event">
  <div class="edit-link"><?php echo $edit_link_text;?></div>
  <div class="edan-record-detail event-detail">
    <div class="event-dates"><?php echo $exhibition_dates; ?></div>
    <div class="event-location">
      <?php echo $facility_link; ?><br />
      <?php
        echo $address1;
        echo $address2;
      ?>
      <?php if (isset($content['location']['content'])): ?>
        <div class="event-location-detail">
          <b>Location:</b> <?php echo $content['location']['content']; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if($content['event_type'] == 'imax-movie'): ?>
      <?php
        //http://si.edu/Content/Imax/Thumbnail/A_Beautiful_Planet_thumbnail.jpg
        $image = isset($content['online_media'][0]['content'])
          ? $content['online_media'][0]['content'] : false;

        $caption = isset($content['online_media'][0]['caption'])
          ? $content['online_media'][0]['caption'] : '';

        if(false !== $image) {
          $image = '<img src="' . $image . '" alt="' . $caption . '" />';
        }
      ?>
      <div class="event-runtime">
        <?php $runtime = isset($content['event_details']['extended']['runtime']) ? $content['event_details']['extended']['runtime'] : ''; echo('<b>Runtime:</b> ' . $runtime); ?>
      </div>
      <div class="event-rating">
        <?php $rating = isset($content['event_details']['extended']['rating']) ? $content['event_details']['extended']['rating'] : ''; echo('<b>MPAA Rating:</b> ' . $rating); ?>
      </div>

      <div class="imax-image">
        <?php if(false !== $image) {
          echo($image);
        }
        ?>
      </div>
    <?php endif; ?>

    <div class="event-icon icon"><?php echo $logo_html; ?></div>
    <div class="event-description"><?php echo $content['description']; ?></div>
    <?php if(isset($content['status'])): ?>
      <div class="event-status"><?php echo $content['status']; ?></div>
    <?php endif; ?>

    <?php if(isset($content['online_exhibit'])): ?>
      <?php echo('<a href="' . $content['online_exhibit'] . '">View online exhibit</a>'); ?>
    <?php endif; ?>

  </div>
  <?php if($content['event_type'] == 'imax-movie'): ?>
    <div class="event-showtimes">
      <?php
      $showtimes = '';
        if(isset($content['showtimes'])) {
          foreach($content['showtimes'] as $showtime_date) {
            if(is_array($showtime_date)) {
              foreach($showtime_date as $date => $times) {
                if(is_array($times) && count($times) > 0) {
                  $showtimes .= '<div class="showtimes"><div class="showtimes-date">' . $date . '</div><ul>';
                  foreach($times as $showtime) {
                    $showtimes .= '<li class="showtime-time">' . $showtime . '</li>';
                  }
                  $showtimes .= '</ul></div>';
                }
              }
            }
          }
        }
      if(strlen($showtimes) > 0) {
        echo('<h2>Showtimes</h2>' . $showtimes);
      }
      ?>
    </div>
  <?php endif; ?>

</div>
