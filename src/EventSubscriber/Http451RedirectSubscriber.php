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
        $request = $event->getRequest();
        $filename = 'blocked_ids.json';
    
        // prevents pages like "edit", "revisions", etc from bring redirected.
        if ($request->attributes->get('_route') !== 'entity.node.canonical') {
            return;
        } else {
            $current_nodeId = (string) $request->attributes->get('node')->id();
            // Read blocked ids from blocked_ids.json file
            $root_dir = dirname(__DIR__);
            if(file_exists("$root_dir" . '/Form' . "/$filename")) {
                $file = file_get_contents("$root_dir" . '/Form' . "/$filename");
                $blocked_nodes = json_decode($file, true);
                foreach($blocked_nodes as $key) {
                    if($key["nid"] == $current_nodeId) {
                        $response = new Response();

                        $response->setContent($key["content"] . '<h4>Resource title: ' . $request->attributes->get('node')->getTitle() . '</h4>
                            <p>This resource has been blocked as requested by: <a href="'. $key["authority"] . '">' . $key["authority"] . '</a></p>');
                        
                        $response->setStatusCode(Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS, 'Unavailable For Legal Reasons');

                        $response->headers->set('Content-Type', 'text/html');

                        // Web Linking: https://tools.ietf.org/html/rfc5988
                        $response->headers->set('Link', '<' . $key['authority'] . '>' . 'rel="blocked-by"');

                        $response->prepare($request);

                        $event->setResponse($response);
                        
                        return $response;
                    } 
                }
            }
        } 
    }
}