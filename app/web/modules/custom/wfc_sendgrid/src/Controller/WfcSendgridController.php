<?php

namespace Drupal\wfc_sendgrid\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\wfc_sendgrid\Entity\WfcUserConfirmation;


class WfcSendgridController extends ControllerBase
{
  /**
   * WfcSendgridController constructor.
   */
  public function __construct()
  {

  }

  public function emailConfirmationProcessing()
  {
    $token = \Drupal::request()->query->get('token');
    $sendgrid = new \SendGrid(\Drupal::state()->get('sendgrid_api_key') ? \Drupal::state()->get('sendgrid_api_key') : '');
    $markup = '<div class="email-confirmation outer-wrapper">';

    if($token) {
      if($local_user_record = WfcUserConfirmation::getUserByToken($token)) {

        $email = $local_user_record->get('email')->value;

        if($sendgrid_id = self::sendUserToSendgrid($sendgrid, $email)) {
          if(self::moveUserToList($sendgrid, $sendgrid_id[0])) {
            try {
              $local_user_record->delete();

              // Send Bine ai venit in club email

              $markup .= '<h4>Your email address has been verified. <br> Welcome to the club! </h4>';
              $markup .= '<p class="back-link"><a href="/">Back to homepage.</a></p>';
              $markup .= '</div>';

              return array(
                '#type' => 'markup',
                '#markup' => $markup,
                '#cache' => array('max-age' => 0),
              );

            }catch (Exception $exception) {
              \Drupal::logger('wfc_sendgrid')->notice('Delete local user error: $email >>> '.$exception);
            }
          }
        }
      }
    }

    $markup .= '<h4>We couldn\'t verify you! <br> Please try to register again or contact us at <a class="info" href="mailto:info@wanderersflightclub.com">info@wanderersflightclub.com</a></h4>';
    $markup .= '<p class="back-link"><a href="/">Back to homepage.</a></p>';
    $markup .= '</div>';

    return array(
      '#type' => 'markup',
      '#markup' => $markup,
      '#cache' => array('max-age' => 0),
    );
  }

  public function testSendgrid()
  {
    $sendgrid = new \SendGrid(\Drupal::state()->get('sendgrid_api_key') ? \Drupal::state()->get('sendgrid_api_key') : '');
    $sendgrid_id = self::sendUserToSendgrid($sendgrid, "example3@email.com");

    var_dump($sendgrid_id);exit;

    return true;
  }

  public static function sendConfirmationEmail($sendgrid, $token, $email_address)
  {

    $email = new \SendGrid\Mail\Mail();

    $email->setFrom("info@wanderersflightclub.com", "Wanderers\' Flight Club");
    $email->setSubject("Please confirm your subscription to Wanderers\' Flight Club!");
    $email->addTo($email_address, "");

    $body_data = array (
      '#theme' => 'email_confirmation_template',
      '#vars' => array(
        "unique_url" => \Drupal::request()->getSchemeAndHttpHost().'/email-confirmation?token='.$token
      )
    );

    $body =  \Drupal::service('renderer')->render($body_data);
    $email->addContent("text/html", $body->__toString());

    try {
      $response = $sendgrid->send($email);

      // print $response->statusCode() . "\n";
      // print_r($response->headers());
      // print $response->body() . "\n";

      if (strpos($response->statusCode(), '20') !== false) {
        return true;
      }else {
        return false;
      }

    } catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
  }

  public static function checkIfUserIsSubscribed($sendgrid, $email)
  {
    $query_params = json_decode('{"email": "'.$email.'"}');

    try {
      $response = $sendgrid->client->contactdb()->recipients()->search()->get(null, $query_params);
      // print $response->statusCode() . "\n";
      // print_r($response->headers());
      // print $response->body() . "\n";

      $recipient_count = json_decode($response->body())->{'recipient_count'};

      if($recipient_count !== 0) {
        return true;
      }
    } catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return false;
  }

  public static function moveUserToList($sendgrid, $user_id)
   {
    $list_id = (\Drupal::state()->get('sendgrid_wfc_list_id')) ? \Drupal::state()->get('sendgrid_wfc_list_id'): '';

    if($user_id && $list_id) {
      try {

        $response = $sendgrid->client->contactdb()->lists()->_($list_id)->recipients()->_($user_id)->post();
        // print $response->statusCode() . "\n";
        // print_r($response->headers());
        // print $response->body() . "\n";

        if (strpos($response->statusCode(), '20') !== false) {
          return true;
        }else {
          return false;
        }
      } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
      }
    }
  }

  public static function sendUserToSendgrid($sendgrid, $email)
  {

    $new_contact = json_decode('[
      {
        "email": "'.$email.'",
        "first_name": "",
        "last_name": ""
      }
    ]');

    try {

      $response = $sendgrid->client->contactdb()->recipients()->post($new_contact);
      //print $response->statusCode() . "\n";
      //print_r($response->headers());
      //print $response->body() . "\n";

      $response_data = json_decode($response->body());

      if($response_data->{"new_count"} == 1) {
        return $response_data->{"persisted_recipients"};
      }else {
        return false;
      }
    } catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
  }
}
