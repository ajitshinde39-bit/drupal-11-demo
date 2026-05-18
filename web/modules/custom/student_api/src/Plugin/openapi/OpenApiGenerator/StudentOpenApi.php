<?php

namespace Drupal\student_api\Plugin\openapi\OpenApiGenerator;

use Drupal\openapi\Annotation\OpenApiGenerator;
use Drupal\openapi\Plugin\openapi\OpenApiGeneratorBase;
//use Drupal\Core\Routing\RouteProviderInterface;
//use Symfony\Component\DependencyInjection\ContainerInterface;
//use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * @OpenApiGenerator(
 *   id = "student_openapi",
 *   label = @Translation("Student OpenAPI")
 * )
 */
//class StudentOpenApi extends OpenApiGeneratorBase implements ContainerFactoryPluginInterface {
class StudentOpenApi extends OpenApiGeneratorBase {

  //protected $routeProvider;

//   public function __construct(
//     array $configuration,
//     $plugin_id,
//     $plugin_definition,
//     RouteProviderInterface $route_provider
//   ) {

//     parent::__construct(
//       $configuration,
//       $plugin_id,
//       $plugin_definition
//     );

//     $this->routeProvider = $route_provider;
//   }

//   public static function create(
//     ContainerInterface $container,
//     array $configuration,
//     $plugin_id,
//     $plugin_definition
//   ) {

//     return new static(
//       $configuration,
//       $plugin_id,
//       $plugin_definition,
//       $container->get('router.route_provider')
//     );
//   }

  public function getSpecification() {

    $spec = parent::getSpecification();
    unset($spec['swagger']);
    
    $spec['openapi'] = '3.0.0';

    $spec['info'] = [
      'title' => 'Student APIs',
      'version' => '1.0.0',
    ];

    $spec['paths'] = [];

    // Controller API.
    // $spec['paths']['/api/student-controller'] = [
    //   'get' => [
    //     'summary' => 'Student Controller API',
    //     'responses' => [
    //       '200' => [
    //         'description' => 'Successful response',
    //       ],
    //     ],
    //   ],
    // ];

    $spec['paths']['/api/student/{id}'] = [

        'get' => [

            'summary' => 'Get single student by ID',

            'parameters' => [

            [
                'name' => 'id',
                'in' => 'path',
                'required' => TRUE,

                'schema' => [
                'type' => 'integer',
                ],

                'example' => 1,
            ],
            ],

            'responses' => [

            '200' => [

                'description' => 'Successful response',

                'content' => [

                'application/json' => [

                    'schema' => [

                    'type' => 'object',

                    'properties' => [

                        'id' => [
                        'type' => 'integer',
                        ],

                        'name' => [
                        'type' => 'string',
                        ],

                        'email' => [
                        'type' => 'string',
                        ],

                        'age' => [
                        'type' => 'integer',
                        ],
                    ],
                    ],
                ],
                ],
            ],

            '404' => [
                'description' => 'Student not found',
            ],
            ],
        ],
    ];

    // Views REST Export API.
    $spec['paths']['/api/students'] = [
      'get' => [
        'summary' => 'Views REST Export API',
        'parameters' => [
          [
            'name' => '_format',
            'in' => 'query',
            'required' => TRUE,
            'schema' => [
              'type' => 'string',
              'example' => 'json',
            ],
          ],
        ],
        'responses' => [
          '200' => [
            'description' => 'Successful response',
          ],
        ],
      ],
    ];

    return $spec;
  }

  public function getApiName() {
    return 'Student API';
  }

  public function getApiDescription() {
    return 'Custom Student APIs documentation';
  }

  public function getJsonSchema($described_format, $entity_type_id, $bundle_name = NULL) {
    return [];
  }

}