<?php

/**
 * This controller will return a 451 status page 
 * Nodes will be redirected to this page if they are censored after going through the Http451RedirectSubscriber
 */

namespace Drupal\http451\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;


class Http451Controller extends ControllerBase {
    /**
     * Http451.
     *
     * @return string
     *   Return Hello string.
     */
    public function http451() {
      $response = new Response();

      $response->setContent('<html>
      <head><title>Unavailable For Legal Reasons</title></head>
      <body>
       <h1>Unavailable For Legal Reasons</h1><h3>Article with ID '.$node_id.' has been censored</h3>
       </body>
       </html>');
       
      $response->setStatusCode(Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS, 'Unavailable For Legal Reasons');

      $response->headers->set('Content-Type', 'text/html');

      $response->send();
    }
  }
?>