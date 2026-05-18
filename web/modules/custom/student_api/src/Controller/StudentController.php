<?php

namespace Drupal\student_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StudentController extends ControllerBase {

  protected $entityTypeManager;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

//   public function getStudents() {

//     $storage = $this->entityTypeManager->getStorage('student');

//     $ids = $storage->getQuery()
//       ->accessCheck(FALSE)
//       ->execute();

//     $entities = $storage->loadMultiple($ids);

//     $data = [];

//     foreach ($entities as $entity) {

//       $data[] = [
//         'id' => $entity->id(),
//         'name' => $entity->get('field_name')->value,
//         'email' => $entity->get('field_email')->value,
//         'age' => $entity->get('field_age')->value,
//       ];
//     }

//     return new JsonResponse($data);
//   }
 
    public function getStudent($id) {
        $storage = $this->entityTypeManager->getStorage('student');
        $entity = $storage->load($id);

        if (!$entity) {
            return new JsonResponse([
            'message' => 'Student not found',
            ], 404);
        }

        $data = [
            'id' => $entity->id(),
            'name' => $entity->get('field_name')->value,
            'email' => $entity->get('field_email')->value,
            'age' => $entity->get('field_age')->value,
        ];

        return new JsonResponse($data);
    }
}