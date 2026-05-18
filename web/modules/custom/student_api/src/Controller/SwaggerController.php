<?php

namespace Drupal\student_api\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class SwaggerController {

  public function generate() {

    $swagger = [

      'openapi' => '3.0.0',

      'info' => [
        'title' => 'Student API',
        'version' => '1.0.0',
      ],

      'paths' => [

        '/api/student-controller' => [

          'get' => [

            'summary' => 'Get student list',

            'responses' => [

              '200' => [

                'description' => 'Successful response',
              ],
            ],
          ],
        ],

        '/api/students' => [

          'get' => [

            'summary' => 'Views REST Export API',

            'responses' => [

              '200' => [
                'description' => 'Successful response',
              ],
            ],
          ],
        ],
      ],
    ];

    return new JsonResponse($swagger);
  }

}