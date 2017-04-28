<?php //dpm($variables);?>
<?php $class = (isset($doc['flags']['in_list']) && $doc['flags']['in_list'] === TRUE) ? ' in-list' : ''; ?>
<?php $id = isset($edan_id) ? $edan_id : ''; ?>
<div class="edan-record<?php print $class; ?>" id="<?php echo $id; ?>">
    <?php if (isset($thumbnail['content'])): ?>
      <a href="<?php echo $thumbnail['content']; ?>" class="thumbnail"><img src="<?php echo $thumbnail['content']; ?>" alt="" /></a>
    <?php endif; ?>
    <?php echo $title; ?>
    <?php echo $content['teaser']; ?>
</div>
