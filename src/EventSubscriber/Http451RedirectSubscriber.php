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
   * Redirect requests for my_content_type node detail pages to node/123.
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
            $current_nodeId = $request->attributes->get('node')->id();
        }

        // Read blocked ids from blocked_ids.json file
        $root_dir = realpath(dirname(__FILE__));
        if(file_exists("$root_dir/$filename")) {
            $blocked_ids = json_decode(file_get_contents("$root_dir/$filename"));
        }

        if ($request->attributes->get('node')->id() !== '4') {
            return;
        }
    
        // This is where you set the destination.
        $response = new Response();

        $response->setContent('<html>
        <head><title>Unavailable For Legal Reasons</title></head>
        <body>
        <h1>Unavailable For Legal Reasons</h1><h3>Article with ID '.$current_nodeId.' has been censored</h3>
        <p>This request may not be serviced in the Roman Province
            of Judea due to the Lex Julia Majestatis, which disallows
            access to resources hosted on servers deemed to be
            operated by the People\'s Front of Judea.</p><h1></h1>
        </body>
        </html>');
        
        $response->setStatusCode(Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS, 'Unavailable For Legal Reasons');

        $response->headers->set('Content-Type', 'text/html');

        $response->send();
    }
}