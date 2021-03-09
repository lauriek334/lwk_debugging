<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

class ArtistForm extends FormBase {
  public function getFormId() {
    return 'artist_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $aid = 0) {
    dblog('ArtistForm buildForm ENTERED');
    
    $db = \Drupal::database();
    $form = [];
    $form['title'] = [
      '#markup' => '<h1>' . $this->t('Edit UMS artist') . '</h1>'
    ];
    $url_wid = \Drupal::request()->query->get('wid');
    $url_pid = \Drupal::request()->query->get('pid');
    $url_name = \Drupal::request()->query->get('name');
    $current_path = \Drupal::service('path.current')->getPath();

    if (isset($url_wid)) {
      $work = _ums_cardfile_get_work($url_wid);
      $form['#prefix'] = '<p>Adding NEW Artist as a Creator of ' . $work['title'] . '</p>';
      $form['wid'] = [
        '#type' => 'value',
        '#value' => $work['wid'],
      ];
      // get work roles
      $work_role_options = [];
      $work_roles = $db->query("SELECT * FROM ums_work_roles ORDER BY name");
      foreach ($work_roles as $work_role) {
        $work_role_options[$work_role->wrid] = $work_role->name;
      }
      $form['wrid'] = [
        '#type' => 'select',
        '#title' => 'Role',
        '#options' => $work_role_options,
        '#description' => '[' . ums_cardfile_create_link('Edit Creator Roles', 'cardfile/workroles', ['query' => ['return' => $current_path]]) . ']',
      ];
    } 
    elseif (isset($url_pid)) {
      $performance = _ums_cardfile_get_performance($url_pid);
      $form['#prefix'] = '<p>Adding NEW Artist as a Repertoire Performance Artist of ' . $performance['work']['title'] . '</p>';
      $form['pid'] = [
        '#type' => 'value',
        '#value' => $performance['pid'],
      ];
      // get performance roles
      $perf_role_options = [];
      $perf_roles = $db->query("SELECT * FROM ums_performance_roles ORDER BY name");
      foreach ($perf_roles as $perf_role) {
        $perf_role_options[$perf_role->prid] = $perf_role->name;
      }
      $form['prid'] = [
        '#type' => 'select',
        '#title' => 'Role',
        '#options' => $perf_role_options,
        '#description' => '[' . ums_cardfile_create_link('Edit Artist Roles', 'cardfile/perfroles', 
                                                     ['query' => ['return' => $current_path]]) . ']',
      ];
    }

    $artist = ['aid' => '', 'name' => '', 'alias' => '', 'notes' => '', 'photo_nid' => ''];

    if ($aid) {
      $artist = _ums_cardfile_get_artist($aid);
      $form['aid'] = [
        '#type' => 'value',
        '#value' => $artist['aid'],
      ];
    }
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#size' => 64,
      '#maxlength' => 128,
      '#default_value' => (isset($url_name) ? $url_name : $artist['name']),
      '#description' => $this->t('Name of Artist'),
    ];
    $form['alias'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Alias'),
      '#size' => 64,
      '#maxlength' => 256,
      '#default_value' => $artist['alias'],
      '#description' => $this->t('Artist Aliases') . ' (' . t('separate multiple values with a comma') . ')',
    ];
    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#default_value' => $artist['notes'],
    ];
    $form['photo_nid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Photo ID'),
      '#size' => 64,
      '#maxlength' => 128,
      '#default_value' => $artist['photo_nid'],
      '#description' => $this->t('Node ID of the corresponding photo, separate multiple values with commas'),
    ];

    if ($artist['aid']) {
      $form['collapsible'] = [
        '#type' => 'details',
       '#title' => t('MERGE ARTIST'),
        //'#description' => t($desc_html),
        '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
      ];

      $form['collapsible']['merge_id'] = [
          '#type' => 'textfield',
          '#title' => t('Merge this artist into Artist ID'),
          '#description' => t('Enter another Artist ID number to merge this artist information into that artist record'),
          '#size' => 8,
          '#maxlength' => 8,
        ];
    }

    $form['submit'] = [
      '#prefix' => '<div class="container-inline">',
      '#type' => 'submit',
      '#value' => $this->t('Save Artist'),
      '#suffix' => '&nbsp;' . ums_cardfile_create_link('Cancel', 'cardfile/artists') . '</div>',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('ARTISTFORM: submitForm: ENTERED');
    // Check for merge ID
    //$form_values = $form_state->getValues();
    //dblog('ARTISTFORM: submitForm: form_values =', $form_values);

    $merge_id = $form_state->getValue('merge_id');
    $aid = $form_state->getValue('aid');
    dblog('ARTISTFORM: submitForm: merge_id =', $merge_id, 'aid =', $aid);

    if ($aid && $merge_id) {
     // if ($_REQUEST['destination']) {
      //   unset($_REQUEST['destination']);
      // }
      dblog("ArtistForm: submitForm: SETTING artists_merge REDIRECT, aid=$aid, merge_id=$merge_id");
      $form_state->setRedirect('ums_cardfile.artists.merge',
                                ['old_id' => $aid, //, ['aid' => $aid]);
                                 'merge_id' => $merge_id]); //, ['aid' => $aid]);
      return; 
    }

    $artist = [];
    $artist['name'] = $form_state->getValue('name');
    $artist['alias'] = $form_state->getValue('alias');
    $artist['notes'] = $form_state->getValue('notes');
    $artist['photo_nid'] = $form_state->getValue('photo_nid');

    // Convert Name to NamePlain for matching
    $artist['name_plain'] = ums_cardfile_normalize($artist['name']);

    $aid = $form_state->getValue('aid');
    dblog('EventForm: submitForm: aid=',$aid);
    if ($aid) {
      // update existing record
      $artist['aid'] = $aid;
      ums_cardfile_save('ums_artists', $artist, 'aid');
    } else {
      // new event
      ums_cardfile_save('ums_artists', $artist, NULL);
    }

    $db = \Drupal::database();
    $result = $db->query("SELECT aid FROM ums_artists ORDER BY aid desc limit 1")->fetch();
    $aid = $result->aid;

    if ($form_state->getValue('wid')) {
      // Create new work artist
      $form_state->setRedirect('ums_cardfile.join',
                                ['type1' => 'performance', 
                                  'id1' => $form_state->getValue('wid'),
                                  'type2' => 'artist', 
                                  'id2' => $aid,
                                  'optional_key' => 'wrid',
                                  'optional_value' => $form_state->getValue('wrid')
                                ]);
    } 
    else if ($form_state->getValue('pid')) {
      // Create new work artist
      $form_state->setRedirect('ums_cardfile.join',
                                ['type1' => 'performance', 
                                  'id1' => $form_state->getValue('wid'),
                                  'type2' => 'artist', 
                                  'id2' => $aid,
                                  'optional_key' => 'prid',
                                  'optional_value' => $form_state->getValue('prid')
                                ]);
    }
    else {
      $form_state->setRedirect('ums_cardfile.artist', ['aid' => $aid]);
    }
    drupal_set_message('Artist saved');

    return;
  }
}