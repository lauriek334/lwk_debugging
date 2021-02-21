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

class WorkForm extends FormBase {
  public function getFormId() {
    return 'artist_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $wid = 0) {
    dblog('WorkForm buildForm ENTERED, wid =', $wid);

    $db = \Drupal::database();

    $cancel_path = 'cardfile/works';
    $form = [];
    $form['title'] = [
      '#markup' => '<h1>' . $this->t('Edit UMS Repertoire') . '</h1>'
    ];
    // Get optional parameters from URL
    $url_eid = \Drupal::request()->query->get('eid');
    $url_title = \Drupal::request()->query->get('title');
    dblog('WorkForm buildForm param:eid,title =', $url_eid, $url_title);

    if ($url_eid) {
      $event = _ums_cardfile_get_event($url_eid);
      $form['#prefix'] = '<p>Adding NEW Repertoire to event: ' . $event['date'] . ' at ' . $event['venue'] . '</p>';
      $form['eid'] = [
        '#type' => 'value',
        '#value' => $event['eid'],
      ];
    }
    if ($wid) {
      $work = _ums_cardfile_get_work($wid);
      $form['wid'] = [
        '#type' => 'value',
        '#value' => $wid,
      ];
      $cancel_path = 'cardfile/work/' . $work['wid'];
    }
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#size' => 64,
      '#maxlength' => 128,
      '#default_value' => ($url_title ? $url_title : $work['title']),
      '#description' => t('Title of Repertoire'),
    ];
    $form['alternate'] = [
      '#type' => 'textfield',
      '#title' => t('Alternate Title'),
      '#size' => 64,
      '#maxlength' => 256,
      '#default_value' => $work['alternate'],
      '#description' => t('Alternate Titles for the Repertoire') . ' (' . t('separate multiple values with a comma') . ')',
    ];
    $form['notes'] = [
      '#type' => 'textarea',
      '#title' => t('Notes'),
      '#default_value' => $work['notes'],
    ];
    if ($work['wid']) {
      $form['merge_id'] = [
        '#type' => 'textfield',
        '#title' => t('Merge this repertoire into Repertoire ID'),
        '#size' => 8,
        '#maxlength' => 8,
        '#description' => t("Enter another Repertoire ID number to merge this repertoire information into that repertoire record"),
        '#prefix' => "<fieldset class=\"collapsible collapsed\"><legend>MERGE REPERTOIRE</legend>",
        '#suffix' => "</fieldset>",
      ];
    }

    $form['submit'] = [
      '#prefix' => '<div class="container-inline">',
      '#type' => 'submit',
      '#value' => $this->t('Save Repertoire'),
      '#suffix' => '&nbsp;' . ums_cardfile_create_link('Cancel', $cancel_path) . '</div>',
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    dblog('submitForm: ENTERED');
    //Check for merge ID
    if ($form_state->getValue('aid') && $form_state->getValue('merge_id')) {
      // if ($_REQUEST['destination']) {
      //   unset($_REQUEST['destination']);
      // }
      $form_state->setRedirect(
        'ums_cardfile.works.merge',
        ['oldid' => $form_state->getValue('aid'), 'mergeid' => $form_state->getValue('merge_id')]
      );

      return;
    }

    $work = [
      'title'      => $form_state->getValue('title'),
      'alternate'  => $form_state->getValue('alternate'),
      'notes'      => $form_state->getValue('notes'),
    ];

    $wid = $form_state->getValue('wid');
    dblog('WorkForm: submitForm: wid=', $wid);
    if ($wid) {
      // update existing record
      $work['wid'] = $wid;
      ums_cardfile_save('ums_works', $work, 'wid');
    } else {
      // new event
      ums_cardfile_save('ums_works', $work, NULL);
    }

    $eid = $form_state->getValue('eid');
    dblog('WorkForm: submitForm: eid=', $eid);
    if ($eid) {
      $form_state->setRedirect('ums_cardfile.join.event', ['wid' => $wid]);
    } else {
      $form_state->setRedirect('ums_cardfile.work', ['wid' => $wid]);
    }
    drupal_set_message('Repertoire saved');

    return;
  }
}