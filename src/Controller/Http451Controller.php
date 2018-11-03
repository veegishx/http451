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
     * @param Array  $blocked_node
     * @param String  $node_title
     * @return void
     *
     */
    public function generateHttp451Response($request, $blocked_node) {

      $response = new Response();
      $node_title = (string) $request->attributes->get('node')->getTitle();
      $response->setContent(
          $blocked_node["page_content"] . '<h4>Resource title: ' . $node_title . '</h4>
          <p>This resource has been blocked as requested by: <a href="'. $blocked_node["blocking_authority"] . '">' . $blocked_node["blocking_authority"] . '</a></p>'
      );

      $response->setStatusCode(Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS, 'Unavailable For Legal Reasons');

      $response->headers->set('Content-Type', 'text/html');
      // Web Linking: https://tools.ietf.org/html/rfc5988
      $response->headers->set('Link', '<' . $blocked_node['blocked_by'] . '>' . 'rel="blocked-by"');

      $response->prepare($request);
      $response->setResponse($response);

      return $response;
    }

}
?>