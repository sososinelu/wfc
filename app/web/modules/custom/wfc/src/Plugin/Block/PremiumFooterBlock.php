<?php
/**
 * Provides the premium footer block
 *
 * @Block(
 *   id = "premium_footer_block",
 *   admin_label = @Translation("Premium footer Block"),
 * )
 */

namespace Drupal\wfc\Plugin\Block;
use Drupal\block\Entity\Block;
use Drupal\Core\Block\BlockBase;

class PremiumFooterBlock extends BlockBase
{
  /**
   * @return array
   */
  public function build()
  {
    if($node = \Drupal::routeMatch()->getParameter('node')) {
      $footer_title = ($node->field_payment ? $node->field_payment->entity->field_group_title->value  : '');
      $footer_text = ($node->field_payment ? $node->field_payment->entity->field_group_text->value : '');

      $markup = '<h2>' . $footer_title . '</h2>';
      $markup .= '<p>' . $footer_text . '</p>';
      $markup .= '<a href="https://stripe.com/" target="_blank" title="Powered by Stripe"><img src="/themes/custom/wfc_boot/images/premium/powered_by_stripe_white.svg" alt="Stripe logo" class="powered-by-stripe"></a>';

      return array(
        '#type' => 'markup',
        '#markup' => $markup,
        '#cache' => array('max-age' => 0),
      );
    }

    return array();
  }
}
