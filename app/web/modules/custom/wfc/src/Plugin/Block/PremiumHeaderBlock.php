<?php
/**
 * Provides the premium header block
 *
 * @Block(
 *   id = "premium_header_block",
 *   admin_label = @Translation("Premium header Block"),
 * )
 */

namespace Drupal\wfc\Plugin\Block;
use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;

class PremiumHeaderBlock extends BlockBase
{
  /**
   * @return array
   */
  public function build()
  {
    if($node = \Drupal::routeMatch()->getParameter('node')) {
      $title = $node->getTitle();
      $intro_text = ($node->body ? $node->body->value : '');

      return array(
        '#theme' => 'premium_header_template',
        '#vars' => array(
          'title' => $title,
          'intro_text' => $intro_text
        ),
        '#cache' => array('max-age' => 0),
      );
    }

    return array();
  }
}
