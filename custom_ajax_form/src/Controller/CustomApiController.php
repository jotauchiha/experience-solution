<?php

namespace Drupal\custom_ajax_form\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CustomApiController extends ControllerBase {
    
  public function getUsers(Request $request) {
    // Obtener el contenido de la solicitud POST.
    $content = $request->getContent();
    $data = json_decode($content, TRUE);

    $usuarios_totales = [
      ["id" => 1, "email" => "admin@yopmail.com", "name" => "admin", "surname1" => "admin", "surname2" => "admin"],
      ["id" => 2, "email" => "admin1@yopmail.com", "name" => "admin1", "surname1" => "admin1", "surname2" => "admin1"],
      ["id" => 3, "email" => "admin2@yopmail.com", "name" => "admin2", "surname1" => "admin2", "surname2" => "admin2"],
      ["id" => 4, "email" => "admin3@yopmail.com", "name" => "admin3", "surname1" => "admin3", "surname2" => "admin3"],
      ["id" => 5, "email" => "admin4@yopmail.com", "name" => "admin4", "surname1" => "admin4", "surname2" => "admin4"],
      ["id" => 6, "email" => "pepe@hotmail.com", "name" => "pepe", "surname1" => "pepe", "surname2" => "pepe"],
      ["id" => 7, "email" => "test@hotmail.com", "name" => "test", "surname1" => "rodriguez", "surname2" => "aranda"],
      ["id" => 8, "email" => "admin8@yopmail.com", "name" => "admin8", "surname1" => "admin8", "surname2" => "admin8"],
      ["id" => 9, "email" => "admin9@yopmail.com", "name" => "admin9", "surname1" => "admin9", "surname2" => "admin9"],
      ["id" => 10, "email" => "admin10@yopmail.com", "name" => "admin10", "surname1" => "admin10", "surname2" => "admin10"],
      ["id" => 11, "email" => "admin11@yopmail.com", "name" => "admin11", "surname1" => "admin11", "surname2" => "admin11"],
      ["id" => 12, "email" => "admin12@yopmail.com", "name" => "admin12", "surname1" => "admin12", "surname2" => "admin12"],
      ["id" => 13, "email" => "admin13@yopmail.com", "name" => "admin13", "surname1" => "admin13", "surname2" => "admin13"],
      ["id" => 14, "email" => "admin14@yopmail.com", "name" => "admin14", "surname1" => "admin14", "surname2" => "admin14"],
      ["id" => 15, "email" => "admin15@yopmail.com", "name" => "admin15", "surname1" => "admin15", "surname2" => "admin15"],
    ];

    $searchParams = ['email', 'name', 'surname1', 'surname2'];
    $searchResults = $usuarios_totales; // Por defecto, todos los usuarios

    $hasSearch = false;
    foreach ($searchParams as $param) {
      if (!empty($data[$param])) { // Verifica que el campo no esté vacío
        $hasSearch = true;
        $searchValue = strtolower($data[$param]); // Búsqueda insensible a mayúsculas/minúsculas
        $searchResults = array_filter($searchResults, function($user) use ($param, $searchValue) {
          return strpos(strtolower($user[$param]), $searchValue) !== false;
        });
      }
    }

    if (empty($searchResults)) {
      return new JsonResponse([
        'error' => 'Usuario no encontrado'
      ], 404);
    }

    $porPagina = 5;
    $pagina_actual = isset($data['paginate']) ? (int) $data['paginate'] : 0;
    $start = ($pagina_actual) * $porPagina;
    $usuarios_paginados = array_slice(array_values($searchResults), $start, $porPagina); // Reindexa el array
    $total_paginas = ceil(count($searchResults) / 5) - 1;    
    
    return new JsonResponse([
      'pagina_actual' => $pagina_actual,
      'per_page' => $porPagina,
      'search_applied' => $hasSearch,
      'total_users' => count($searchResults),
      'users' => $usuarios_paginados,
      'total_paginas' => $total_paginas
    ]);
  }
}
