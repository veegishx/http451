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

        // Prevent pages like "edit", "revisions", etc from being redirected.
        $is_node = $request->attributes->get('_route') == 'entity.node.canonical';
        if (!$is_node) {
            return;
        }

        $current_node_id = (string) $request->attributes->get('node')->id();

        // Check if the json file containing the list of blocked nodes was
        // created.
        $root_dir = dirname(__DIR__);
        $filename = 'blocked_ids.json';
        $file_path = "$root_dir" . '/Form' . "/$filename";
        $is_file_exists = file_exists($file_path);
        if (!$is_file_exists) {
            return;
        }

        $file = file_get_contents("$root_dir" . '/Form' . "/$filename");
        $blocked_nodes = json_decode($file, TRUE);
        foreach($blocked_nodes as $key) {
            if($key["page_id"] == $current_node_id) {
                Http451Controller::generateHttp451Response($request, $node);
            }
        }
    }
}