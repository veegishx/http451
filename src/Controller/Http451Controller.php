<?php

/**
 * This controller will return a 451 status page
 * Nodes will be redirected to this page if they are censored after going through the Http451RedirectSubscriber
 */

namespace Drupal\http451\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;


class Http451Controller extends ControllerBase {
    /**
     * Http451.
     *
     * @param GetResponseEvent $event
     * @param Array  $blocked_node
     * @return void
     *
     */
    public function generateHttp451Response(GetResponseEvent $event, $blocked_node) {

      $request = $event->getRequest();

      $response = new Response();
      $response->setContent(
          $blocked_node["page_content"] . '<h4>Resource title: ' . $blocked_node['page_title']. '</h4>
          <p>This resource has been blocked as requested by: <a href="'. $blocked_node["blocking_authority"] . '">' . $blocked_node["blocking_authority"] . '</a></p>'
      );

      $response->setStatusCode(Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS, 'Unavailable For Legal Reasons');

      $response->headers->set('Content-Type', 'text/html');
      // Web Linking: https://tools.ietf.org/html/rfc5988
      $response->headers->set('Link', '<' . $blocked_node['blocked_by'] . '>' . 'rel="blocked-by"');

      $response->prepare($request);
      $event->setResponse($response);
    }

}
?>