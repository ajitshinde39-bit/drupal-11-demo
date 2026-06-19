<?php

namespace Drupal\chatgpt_integration\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Provides a ChatGPT Chat Block.
 *
 * @Block(
 *   id = "chatgpt_chat_block",
 *   admin_label = @Translation("ChatGPT Chat Block")
 * )
 */
class ChatGptBlock extends BlockBase {

    public function build() {
        return \Drupal::formBuilder()->getForm(
            'Drupal\chatgpt_integration\Form\ChatGptForm'
        );
    }
}
