<div class="edan-search clearfix">
  <?php if ($tabs): ?>
  <div class="edan-search-tabs">
    <?php print($tabs); ?>
  </div>
  <?php endif; ?>

  <?php echo $pager; ?>

  <?php //dpm($variables); ?>

  <div class="edan-results-summary"><?php print($results_summary); ?></div>
  <div class="<?php print $container_class; ?>">
    <ul class="search-results<?php print $results_class; ?>">
      <?php
      foreach ($docs as $doc): ?>
      <?php
      // TODO: Move this into a callback
      $class = '';
      if (isset($doc['flags']['in_list']) && $doc['flags']['in_list'] === TRUE) {
        $class .= ' in-list';
      }
      if (isset($doc['flags']['in_unit']) && $doc['flags']['in_unit'] === TRUE) {
        $class .= ' in-unit';
      }

      $recordID = rand();
      if(isset($doc['record_ID'])) {
        $recordID = $doc['record_ID'];
      }
      ?>
      <li class="edan-search-result<?php print $class . $result_class; ?>" id="<?php echo $recordID; // $doc['url']; ?>">
        <div class="edan-row">
          <h3 class="title"><?php echo $doc['#title']; ?></h3>

          <?php if(module_exists('devel') && isset($_GET['dpm']) && user_access('access devel information')) {
            dpm($doc);
          }
          if (isset($_GET['dump'])) {
            echo '<pre>' . var_export($doc, TRUE) . '</pre>';
          }
          else { ?>

            <?php if (isset($doc['images'][0]['content'])):
                $alt = isset($doc['images'][0]['alt']) ? $doc['images'][0]['alt'] : ''; ?>
            <a href="<?php echo $doc['images'][0]['content']; ?>" class="thumbnail"><img src="<?php echo $doc['images'][0]['content']; ?>" alt="<?php echo $alt; ?>" /></a>
            <?php endif; ?>

            <div class="edan-record-description">
              <?php
              echo $doc['teaser'];
              ?>
            </div>

            <?php
              // render the geoLocation from structured data, if available
              // note, to see a nicer view of this nested array, if Devel is enabled on the site, uncomment this:
              // dpm($doc['content']);
              if(array_key_exists('indexedStructured', $doc['content'])) :
                // create a template for the array we're expecting:
                $structuredIndexTemplate = array(
                  'geoLocation' => array(
                    0 => array(
                      'points' => array(
                        'point' => array (
                          'latitude' => array(
                            'content'
                          ),
                          'longitude' => array(
                            'content'
                          )
                        )
                      )
                    )
                  )
                );
                $structuredIndex = array_merge($structuredIndexTemplate, $doc['content']['indexedStructured']);
                $latitude = isset($structuredIndex['geoLocation'][0]['points']['point']['latitude']['content'])
                  ? $structuredIndex['geoLocation'][0]['points']['point']['latitude']['content']
                  : '';
                $longitude = isset($structuredIndex['geoLocation'][0]['points']['point']['longitude']['content'])
                  ? $structuredIndex['geoLocation'][0]['points']['point']['longitude']['content']
                  : '';

                $field = 'geoLocation_0';
                // if you're expecting multiple locations, adjust the $structuredIndexTemplate, and use the array key instead of hard-coding 0 in the $field

                // now render it:
                if(strlen($latitude) > 0 || strlen($longitude) > 0) : ?>
                <dl class="edan-search-<?php echo $field; ?>">
                  <dt><?php echo t('Geographic Location'); ?></dt>
                  <dd><?php echo ($latitude . ', ' . $longitude); ?></dd>
                </dl>
                <?php
                endif;
              endif;

            ?>

            <?php foreach ($doc['content']['freetext'] as $field => $vals): ?>
            <dl class="edan-search-<?php echo $field; ?>">
              <?php $current_label = ''; ?>
              <?php foreach ($vals as $value): ?>
                <?php if ($value['label'] != $current_label): ?>
              <dt><?php echo $value['label']; ?></dt><?php $current_label = $value['label']; ?>
                <?php endif; ?>
              <dd><?php echo $value['content']; ?></dd>
              <?php endforeach; ?>
            </dl>
            <?php endforeach; ?>

          <?php } // End If ?>
        </div>
      </li>
      <?php endforeach; ?>

    </ul>
  </div>

  <?php if ($facets): ?>
  <?php
    // $facets contains the formatted facet content
    // $facets_raw is an array of facet data, which can be used for custom formatting facets
    // $active_facets_raw is an array of the currently active/selected facets
  ?>
  <div class="edan-search-facets">
    <?php echo $facets; ?>
  </div>
  <?php endif; ?>

  <?php echo $pager; ?>
</div>