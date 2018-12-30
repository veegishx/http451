<?php
/**
 * Http451RedirectSubcriber.php
 * This file contains the core logic to determine whether a node will return a 451 status code or not
 */

namespace Drupal\http451\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Drupal\http451\Controller\Http451Controller;

class Http451RedirectSubscriber implements EventSubscriberInterface {
    public static function getSubscribedEvents() {
        return([
          KernelEvents::REQUEST => [
            ['redirectMyContentTypeNode'],
          ]
        ]);
    }

    /**
     *
     *
     * @param GetResponseEvent $event
     * @return void
     */
    public function redirectMyContentTypeNode(GetResponseEvent $event) {
        // Load the default configurations
        $config = \Drupal::config('http451.settings');
        $http451_custom_field = $config->get('http451.custom_field_name');

        $request = $event->getRequest();

        // Prevent pages like "edit", "revisions", etc from being redirected.
        $is_node = $request->attributes->get('_route') == 'entity.node.canonical';
        if (!$is_node) {
            return;
        }

        $current_node_id = (string) $request->attributes->get('node')->id();


        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $node = $node_storage->load($current_node_id);
        $node_status = $node->get($http451_custom_field)->status;
        
        if($node_status == 1) {
            $response = new Response();
            $response->setContent(
                '<p>' . $node->get($http451_custom_field)->page_content . '</p>
                <p>Enforced by: <a href="'. $node->get($http451_custom_field)->blocking_authority . '">' . $node->get($http451_custom_field)->blocking_authority . '</a></p>'
            );

            $response->setStatusCode(Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS, 'Unavailable For Legal Reasons');

            $response->headers->set('Content-Type', 'text/html');
            // Web Linking: https://tools.ietf.org/html/rfc5988
            $response->headers->set('Link', '<' . $node->get($http451_custom_field)->blocking_authority . '>' . 'rel="blocked-by"');

            $response->prepare($request);
            $event->setResponse($response);
        }
    }
}
