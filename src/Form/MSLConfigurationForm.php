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
    $names = $config->get('names') ?? [];

  $form['names'] = [
    '#type' => 'table',
    '#header' => [
      $this->t('Name'),
      $this->t('Delete'),
    ],
    '#empty' => $this->t('No names added yet.'),
    '#tabledrag' => [
      [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'name-weight',
      ],
    ],
    '#prefix' => '<div id="names-wrapper">',
    '#suffix' => '</div>',
  ];

  foreach ($names as $key => $name) {
    $form['names'][$key]['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $name,
      '#attributes' => [
        'class' => ['name-weight'],
        'data-weight' => $key,
      ],
    ];

    $form['names'][$key]['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#name' => 'delete_' . $key,
      '#submit' => ['::deleteNameSubmit'],
      '#ajax' => [
        'callback' => '::updateNamesTable',
        'wrapper' => 'names-wrapper',
      ],
    ];
  }

  $form['add_more'] = [
    '#type' => 'submit',
    '#value' => $this->t('Add more'),
    '#submit' => ['::addMoreNameSubmit'],
    '#ajax' => [
      'callback' => '::updateNamesTable',
      'wrapper' => 'names-wrapper',
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
   * Ajax callback for updating the names table.
   */
  public function updateNamesTable(array &$form, FormStateInterface $form_state) {
    return $form['names'];
  }

  /**
   * Submit callback for adding a new name.
   */
  public function addMoreNameSubmit(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('multisite_easy_commands.settings');
    $names = $config->get('names') ?? [];

    $names[] = '';

    $config->set('names', $names)->save();

    $form_state->setRebuild();
  }

  /**
   * Submit callback for deleting a name.
   */
  public function deleteNameSubmit(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $key = str_replace('delete_', '', $triggering_element['#name']);

    $config = $this->configFactory->getEditable('multisite_easy_commands.settings');
    $names = $config->get('names') ?? [];

    unset($names[$key]);
    $config->set('names', $names)->save();

    $form_state->setRebuild(TRUE);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    foreach ($values['names'] as &$subArray) {
      unset($subArray['delete']);
    }

    $config = $this->config('multisite_easy_commands.settings');
    $config->set('names', array_filter($values['names']))->save();

    // drupal_set_message($this->t('The configuration options have been saved.'));
    $this->messenger()->addMessage('The configuration options have been saved.');
    parent::submitForm($form, $form_state);
  }
  




  // $form['description'] = [
  //   '#type' => 'item',
  //   '#markup' => $this->t('This form is to add site URLs and site names.'),
  // ];

  // // Gather the number of names in the form already.
  // $num_names = $form_state->get('num_names');
  // // We have to ensure that there is at least one name field.
  // if ($num_names === NULL) {
  //   $name_field = $form_state->set('num_names', 1);
  //   $num_names = 1;
  // }

  // $form['#tree'] = TRUE;
  // $form['names_fieldset'] = [
  //   '#type' => 'fieldset',
  //   '#title' => $this->t('People coming to picnic'),
  //   '#prefix' => '<div id="names-fieldset-wrapper">',
  //   '#suffix' => '</div>',
  // ];

  // $names = $config->get('names_fieldset.name') ?? [];

  // foreach ($names as $i => $name) {
  //   $form['names_fieldset']['name'][$i] = [
  //     '#type' => 'textfield',
  //     '#title' => $this->t('Name'),
  //     '#default_value' => $name,
  //     '#attributes' => ['class' => ['site-url-field']],
  //   ];
  //   $form['names_fieldset']['delete'][$i] = [
  //     '#type' => 'submit',
  //     '#value' => $this->t('Delete'),
  //     '#attributes' => ['class' => ['delete-url']],
  //     '#submit' => ['::removeCallback'],
  //     '#ajax' => [
  //       'callback' => '::addmoreCallback',
  //       'wrapper' => 'names-fieldset-wrapper',
  //     ],
  //     '#name' => $i,
  //   ];
  // }

  // // for ($i = 0; $i < $num_names; $i++) {
  // //   $name = $config->get('names_fieldset.name.' . $i);
  // //   $form['names_fieldset']['name'][$i] = [
  // //     '#type' => 'textfield',
  // //     '#title' => $this->t('Name'),
  // //     '#default_value' => $name,
  // //   ];
  // // }

  // for ($i = count($names); $i < count($names) + $num_names; $i++) {
  //   $form['names_fieldset']['name'][$i] = [
  //     '#type' => 'textfield',
  //     '#title' => $this->t('Name'),
  //     '#attributes' => ['class' => ['site-url-field']],
  //   ];
  // }

  // $form['names_fieldset']['actions'] = [
  //   '#type' => 'actions',
  // ];
  // $form['names_fieldset']['actions']['add_name'] = [
  //   '#type' => 'submit',
  //   '#value' => $this->t('Add one more'),
  //   '#submit' => ['::addOne'],
  //   '#ajax' => [
  //     'callback' => '::addmoreCallback',
  //     'wrapper' => 'names-fieldset-wrapper',
  //   ],
  // ];
  // $form['actions']['submit'] = [
  //   '#type' => 'submit',
  //   '#value' => $this->t('Submit'),
  // ];

  // return $form;

  // /**
  //  * Callback for both ajax-enabled buttons.
  //  *
  //  * Selects and returns the fieldset with the names in it.
  //  */
  // public function addmoreCallback(array &$form, FormStateInterface $form_state) {
  //   return $form['names_fieldset'];
  // }

  // /**
  //  * Submit handler for the "add-one-more" button.
  //  *
  //  * Increments the max counter and causes a rebuild.
  //  */
  // public function addOne(array &$form, FormStateInterface $form_state) {
  //   $name_field = $form_state->get('num_names');
  //   $add_button = $name_field + 1;
  //   $form_state->set('num_names', $add_button);
  //   $form_state->setRebuild();
  // }

  // /**
  //  * Submit handler for the "remove one" button.
  //  *
  //  * Decrements the max counter and causes a form rebuild.
  //  */
  // public function removeCallback(array &$form, FormStateInterface $form_state) {
  //   // $name_field = $form_state->get('num_names');
  //   // if ($name_field > 1) {
  //   //   $remove_button = $name_field - 1;
  //   //   $form_state->set('num_names', $remove_button);
  //   // }
  //   $config = $this->configFactory->getEditable('multisite_easy_commands.settings');
  //   $key = $form_state->getTriggeringElement()['#name'];
  //   $config->clear("names_fieldset.name.$key");
  //   $config->save();
  //   // $form_state->clear("names_fieldset.name.$key");
  //   $form_state->setValues(array());
  //   $form_state->set('names_fieldset.name', $config->get('names_fieldset.name'));
  //   // Rebuild the form to remove the deleted field
  //   $form_state->setRebuild();
  // }

  // /**
  //  * {@inheritdoc}
  //  */
  // public function submitForm(array &$form, FormStateInterface $form_state) {
  //   $config = $this->configFactory->getEditable('multisite_easy_commands.settings');
  //   $values = $form_state->getValue(['names_fieldset', 'name']);
  //   $config->set('num_names', count($values));

  //   foreach ($values as $key => $value) {
  //     $config->set("names_fieldset.name.$key", $value);
  //   }

  //   $config->save();

  //   $output = $this->t('These people are coming to the picnic: @names', [
  //     '@names' => implode(', ', $values),
  //   ]);
  //   $this->messenger()->addMessage($output);
  // }

}

    // $form['terms_and_conditions'] = [
    //   // '#type' => 'textarea',
    //   '#type' => 'text_format',
    //   '#title' => 'Shop terms and conditions',
    //   // '#default_value' => $config->get('terms_and_conditions'),
    //   '#default_value' => $config->get('terms_and_conditions')['value'],
    //   '#required' => TRUE,
    // ];

    // $form['country_of_origin'] = [
    //   '#type' => 'textfield',
    //   '#title' => 'Country of all shops origins',
    //   '#default_value' => $config->get('country_of_origin'),
    // ];

    // $form['open_shops'] = [
    //   '#type' => 'checkbox',
    //   '#title' => 'Shops availability',
    //   '#default_value' => $config->get('open_shops'),
    // ];

    // $form['submit'] = [
    //   '#type' => 'submit',
    //   '#value' => 'Submit form',
    // ];

    // return $form;