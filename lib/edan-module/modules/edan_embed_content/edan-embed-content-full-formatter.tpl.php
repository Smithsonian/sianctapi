<?php //dpm($variables); ?>
<?php $class = (isset($doc['flags']['in_list']) && $doc['flags']['in_list'] === TRUE) ? ' in-list' : ''; ?>

<?php $class = (isset($doc['flags']['in_list']) && $doc['flags']['in_list'] === TRUE) ? ' in-list' : ''; ?>
<?php $id = isset($edan_id) ? $edan_id : ''; ?>
<div class="edan-record<?php print $class; ?>" id="<?php echo $id; ?>">
  <?php echo $title; ?>
  <?php if (isset($thumbnail)): ?>
    <a href="<?php echo $thumbnail['content']; ?>" class="thumbnail"><img src="<?php echo $thumbnail['content']; ?>" alt="" /></a>
  <?php endif; ?>
  <?php echo $content['description']; ?>

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
