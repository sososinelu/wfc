<?php

namespace Drupal\wfc_stripe\Controller;


use Drupal\Core\Controller\ControllerBase;


class WfcStripeController extends ControllerBase
{
  public function stripePlan($plan)
  {
    // TO BE REMOVED

    // switch ($plan) {
    //   case 'quaterly':
    //     $stripeDetails['product_key'] = (\Drupal::state()->get('quarterly_key')) ? \Drupal::state()->get('quarterly_key'): '';
    //     $stripeDetails['price'] = (\Drupal::state()->get('quarterly_price')) ? \Drupal::state()->get('quarterly_price'): '';
    //     break;
    //   case 'semiannual':
    //     $stripeDetails['product_key'] = (\Drupal::state()->get('semiannual_key')) ? \Drupal::state()->get('semiannual_key'): '';
    //     $stripeDetails['price'] = (\Drupal::state()->get('semiannual_price')) ? \Drupal::state()->get('semiannual_price'): '';
    //     break;
    //   case 'annual':
    //     $stripeDetails['product_key'] = (\Drupal::state()->get('annual_key')) ? \Drupal::state()->get('annual_key'): '';
    //     $stripeDetails['price'] = (\Drupal::state()->get('annual_price')) ? \Drupal::state()->get('annual_price'): '';
    //     break;

    //   default:
    //     // @todo Throw exception
    //     break;
    // }

    if ($plan) {
      $stripeDetails['product_key'] = (\Drupal::state()->get($plan.'_key')) ? \Drupal::state()->get($plan.'_key'): '';
      $stripeDetails['price'] = (\Drupal::state()->get($plan.'_price')) ? \Drupal::state()->get($plan.'_price'): '';
      $stripeDetails['stripe_token'] = \Drupal::request()->request->get('stripeToken');
      $stripeDetails['email'] = \Drupal::request()->request->get('stripeEmail');

      $this->processStripePayment($stripeDetails);
    } else {
      // @todo Throw exception
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
      // @todo  Check if the customer exists and use existing customer id to create the payment
      // https://stackoverflow.com/questions/27588258/stripe-check-if-a-customer-exists

      $customer = \Stripe\Customer::create([
        'email' => $stripeDetails['email'],
        'source'  => $stripeDetails['stripe_token']
      ]);

      //\Drupal::logger('clubulcalatorilor_stripe')->notice($customer);

      $subscription = \Stripe\Subscription::create([
        'customer' => $customer->id,
        'items' => [['plan' => $stripeDetails['product_key']]],
      ]);



      // @todo
      // Add user to Sendgrid premium list
      // OR
      // move user from standard to premium list

      // @todo
      // create user and send password email
      // set email, price paid, product ID, product name, stripe customer id

      // @todo Redirect to success page / Ajax return
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
