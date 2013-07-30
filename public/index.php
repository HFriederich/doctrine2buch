<?php
include '../vendor/autoload.php';
include '../config/doctrine.php';

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
$cachingBackend = new \Doctrine\Common\Cache\FilesystemCache('/tmp/doctrine2');
/* $config->setMetadataCacheImpl($cachingBackend);
$config->setQueryCacheImpl($cachingBackend);
$config->setResultCacheImpl($cachingBackend); */
$em = EntityManager::create($dbParams, $config);

$em->getEventManager()->addEventSubscriber(
    new \Doctrine\DBAL\Event\Listeners\MysqlSessionInit('utf8', 'utf8_unicode_ci')
);

$app = new \Slim\Slim(array(
    'view' => '\Slim\LayoutView',
    'layout' => 'layouts/main.phtml',
    'templates.path' => __DIR__ . '/../templates/'
));

$app->get('/', function () use ($app, $em) {
    $posts = $em->getRepository('Entity\Post')->findAll();
    $app->render('index.phtml', array('posts' => $posts, 'app' => $app));
})->name('/');

$app->get('/test/', function () use ($app, $em) {
    $tag = $em->getRepository('Entity\Post')->findAllPostsWithTag("Mustermann");
    $em->persist(new Entity\User());
    $em->flush();
})->name('/test');

$app->get('/post/:id', function ($id) use ($app, $em) {
    $post = $em->getRepository('Entity\Post')->findOneById($id);
    $app->render('post.phtml', array('post' => $post, 'app' => $app));
})->name('/post');

$app->get('/:label/', function ($label) use ($app, $em) {
    $tag = $em->getRepository('Entity\Tag')->findOneByLabel($label);
    if (!$tag || !$tag->getPosts()) $app->halt(404, "Keine Posts mit diesem Tag gefunden :-/");
    $app->render('tag.phtml', array('label' => $label, 'posts' => $tag->getPosts(), 'app' => $app));
})->name('/tag');

$app->get('/user/:id', function ($id) use ($app, $em) {
    $user = $em->getRepository('Entity\User')->findOneById($id);
    $app->render('user.phtml', array('user' => $user, 'app' => $app));
})->name('/user');;


$app->get('/add/post', function () use ($app, $em) {
    $newPost = new \Entity\VideoPost();
    $newPost->setTitle('A new post!');
    $newPost->setContent('This is the body of the new post.');
    $em->persist($newPost);

    $user = $em->getRepository('Entity\User')->findOneById(1);
    $newPost->setUser($user);
    $em->flush();

    /*
    $newPost->setUser(null);
    $em->flush();
    */
    $user->getPosts()->removeElement($newPost);
    if(!$user->getPosts()->contains($newPost))
        echo "It's gone!";
    $em->flush();
    die();
    //$em->flush();
    var_dump(count($user->getPosts()));    die("ja");
    //$app->render('post.phtml', array('post' => $post, 'app' => $app));
})->name('/add/post');

$app->run();