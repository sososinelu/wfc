<?php

namespace Drupal\wfc_sendgrid\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\wfc_sendgrid\Entity\WfcUserConfirmation;

class WfcSendgridController extends ControllerBase
{
  /**
   * @var SendGrid library
   */
    private $sendgrid;

  /**
   * WfcSendgridController constructor.
   */
  public function __construct()
  {
    $this->sendgrid = new \SendGrid(\Drupal::state()->get('sendgrid_api_key') ? \Drupal::state()->get('sendgrid_api_key') : '');
  }

  public function emailConfirmationProcessing()
  {
    $token = \Drupal::request()->query->get('token');
    $markup = '<div class="email-confirmation container">';

    if($token) {
      if($localUserRecord = WfcUserConfirmation::getUserByToken($token)) {

        $email = $localUserRecord->get('email')->value;

        if($sendgridId = $this->sendUserToSendgrid($email)) {

          $listId = (\Drupal::state()->get('sendgrid_wfc_list_id')) ? \Drupal::state()->get('sendgrid_wfc_list_id'): '';
          if($this->manageUserLists('add', $sendgridId[0], $listId)) {
            try {
              $localUserRecord->delete();

              // Send Welcome to the club email

              $markup .= '<h4>Your email address has been verified. <br> Welcome to the club! </h4>';
              $markup .= '<p class="back-link"><a href="/">Back to homepage.</a></p>';
              $markup .= '</div>';

              return [
                '#type' => 'markup',
                '#markup' => $markup,
                '#cache' => ['max-age' => 0],
              ];

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

    return [
      '#type' => 'markup',
      '#markup' => $markup,
      '#cache' => ['max-age' => 0],
      ];
  }

  public function testSendgrid()
  {
    $sendgridId = $this->checkIfUserIsSubscribed('example3@email.com');

    var_dump($sendgridId);exit;

    return true;
  }

  public function sendSendgridEmail($subject, $toEmailAddress, $template, $passResetUrl, $token)
  {

    $email = new \SendGrid\Mail\Mail();

    $email->setFrom("info@wanderersflightclub.com", "Wanderers' Flight Club");
    $email->setSubject($subject);
    $email->addTo($toEmailAddress, "");

    switch ($template) {
      case 'email_confirmation_template':
        $bodyData = [
          '#theme' => 'email_confirmation_template',
          '#vars' => [
            "unique_url" => \Drupal::request()->getSchemeAndHttpHost().'/email-confirmation?token='.$token
          ]
        ];
        break;
      case 'new_user_template':
        $bodyData = [
          '#theme' => 'new_user_template',
          '#vars' => [
            "pass_reset" => $passResetUrl
          ]
        ];
        break;
    }

    $body =  \Drupal::service('renderer')->render($bodyData);
    $email->addContent("text/html", $body->__toString());

    try {
      $response = $this->sendgrid->send($email);

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

  public function checkIfUserIsSubscribed($email)  {
    $queryParams = json_decode('{"email": "'.$email.'"}');

    try {
      $response = $this->sendgrid->client->contactdb()->recipients()->search()->get(null, $queryParams);
      // print $response->statusCode() . "\n";
      // print_r($response->headers());
      // print $response->body() . "\n";

      $recipientCount = json_decode($response->body())->{'recipient_count'};
      $recipients = json_decode($response->body())->{'recipients'};

      if($recipientCount !== 0 && $recipients) {
        return $recipients[0]->id;
      }
    } catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

    return false;
  }

  /**
   * Add / delete users to Sendgrid lists
   *
   * @param string $type add/delete
   * @param int $userId
   * @param int $listId
   * @return boolean
   */
  public function manageUserLists($type, $userId, $listId)
  {
    if($userId && $listId) {
      try {
        switch ($type) {
          case 'add':
            $response = $this->sendgrid->client->contactdb()->lists()->_($listId)->recipients()->_($userId)->post();
            break;
          case 'remove':
            $response = $this->sendgrid->client->contactdb()->lists()->_($listId)->recipients()->_($userId)->delete();
            break;
          default:
            throw new Exception('type not defined');
            break;
        }

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

  public function removeUserFromList($userId, $listId)
  {
    if($userId && $listId) {
      try {

        $response = $this->sendgrid->client->contactdb()->lists()->_($listId)->recipients()->_($userId)->delete();
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

  public function sendUserToSendgrid($email)
  {
    $new_contact = json_decode('[
      {
        "email": "'.$email.'",
        "first_name": "",
        "last_name": ""
      }
    ]');

    try {

      $response = $this->sendgrid->client->contactdb()->recipients()->post($new_contact);
      //print $response->statusCode() . "\n";
      //print_r($response->headers());
      //print $response->body() . "\n";

      $responseData = json_decode($response->body());

      if($responseData->{"new_count"} == 1) {
        return $responseData->{"persisted_recipients"};
      }else {
        return false;
      }
    } catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
  }
}
