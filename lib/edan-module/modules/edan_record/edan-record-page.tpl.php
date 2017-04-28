      <?php foreach ($docs as $doc):?>
      <?php $class = (isset($doc['flags']['in_list']) && $doc['flags']['in_list'] === TRUE) ? ' in-list' : ''; ?>
        <?php $id = isset($doc['content']['descriptiveNonRepeating']['record_ID']) ? $doc['content']['descriptiveNonRepeating']['record_ID'] : '';
        if($id == '') { $id = isset($doc['id']) ? $doc['id'] : '';}?>
      <div class="edan-search-result<?php print $class; ?>" id="<?php echo $id; ?>">
        <div class="edan-row">
          <?php if (isset($doc['content']['descriptiveNonRepeating']['online_media']['media'])): ?>
          <a href="<?php echo $doc['content']['descriptiveNonRepeating']['online_media']['media'][0]['content']; ?>" class="thumbnail"><img src="<?php echo $doc['content']['descriptiveNonRepeating']['online_media']['media'][0]['thumbnail']; ?>" alt="" /></a>
          <?php endif; ?>
          <?php if(isset($doc['content']['online_media'][0])) : ?>
            <a href="<?php echo $doc['content']['online_media'][0]['content']; ?>" class="thumbnail"><img src="<?php echo $doc['content']['online_media'][0]['thumbnail']; ?>" alt="" /></a>
          <?php endif; ?>

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
        </div>
      </div>
      <?php endforeach; ?>
