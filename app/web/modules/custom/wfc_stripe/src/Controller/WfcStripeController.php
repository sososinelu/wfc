<?php

namespace Drupal\wfc_stripe\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\wfc_sendgrid\Controller\WfcSendgridController;
use Drupal\user\Entity\User;


class WfcStripeController extends ControllerBase
{
  /**
   * @var SendGrid Controller
   */
  private $sendgrid;

  public function __construct()
  {
    $this->sendgrid = new WfcSendgridController;
  }

  public function stripePlan($plan)
  {
    if ($plan) {
      $stripeDetails['product_key'] = (\Drupal::state()->get($plan.'_key')) ? \Drupal::state()->get($plan.'_key'): '';
      $stripeDetails['product_name'] = $plan;
      $stripeDetails['price'] = (\Drupal::state()->get($plan.'_price')) ? \Drupal::state()->get($plan.'_price'): '';
      $stripeDetails['stripe_token'] = \Drupal::request()->request->get('stripeToken');
      $stripeDetails['email'] = \Drupal::request()->request->get('stripeEmail');

      $this->processStripePayment($stripeDetails);
    } else {
      \Drupal::logger('wfc_stripe')->notice('Stripe subscription plan missing.');
    }
  }

  public function processStripePayment($stripeDetails)
  {
    // 4242 4242 4242 4242

    // Stripe secret API key
    $stripeSecretPpiKey = (\Drupal::state()->get('stripe_secret_api_key')) ? \Drupal::state()->get('stripe_secret_api_key'): '';
    \Stripe\Stripe::setApiKey($stripeSecretPpiKey);

    try
    {
      // Check if the customer exists and use existing customer id to create the payment
      // https://stackoverflow.com/questions/27588258/stripe-check-if-a-customer-exists
      $user = user_load_by_mail($stripeDetails['email']);
      $stripeCustomerId = ($user ? $user->get('field_stripe_customer_id')->value : false);

      // If user doens't exists create new Stripe customer
      if (!$stripeCustomerId) {
        $customer = \Stripe\Customer::create([
          'email' => $stripeDetails['email'],
          'source'  => $stripeDetails['stripe_token']
        ]);

        $stripeCustomerId = $customer->id;
      }

      // If we have a customer ID create a new Stripe subscription
      $subscription = \Stripe\Subscription::create([
        'customer' => $stripeCustomerId,
        'items' => [['plan' => $stripeDetails['product_key']]],
      ]);

      $premiumListId = (\Drupal::state()->get('sendgrid_wfc_premiumlist_id')) ? \Drupal::state()->get('sendgrid_wfc_premiumlist_id'): '';
      $basicListId = (\Drupal::state()->get('sendgrid_wfc_list_id')) ? \Drupal::state()->get('sendgrid_wfc_list_id'): '';

      // Check if the user is on SendGrid
      if($userSendgridId = $this->sendgrid->checkIfUserIsSubscribed($stripeDetails['email'])) {
        // Add user to Sendgrid premium list
        $this->sendgrid->manageUserLists('add', $userSendgridId, $premiumListId);

        // Remove from Sendgrid basic list
        $this->sendgrid->manageUserLists('remove', $userSendgridId, $premiumListId);
      } else {
        // Add user to Sendgrid premium list
        if($sendgridId = $this->sendgrid->sendUserToSendgrid($stripeDetails['email'])) {
          // Move user to premium list on SendGrid
          $this->sendgrid->manageUserLists('add', $sendgridId[0], $premiumListId);
        }
      }

      $newUser = false;

      // Check if user exists
      if (!$user) {
          // Create user object.
          $user = User::create();
          $newUser = true;

          // Mandatory settings
          $user->setPassword("password");
          $user->enforceIsNew();
          $user->setEmail($stripeDetails['email']);
          $user->setUsername($stripeDetails['email']);
          $user->addRole('premium'); // premium
      }

      // set price paid, product ID, product name, stripe customer id, subscription date
      $user->set('field_price',  $stripeDetails['price']);
      $user->set('field_product_id', $stripeDetails['product_key']);
      $user->set('field_product_name', $stripeDetails['product_name']);
      $user->set('field_stripe_customer_id', $stripeCustomerId);
      $user->save();

       // If new user send custom password confirmation email
      if ($newUser) {
        $passResetUrl = user_pass_reset_url($user);
        if($this->sendgrid->sendSendgridEmail(
          'Create account on Wanderers\' Flight Club!',
          $email,
          'new_user_template',
          $passResetUrl,
          false
        )) {
        $response->addCommand(
          new HtmlCommand(
            '.result_message',
            'Your confirmation email has been sent. <br> Please check your inbox and confirm your subscription .'
          )
        );

        $response->addCommand(new InvokeCommand('.form-email', 'val', ['']));
      }
      }

      // @todo
      // Redirect to success page / Ajax return
      exit('Stripe success');
    }
    catch(Exception $exception)
    {
      // @todo Redirect to fail page / Ajax return
      \Drupal::logger('wfc_stripe')->notice('Unable to sign up customer ' . $stripeDetails['email'] . ' >>> '.$exception);
      exit('Stripe fail');
    }
  }
}
