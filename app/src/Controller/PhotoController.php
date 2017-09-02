<?php
/**
 * Photocontroller.
 *
 */
namespace Controller;

use Form\PhotoType;
use Form\RatingType;
use Form\CommentType;
use Repository\PhotoRepository;
use Repository\TagRepository;
use Repository\ProfileRepository;
use Repository\RatingRepository;
use Repository\CommentRepository;
use Repository\UserRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Service\FileUploader;


/**
 * Class PhotoController.
 *
 * @package Controller
 */
class PhotoController implements ControllerProviderInterface
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
        $controller->get('/', [$this, 'indexAction'])->bind('photo_index');
        $controller->match('/{id}/page/{page}', [$this, 'viewAction'])
            ->method('GET|POST')
            ->value('page', 1)
            ->bind('photo_view');
        $controller->get('/tag/{id}', [$this, 'tagAction'])->bind('photo_tag');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('photo_index_paginated');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('photo_edit');
        $controller->match('/{id}/delete', [$this,'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('photo_delete');
        $controller->match('/{id}/comment/delete', [$this,'deleteCommentAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('comment_delete');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('photo_add');

        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexAction(Application $app, $page = 1)
    {
        $userRepository = new UserRepository($app['db']);
        $logged_user=$userRepository->getLoggedUser($app);


        $photoRepository = new PhotoRepository($app['db']);
        $photos=$photoRepository->findAllPaginated($page);


        return $app['twig']->render(
            'photo/index.html.twig',
            [
                'logged_user'=>$logged_user,
                'photos' => $photos
            ]
        );
    }




    /**
     * View action.
     *
     * @param \Silex\Application $app Silex application
     * @param string             $id  Element Id
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function viewActionWithoutPaginated(Application $app, $id, Request $request)
    {
        $photoRepository = new PhotoRepository($app['db']);
        $profileRepository = new ProfileRepository($app['db']);
        $userRepository = new UserRepository($app['db']);
        $logged_user=$userRepository->getLoggedUser($app);
        $ratingRepository = new RatingRepository($app['db']);
        $commentRepository = new CommentRepository($app['db']);

        $token = $app['security.token_storage']->getToken();


        $photo = $photoRepository->findOneById($id);
        if (!$photo) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );
            return $app->redirect($app['url_generator']->generate('photo_index'));
        }

        $rating = [];
        $form = $app['form.factory']->createBuilder(
            RatingType::class,
            $rating,
            ['rating_repository' => new RatingRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ratingRepository = new RatingRepository($app['db']);

            $rating=$form->getData();
            $rating['user_id']=$logged_user['id'];
            $rating['photo_id']=$id;

            $ratingRepository->save($rating);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.photo_successfully_rated',
                ]
            );

        }

        $comment = [];
        $c_form = $app['form.factory']->createBuilder(
            CommentType::class,
            $comment,
            ['comment_repository' => new CommentRepository($app['db'])]
        )->getForm();
        $c_form->handleRequest($request);

        if ($c_form->isSubmitted() && $c_form->isValid()) {
            $commentRepository = new CommentRepository($app['db']);

            $comment = $c_form->getData();
            $comment['user_id'] = $logged_user['id'];
            $comment['photo_id'] = $id;

            $commentRepository->save($comment);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

        }

        return $app['twig']->render(
            'photo/view.html.twig',
            [
                'logged_user'=>$logged_user,
                'id'=>$id,
                'photo' => $photo,
                'profile' => $profileRepository->findOneById($photo['user_id']),
                'rating' => $ratingRepository->AverageRaringForPhoto($id),
                'form' => $form->createView(),
                'form_comment' => $c_form->createView(),
                'comments'=>$commentRepository->findAllOfPhoto($id),
            ]

        );
    }




    /**
     * View action.
     *
     * @param \Silex\Application $app Silex application
     * @param string             $id  Element Id
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function viewAction(Application $app, $id, Request $request, $page=1)
    {
        $photoRepository = new PhotoRepository($app['db']);
        $profileRepository = new ProfileRepository($app['db']);
        $ratingRepository = new RatingRepository($app['db']);
        $commentRepository = new CommentRepository($app['db']);
        $userRepository = new UserRepository($app['db']);
        $logged_user=$userRepository->getLoggedUser($app);

        $user_have_rated=$ratingRepository->CheckIfUserRatedPhoto($id, $logged_user['id']);



        $photo = $photoRepository->findOneById($id);
        $tags = $photoRepository->findLinkedTagsNames($id);

        if (!$photo) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );
            return $app->redirect($app['url_generator']->generate('photo_index'));
        }

        $rating = [];
        $form = $app['form.factory']->createBuilder(
            RatingType::class,
            $rating,
            ['rating_repository' => new RatingRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ratingRepository = new RatingRepository($app['db']);

            $rating=$form->getData();
            $rating['user_id']=$logged_user['id'];
            $rating['photo_id']=$id;

            $ratingRepository->save($rating);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );
            echo "<meta http-equiv='Refresh' content='0.1'/>";
        }

        $comment = [];
        $c_form = $app['form.factory']->createBuilder(
            CommentType::class,
            $comment,
            ['comment_repository' => new CommentRepository($app['db'])]
        )->getForm();
        $c_form->handleRequest($request);

        if ($c_form->isSubmitted() && $c_form->isValid()) {
            $commentRepository = new CommentRepository($app['db']);

            $comment = $c_form->getData();
            $comment['user_id'] = $logged_user['id'];
            $comment['photo_id'] = $id;

            $commentRepository->save($comment);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

        }

        return $app['twig']->render(
            'photo/view_paginated.html.twig',
            [
                'logged_user'=>$logged_user,
                'id'=>$id,
                'photo' => $photo,
                'tags' => $tags,
                'profile' => $profileRepository->findOneById($photo['user_id']),
                'rating' => $ratingRepository->AverageRaringForPhoto($id),
                'user_have_rated' => $user_have_rated,
                'form' => $form->createView(),
                'form_comment' => $c_form->createView(),
                'comments'=>$commentRepository->findAllOfPhotoPaginated($id, $page),
            ]

        );
    }



    /**
     * Tagaction.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function tagAction(Application $app, $id, Request $request, $page=1) //public function tagAction(Application $app, $tags)
    {
        $userRepository = new UserRepository($app['db']);
        $logged_user=$userRepository->getLoggedUser($app);

        $photoRepository = new PhotoRepository($app['db']);
        $photos=$photoRepository->findAllWithTagPaginated($id);

        return $app['twig']->render(
            'photo/tag.html.twig',
            [
                'logged_user'=>$logged_user,
                'photos' => $photos
            ]

        );
    }




    /**
     * Edit action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $photoRepository = new PhotoRepository($app['db']);
        $photo = $photoRepository->findOneById($id);
        $userRepository = new UserRepository($app['db']);
        $logged_user=$userRepository->getLoggedUser($app);

        if (!$photo) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('home_index'));   //generate('profile_view', ['id'=>$user_id])); $user_id = $photo['user_id']
        }
//dump(is_granted('IS_AUTHENTICATED_FULLY'))
        if($logged_user['id']===$photo['user_id'] or $logged_user['role']==='admin') {
            $form = $app['form.factory']->createBuilder(
                PhotoType::class,
                $photo,
                ['tag_repository' => new TagRepository($app['db'])]
            )->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $photoRepository->save($form->getData(),'');

                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_edited',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('profile_view', ['id'=>$photo['user_id']]), 301);
            }
        }else{
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.it_is_not_your_photo',
                ]
            );
            return $app->redirect($app['url_generator']->generate('home_index'));   //generate('profile_view', ['id'=>$user_id])); $user_id = $photo['user_id']
        }


        return $app['twig']->render(
            'photo/edit.html.twig',
            [
                'logged_user'=>$logged_user,
                'photo' => $photo,
                'form' => $form->createView(),
            ]
        );
    }
    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */

    public function deleteAction(Application $app, $id, Request $request)
    {
        $photoRepository = new PhotoRepository($app['db']);
        $photo = $photoRepository->findOneById($id);
        $userRepository = new UserRepository($app['db']);
        $logged_user=$userRepository->getLoggedUser($app);

        if (!$photo) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('photo_index'), 301);
        }
        if($logged_user['id']===$photo['user_id'] or $logged_user['role']==='admin') {
            $form = $app['form.factory']->createBuilder(
                FormType::class,
                $photo
            )->add('id', HiddenType::class)->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $photoRepository->delete($form->getData());

                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_deleted',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('profile_view', ['id' => $photo['user_id']]), 301);

            }
        }else{
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.it_is_not_your_photo',
                ]
            );
            return $app->redirect($app['url_generator']->generate('home_index'));   //generate('profile_view', ['id'=>$user_id])); $user_id = $photo['user_id']
        }

        return $app['twig']->render(
            'photo/delete.html.twig',
            [
                'logged_user'=>$logged_user,
                'photo' => $photo,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */

    public function deleteCommentAction(Application $app, $id, Request $request)
    {
        $commentRepository = new CommentRepository($app['db']);
        $comment = $commentRepository->findOneById($id);
        $userRepository = new UserRepository($app['db']);
        $logged_user=$userRepository->getLoggedUser($app);

        if (!$comment) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('home_index'), 301);
        }

        if($logged_user['id']===$comment['user_id'] or $logged_user['role']==='admin') {
            $form = $app['form.factory']->createBuilder(
                FormType::class,
                $comment
            )->add('id', HiddenType::class)->getForm();
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $commentRepository->delete($form->getData());

                $app['session']->getFlashBag()->add(
                    'messages',
                    [
                        'type' => 'success',
                        'message' => 'message.element_successfully_deleted',
                    ]
                );

                return $app->redirect($app['url_generator']->generate('photo_view', ['id'=>$comment['photo_id']]), 301);
            }
        }else{
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.it_is_not_your_comment',
                ]
            );
            return $app->redirect($app['url_generator']->generate('home_index'));   //generate('profile_view', ['id'=>$user_id])); $user_id = $photo['user_id']
        }

        return $app['twig']->render(
            'comment/delete.html.twig',
            [
                'logged_user'=>$logged_user,
                'comment' => $comment,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Add action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function addAction(Application $app, Request $request)
    {
        $userRepository = new UserRepository($app['db']);
        $logged_user=$userRepository->getLoggedUser($app);
        $id=$logged_user['id'];
        $photo = [];

        $token = $app['security.token_storage']->getToken();


        if(!$logged_user['id']) {
                return $app->redirect($app['url_generator']->generate('home_index', 301));
            }

        $form = $app['form.factory']->createBuilder(
            PhotoType::class,
            $photo,
            ['tag_repository' => new TagRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photo  = $form->getData();
            $fileUploader = new FileUploader($app['config.photos_directory']);
            $fileName = $fileUploader->upload($photo['source']);
            $photo['source'] = $fileName;
            $photoRepository = new PhotoRepository($app['db']);



            $photo['user_id']=$logged_user['id'];
            $photoRepository->save($photo);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type'    => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('profile_view', ['id'=>$logged_user['id']]), 301);
        }

        return $app['twig']->render(
            'photo/add.html.twig',
            [
                'logged_user'=>$logged_user,
                'id'=>$id,
                'profile' => $userRepository->findOneById($id),
                'photo'  => $photo,
                'form' => $form->createView(),
            ]
        );
    }

}

