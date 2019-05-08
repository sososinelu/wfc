<?php
/**
 * @file
 * Contains \Drupal\wfc_settings\Form\WfcSettingsConfigForm.
 */

namespace Drupal\wfc_settings\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Wanderers' Flight Club Settings Config form.
 */
class WfcSettingsConfigForm extends FormBase
{
  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'wfc_settings_forms_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $formState)
  {

    $form['wfc'] = array(
        '#type' => 'vertical_tabs',
    );

    /**
     * General tab
     */

    $form['general'] = array(
      '#type' => 'details',
      '#title' => t('General'),
      '#collapsible' => TRUE,
      '#group'       => 'wfc'
    );

    // Info email address
    $form['general']['info_email'] = array(
      '#type' => 'textfield',
      '#title' => t('Info email address'),
      '#default_value' => (\Drupal::state()->get('info_email')) ? \Drupal::state()->get('info_email'): '',
    );

    // Facebook page
    $form['general']['facebook'] = array(
      '#type' => 'textfield',
      '#title' => t('Facebook page'),
      '#default_value' => (\Drupal::state()->get('facebook')) ? \Drupal::state()->get('facebook'): '',
    );

    // Instagram page
    $form['general']['instagram'] = array(
      '#type' => 'textfield',
      '#title' => t('Instagram page'),
      '#default_value' => (\Drupal::state()->get('instagram')) ? \Drupal::state()->get('instagram'): '',
    );

    // Site slogan
    $siteSloganDefault = \Drupal::state()->get('site_slogan');
    $form['general']['site_slogan'] = array(
      '#type' => 'text_format',
      '#title' => t('Site slogan'),
      '#format' => 'basic_html',
      '#allowed_formats' => array('basic_html'),
      '#default_value' => ($siteSloganDefault) ? $site_slogan_default['value'] : '',
    );

    // Sign up text
    $signUpTextDefault = \Drupal::state()->get('sign_up_text');
    $form['general']['sign_up_text'] = array(
      '#type' => 'text_format',
      '#title' => t('Sign up text'),
      '#format' => 'basic_html',
      '#allowed_formats' => array('basic_html'),
      '#default_value' => ($signUpTextDefault) ? $signUpTextDefault['value'] : '',
    );

    /**
     * Footer tab
     */

    $form['footer'] = array(
      '#type' => 'details',
      '#title' => t('Footer'),
      '#collapsible' => TRUE,
      '#group'       => 'wfc'
    );

    // Footer sign up text
    $footerSignUpTextDefault = \Drupal::state()->get('footer_sign_up_text');
    $form['footer']['footer_sign_up_text'] = array(
      '#type' => 'text_format',
      '#title' => t('Footer sign up text'),
      '#format' => 'basic_html',
      '#allowed_formats' => array('basic_html'),
      '#default_value' => ($footerSignUpTextDefault) ? $footerSignUpTextDefault['value'] : '',
    );

    // Info email address
    $form['footer']['footer_contact_text'] = array(
      '#type' => 'textfield',
      '#title' => t('Footer contact text'),
      '#default_value' => (\Drupal::state()->get('footer_contact_text')) ? \Drupal::state()->get('footer_contact_text'): '',
    );

    /**
     * Overlay tab
     */

    $form['overlay'] = array(
      '#type' => 'details',
      '#title' => t('Site-wide overlay'),
      '#collapsible' => TRUE,
      '#group'       => 'wfc'
    );

    // Overlay title text
    $form['overlay']['overlay_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Overlay title'),
      '#default_value' => (\Drupal::state()->get('overlay_title')) ? \Drupal::state()->get('overlay_title'): '',
    );

    // Overlay message text
    $messageDefault = \Drupal::state()->get('overlay_text');
    $form['overlay']['overlay_text'] = array(
      '#type' => 'text_format',
      '#title' => t('Overlay message'),
      '#format' => 'restricted_html',
      '#allowed_formats' => array('restricted_html'),
      '#default_value' => ($messageDefault) ? $messageDefault['value'] : '',
    );

    /**
     * SendGrid tab
     */

    $form['sendgrid'] = array(
      '#type' => 'details',
      '#title' => t('SendGrid'),
      '#collapsible' => TRUE,
      '#group'       => 'wfc'
    );

    $form['sendgrid']['sendgrid_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('SendGrid API Key'),
      '#default_value' => (\Drupal::state()->get('sendgrid_api_key')) ? \Drupal::state()->get('sendgrid_api_key'): '',
    );

    $form['sendgrid']['sendgrid_wfc_list_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Wanderers\' Flight Club List ID'),
      '#default_value' => (\Drupal::state()->get('sendgrid_wfc_list_id')) ? \Drupal::state()->get('sendgrid_wfc_list_id'): '',
    );

    $form['sendgrid']['sendgrid_wfc_premiumlist_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Wanderers\' Flight Club Premium List ID'),
      '#default_value' => (\Drupal::state()->get('sendgrid_wfc_premiumlist_id')) ? \Drupal::state()->get('sendgrid_wfc_premiumlist_id'): '',
    );

    /**
     * Stripe tab
     */

    $form['stripe'] = array(
      '#type' => 'details',
      '#title' => t('Stripe'),
      '#collapsible' => TRUE,
      '#group'       => 'wfc'
    );

    // Secret Api key
    $form['stripe']['stripe_secret_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Stripe Secret API Key'),
      '#default_value' => (\Drupal::state()->get('stripe_secret_api_key')) ? \Drupal::state()->get('stripe_secret_api_key'): '',
    );

    // Quarterly
    $form['stripe']['quarterly_text'] = [
      '#markup' => $this->t('Quarterly plan'),
    ];

    $form['stripe']['quarterly_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Quarterly plan product key'),
      '#default_value' => (\Drupal::state()->get('quarterly_key')) ? \Drupal::state()->get('quarterly_key'): '',
    );

    $form['stripe']['quarterly_price'] = array(
      '#type' => 'textfield',
      '#title' => t('Quarterly plan product price'),
      '#default_value' => (\Drupal::state()->get('quarterly_price')) ? \Drupal::state()->get('quarterly_price'): '',
    );

    // Semi-annual
    $form['stripe']['semiannual_text'] = [
      '#markup' => $this->t('Semi-annual plan'),
    ];

    $form['stripe']['semiannual_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Semi-annual plan product key'),
      '#default_value' => (\Drupal::state()->get('semiannual_key')) ? \Drupal::state()->get('semiannual_key'): '',
    );

    $form['stripe']['semiannual_price'] = array(
      '#type' => 'textfield',
      '#title' => t('Semi-annual plan product price'),
      '#default_value' => (\Drupal::state()->get('semiannual_price')) ? \Drupal::state()->get('semiannual_price'): '',
    );

    // Annual
    $form['stripe']['annual_text'] = [
      '#markup' => $this->t('Annual plan'),
    ];

    $form['stripe']['annual_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Annual plan product key'),
      '#default_value' => (\Drupal::state()->get('annual_key')) ? \Drupal::state()->get('annual_key'): '',
    );

    $form['stripe']['annual_price'] = array(
      '#type' => 'textfield',
      '#title' => t('Annual plan product price'),
      '#default_value' => (\Drupal::state()->get('annual_price')) ? \Drupal::state()->get('annual_price'): '',
    );

    /**
     * Submit button
     */

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $formState)
  {
    $formValues = $formState->getValues();
    foreach ($formValues as $key => $value) {
      \Drupal::state()->set($key, $value);
      if ($key == 'hub_default_image' || $key == 'article_default_image') {
        if (isset($value[0])) {
          $file = File::load($value[0]);
          $file->setPermanent();
          $file->save();
        }
      }
    }
  }
}
?>
