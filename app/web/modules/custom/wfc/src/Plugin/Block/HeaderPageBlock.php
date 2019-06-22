<?php
/**
 * Provides the header page block
 *
 * @Block(
 *   id = "header_page_block",
 *   admin_label = @Translation("Header page block"),
 * )
 */

namespace Drupal\wfc\Plugin\Block;
use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;

class HeaderPageBlock extends BlockBase
{
  /**
   * @return array
   */
  public function build()
  {
    if($node = \Drupal::routeMatch()->getParameter('node')) {
      $slogan = (\Drupal::state()->get('site_slogan') ? \Drupal::state()->get('site_slogan')['value'] : '');
      $sign_up_text = (\Drupal::state()->get('sign_up_text') ? \Drupal::state()->get('sign_up_text')['value'] : '');

      return array(
        '#theme' => 'header_page_template',
        '#vars' => array(
          'slogan' => $slogan,
          'sign_up_text' => $sign_up_text
        ),
        '#cache' => array('max-age' => 0),
      );
    }

    return array();
  }
}
