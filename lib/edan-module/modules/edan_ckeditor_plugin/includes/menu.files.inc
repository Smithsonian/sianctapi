<?php

function edan_ckeditor_picker_files($action) {

  $possible_actions = array('list', 'picker'); // 'upload',

  if (!in_array($action, $possible_actions)) {
    return MENU_NOT_FOUND;
  }

  $mod_path = drupal_get_path('module', 'edan_ckeditor_plugin');

  drupal_add_js($mod_path . '/files.js');

  $page_start = '<html><head>';
  $page_start .= "\n\t" . '<title>' . t('EDAN File Manager') . '</title>';
  $page_start .= "\n\t" . '<meta charset="UTF-8">';
  #$page_start .= "\n" . drupal_get_css();
  $page_start .= "\n" . drupal_get_js('header');
  $page_start .= "\n\t" . '<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" />';
  $page_start .= "\n\t" . '<link rel="stylesheet" href="http://blueimp.github.io/Gallery/css/blueimp-gallery.min.css" />';
  $page_start .= "\n\t" . '<style type="text/css">iframe { border: none; } .element-invisible { display: none; } ul.nav { margin-bottom: 10px; } .edan-search-result { min-height: 300px; }</style>';
  $page_start .= "\n\t" . '<link rel="stylesheet" href="/' . $mod_path . '/edan-ckeditor-plugin.css" />';
  $page_start .= '</head><body class="edan-search" style="padding-top: 60px;">';
  $page_start .= '<div class="container"><div class="row"><div class="col-md-12">';
  $page_end = '</div></div></div></body></html>';

  $body = '';

  // Opts
  $opts = array(
    'variables' => array(
      'field_order' => array('record_ID', 'dataSource'),
      'mini_fields' => array(),
      'results_per_page' => 50,
    ),
    'theme' => array(
      'results_class_add' => 'container',
      'result_class_add' => 'col-md-4',
      'custom_callback' => '_edan_ckeditor_picker_cb_picker_results',
    ),
  );

  // Args
  $args = _edan_search_get_query('*:*', $opts);
  $args['fq'][] = 'online_media_type:"Images"';
  #$opts['dump'] = 1;

  // Perform the search
  $opts['plainsearch'] = 1;
  // force use of our search results template
  $body .= _edan_search_get_results($args, $opts); //, 'ogmt_edan_search_results');

  echo $page_start . $body . $page_end;
  exit();
}

/**
 * This is a callback used by _edan_ckeditor_picker_get_results instead of invoking theme API.
 * This may change.
 */
function _edan_ckeditor_picker_cb_picker_results(&$vars, &$opts) {
  $out = '';
  $docs =& $vars['docs'];

  $get_edan_q = _edan_get_param('edan_q', '*:*');

  $form = '<div class="col-md-3">';
  $form .= '<div class="edan-image-edan-search-form">';
  $form .= '<strong>Search EDAN</strong>';
  $form .= '<form action="/edan/files/picker" method="get" role="form" class="">';
  if (isset($_GET['localPicker'])) {
    $form .= '<input type="hidden" name="localPicker" value="1" />';
  }
  $form .= '<div class="form-group">';
  $form .= '<label for="edan-q" class="sr-only">Search Term</label>';

  $form .= '<input type="text" name="edan_q" id="edan-q" value="' . $get_edan_q . '" class="form-control" />';
  $form .= '</div>';
  $form .= '<input type="submit" value="Search" class="btn btn-default" />';
  $form .= '</form>';
  $form .= '<hr/>';
//  $form .= '<p>Max: <input type="text" id="width" value="200" size="4" /></p>';
//  $form .= '<p>Alt: <input type="text" id="alt" value="" size="30" /></p>';
  $form .= '</div>'; // Position
  $form .= '</div>';

  $out = '<div class="col-md-9">';
  foreach ($docs as $doc) {
    $out .= "\n" . '<div class="row" style="border-bottom: 1px solid #EFEFEF; margin: 5px 0; padding-bottom: 5px;">';
    $out .= "\n\t" . '<div class="col-md-12"><strong>' . $doc['#title'] . '</strong></div>';
    if (isset($doc['content']['descriptiveNonRepeating']['online_media']['media'])) {
      $out .= '<div class="row">';
      $i = 1;
      foreach ($doc['content']['descriptiveNonRepeating']['online_media']['media'] as $media) {

        if ($media['type'] == 'Images') {
          if ($i > 6) {
            $i = 0;
            $out .= '</div><div class="row">';
          }
          $out .= '<div class="col-md-2"><div class="thumbnail"><img src="' . $media['thumbnail'] . '" alt="" />';
          $out .= '<div class="caption"><a href="' . $media['content'] . '" class="btn-primary btn btn-xs insert-file" data-src="' . $media['idsId'] . '" data-name="' . $doc['content']['descriptiveNonRepeating']['title']['content'] . '">Insert</a></div>';
          $out .= '</div></div>';
          $i++;
        }

      }
      $out .= '</div>';
    }
    $out .= "\n" . '</div>';
  }
  $out .= $vars['pager'];
  $out .= '</div>'; // End col-md-9

  return '<div class="container">' . $form . $out . '</div>';
}