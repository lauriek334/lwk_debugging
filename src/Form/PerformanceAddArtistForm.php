<?php

/**
 * @file
 * Contains \Drupal\ums_cardfile\Form\UserListCreateForm
 */

namespace Drupal\ums_cardfile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PerformanceAddArtistForm extends FormBase {
  public function getFormId() {
    return 'performance_add_artist_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $pid = 0) {
    dblog('PerformanceAddArtistForm: buildForm ENTERED - pid', $pid);
    $db = \Drupal::database();

  // get work roles
    $perf_role_options = [];
    $performance_roles = $db->query("SELECT * FROM ums_work_roles ORDER BY name")->fetchAll();
    //dblog('PerformanceAddArtistForm: performance_roles (2)', $performance_roles);
    foreach ($performance_roles as $performance_role) {
      $perf_role_options[$performance_role->wrid] = $performance_role->name;
    }
    dblog('PerformanceAddArtistForm: perf_role_options: ', $perf_role_options);

    $form['collapsible'] = [
      '#type' => 'details',
      '#title' => t('Add Repertoire Performance Artist'),
      //'#description' => t($desc_html),
      '#open' => FALSE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#prefix' => '<div id="LWK" style="width:1000px">',
      '#suffix' => '</div>'
    ];
    $form['collapsible']['table'] = [
      '#prefix' => '<table><tr><th>Select Role:</th><th>Select Artist:</th></tr><tr>',
      '#suffix' => '</tr></table>',
    ];

    // $form = array(
    //   '#prefix' => '<fieldset class="collapsible collapsed"><legend>Add Repertoire Performance Artist</legend>' .
    //               '<table><tr><th>Select Role:</th><th>Select Artist:</th></tr><tr>',
    //   '#suffix' => '</tr></table></fieldset>',
    // );
    $form['collapsible']['table']['pid'] = array(
      '#type' => 'value',
      '#value' => $pid,
    );
    $form['collapsible']['table']['role'] = array(
      '#prefix' => '<td style="padding-right: 25px"><div class="container-inline">',
      '#suffix' => '</div></td>',
    );

    $current_path = \Drupal::service('path.current')->getPath();

    $form['collapsible']['table']['role']['prid'] = array(
      '#type' => 'select',
      '#title' => 'Role',
      '#options' => $perf_role_options,
      '#description' => '[' .ums_cardfile_create_link('Edit Artist Roles', 'cardfile/perfroles', ['query' => ['return' => $current_path]]) . ']',
    );
    $form['collapsible']['table']['search'] = array(
      '#prefix' => '<td><div class="container-inline">',
      '#suffix' => '</div><p><strong>- OR -</strong></p>',
    );
    $form['collapsible']['table']['search']['search_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Search for existing artist'),
      '#size' => 32,
      '#maxlength' => 32,
    );
    $form['collapsible']['table']['search']['submit_search'] = array(
      '#type' => 'submit',
      '#value' => t('Search'),
    );
    $form['collapsible']['table']['recent'] = array(
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div><p><strong>- OR -</strong></p>',
    );
    $form['collapsible']['table']['recent']['recent_aid'] = array(
      '#type' => 'select',
      '#title' => 'Recent artists',
      '#options' => ums_cardfile_recent_artists_d8(),
    );
    $form['collapsible']['table']['recent']['submit_recent'] = array(
      '#type' => 'submit',
      '#value' => t('Use This Artist'),
    );
    $form['collapsible']['table']['addNew'] = array(
      '#suffix' => ums_cardfile_create_link('ADD NEW ARTIST', 'cardfile/artist/edit', ['query' => ['pid' => $pid]]) . '</p></td>'
    );
    
    dblog('performance_add_artist_form - RETURNING' ); // $form:', $form);
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('PerformanceAddArtistForm: submitForm ENTERED');
 
    if ($form_state['clicked_button']['#parents'][0] == 'submit_recent') {
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/join/performance/' . $form_state->getValue('pid') . '/artist/' . $form_state->getValue('recent_aid'),
                                                    ['prid' => $form_state->getValue('prid')]);
    } else {
      $drupal_goto_url = ums_cardfile_drupal_goto('cardfile/searchadd/performance/' . $form_state->getValue('pid') . '/artist/' . $form_state->getValue('search_text'),
                                                    ['prid' => $form_state->getValue('prid')]);
    }
    dblog('PerformanceAddArtistForm:submitForm: $drupal_goto_url = [' . $drupal_goto_url . ']');
    return new RedirectResponse($drupal_goto_url);
  }
}