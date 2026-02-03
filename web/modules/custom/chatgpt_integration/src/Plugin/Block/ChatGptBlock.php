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

//   public function build() {
//     return [
//         '#type' => 'container',
//         '#attributes' => ['class' => ['chatgpt-box']],
//         'message' => [
//         '#type' => 'textfield',
//         '#attributes' => [
//             'id' => 'chat-message',
//             'placeholder' => 'Ask something...',
//         ],
//         ],
//         'send' => [
//         '#type' => 'html_tag',
//         '#tag' => 'button',
//         '#value' => 'Send',
//         '#attributes' => [
//             'id' => 'chat-send',
//             'type' => 'button',
//         ],
//         ],
//         'response' => [
//         '#type' => 'container',
//         '#attributes' => ['id' => 'chat-response'],
//         ],
//         '#attached' => [
//         'library' => [
//             'chatgpt_integration/chatgpt',
//         ],
//         ],
//         '#cache' => [
//         'max-age' => 0,
//         ],
//     ];
//   }
}
