<?php
/**
 * Photocontroller.
 *
 */
namespace Controller;

use Form\UserType;
use Form\UserdataType;
use Form\ProfileType;
use Form\PhotoType;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Repository\ProfileRepository;
use Repository\PhotoRepository;
use Repository\TagRepository;
use Repository\RatingRepository;
use Repository\UserRepository;
use Repository\UserdataRepository;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class PhotoController.
 *
 * @package Controller
 */
class ProfileController implements ControllerProviderInterface
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

        $controller->get('/', [$this, 'indexAction'])
            ->bind('profile_index');
        $controller->get('/page/{page}', [$this, 'indexActionPaginated'])
            ->value('page', 1)
            ->bind('profile_index_paginated');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->assert('id', '[1-9]\d*')
            ->bind('profile_view');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('profile_edit');
        $controller->match('/{id}/delete', [$this, 'deleteUserAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('profile_delete');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexAction(Application $app)
    {
        $profileRepository = new ProfileRepository($app['db']);

        $userRepository = new UserRepository($app['db']);
        $loggedUser = $userRepository->getLoggedUser($app);


        return $app['twig']->render(
            'profile/index.html.twig',
            [
                'profiles' => $profileRepository->findAllUsers(),
                'loggedUser' => $loggedUser,
            ]
        );
    }


    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $page Current page number
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexActionPaginated(Application $app, $page = 1)
    {
        $userRepository = new UserRepository($app['db']);
        $loggedUser = $userRepository->getLoggedUser($app);

        $users = $userRepository->findAllPaginated($page);


        return $app['twig']->render(
            'profile/index_paginated.html.twig',
            [
                'profiles' => $users,
                'loggedUser' => $loggedUser,
            ]
        );
    }


    /**
     * View action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $id Element Id
     * @param int $page Current page number
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */


    public function viewAction(Application $app, $id, $page = 1)
    {
        $profileRepository = new ProfileRepository($app['db']);
        $photoRepository = new PhotoRepository($app['db']);
        $userRepository = new UserRepository($app['db']);
        $loggedUser = $userRepository->getLoggedUser($app);


        $profile = $profileRepository->findOneByIdUser($id);

        if (!$profile) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('profile_index'));
        }

        $userId = $id;

        return $app['twig']->render(
            'profile/view.html.twig',
            [
                'id' => $id,
                'loggedUser' => $loggedUser,
                'profile' => $profile,
                'complete_profile' => $profileRepository->findOneById($id),
                'photos' => $photoRepository->findAllByUserPaginated($userId, $page),
            ]
        );
    }


    /**
     * Edit action.
     *
     * @param \Silex\Application $app Silex application
     * @param int $id Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $profileRepository = new ProfileRepository($app['db']);
        $profile = $profileRepository->findOneById($id);

        $userRepository = new UserRepository($app['db']);
        $user = $userRepository->findOneById($id);
        $userdataRepository = new UserdataRepository($app['db']);
        $userdata = $userdataRepository->findOneByUserId($id);

        $loggedUser = $userRepository->getLoggedUser($app);


        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('home_index'));
        }
//FORMULARZ - EDYCJA USER
        if ($loggedUser['id'] == $id or $app['security.authorization_checker']->isGranted('ROLE_ADMIN')) { //jesli to twoj profil albo jestes adminem to zezwol
            $form = $app['form.factory']->createBuilder(
                UserType::class,
                $user,
                ['user_repository' => new UserRepository($app['db'])]
            )->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $userRepository->save($app, $form->getData());

                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_edited',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('profile_view', ['id' => $id]), 301);
            }
            ////EDYCJA USERDATA
            $formData = $app['form.factory']->createBuilder(
                UserdataType::class,
                $userdata,
                ['userdata_repository' => new UserdataRepository($app['db'])]
            )->getForm();
            $formData->handleRequest($request);

            if ($formData->isSubmitted() && $formData->isValid()) {
                $userdataRepository->save($formData->getData());

                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_edited',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('profile_view', ['id' => $id]), 301);
            }
        } else {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.it_is_not_your_profile',
                ]
            );
            return $app->redirect($app['url_generator']->generate('home_index'));
        }


        return $app['twig']->render(
            'profile/edit.html.twig',
            [
                'loggedUser' => $loggedUser,
                'profile' => $profile,
                'form' => $form->createView(),
                'formData' => $formData->createView(),
            ]
        );
    }


    /**
     * Delete action. USER
     *
     * @param \Silex\Application $app Silex application
     * @param int $id Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteUserAction(Application $app, $id, Request $request)
    {
        $userRepository = new UserRepository($app['db']);
        $loggedUser = $userRepository->getLoggedUser($app);

        $user = $userRepository->findOneById($id);

        if (!$user) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('home_index'), 301);
        }

        if ($loggedUser['id'] == $id or $app['security.authorization_checker']->isGranted('ROLE_ADMIN')) { //jesli to twoj profil albo jestes adminem to zezwol
            $form = $app['form.factory']->createBuilder(
                FormType::class,
                $user
            )->add('id', HiddenType::class)->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $userRepository->deleteUser($app, $form->getData());
                //$userRepository->delete($form->getData());

                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_deleted',
                    ]
                );

                return $app->redirect(
                    $app['url_generator']->generate('home_index'),
                    301
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.it_is_not_your_profile',
                ]
            );

            return $app->redirect($app['url_generator']->generate('home_index'));
        }

        return $app['twig']->render(
            'profile/delete.html.twig',
            [
                'loggedUser' => $loggedUser,
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }
}
