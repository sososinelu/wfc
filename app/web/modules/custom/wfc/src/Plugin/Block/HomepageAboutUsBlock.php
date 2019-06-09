<?php
/**
 * Provides the homepage about us block
 *
 * @Block(
 *   id = "homepage_about_us_block",
 *   admin_label = @Translation("Homepage about us block"),
 * )
 */

namespace Drupal\wfc\Plugin\Block;
use Drupal\Core\Block\BlockBase;

class HomepageAboutUsBlock extends BlockBase
{
  /**
   * @return array
   */
  public function build()
  {
    if($node = \Drupal::routeMatch()->getParameter('node')) {
      $title = ($node->field_about_us_title	 ? $node->field_about_us_title->value	 : '');
      $content = ($node->field_about_us_content	 ? $node->field_about_us_content->value	 : '');
      $images = ($node->field_about_us_images ? $node->field_about_us_images : '');

      return array(
        '#theme' => 'homepage_about_us_template',
        '#vars' => array(
          'title' => $title,
          'content' => $content,
          'images' => $images,
        ),
        '#cache' => array('max-age' => 0),
      );
    }

    return array();
  }
}
