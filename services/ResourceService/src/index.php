<?php

namespace Islandora\ResourceService;

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Islandora\Chullo\Chullo;
use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Psr\Http\Message\ResponseInterface;
use Silex\Provider\TwigServiceProvider;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;


date_default_timezone_set('UTC');

$app = new Application();

$app['debug'] = true;

$app->register(new \Silex\Provider\TwigServiceProvider(), array(
  'twig.path' => __DIR__.'/../templates',
));

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
  return $twig;
}));

$app['api'] =  $app->share(function() {
  return FedoraApi::create('http://localhost:8080/fcrepo/rest');
});

$app['fedora'] =  $app->share(function() use($app) {
  return new Chullo($app['api']);
});

$app['triplestore'] = $app->share(function() {
  return TriplestoreClient::create('http://localhost:8080/bigdata/sparql');
});

$htmlHeaderToTurtle = function(Request $request) {
  // In case the request was made by a browser, avoid
  // returning the whole Fedora4 API Rest interface page.
  if (0 === strpos($request->headers->get('Accept'),'text/html')) {
    $request->headers->set('Accept', 'text/turtle', TRUE);
  }
};

$getPathFromTriple = function (Request $request, Application $app) {

};

$idToUri = function ($id) use ($app) {
  $sparql_query = $app['twig']->render('getResourceByUUIDfromTS.sparql', array(
    'uuid' => $id,
  ));

  $sparql_result = $app['triplestore']->query($sparql_query);
  // We only assign one in case of multiple ones
  // Will have to check for edge cases?
  // TODO: We should throw an exception.
  foreach ($sparql_result as $triple) {
    return $triple->s->getUri();
  }
  return null;
};

/**
 * Convert returned Guzzle responses to Symfony responses.
 */
$app->view(function (ResponseInterface $psr7) {
  return new Response($psr7->getBody(), $psr7->getStatusCode(), $psr7->getHeaders());
});

/**
 * resource GET route. takes an UUID as first value to match, optional a child resource path
 * takes 'rx' and/or 'metadata' as optional query arguments
 * @see https://wiki.duraspace.org/display/FEDORA40/RESTful+HTTP+API#RESTfulHTTPAPI-GETRetrievethecontentoftheresource
 */
$app->get("/islandora/resource/{id}",function (Application $app, Request $request, $id) {
   $tx = $request->query->get('tx', "");
   return $app['api']->getResource($id, $request->headers->all(), $tx);
})
->assert('id','([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})')
->convert('id', $idToUri)
->before($htmlHeaderToTurtle);

/**
 * resource POST route. takes an UUID for the parent resource as first value to match
 * takes 'rx' and/or 'checksum' as optional query arguments
 * @see https://wiki.duraspace.org/display/FEDORA40/RESTful+HTTP+API#RESTfulHTTPAPI-BluePOSTCreatenewresourceswithinaLDPcontainer
 */
//$app->post("/islandora/resource/{uuid}",function (\Silex\Application $app, Request $request, $uuid, $checksum) {
   //$tx = $request->query->get('tx', "");
   //$checksum = $request->query->get('checksum', "");
   //$response = $app['fedora']->createResource($app->escape($app['data.resourcepath']), $request->getContent(), $request->headers->all(), $tx, $checksum);
   //if (NULL === $response) {
     //$app->abort(404, 'Failed creating resource in Fedora4');
   //}

   //return $response;
//})
//->assert('uuid','([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})')
//->before($getPathFromTriple);

/**
 * resource PUT route. takes an UUID for the resource to be update/created as first value to match,
 * optional a Child resource relative path
 * takes 'rx' and/or 'checksum' as optional query arguments
 * @see https://wiki.duraspace.org/display/FEDORA40/RESTful+HTTP+API#RESTfulHTTPAPI-YellowPUTCreatearesourcewithaspecifiedpath,orreplacethetriplesassociatedwitharesourcewiththetriplesprovidedintherequestbody.
 */
//$app->put("/islandora/resource/{uuid}/{child}",function (\Silex\Application $app, Request $request, $uuid, $child) {
   //$tx = $request->query->get('tx', "");
   //$checksum = $request->query->get('checksum', "");
   //$response = $app['fedora']->saveResource($app->escape($app['data.resourcepath']).'/'.$child, $request->getContent(), $request->headers->all(), $tx, $checksum);
   //if (NULL === $response) {
     //$app->abort(404, 'Failed putting resource into Fedora4');
   //}

   //return $response;
//})
//->value('child',"")
//->assert('uuid','([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})')
//->before($getPathFromTriple);

/**
* resource PATCH route. takes an UUID for the resource to be modified via SPARQL as first value to match,
* optional a Child resource relative path
* takes 'rx' as optional query argument
* @see https://wiki.duraspace.org/display/FEDORA40/RESTful+HTTP+API#RESTfulHTTPAPI-GreenPATCHModifythetriplesassociatedwitharesourcewithSPARQL-Update
*/
//$app->patch("/islandora/resource/{uuid}/{child}",function (\Silex\Application $app, Request $request, $uuid, $child) {
  //$tx = $request->query->get('tx', "");
  //$response = $app['fedora']->modifyResource($app->escape($app['data.resourcepath']).'/'.$child, $request->getContent(), $request->headers->all(), $tx);
  //if (NULL === $response) {
    //$app->abort(404, 'Failed modifying resource in Fedora4');
  //}

  //return $response;
//})
//->value('child',"")
//->assert('uuid','([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})')
//->before($getPathFromTriple);

//$app->after(function (Request $request, Response $response, \Silex\Application $app) {
  //// Todo a closing controller, not sure what now but i had an idea.
//});

$app->run();
