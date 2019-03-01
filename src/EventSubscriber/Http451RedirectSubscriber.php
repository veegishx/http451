<?php

namespace Drupal\http451\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Utility\Xss;

/**
 * Event Subscriber Http451RedirectSubscriber.
 */
class Http451RedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return([
      KernelEvents::REQUEST => [
        ['redirectMyContentTypeNode'],
      ],
    ]);
  }

  /**
   * Get the IP address of the client.
   *
   * @return string
   *
   *   Returns the IP address of the client as a string
   */
  private function getIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $client_ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else {
      $client_ip = \Drupal::request()->getClientIp();
    }

    return $client_ip;
  }

  /**
   * Get the origin country of the IP address.
   *
   * @return string
   *
   *   Returns the country where the IP address originated as a string
   */
  private function getIpAddressOriginCountry($client_ip) {
    // Load GeoIP API Key.
    $config = \Drupal::config('http451.settings');
    $api_key = $config->get('geoip_api_key');

    // Make API call via a CURL request.
    $ch = curl_init('http://api.ipstack.com/' . $client_ip . '?access_key=' . $api_key . '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    // Store response.
    $json_response = curl_exec($ch);
    curl_close($ch);

    // Decode response.
    $api_response = json_decode($json_response, TRUE);

    // Save country name.
    $country_code = $api_response['country_code'];

    return $country_code;
  }

  /**
   * Redirect node to a http 451 error page.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *
   *   Takes an event as parameter and redirects it if censorship is applied to it.
   */
  public function redirectMyContentTypeNode(GetResponseEvent $event) {
    // Load the default configurations.
    $config = \Drupal::config('http451.settings');
    $http451_custom_field = $config->get('http451.custom_field_name');

    $ip = Http451RedirectSubscriber::getIpAddress();

    $request = $event->getRequest();

    // Prevent pages like "edit", "revisions", etc from being redirected.
    $is_node = $request->attributes->get('_route') == 'entity.node.canonical';
    if (!$is_node) {
      return;
    }

    // Retrieve current node id.
    $current_node_id = (string) $request->attributes->get('node')->id();

    // Check if node has $http451_custom_field assigned to it.
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node = $node_storage->load($current_node_id);
    $contains_field = $node->hasField($http451_custom_field);

    // Check the status property of $http451_custom_field if it is assigned to the node.
    $node_status = '';
    $found = FALSE;
    $worldwide = FALSE;

    if ($contains_field) {
      $node_status = $node->get($http451_custom_field)->status;
    }

    if ($node_status == 1) {
      // Get comma delimited string of the countries affected by the censorship.
      $countries = $node->get($http451_custom_field)->countries_affected;

      // Split comma delimited string of countries into $list array
      // Remove whitespaces and convert to uppercase.
      $countries == NULL ? $list = NULL : $list = array_map('strtoupper', preg_replace('/\s+/', '', (explode(",", $countries))));
      $client_country = strtoupper(preg_replace('/\s+/', '', Http451RedirectSubscriber::getIpAddressOriginCountry($ip)));
      // If client country is found in list then set flag to TRUE.
      if ($list != NULL) {
        foreach ($list as $list_item) {
          if ($list_item == $client_country) {
            $found = TRUE;
          }
        }
      }

      // If flag = TRUE initialize a new response and set headers for HTTP451 status code.
      if ($found || $list == NULL) {
        $response = new Response();
        $response->setContent('<h1>' . $node->get($http451_custom_field)->page_title . '</h1>' .
        '<p>' . Xss::filter($node->get($http451_custom_field)->page_content) . '</p>
                    <p>Enforced by: <a href="' . $node->get($http451_custom_field)->blocking_authority . '">' . $node->get($http451_custom_field)->blocking_authority . '</a></p>'
        );

        $response->setStatusCode(Response::HTTP_UNAVAILABLE_FOR_LEGAL_REASONS, 'Unavailable For Legal Reasons');

        $response->headers->set('Content-Type', 'text/html');
        // Web Linking: https://tools.ietf.org/html/rfc5988
        $response->headers->set('Link', '<' . $node->get($http451_custom_field)->blocking_authority . '>' . 'rel="blocked-by"');

        $response->prepare($request);
        $event->setResponse($response);
      }
    }
    else {
      return;
    }
  }

}
