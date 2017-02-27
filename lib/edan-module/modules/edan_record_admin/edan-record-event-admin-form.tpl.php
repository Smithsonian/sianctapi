<?php
// dpm($data);
// dpm($form);
?>
<form class="edan-form edan-event-form" action="/edan/event" method="post" id="event-edan-form" accept-charset="UTF-8">
  <div>
    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Details</span></legend>
      <?php print drupal_render($form['title']); ?>
      <?php print drupal_render($form['brief_description']); ?>
      <?php print drupal_render($form['full_description']); ?>
      <?php print drupal_render($form['event_url']); ?>
      <?php print drupal_render($form['online_event_url']); ?>
      <?php print drupal_render($form['status_id']); ?>
      <?php print drupal_render($form['status_text']); ?>
    </fieldset>
    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Legacy Info</span></legend>
      <?php print drupal_render($form['legacy_event_id']); ?>
      <?php print drupal_render($form['legacy_building']); ?>
      <?php print drupal_render($form['legacy_lastupdated']); ?>
      <?php print drupal_render($form['sponsor']); ?>
    </fieldset>
    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Dates</span></legend>
      <div class="row collapse">
        <div class="medium-6 columns">
          <?php print drupal_render($form['opening_date']); ?>
        </div>
        <div class="medium-6 columns">
          <?php print drupal_render($form['opening_text']); ?>
        </div>
        <div class="medium-6 columns">
          <?php print drupal_render($form['opening_sort']); ?>
        </div>
        <div class="medium-6 columns">
          <?php print drupal_render($form['closing_date']); ?>
        </div>
        <div class="medium-6 columns">
          <?php print drupal_render($form['closing_text']); ?>
        </div>
        <div class="medium-6 columns">
          <?php print drupal_render($form['closing_sort']); ?>
        </div>
        <div class="medium-6 columns">
          <?php print drupal_render($form['comments']); ?>
        </div>
      </div>
      <div style="height: 1.5rem;"></div>
      <div class="row collapse">
        <div class="medium-12 columns">
          <?php print drupal_render($form['closedate_text']); ?>
        </div>
      </div>
    </fieldset>
    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Location</span></legend>
      <?php print drupal_render($form['unit_code']); ?>
      <?php print drupal_render($form['edan_museum_id']); ?>
      <?php print drupal_render($form['location_in_museum']); ?>
      <?php print drupal_render($form['floor_number']); ?>
    </fieldset>
    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Metadata</span></legend>
      <?php print drupal_render($form['encyclopedia']); ?>
      <?php print drupal_render($form['featured']); ?>
      <?php print drupal_render($form['topic_art']); ?>
      <?php print drupal_render($form['topic_history']); ?>
      <?php print drupal_render($form['topic_science']); ?>
      <?php print drupal_render($form['topic_kids']); ?>
      <?php print drupal_render($form['challenge_american_experience']); ?>
      <?php print drupal_render($form['challenge_biodiversity']); ?>
      <?php print drupal_render($form['challenge_universe']); ?>
      <?php print drupal_render($form['challenge_world_culture']); ?>
      <?php print drupal_render($form['keywords']); ?>
      <!-- Submit, Preview, and Delete buttons -->
    </fieldset>

    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Images</span></legend>
      <?php print drupal_render($form['image_100x100_url']); ?>
      <?php print drupal_render($form['image_100x100_title']); ?>
      <?php print drupal_render($form['image_100x100_alt']); ?>
      <?php print drupal_render($form['image_thumbnail_url']); ?>
      <?php print drupal_render($form['image_thumbnail_title']); ?>
      <?php print drupal_render($form['image_thumbnail_alt']); ?>
      <?php print drupal_render($form['image_main_url']); ?>
      <?php print drupal_render($form['image_main_title']); ?>
      <?php print drupal_render($form['image_main_alt']); ?>
    </fieldset>

    <div class="row collapse">
      <div class="medium-3 columns">
        <?php //print drupal_render($form['actions']['submit']); ?>
        <?php //print drupal_render($form['actions']['preview']); ?>
        <?php //print drupal_render($form['actions']['delete']); ?>
      </div>
    </div>

  </div>
</form>
<?php print drupal_render_children($form); ?>
