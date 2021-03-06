<?php
/**
 * Provides the stripe test form block
 *
 * @Block(
 *   id = "stripe_test_form_block",
 *   admin_label = @Translation("Stripe test form block"),
 * )
 */

namespace Drupal\wfc_stripe\Plugin\Block;
use Drupal\Core\Block\BlockBase;


class StripeTestFormBlock extends BlockBase
{
  /**
   * @return array
   */
  public function build()
  {
    if($node = \Drupal::routeMatch()->getParameter('node')) {


      return [
        '#theme' => 'stripe_test_form',
        '#vars' => [],
        '#cache' => ['max-age' => 0],
      ];
    }

    return [];
  }
}
