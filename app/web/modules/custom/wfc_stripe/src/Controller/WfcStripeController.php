<?php

namespace Drupal\wfc_stripe\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\wfc_sendgrid\Controller\WfcSendgridController;


class WfcStripeController extends ControllerBase
{
  /**
   * @var SendGrid Controller
   */
  private $sendgrid;

  public function __construct(WfcSendgridController $sendgrid)
  {
    $this->sendgrid = $sendgrid;
  }

  public function stripePlan($plan)
  {
    if ($plan) {
      $stripeDetails['product_key'] = (\Drupal::state()->get($plan.'_key')) ? \Drupal::state()->get($plan.'_key'): '';
      $stripeDetails['price'] = (\Drupal::state()->get($plan.'_price')) ? \Drupal::state()->get($plan.'_price'): '';
      $stripeDetails['stripe_token'] = \Drupal::request()->request->get('stripeToken');
      $stripeDetails['email'] = \Drupal::request()->request->get('stripeEmail');

      $this->processStripePayment($stripeDetails);
    } else {
      \Drupal::logger('wfc_stripe')->notice('Stripe subscription plan missing.');
    }
  }

  public function processStripePayment($stripeDetailsl)
  {
    // 4242 4242 4242 4242

    // Stripe secret API key
    //\Stripe\Stripe::setApiKey("sk_test_pRgltPtYdkjnr3skB3NkQMxo");
    $stripeSecretPpiKey = (\Drupal::state()->get('stripe_secret_api_key')) ? \Drupal::state()->get('stripe_secret_api_key'): '';
    \Stripe\Stripe::setApiKey($stripeSecretPpiKey);

    try
    {
      // @todo
      // Check if the customer exists and use existing customer id to create the payment
      // https://stackoverflow.com/questions/27588258/stripe-check-if-a-customer-exists



      $user = \Drupal::user_load_by_email($stripeDetails['email']);
      $stripeCustomerId = $user->get('field_stripe_customer_id')->value ?? false;

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

      // @todo
      // Check if the user is on SendGrid

      // Modify checkIfUserIsSubscribed to return the id of the user if subscribed
      if($userId = $this->sendgrid->checkIfUserIsSubscribed($stripeDetails['email'])) {
        // Add user to Sendgrid premium list
        $this->sendgrid->moveUserToList($userId, $premiumListId);
      } else {
        // Add user to Sendgrid premium & basic lists
        if($sendgridId = $this->sendgrid->sendUserToSendgrid($stripeDetails['email'])) {
          // Move user to basic list on SendGrid
          $this->sendgrid->moveUserToList($sendgridId[0], $basicListId);

          // Move user to premium list on SendGrid
          $this->sendgrid->moveUserToList($sendgridId[0], $premiumListId);
        }
      }

      // @todo
      // check if user exists
      // Update details
      // OR
      // create user and send password email
      // set email, price paid, product ID, product name, stripe customer id

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
