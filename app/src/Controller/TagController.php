<?php
/**
 * Tagcontroller.
 *
 */
namespace Controller;

use Form\TagType;
//use Repository\PhotoRepository;
use Repository\ProfileRepository;
use Repository\RatingRepository;
use Repository\CommentRepository;
use Repository\UserRepository;
use Repository\TagRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


/**
 * Class TagController.
 *
 * @package Controller
 */
class TagController implements ControllerProviderInterface
{
    /**
     * Routing settings.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Silex\ControllerCollection Result
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->match('/page/{page}', [$this, 'indexAction'])
            ->method('GET|POST')
            ->value('page', 1)
            ->bind('tag_index');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('tag_delete');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('tag_add');


        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $page Current page number
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexAction(Application $app, $page = 1)
    {
        $tagRepository = new TagRepository($app['db']);
        $tags = $tagRepository->findAllPaginated($page);
        $userRepository = new UserRepository($app['db']);
        $loggedUser = $userRepository->getLoggedUser($app);

        return $app['twig']->render(
            'tag/index.html.twig',
            [
                'loggedUser' => $loggedUser,
                'tags' => $tags,
            ]
        );
    }


    /**
     * Tagaction.
     *
     * @param \Silex\Application $app Silex application
     * @param int $id Element Id
     * @param int $page Current page number
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */

    public function tagAction(Application $app, $id, $page = 1)
    {
        $tagRepository = new TagRepository($app['db']);
        $tags = $tagRepository->findAllPaginated($page);

        return $app['twig']->render(
            'tag/index.html.twig',

            ['tags' => $tags]
        );
    }


    /**
     * Delete action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $id Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        $userRepository = new UserRepository($app['db']);
        $loggedUser = $userRepository->getLoggedUser($app);

        $tagRepository = new TagRepository($app['db']);
        $tag = $tagRepository->findOneById($id);

        if (!$tag) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('tag_index'), 301);
        }

        if ($app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
            $form = $app['form.factory']->createBuilder(
                FormType::class,
                $tag
            )->add('id', HiddenType::class)->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $tagRepository->delete($form->getData());

                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_deleted',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('tag_index'), 301);
            }
        } else {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.you_are_not_an_admin',
                ]
            );

            return $app->redirect($app['url_generator']->generate('home_index'), 301);
        }


        return $app['twig']->render(
            'tag/delete.html.twig',
            [
                'loggedUser' => $loggedUser,
                'tag' => $tag,
                'form' => $form->createView(),
            ]
        );
    }


    /**
     * Add action.
     *
     * @param \Silex\Application $app Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function addAction(Application $app, Request $request)
    {
        $userRepository = new UserRepository($app['db']);
        $loggedUser = $userRepository->getLoggedUser($app);

        $tag = [];

        if ($loggedUser['id']) {
            $form = $app['form.factory']->createBuilder(
                TagType::class,
                $tag,
                ['tag_repository' => new TagRepository($app['db'])]
            )->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $tagRepository = new tagRepository($app['db']);
                $tag = $form->getData();
                $tagRepository->save($tag);


                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_added',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('photo_add', ['id' => $loggedUser['id']]), 301);
            }
        } else {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.you_have_to_log_in',
                ]
            );

            return $app->redirect($app['url_generator']->generate('home_index'));
        }

        return $app['twig']->render(
            'tag/add.html.twig',
            [
                'loggedUser' => $loggedUser,
                'id' => $loggedUser['id'],
                'tag' => $tag,
                'form' => $form->createView(),
            ]
        );
    }
}
