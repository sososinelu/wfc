<?php

namespace Drupal\wfc_stripe\Controller;


use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\wfc_sendgrid\Controller\WfcSendgridController;


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
        $this->sendgrid->manageUserLists('remove', $userSendgridId, $basicListId);
      } else {
        // Add user to Sendgrid
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
          $user->set('field_subscription_date', date('Y-m-d'));
          $user->activate();
          $user->enforceIsNew();
      }

      // set price paid, product ID, product name, stripe customer id, subscription date
      $duration = $this->getMonthsFromProduct($stripeDetails['product_name']);
      $user->set('field_price',  $stripeDetails['price']);
      $user->set('field_product_id', $stripeDetails['product_key']);
      $user->set('field_product_name', $stripeDetails['product_name']);
      $user->set('field_stripe_customer_id', $stripeCustomerId);
      $user->set('field_active_subscription_start', date('Y-m-d'));
      $user->set('field_active_subscription_end', date('Y-m-d', strtotime('+'.$duration)));
      $user->save();

      $config = [
        'duration' => $duration,
        'price' => $stripeDetails['price'],
        'subscription_start' => date('d/m/Y'),
        'passResetUrl' => ''
      ];
      $title = '';
      $confirmationStatus = 'success';

       // If new user send custom password confirmation email
      if ($newUser) {
        $title = "Create account on Wanderers' Flight Club!";
        $config['passResetUrl'] = user_pass_reset_url($user);
        $confirmationStatus = 'success-new-user';
      }

      if(!$this->sendgrid->sendSendgridEmail(
        $title ?: "Welcome to Wanderers' Flight Club!",
        $stripeDetails['email'],
        'user_template',
        $config
      )) {
        // Sendgrid fail
        \Drupal::logger('wfc_stripe')->notice('Sendgrid fail - ' . $stripeDetails['email'] . ' >>> '.$exception);
        $response = new RedirectResponse(Url::fromRoute('wfc_stripe.wanderer_registration', ['status' => 'efail'])->setAbsolute()->toString());
        $response->send();
      }

      // Stripe success
      $response = new RedirectResponse(Url::fromRoute('wfc_stripe.wanderer_registration', ['status' => $confirmationStatus])->setAbsolute()->toString());
      $response->send();
    }
    catch(Exception $exception)
    {
      // Stripe fail
      \Drupal::logger('wfc_stripe')->notice('Stripe fail - Unable to sign up customer ' . $stripeDetails['email'] . ' >>> '.$exception);
      $response = new RedirectResponse(Url::fromRoute('wfc_stripe.wanderer_registration', ['status' => 'pfail'])->setAbsolute()->toString());
      $response->send();
    }

    return [];
  }

  public function registrationComplete($status)
  {
    $markup = '<div class="container registration">';

    if ($status) {
      switch ($status) {
        case 'success':
          $markup .= '<h4>Payment successful</h4><h4>Welcome to the club!</h4>';
          $markup .= '<p>You should receive an email with details of your membership purchase.</p>';
          $markup .= '<p class="back-link"><a href="/">Back to homepage</a></p>';
          break;
          case 'success-new-user':
          $markup .= '<h4>Payment successful</h4><h4>Welcome to the club!</h4>';
          $markup .= '<p>You should receive an email with details to complete your account creation.</p>';
          $markup .= '<p class="back-link"><a href="/">Back to homepage</a></p>';
          break;
        case 'pfail':
          $markup .= '<h4>Payment failed.</h4>';
          $markup .= '<p>Please go back and try again or contact us at <a class="info" href="mailto:info@wanderersflightclub.com">info@wanderersflightclub.com</a>.</p>';
          $markup .= '<p class="back-link"><a href="/wanderer">Back to Wanderer package page</a></p>';
          break;
        case 'efail':
          $markup .= '<h4>Payment successful</h4><h4>Welcome to the club!</h4>';
          $markup .= '<h4>Account creation failed.</h4>';
          $markup .= '<p>Please contact us at <a class="info" href="mailto:info@wanderersflightclub.com">info@wanderersflightclub.com</a>.</p>';
          $markup .= '<p class="back-link"><a href="/">Back to homepage</a></p>';
          break;
        default:
          $markup .= '<p class="back-link"><a href="/">Back to homepage</a></p>';
          break;
      }
    }

    $markup .= '</div>';

    return [
      '#type' => 'markup',
      '#markup' => $markup,
      '#cache' => ['max-age' => 0],
    ];
  }

  private function getMonthsFromProduct($product)
  {
    $months = '3 months';

    if ($product == 'semiannual') {
      $months = '6 months';
    } elseif ($product == 'annual') {
      $months = '12 months';
    }

    return $months;
  }
}
