<?php

namespace pathwayx\drupalcommander;


use GuzzleHttp\Psr7;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\DomCrawler\Crawler;

class Connector {

  // Generic container for authentication info.
  public $session;

  public function startSession($url, $username, $password, $type='D8') {
    $client = new GuzzleClient;
    $jar = new CookieJar;

    // Step 1: retrieve /user/login page.
    $html = $client->request('GET', $url)->getBody()->getContents();
    
    // Step 2: retrieve form_build_id from user login form.
    $crawler = new Crawler($html);
    $form_build_id = $crawler->filter('input[name=form_build_id]')->attr('value');

    // Step 3: post login form, passing along form_build_id.
    $response = $client->request(
      'POST', 
      $url, 
      [
        'cookies' => $jar,
        'form_params' => [
          'name' => $username,
          'pass' => $password,
          'form_id' => 'user_login_form',
          'form_build_id' => $form_build_id,
        ]
      ]
    );
    
    // Step 4: retrieve and store session cookie.
    if (!$response->hasHeader('Set-Cookie')) {
      throw new Exception('Drupal authentication failed.');
    }

    $cookie_header =  Psr7\parse_header($response->getHeader('Set-Cookie'));
    $this->session['cookie'] = array_slice($cookie_header[0], 0, 1);

    return TRUE;
  }

}