<?php

namespace Drupal\student_api\Plugin\rest\resource;

use Drupal\rest\ResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\Attribute\RestResource;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

#[RestResource(
  id: "student_resource",
  label: new TranslatableMarkup("Student Resource"),
  uri_paths: [
    "canonical" => "/api/student-custom"
  ]
)]
class StudentResource extends ResourceBase {

  protected $entityTypeManager;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager
  ) {

    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );

    $this->entityTypeManager = $entity_type_manager;
  }

  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('student_api'),
      $container->get('entity_type.manager')
    );
  }

  public function get() {

    $storage = $this->entityTypeManager->getStorage('student');

    $ids = $storage->getQuery()->accessCheck(FALSE)->execute();

    $entities = $storage->loadMultiple($ids);

    $data = [];

    foreach ($entities as $entity) {

      $data[] = [
        'id' => $entity->id(),
        'name' => $entity->get('field_name')->value,
        'email' => $entity->get('field_email')->value,
        'age' => $entity->get('field_age')->value,
      ];
    }

    return new ResourceResponse($data);
  }

}