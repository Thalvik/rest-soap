<?php

namespace App\Controller\Rest;

use JMS\Serializer\Exception\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\View\View;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as NelmioSecurity;
use Swagger\Annotations as SWG;

use App\Entity\User;
use App\Form\UserType;
use App\Form\UserEditType;

/**
 * @Rest\Route("/api/user")
 */
class UserController extends FOSRestController
{
    /**
     * Returns the single user based on params
     *
     * @Route("", methods={"GET"})
     * @SWG\Response(
     *     response=200,
     *     description="Returns user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"full"}))
     *     )
     * )
     * @SWG\Parameter(name="id",in="query",type="integer",description="id of the user")
     * @SWG\Parameter(name="firstName",in="query",type="string",description="firstName of the user")
     * @SWG\Parameter(name="lastName",in="query",type="string",description="lastName of the user")
     * @SWG\Parameter(name="email",in="query",type="string",description="email of the user")
     * @SWG\Parameter(name="street",in="query",type="string",description="street of the user")
     * @SWG\Parameter(name="country",in="query",type="string",description="country of the user")
     * @SWG\Tag(name="user")
     * @NelmioSecurity(name="Bearer")
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function getAction(Request $request)
    {
        //Check all params and form array
        $params = array_filter([
            'id' => $request->query->get('id', null),
            'firstName' => $request->query->get('firstName', null),
            'lastName' => $request->query->get('lastName', null),
            'email' => $request->query->get('email', null),
            'street' => $request->query->get('street', null),
            'country' => $request->query->get('country', null),
        ]);

        //Find user by params
        $user = !empty($params) ? $this->getDoctrine()->getRepository('App:User')->findOneBy($params) : [];

        if (!$user) {
            $view = $this->view(['User not found'], 404);
            return $this->handleView($view);
        }

        $view = $this->view($user, 200);

        return $this->handleView($view);
    }


    /**
     * Insert new user
     *
     * @Route("", methods={"POST"})
     * @SWG\Response(
     *     response=201,
     *     description="Insert new user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"full"}))
     *     )
     * )
     * @SWG\Parameter(name="firstName",in="request",type="string",description="firstName of the user")
     * @SWG\Parameter(name="lastName",in="request",type="string",description="lastName of the user")
     * @SWG\Parameter(name="email",in="request",type="string",description="email of the user")
     * @SWG\Parameter(name="street",in="request",type="string",description="street of the user")
     * @SWG\Parameter(name="country",in="request",type="string",description="country of the user")
     * @SWG\Tag(name="user")
     * @NelmioSecurity(name="Bearer")
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function addAction(Request $request)
    {
        //Get services
        $dispatcher = $this->get('event_dispatcher');

        //Instance User class and auto enable it
        $user = new User();
        $user->setEnabled(true);

        //Dispatch registration
        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        //The API call is sending JSON content type, so we must decode it
        $data = json_decode($request->getContent(), true);

        //Create form and submmit data
        $form = $this->createForm(UserType::class, $user);
        $form->submit($data);

        if ($form->isValid()) {
            //We will use e-mail as username
            $user->setUsername($form->getData()->getEmail());

            //Dispatch registration success
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

            //Update user with new data
            $this->get('fos_user.user_manager')->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $view = $this->view($user, 201);
                return $this->handleView($view);
            }

            //Dispatch registration complete
            $dispatcher->dispatch(
                FOSUserEvents::REGISTRATION_COMPLETED,
                new FilterUserResponseEvent($user, $request, $response)
            );

            $view = $this->view($user, 201);
            return $this->handleView($view);
        }

        //The request is not valid
        $view = $this->view($form->getErrors(), 400);
        return $this->handleView($view);
    }

    /**
     * Updates user
     *
     * @Route("", methods={"PUT"})
     * @SWG\Response(
     *     response=201,
     *     description="Updates user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"full"}))
     *     )
     * )
     * @SWG\Parameter(name="firstName",in="request",type="string",description="firstName of the user")
     * @SWG\Parameter(name="lastName",in="request",type="string",description="lastName of the user")
     * @SWG\Parameter(name="email",in="request",type="string",description="email of the user")
     * @SWG\Parameter(name="street",in="request",type="string",description="street of the user")
     * @SWG\Parameter(name="country",in="request",type="string",description="country of the user")
     * @SWG\Tag(name="user")
     * @NelmioSecurity(name="Bearer")
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function updateAction(Request $request)
    {
        //Find user
        $user = $this->getDoctrine()->getRepository('App:User')->findOneBy([
            'id' => $request->request->get('id', null)
        ]);

        if (!$user) {
            $view = $this->view(['User not found'], 404);
            return $this->handleView($view);
        }

        //The API call is sending JSON content type, so we must decode it
        $data = json_decode($request->getContent(), true);

        //Create form and submmit data
        $form = $this->createForm(UserEditType::class, $user);
        $form->submit($data);

        if ($form->isValid()) {
            if (isset($data['firstName'])) {
                $user->setFirstName($data['firstName']);
            }

            if (isset($data['lastName'])) {
                $user->setLastName($data['lastName']);
            }

            if (isset($data['street'])) {
                $user->setLastName($data['street']);
            }

            if (isset($data['country'])) {
                $user->setLastName($data['country']);
            }

            //Update user with new data
            $this->get('fos_user.user_manager')->updateUser($user);

            $view = $this->view($user, 201);
            return $this->handleView($view);
        }

        //The request is not valid
        $view = $this->view($form->getErrors(), 400);
        return $this->handleView($view);
    }

    /**
     * Deletes user
     *
     * @Route("", methods={"DELETE"})
     * @SWG\Response(
     *     response=200,
     *     description="Deletes user",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=User::class, groups={"full"}))
     *     )
     * )
     * @SWG\Parameter(name="id",in="request",type="integer",description="id of the user")
     * @SWG\Tag(name="user")
     * @NelmioSecurity(name="Bearer")
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function deleteAction(Request $request)
    {
        $userManager = $this->get('fos_user.user_manager');

        //Find user
        $user = $this->getDoctrine()->getRepository('App:User')->findOneBy([
            'id' => $request->request->get('id', null)
        ]);

        //If not found
        if (!$user) {
            $view = $this->view(['User not found'], 404);
            return $this->handleView($view);
        }

        //Delete user
        $userManager->deleteUser($user);

        $view = $this->view([], 200);
        return $this->handleView($view);
    }
}
