<?php
/**
 * Provides the homepage header branding block
 *
 * @Block(
 *   id = "homepage_header_branding_block",
 *   admin_label = @Translation("Homepage header branding block"),
 * )
 */

namespace Drupal\wfc\Plugin\Block;
use Drupal\Core\Block\BlockBase;

class HomepageHeaderBranding extends BlockBase
{
  /**
   * @return array
   */
  public function build()
  {
    $content = '<div class="homepage-header-logo">';
    $content .= '<a href="/" title="Home" rel="home">';
    $content .= '<img src="/themes/custom/wfc_theme/images/cc_logo.png" title="Wanderers\' Flight Club logo" alt="Wanderers\' Flight Club logo">';
    $content .= '</a></div>';

    return array(
      '#type' => 'markup',
      '#markup' => $content,
      '#cache' => array('max-age' => 0),
    );

  }
}
