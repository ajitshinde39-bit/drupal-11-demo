<?php

/**
 * Implements hook_views_data().
 */
function employee_api_views_data() {

  $data = [];

  $data['employee']['table'] = [
    'group' => 'Employee',
    'base' => [
      'field' => 'eid',
      'title' => 'Employee',
      'help' => 'Employee custom table',
    ],
  ];

  // EID
  $data['employee']['eid'] = [
    'title' => 'Employee ID',
    'help' => 'Employee ID',
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // Employee Name
  $data['employee']['ename'] = [
    'title' => 'Employee Name',
    'help' => 'Employee Name',
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  // DOJ
  $data['employee']['doj'] = [
    'title' => 'Date of Joining',
    'help' => 'Employee DOJ',
    'field' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];

  return $data;
}