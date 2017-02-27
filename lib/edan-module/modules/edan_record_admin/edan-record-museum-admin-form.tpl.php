<form class="edan-form edan-museum-form" action="/edan/museum" method="post" id="museum-edan-form" accept-charset="UTF-8">
  <div>
    <?php print render($form['view_link']); ?>
    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Museum Information</span></legend>
      <fieldset class="form-wrapper">
        <legend><span class="fieldset-legend">Names</span></legend>
        <?php print render($form['unit']); ?>
        <?php print render($form['acronymn']); ?>
        <?php print render($form['short_name']); ?>
        <?php print render($form['unit_category']); ?>
        <?php print render($form['organizational_parent']); ?>
        <?php print render($form['record_link']); ?>
      </fieldset>
      <fieldset class="form-wrapper">
        <legend><span class="fieldset-legend">Place</span></legend>
        <?php print render($form['streetAddress']); ?>
        <?php print render($form['addressRegion']); ?>
        <?php print render($form['addressLocality']); ?>
        <?php print render($form['postalCode']); ?>
        <?php print render($form['addressCountry']); ?>
        <?php print render($form['latitude']); ?>
        <?php print render($form['longitude']); ?>
      </fieldset>
      <fieldset class="form-wrapper">
        <legend><span class="fieldset-legend">Access</span></legend>
        <?php print render($form['metroBus']); ?>
        <?php print render($form['metroRail']); ?>
        <?php print render($form['parkingGarage']); ?>
      </fieldset>
      <?php print render($form['mission_statement']); ?>
      <?php print render($form['highlights']); ?>
      <?php print render($form['events']); ?>
      <?php print render($form['date']); ?>
      <?php print render($form['openingHours']); ?>
      <?php print render($form['category']); ?>
    </fieldset>
    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Contact Information</span></legend>
      <div class="row collapse">
        <div class="medium-6 columns">
          <?php print render($form['email']); ?>
        </div>
        <div class="medium-6 columns">
          <?php print render($form['phone']); ?>
        </div>
      </div>
      <div class="row collapse">
        <div class="medium-12 columns">
          <?php print render($form['url']); ?>
        </div>
      </div>
    </fieldset>
    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Graphics</span></legend>
      <?php print render($form['icon']); ?>
      <?php print render($form['logo']); ?>
      <?php print render($form['image']); ?>
      <?php print render($form['floor_plan']); ?>
    </fieldset>
    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Features</span></legend>
      <?php print render($form['accessibility']); ?>
      <?php print render($form['admission']); ?>
      <?php print render($form['bicycle']); ?>
      <?php print render($form['dining']); ?>
      <?php print render($form['lockers']); ?>
      <?php print render($form['parking']); ?>
      <?php print render($form['public_wifi']); ?>
    </fieldset>
    <fieldset class="form-wrapper">
      <legend><span class="fieldset-legend">Reference</span></legend>
      <?php print render($form['edan_object_group_id']); ?>
      <?php print render($form['trumba_sponsor_id']); ?>
      <?php print render($form['trumba_vendor_id']); ?>
    </fieldset>
    <!-- Submit, Preview, and Delete buttons -->
    <div class="row collapse">
      <div class="medium-3 columns">
        <?php print render($form['actions']['submit']); ?>
        <?php print render($form['actions']['preview']); ?>
        <?php print render($form['actions']['delete']); ?>
      </div>
    </div>

  </div>
</form>
<?php print drupal_render_children($form); ?>
