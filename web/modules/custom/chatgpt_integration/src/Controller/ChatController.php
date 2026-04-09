<?php
namespace Drupal\chatgpt_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

class ChatController extends ControllerBase {

    public function chat(Request $request) {
    $message = $request->query->get('message');
    $apiKey = \Drupal::service('settings')->get('openai_api_key');

    $client = \Drupal::httpClient();

    try {
        $response = $client->post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'model' => 'gpt-4o-mini',
            'messages' => [
            ['role' => 'user', 'content' => $message],
            ],
            'max_tokens' => 150,
        ],
        'timeout' => 30,
        ]);

        $data = json_decode($response->getBody(), TRUE);

        return new JsonResponse([
        'status' => 'success',
        'reply' => $data['choices'][0]['message']['content'] ?? '',
        ], 200);
    }
    catch (ClientException $e) {
        // 🔥 OpenAI returned 4xx (429, 401, 403, etc.)
        $responseBody = $e->getResponse()
        ? (string) $e->getResponse()->getBody()
        : 'No response body';

        \Drupal::logger('chatgpt_integration')->error(
        'OpenAI Client Error (@code): @body',
        [
            '@code' => $e->getCode(),
            '@body' => $responseBody,
        ]
        );

        return new JsonResponse([
        'status' => 'error',
        'message' => 'Technical issue. Please try again later.',
        ], 500);
    }
    catch (RequestException $e) {
        // 🔥 Network / timeout / DNS issues
        \Drupal::logger('chatgpt_integration')->error(
        'OpenAI Request Error: @message',
        ['@message' => $e->getMessage()]
        );

        return new JsonResponse([
        'status' => 'error',
        'message' => 'Technical issue. Please try again later.',
        ], 500);
    }
    catch (\Exception $e) {
        // 🔥 Any unexpected error
        \Drupal::logger('chatgpt_integration')->error(
        'Unexpected ChatGPT error: @message',
        ['@message' => $e->getMessage()]
        );

        return new JsonResponse([
        'status' => 'error',
        'message' => 'Technical issue. Please try again later.',
        ], 500);
    }
    }

//   public function chat(Request $request) {
//     // $message = $request->get('message');
//     $message = trim($request->get('message'));

//     if (strlen($message) > 500) {
//     return new JsonResponse(['error' => 'Message too long'], 400);
//     }
//     //start 5 requests per minute per IP
//     $flood = \Drupal::service('flood');
//     $identifier = $request->getClientIp();

//     if (!$flood->isAllowed('chatgpt_request', 5, 60, $identifier)) {
//     return new JsonResponse(['error' => 'Too many requests'], 429);
//     }

//     $flood->register('chatgpt_request', 60, $identifier);
//     // end 5 requests per minute per IP
//     $apiKey = \Drupal::service('settings')->get('openai_api_key');

//     if (!$message || !$apiKey) {
//       return new JsonResponse(['error' => 'Invalid request'], 400);
//     }

//     try {
//       $client = \Drupal::httpClient();

//       $response = $client->post('https://api.openai.com/v1/chat/completions', [
//         'headers' => [
//           'Authorization' => 'Bearer ' . $apiKey,
//           'Content-Type' => 'application/json',
//         ],
//         'json' => [
//           'model' => 'gpt-4o-mini',
//           'messages' => [
//             ['role' => 'user', 'content' => $message],
//           ],
//         ],
//       ]);

//       $data = json_decode($response->getBody(), TRUE);

//       return new JsonResponse([
//         'reply' => $data['choices'][0]['message']['content'],
//       ]);

//     } 
//     catch (\Exception $e) {
//         \Drupal::logger('chatgpt_integration')
//             ->error($e->getMessage());

//         return new JsonResponse([
//             'error' => $e->getMessage(),
//         ], 500);
//     }
//     // catch (RequestException $e) {
//     //   return new JsonResponse(['error' => 'API error'], 500);
//     // }
//   }
}
