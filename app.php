<?php

require_once __DIR__.'/vendor/autoload.php';

use Neutron\Silex\Provider\MongoDBODMServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider;


/**
 * Launch App
 */
$app = new Silex\Application();
$app['debug'] = true;
//register Shop App
$shop = $app['controllers_factory'];


/*
* ************************************** Register Providers***********************************
*/

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'path'     => __DIR__.'/app.db',
        'dbname' => 'horus',
        'host' => 'localhost',
        'user' => 'root',
        'password' => 'djscrave',
        'charset' => 'utf-8',
    ),
));

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new FormServiceProvider());

$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallbacks' => array('fr'),
));

$app->register(new Silex\Provider\ValidatorServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
$app->register(new Provider\HttpFragmentServiceProvider());
$app->register(new Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/logs/dev.log',
));

$app->register(new Provider\WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => __DIR__.'/../cache/profiler',
    'profiler.mount_prefix' => '/_profiler', // this is the default
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new MongoDBODMServiceProvider(), array(
    'doctrine.odm.mongodb.connection_options' => array(
        'database' => 'post',
        // connection string:
        // mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db
        'host'     => 'mongodb://localhost:27017',

        // connection options as described here:
        // http://www.php.net/manual/en/mongoclient.construct.php
        'options'  => array(
//            'fsync' => false,
            "username" => "root",
            "db" => "post"
        )
    ),
    'doctrine.odm.mongodb.documents' => array(
        0 => array(
            'type' => 'annotation',
            'path' => array(
                'src/Post/Documents',
            ),
            'namespace' => 'Post\Documents',
            'alias'     => 'docs',
        ),
    ),
    // .    'doctrine.odm.mongodb.proxies_dir'             => 'cache/doctrine/odm/mongodb/Proxy',
    'doctrine.odm.mongodb.proxies_namespace'       => 'DoctrineMongoDBProxy',
    'doctrine.odm.mongodb.auto_generate_proxies'   => true,
    'doctrine.odm.mongodb.hydrators_dir'           => 'cache/doctrine/odm/mongodb/Hydrator',
    'doctrine.odm.mongodb.hydrators_namespace'     => 'DoctrineMongoDBHydrator',
    'doctrine.odm.mongodb.auto_generate_hydrators' => true,
    'doctrine.odm.mongodb.metadata_cache'          => new \Doctrine\Common\Cache\ArrayCache(),
    'doctrine.odm.mongodb.logger_callable'         => $app->protect(function($query) {
        // log your query
    }),
));

//Extends Twig Function
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) {
        return sprintf('http://localhost/elastic/assets/%s', ltrim($asset, '/'));
    }));

    return $twig;
}));

//$stopwatch = new Stopwatch();

/**
 * Hooks action
 */
/*
$app->before(function (\Symfony\Component\HttpFoundation\Request $request, \Silex\Application $app) use($stopwatch) {
    $stopwatch->start('eventName');
});
*/
/**
 * after: An after application middleware allows you to tweak the Response before it is sent to the client:
 */

/*
 * A finish application middleware allows you to execute tasks after the Response has been sent to the client (like sending emails or logging):
 */
/*$app->finish(function (\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Response $response) use($stopwatch) {
    $event = $stopwatch->stop('eventName');
    dump("{$event->getDuration()} msecondes");   // Returns the event duration, including all periods
    dump("{$event->getMemory()} Mb");
});
*/
/*********************************************** CRUD Application************************************/

/**
 * Create Action
 */
$shop->get('/view/{id}', function (\Symfony\Component\HttpFoundation\Request $request, $id) use ($app) {

    $post = $app['doctrine.odm.mongodb.dm']->getRepository('docs:Posts')->find($id);

    if(!$post){
        $app->abort(404, "Post $id does not exist.");
    }
    return $app['twig']->render('view.twig',
        [
            'post' => $post
        ]
    );

})->bind('view')
  ->assert('id', '[a-z0-9]+');


/*
 * Form Post
 */
$form = $app['form.factory']->createBuilder('form')
    ->add(
        'firstname', "text", [
        'required' => true,
        'constraints' => [
            new \Symfony\Component\Validator\Constraints\Length(
                [
                    'min' => 3,
                    'max' => 50,
                    'minMessage' => 'Votre prénom est trop court',
                    'maxMessage' => 'Votre prénom est trop long'
                ])
        ],
        'attr' => [
            'class' => 'form-control'
        ]
    ])
    ->add('lastname', "text", array(
        'required' => true,
        'attr' => array(
            'class' => 'form-control'
        )
    ))
    ->add('email', "email", array(
        'required' => true,
        'constraints' => array(
            new \Symfony\Component\Validator\Constraints\Email(
                array(
                    'message' => 'Votre email est incorrecte'
                )
            )
        ),
        'attr' => array(
            'class' => 'form-control',
        )
    ))
    ->add('tags', "choice", array(
        'choices' => [
            "Vieux-Lyon" => "Vieux-Lyon",
            "Nation" => "Nation",
            "Bellecour" => "Bellecour",
            "Chatelet" => "Chatelet",
            "Odéon" => "Odéon",
            "Effeil" => "La Tour Effeil",
            "Montparnasse"  => "Montparnasse",
            "Saint-Michel" => "Saint-Michel",
            "Chateau-Rouge" => "Chateau-Rouge"
        ],
        'required' => true,
        'multiple' => true,
        'attr' => array(
            'class' => 'form-control',
        )
    ))
    ->add('homeTeam', "text", array(
        'required' => true,
        'attr' => array(
            'placeholder' => 'Team',
            'class' => 'form-control'
        )
    ))
    ->add('image', "url", array(
        'required' => true,
        'attr' => array(
            'placeholder' => 'http://maphoto.jpg',
            'class' => 'form-control'
        )
    ))
    ->add('visible', "choice", array(
        'choices' => array(
            "Non","Oui"
        ),
        'expanded' => true,
        'attr' => array()
    ))
    ->add('title', "text", array(
        'required' => true,
        'attr' => array(
            'class' => 'form-control'
        )
    ))
    ->add('description', "textarea", array(
        'required' => true,
        'attr' => array(
            'class' => 'form-control'
        )
    ))
    ->add('age', "text", array(
        'required' => true,
        'attr' => array(
            'class' => 'form-control'
        )
    ))
    ->add('phoneNumber', "text", array(
        'required' => true,
        'attr' => array(
            'class' => 'form-control'
        )
    ))
    ->getForm();


/**
 * CReate Action
 */
$shop->post('/create', function (\Symfony\Component\HttpFoundation\Request $request) use ($app,$form) {


    $form->handleRequest($request);

    /*dump($form->getErrors());
    exit();*/


    if ($form->isValid()) {
        $data = $form->getData();

        $post = new \Post\Documents\Posts();
        $post->setFirstName($data['firstname']);
        $post->setLastName($data['lastname']);
        $post->setAge($data['age']);
        $post->setTags($data['tags']);
        $post->setVisible($data['visible']);
        $post->setPhoneNumber($data['phoneNumber']);
        $post->setThumbnail(array(
            'hqDefault' => $data['image']
        ));
        $post->setTitle($data['title']);
        $post->setCreatedTime(new \DateTime('now'));
        $post->setDescription($data['description']);

        $app['doctrine.odm.mongodb.dm']->persist($post);
        $app['doctrine.odm.mongodb.dm']->flush();
    }

    return $app->redirect($app["url_generator"]->generate("homepage"));


})->bind('create');


/**
 * Homepage
 */
$shop->get('/', function (\Symfony\Component\HttpFoundation\Request $request) use ($app, $form) {

    /*$client = new Predis\Client();
    $client->set('foo', 'alphaboyer');
    $value = $client->get('foo');*/

    $query = $request->get('q', "*");
    $minage = $request->get('minage', 0);
    $maxage = $request->get('maxage', 99);
    $display = $request->get('display', 6);

    if($query != "*" && NULL !== $query && !empty($query)){
        $app['session']->set('keyword', $query);
        $app['monolog']->addInfo(sprintf("Rechercher effectuée sur '%s'", $query));

    }

    $client = new \Elastica\Client();


    /*
     *    Define a Query. We want a string query.
     *   $elasticaQueryString = new Elastica_Query_QueryString();
     /   $elasticaQueryString->setQuery((string)$value);
    // Create the actual search object with some data.
     *   $elasticaQuery = new Elastica_Query();
        $elasticaQuery->setQuery($elasticaQueryString);
     */

    // First Method
    /*$query = array(
        'query' => array(
            'query_string' => array(
                'query' => $query,
            )
        )
    );*/

    // Second Method
    $query = '{
         "from" : 0, "size" : '.$display.',
         "suggest" : {
            "one" : {
                "text" : "Paris",
                "term" : {
                  "field" : "title"
                }
              },
              "two" : {
                "text" : "Lyon",
                "term" : {
                  "field" : "title"
                }
              },
              "three" : {
                "text" : "Marseille",
                "term" : {
                  "field" : "title"
                }
              }
          },
          "query": {
            "bool": {
                "must" : {
                    "term" : { "visible" : true }
                },
                 "should": [{
                        "query_string": {
                           "query": "'.$query.'"
                        }
                },{
                        "fuzzy_like_this": {
                           "fields": ["title"],
                           "like_text": "'.$query.'",
                           "max_query_terms": 5,
                           "boost": 5
                        }
                }]
            }
        },
         "facets" : {
              "categories" : { "terms" : {"field" : "categories"} },
              "importance" : { "terms" : {"field" : "importance"} },
              "city" : { "terms" : {"field" : "address.city"} },
              "state" : { "terms" : {"field" : "address.state"} }

         },
         "filter": {
                    "range": {
                       "age": {
                          "gte": '.$minage.',
                          "lte": '.$maxage.'
                          }
                    }
        }
    }';

    $path =   'post2/_search';

    $response = $client->request($path, \Elastica\Request::GET, $query);
    $responseArray = $response->getData();


//    exit(dump($responseArray));

    $totalHits = $responseArray['hits']['total'];
    $result = $responseArray['hits']['hits'];
    $facets = $responseArray['facets'];

    /**
     * If AJAX..
     */
    if($request->isXmlHttpRequest()) {
        if(isset($responseArray['hits']['hits']) && !empty($responseArray['hits']['hits'])){

            // Traitement
            foreach($result as $post){
                $res[] = $post['_source'];
            }

            return json_encode($res);
        }
    }


    //dump($responseArray['hits']['hits']);
    //dump($totalHits);
    //exit();

    /*
     *  dump($responseArray['hits']);
     *  dump($totalHits);
     *  exit();
     */


    //$post = $app['doctrine.odm.mongodb.dm']->getRepository('docs:Posts')->find("5576f8fa9106341b128b4567");

    //exit(dump(json_decode($post)));


    $posts = $app['doctrine.odm.mongodb.dm']->getRepository('docs:Posts')
    ->findAll();

    return $app['twig']->render('search.twig',
        [
            "posts" => $responseArray['hits']['hits'],
            "nb" => $totalHits,
            "facets" => $facets,
            "form" => $form->createView()
        ]
    );
})->bind('homepage');


//Monter
$app->mount('/', $shop);

/**
 * Running
 */
$app->run();



