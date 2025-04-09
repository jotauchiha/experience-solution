<?php

namespace Drupal\custom_ajax_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;


class CustomAjaxApiFormtest extends FormBase {

  public function getFormId() {
    return 'customajaxapiformtest';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Página actual (por defecto 0)
    $page = $form_state->get('page') ?? 0;
    $total_pages = $form_state->get('total_pages') ?? 2;
    $filtro = $form_state->get('filtro') ?? 'todos';
    $buscar = $form_state->get('buscar') ?? '';
    // Simulamos datos para cada página
    $data = $this->getTableData($page, $filtro, $buscar, $form_state);

    // Tabla con datos
    $form['table_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax-table-container'],
    ];
    $form['table_container']['buscar'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Buscar nombre'),
        '#size' => 25,
        '#default_value' => $form_state->getValue('buscar'),
      ];
  
      // Select desplegable
    $form['table_container']['filtro'] = [
        '#type' => 'select',
        '#title' => $this->t('Filtrado por campos:'),
        '#options' => [
          '0' => $this->t('Ninguno'),
          '1' => $this->t('name'),
          '2' => $this->t('username1'),
          '3' => $this->t('username2'),
          '4' => $this->t('email'),
        ],
        '#default_value' => $form_state->getValue('filtro'),
      ];
    $form['table_container']['table'] = [
      '#type' => 'table',
      '#header' => ['name', 'surname1', 'surname2','email'],
      '#rows' => $data,
      '#empty' => $this->t('No hay resultados'),
      '#attributes' => [
        'class' => ['table', 'permitirtable-bordered', 'table-striped', 'table-hover', 'align-middle', 'text-center'],
        'style' => 'font-family: Inter, sans-serif; font-size: 15px; color: #333; text-transform: capitalize;',
    ]
    ];
    
    // Mostramos "Anterior" si no estamos en la primera página (página 0)
    if ($form_state->get('page') > 0) {
        $form['table_container']['prev'] = [
        '#type' => 'submit',
        '#value' => $this->t('Anterior'),
        '#submit' => ['::prevPageSubmit'],
        '#ajax' => [
            'callback' => '::ajaxRefreshTable',
            'wrapper' => 'ajax-table-container',
            'effect' => 'fade',
        ],
    ];
  }

  // Si total_pages = 2 => queremos permitir hasta página 2 incluida (es decir, 3 páginas: 0, 1, 2)
  if ($form_state->get('page') < $form_state->get('total_pages')) {
    $form['table_container']['next'] = [
      '#type' => 'submit',
      '#value' => $this->t('Siguiente'),
      '#submit' => ['::nextPageSubmit'],
      '#ajax' => [
        'callback' => '::ajaxRefreshTable',
        'wrapper' => 'ajax-table-container',
        'effect' => 'fade',
      ],
    ];
  }
  $form['table_container']['filtrar'] = [
    '#type' => 'submit',
    '#value' => $this->t('Filtrar'),
    '#submit' => ['::filtrarSubmit'],
    '#ajax' => [
      'callback' => '::ajaxRefreshTable',
      'wrapper' => 'ajax-table-container',
      'effect' => 'fade',
    ],
  ];
  
  $form['table_container']['info'] = [
    '#type' => 'markup',
    '#markup' => '<div class="page-info">Página actual: ' . $form_state->get('page') + 1 . ' / Total: ' . $form_state->get('total_pages') + 1 . '</div>',
  ];
  $form['#attached']['library'][] = 'custom_ajax_form/bootstrap';
    return $form;
  }

  public function nextPageSubmit(array &$form, FormStateInterface $form_state) {
    // Incrementamos la "página"
    $page = $form_state->get('page');
    $form_state->set('page', $page + 1);
    $form_state->setRebuild();
  }

  public function prevPageSubmit(array &$form, FormStateInterface $form_state) {
    // Incrementamos la "página"
    $page = $form_state->get('page');
    $form_state->set('page', $page - 1);
    // Evitamos que se limpie el estado del formulario
    $form_state->setRebuild();
  }

  public function ajaxRefreshTable(array &$form, FormStateInterface $form_state) {
    return $form['table_container'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No se usa aquí
  }

  public function filtrarSubmit(array &$form, FormStateInterface $form_state) {
    // Guarda los filtros (ya se hace automáticamente por Drupal, pero puedes asegurarlo)

    $form_state->set('buscar', $form_state->getValue('buscar'));
    $form_state->set('filtro', $form_state->getValue('filtro'));
    $form_state->set('total_pages',$form_state->getValue('total_pages'));
    $form_state->set('page', 0);
    $form_state->setRebuild();
  }

  public function filtrado_api($filter, $search, $page, FormStateInterface $form_state) {
    $filtro = intval($filter);
    $search = strval($search);
    $rows = [];
    $client = new Client();

    // Configuramos los datos de la solicitud según los filtros
    if ($filtro === 0 || $search === '') {
        $requestData = ['paginate' => $page];
    } else {
        $requestData = ['paginate' => $page];
        switch ($filtro) {
            case '1':
                $requestData['name'] = $search;
                break;
            case '2':
                $requestData['surname1'] = $search;
                break;
            case '3':
                $requestData['surname2'] = $search;
                break;
            case '4':
                $requestData['email'] = $search;
                break;
            default:
                return false; // No coincide ningún filtro
        }
    }

    try {
      
        $api_url = 'http://localhost/custom/get-users';
        $response = $client->post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => $requestData,
        ]);
        
        // Obtenemos la respuesta de la API
        $data = json_decode($response->getBody()->getContents(), TRUE);

        // Actualizamos el total de páginas con la respuesta de la API
        if (isset($data['total_paginas'])) {
            $form_state->set('total_pages', $data['total_paginas']);
        }

        // Llenamos los rows con los datos obtenidos
        if (!empty($data['users'])) {
            foreach ($data['users'] as $user) {
                $rows[] = [
                    'name' => ['data' => ucwords($user['name'])],
                    'surname1' => ['data' => ucwords($user['surname1'])],
                    'surname2' => ['data' => ucwords($user['surname2'])],
                    'email' => ['data' => ($user['email'])],
                ];
            }
        }
    } catch (RequestException $e) {
        \Drupal::logger('custom_ajax_form')->error($e->getMessage());
    }

    return $rows;
}

  /**
   * Simula datos distintos por página.
   */
  private function getTableData($page,$filtro,$buscar,$form_state) {
        $rows = $this->filtrado_api($filtro, $buscar, $page, $form_state);
    return $rows;
  }
}
