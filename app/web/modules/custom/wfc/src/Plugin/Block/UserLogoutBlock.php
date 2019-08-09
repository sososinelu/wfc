<?php
/**
 * Provides the user logout block
 *
 * @Block(
 *   id = "user_logout_block",
 *   admin_label = @Translation("User Logout Block"),
 * )
 */

namespace Drupal\wfc\Plugin\Block;
use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;

class UserLogoutBlock extends BlockBase
{
  /**
   * @return array
   */
  public function build()
  {
    $currentUser = \Drupal::currentUser();
    $roles = $currentUser->getRoles();
    $current_path = \Drupal::service('path.current')->getPath();

    if ($currentUser->isAuthenticated() && in_array("premium", $roles)) {
      $content = '<div class="content-wrapper">';
      $content .= '<a href="/user">My account</a>';
      $content .= '<a href="/user/logout">Log out</a>';
      $content .= '</div>';

      return [
        '#type' => 'markup',
        '#markup' => $content,
        '#cache' => array('max-age' => 0),
      ];
    }

    return [];
  }
}
