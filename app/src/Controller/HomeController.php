<?php
/**
 * Homecontroller.
 *
 */
namespace Controller;

use Form\UserType;
use Form\SearchType;
use Repository\UserRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Repository\PhotoRepository;
use Repository\TagRepository;

/**
 * Class HomeController.
 *
 * @package Controller
 */
class HomeController implements ControllerProviderInterface
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
        $controller->match('/', [$this, 'indexAction'])
            ->method('POST|GET')
            ->bind('home_index');
        $controller->match('/registration', [$this, 'registrationAction'])
            ->method('POST|GET')
            ->bind('home_registration');
        $controller->match('/search', [$this, 'searchAction'])
            ->method('POST|GET')
            ->bind('search_action');

        return $controller;
    }

    /**
     * Index action.
     * @param \Silex\Application $app Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     * @param int $page Current page number
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexAction(Application $app, Request $request, $page = 1)
    {
        $userRepository = new UserRepository($app['db']);
        $loggedUser = $userRepository->getLoggedUser($app);


        $photos = [];
        if ($loggedUser) {
            $photoRepository = new PhotoRepository($app['db']);
            $photos = $photoRepository->findAllPaginated($page);
        }

        return $app['twig']->render(
            'home/index.html.twig',
            [
                'loggedUser' => $loggedUser,
                'photos' => $photos,
            ]
        );
    }


    /**
     * Registration action.
     * @param \Silex\Application $app Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function registrationAction(Application $app, Request $request) //like add User and userdata
    {

        $user = [];
        $form = $app['form.factory']->createBuilder(
            UserType::class,
            $user,
            ['user_repository' => new UserRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $userRepository = new userRepository($app['db']);
            $user = $form->getData();
            $userRepository->save($app, $user);

            //tworzenie pustego imienia i nazwiska dla uzytkownika
            $user = $userRepository->findOneByLoginUser($user['login']);
            $userRepository->saveEmptyData($user);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('home_index'), 301);
        }

        return $app['twig']->render(
            'home/registration.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Search action.
     * @param \Silex\Application $app Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function searchAction(Application $app, Request $request)
    {
        $userRepository = new UserRepository($app['db']);
        $loggedUser = $userRepository->getLoggedUser($app);

        $search = [];
        $formSearch = $app['form.factory']->createBuilder(
            SearchType::class,
            $search
        )->getForm();
        $formSearch->handleRequest($request);

        if ($formSearch->isSubmitted() && $formSearch->isValid()) {
            $tag = $formSearch->getData();

            if ($tag['category'] == 'photo') {
                $tagRepository = new TagRepository($app['db']);
                $tag = $tagRepository->findIdByName($tag['value']);
                if ($tag) {
                    return $app->redirect($app['url_generator']->generate('photo_tag', ['id' => $tag['id']]), 301);
                } else {
                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'warning',
                            'message' => 'message.record_not_found',
                        ]
                    );
                }
            } elseif ($tag['category'] == 'user') {
                $userRepository = new UserRepository($app['db']);
                $user = $formSearch->getData();
                $user = $userRepository->findOneByLoginUser($user['value']);
                if ($user) {
                    return $app->redirect($app['url_generator']->generate('profile_view', ['id' => $user['id']]), 301);
                } else {
                    $app['session']->getFlashBag()->add(
                        'messages',
                        [
                            'type' => 'warning',
                            'message' => 'message.record_not_found',
                        ]
                    );
                }
            }
        }

        return $app['twig']->render(
            'home/search.html.twig',
            [
                'loggedUser' => $loggedUser,
                'formSearch' => $formSearch->createView(),
            ]
        );
    }
}
