<?php
/**
 * @file
 * Contains \Drupal\wfc_sendgrid\Form\SendGridEmailRegistrationForm.
 */

namespace Drupal\wfc_sendgrid\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Component\Utility\Crypt;
use Drupal\wfc_sendgrid\Controller\WfcSendgridController;
use Drupal\wfc_sendgrid\Entity\WfcUserConfirmation;

/**
 * SendGrid email registration form.
 */
class SendGridEmailRegistrationForm extends FormBase {

  /**
   * @var SendGrid controller
   */
  private $sendgrid;

  public function __construct() {
    $this->sendgrid = new WfcSendgridController;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sendgrid_email_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState) {
    $form['email'] = [
      '#type' => 'email',
      '#title' => t('I want cheap flights!'),
      '#attributes' => [
        'placeholder' => t('I want cheap flights!'),
      ],
      '#required' => FALSE
    ];

    $form['markup'] = [
      '#type' => 'markup',
      '#markup' => '<div class="submit-wrapper">'
    ];

    // Submit button
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Sign-up!'),
      '#ajax' => [
        'callback' => '::processSubmit',
      ],
    ];

    $form['markup1'] = [
      '#type' => 'markup',
      '#markup' => '</div>'
    ];

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="result_message"></div>'
    ];

    \Drupal::service('page_cache_kill_switch')->trigger();

    $form['#cache'] = [
      'max-age' => 0
    ];

    return $form;
  }

  public function processSubmit(array $form, FormStateInterface $formState) {
    $email = $formState->getValue('email');
    $response = new AjaxResponse();

    // No email address provided
    if (empty($email)) {
      $response->addCommand(
        new HtmlCommand(
          '.result_message',
          'Please enter your email address to signup.'
        )
      );

      return $response;
    }

    // Email address is not valid
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $response->addCommand(
        new HtmlCommand(
          '.result_message',
          'Your email address is invalid. Please check your email address and try again.'
        )
      );

      return $response;
    }

    // Check if the user is already subscribed
    if($this->sendgrid->checkIfUserIsSubscribed($email)) {
      $response->addCommand(
        new HtmlCommand(
          '.result_message',
          'It looks like your email address is already registered! <br> If you don\'t receive our flight offers please contact us on <a href="mailto:info@wanderersflightclub.com">info@wanderersflightclub.com</a>'
        )
      );

      return $response;
    }

    $localUserRecord = WfcUserConfirmation::getUserByEmail($email);

    // Check if the user already tried to register
    if(!$localUserRecord) {
      $token = Crypt::hashBase64($email);
      $details = WfcUserConfirmation::create([
        'email' => $email,
        'token' => $token,
        'date' => date("Y-m-d h:i:sa")
      ]);
      $details->save();
    }else{
      $token = $localUserRecord->get('token')->value;
    }

    if($this->sendgrid->sendSendgridEmail(
        'Please confirm your subscription to Wanderers\' Flight Club!',
        $email,
        'email_confirmation_template',
        false,
        $token
      )) {
      $response->addCommand(
        new HtmlCommand(
          '.result_message',
          'Your confirmation email has been sent. <br> Please check your inbox and confirm your subscription .'
        )
      );

      $response->addCommand(new InvokeCommand('.form-email', 'val', ['']));
    }else {
      $response->addCommand(
        new HtmlCommand(
          '.result_message',
          'Your confirmation email failed to sent. Please try again.'
        )
      );
    }

    return $response;
  }

  public function submitForm(array &$form, FormStateInterface $formState) {
  }
}
