<?php
/**
 * Commentcontroller.
 */
namespace Controller;

use Repository\CommentRepository;
use Repository\UserRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Class CommentController.
 *
 * @package Controller
 */
class CommentController implements ControllerProviderInterface
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
        $controller->match('/{id}/delete', [$this, 'deleteCommentAction'])
            ->method('GET|POST')
            ->assert('id', '[1-9]\d*')
            ->bind('comment_delete');

        return $controller;
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
    public function deleteCommentAction(Application $app, $id, Request $request)
    {
        $commentRepository = new CommentRepository($app['db']);
        $comment = $commentRepository->findOneById($id);
        $userRepository = new UserRepository($app['db']);
        $loggedUser = $userRepository->getLoggedUser($app);

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

        if ($loggedUser['id'] === $comment['userId'] or $app['security.authorization_checker']->isGranted('ROLE_ADMIN')) {
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

                return $app->redirect($app['url_generator']->generate('photo_view', ['id' => $comment['photoId']]), 301);
            }
        } else {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.it_is_not_your_comment',
                ]
            );

            return $app->redirect($app['url_generator']->generate('home_index'));   //generate('profile_view', ['id'=>$userId])); $userId = $photo['userId']
        }

        return $app['twig']->render(
            'comment/delete.html.twig',
            [
                'loggedUser' => $loggedUser,
                'comment' => $comment,
                'form' => $form->createView(),
            ]
        );
    }
}

