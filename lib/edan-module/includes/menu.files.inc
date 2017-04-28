<?php

function edan_files($action) {
  $possible_actions = array('picker'); // 'upload',

  if (!in_array($action, $possible_actions)) {
    return MENU_NOT_FOUND;
  }

  $mod_path = drupal_get_path('module', 'edan');

  drupal_add_js($mod_path . '/files.js');

  $page_start = '<html><head>';
  $page_start .= "\n\t" . '<title>' . t('EDAN File Manager') . '</title>';
  $page_start .= "\n\t" . '<meta charset="UTF-8">';
  #$page_start .= "\n" . drupal_get_css();
  $page_start .= "\n" . drupal_get_js('header');
  $page_start .= "\n\t" . '<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" />';
  $page_start .= "\n\t" . '<link rel="stylesheet" href="http://blueimp.github.io/Gallery/css/blueimp-gallery.min.css" />';
  $page_start .= "\n\t" . '<style type="text/css">iframe { border: none; } .element-invisible { display: none; } ul.nav { margin-bottom: 10px; } .edan-search-result { min-height: 300px; }</style>';
  $page_start .= "\n\t" . '<link rel="stylesheet" href="/' . $mod_path . '/edan-files.css" />';
  #$page_start .= "\n\t" . '<script src="/' . $mod_path . '/files.js?p=d' . rand(1, 100) . 'ssw1" type="text/javascript"></script>';
  $page_start .= '</head><body class="edan-files" style="padding-top: 60px;">';
  $page_start .= '<div class="container"><div class="row"><div class="col-md-12">';
  $page_end = '</div></div></div></body></html>';

  if ($action == 'picker') {
    $body = _edan_files_tabs('picker');

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
        'custom_callback' => '_edan_files_cb_picker_results',
      ),
    );

    // Args
    $args = _edan_search_get_query('*:*', $opts);
    $args['fq'][] = 'online_media_type:"Images"';
    #$opts['dump'] = 1;

    // Perform the search
    $body .= _edan_search_get_results($args, $opts);
  }

  echo $page_start . $body . $page_end;
  exit();
}

function _edan_files_tabs($active = '') {
  if (isset($_GET['localPicker'])) {
    $options = array(
      'query' => array('localPicker' => 1),
    );
  } else if (isset($_GET['app'])) {
    $get_app = _edan_get_param('app');
    $get_ckeditorfuncnum = _edan_get_param('CKEditorFuncNum', '');
    $options = array(
      'query' => array(
        'app' => $get_app, // $_GET['app'],
        'CKEditorFuncNum' => $get_ckeditorfuncnum, //$_GET['CKEditorFuncNum'],
      ),
    );
  } else {
    $options = array();
  }

  $links = array(
    //'picker' => l('Select Image from EDAN', EDAN_ADMIN_FILES_PATH . '/picker/' . $object_group, $options)
    'picker' => l('Select Image from EDAN', EDAN_ADMIN_FILES_PATH . '/picker', $options)
  );

  $out = '<ul class="nav nav-tabs">';
  foreach ($links as $action => $link) {
    if ($action == $active) {
      $out .= '<li class="active">' . $link . '</li>';
    } else {
      $out .= '<li>' . $link . '</li>';
    }
  }

  $out .= '</ul>';

  return $out;
}

/**
 * This is a callback used by _edan_search_get_results instead of invoking theme API.
 * This may change.
 */
function _edan_files_cb_picker_results(&$vars, &$opts) {
  $out = '';
  $docs =& $vars['docs'];

  $get_edan_q = _edan_get_param('edan_q', '*:*');

  $form = '<div class="col-md-3">';
  $form .= '<div class="edan-image-edan-search-form">';
  $form .= '<strong>Search EDAN</strong>';
  $form .= '<form action="" method="get" role="form" class="">';
  if (isset($_GET['localPicker'])) {
    $form .= '<input type="hidden" name="localPicker" value="1" />';
  }
  $form .= '<div class="form-group">';
  $form .= '<label for="edan-q" class="sr-only">Search Term</label>';
  //$form .= '<input type="text" name="edan_q" id="edan-q" value="' . ( (isset($_GET['edan_q'])) ? $_GET['edan_q'] : '*:*' ) . '" class="form-control" />';
  $form .= '<input type="text" name="edan_q" id="edan-q" value="' . $get_edan_q . '" class="form-control" />';
  $form .= '</div>';
  $form .= '<input type="submit" value="Search" class="btn btn-default" />';
  $form .= '</form>';
  $form .= '<hr/>';
  $form .= '<p>Max: <input type="text" id="width" value="200" size="4" /></p>';
  $form .= '<p>Alt: <input type="text" id="alt" value="" size="30" /></p>';
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
        /*
        array (
          'idsId' => 'http://www.americanindian.si.edu/searchcollections/multimedia/10122/565/232173.700x700.jpg',
          'content' => 'http://www.americanindian.si.edu/searchcollections/multimedia/10122/565/232173.700x700.jpg',
          'thumbnail' => 'http://www.americanindian.si.edu/searchcollections/multimedia/10122/565/232173.thumb.jpg',
          'type' => 'Images',
        )
        */
        #$out .= '<pre>' . var_export($media, TRUE) . '</pre>';
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
        #$doc['descriptiveNonRepeating']['online_media']['media'][0]['content']; " class="thumbnail"><img src="<?php echo $doc['descriptiveNonRepeating']['online_media']['media'][0]['thumbnail']; " alt="" /></a>
      }
      $out .= '</div>';
    }
    $out .= "\n" . '</div>';
  }
  $out .= $vars['pager'];
  $out .= '</div>'; // End col-md-9

  return '<div class="container">' . $form . $out . '</div>';
}