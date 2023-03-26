<?php

namespace Drupal\multisite_easy_commands\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Configuration form definition for the salutation message.
 */
class MSLConfigurationForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['multisite_easy_commands.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'msl_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('multisite_easy_commands.settings');
    // $config->set('sites', NULL)->save();die;
    $sites = $config->get('sites') ?? [];

  $form['sites'] = [
    '#type' => 'table',
    '#header' => [
      $this->t('Site URL'),
      $this->t('Site Name'),
      $this->t('Delete'),
    ],
    '#empty' => $this->t('No sites added yet.'),
    '#tabledrag' => [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'name-weight',
      ],
    ],
    '#prefix' => '<div id="sites-wrapper">',
    '#suffix' => '</div>',
  ];

  foreach ($sites as $key => $site) {
    $form['sites'][$key]['url'] = [
      '#type' => 'textfield',
      // '#title' => $this->t('URL'),
      '#default_value' => $site['url'],
      '#attributes' => [
        'class' => ['name-weight'],
        'data-weight' => $key,
      ],
    ];

    $form['sites'][$key]['name'] = [
      '#type' => 'textfield',
      // '#title' => $this->t('Name'),
      '#default_value' => $site['name'],
      '#attributes' => [
        'class' => ['name-weight'],
        'data-weight' => $key,
      ],
    ];

    $form['sites'][$key]['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#name' => 'delete_' . $key,
      '#submit' => ['::deleteNameSubmit'],
      '#ajax' => [
        'callback' => '::updateNamesTable',
        'wrapper' => 'sites-wrapper',
      ],
    ];
  }

  $form['add_more'] = [
    '#type' => 'submit',
    '#value' => $this->t('Add more'),
    '#submit' => ['::addMoreNameSubmit'],
    '#ajax' => [
      'callback' => '::updateNamesTable',
      'wrapper' => 'sites-wrapper',
    ],
    '#prefix' => '<div>',
    '#suffix' => '</div>',
  ];

  $form['#attached']['library'][] = 'core/drupal.tabledrag';

  $form['actions'] = [
    '#type' => 'actions',
  ];

  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => $this->t('Save configuration'),
    '#submit' => ['::submitForm'],
  ];

  return parent::buildForm($form, $form_state);
    // return $form;
  }
  /**
   * Ajax callback for updating the sites table.
   */
  public function updateNamesTable(array &$form, FormStateInterface $form_state) {
    return $form['sites'];
  }

  /**
   * Submit callback for adding a new name.
   */
  public function addMoreNameSubmit(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('multisite_easy_commands.settings');
    $sites = $config->get('sites') ?? [];

    $sites[] = [
      'url' => '',
      'name' => ''
    ];

    $config->set('sites', $sites)->save();

    $form_state->setRebuild();
  }

  /**
   * Submit callback for deleting a name.
   */
  public function deleteNameSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $key = str_replace('delete_', '', $triggering_element['#name']);

    $config = $this->configFactory->getEditable('multisite_easy_commands.settings');
    $sites = $config->get('sites') ?? [];

    unset($sites[$key]);
    $config->set('sites', $sites)->save();

    $form_state->setRebuild(TRUE);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['sites'] as &$subArray) {
      unset($subArray['delete']);
    }

    $config = $this->config('multisite_easy_commands.settings');
    $config->set('sites', array_filter($values['sites']))->save();

    // drupal_set_message($this->t('The configuration options have been saved.'));
    $this->messenger()->addMessage('The configuration options have been saved.');
    parent::submitForm($form, $form_state);
  }
}