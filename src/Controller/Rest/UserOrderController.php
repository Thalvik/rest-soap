<?php

namespace App\Controller\Rest;

use JMS\Serializer\Exception\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security as NelmioSecurity;
use Swagger\Annotations as SWG;

use App\Entity\UserOrder;
use App\Form\UserOrderType;
use App\Form\UserOrderEditType;

/**
 * @Rest\Route("/api/userorder")
 */
class UserOrderController extends AbstractFOSRestController
{
    /**
     * Returns the single order based on params
     *
     * @Route("", methods={"GET"})
     * @SWG\Response(
     *     response=200,
     *     description="Returns order",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=UserOrder::class, groups={"full"}))
     *     )
     * )
     * @SWG\Parameter(name="id",in="query",type="integer",description="id of the order")
     * @SWG\Tag(name="order")
     * @NelmioSecurity(name="Bearer")
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function getAction(Request $request)
    {
        //Find order by id
        $order = $this->getDoctrine()->getRepository('App:UserOrder')->findOneBy([
            'id' => $request->query->get('id', null)
        ]);

        if (!$order) {
            $view = $this->view(['Order not found'], 404);
            return $this->handleView($view);
        }

        $view = $this->view($order, 200);

        return $this->handleView($view);
    }

    /**
     * Insert new order
     *
     * @Route("", methods={"POST"})
     * @SWG\Response(
     *     response=201,
     *     description="Insert new order",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=Order::class, groups={"full"}))
     *     )
     * )
     * @SWG\Parameter(name="userId",in="request",type="integer",description="id of the user")
     * @SWG\Parameter(name="orderAmount",in="request",type="decimal",description="orderAmount")
     * @SWG\Parameter(name="shippingAmount",in="request",type="decimal",description="shippingAmount")
     * @SWG\Parameter(name="taxAmount",in="request",type="decimal",description="taxAmount")
     * @SWG\Tag(name="order")
     * @NelmioSecurity(name="Bearer")
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function addAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        //The API call is sending JSON content type, so we must decode it
        $data = json_decode($request->getContent(), true);

        $user = null;
        if (array_key_exists('userId', $data)) {
            $user = $this->getDoctrine()->getRepository('App:User')->findOneBy([
                'id' => $data['userId']
            ]);
        }

        if (!$user) {
            $view = $this->view(['User not found'], 404);
            return $this->handleView($view);
        }

        $userOrder = new UserOrder();
        $userOrder->setUser($user);

        //Create form and submmit data
        $form = $this->createForm(UserOrderType::class, $userOrder);
        $form->submit($data);

        if ($form->isValid()) {
            try {
                $em->persist($userOrder);
                $em->flush();

                $view = $this->view($userOrder, 201);
                return $this->handleView($view);
            } catch (\Exception $e) {
                $view = $this->view([], 500);
                return $this->handleView($view);
            }
        }

        //The request is not valid
        $view = $this->view($form->getErrors(), 400);
        return $this->handleView($view);
    }

    /**
     * Updates order
     *
     * @Route("", methods={"PUT"})
     * @SWG\Response(
     *     response=201,
     *     description="Updates order",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=UserOrder::class, groups={"full"}))
     *     )
     * )
     * @SWG\Parameter(name="id",in="request",type="integer",description="id of the order")
     * @SWG\Parameter(name="orderAmount",in="request",type="decimal",description="orderAmount")
     * @SWG\Parameter(name="shippingAmount",in="request",type="decimal",description="shippingAmount")
     * @SWG\Parameter(name="taxAmount",in="request",type="decimal",description="taxAmount")
     * @SWG\Tag(name="userorder")
     * @NelmioSecurity(name="Bearer")
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function updateAction(Request $request)
    {
        //Find order
        //The API call is sending JSON content type, so we must decode it
        $data = json_decode($request->getContent(), true);

        $userOrder = null;
        if (array_key_exists('id', $data)) {
            $userOrder = $this->getDoctrine()->getRepository('App:UserOrder')->findOneBy([
                'id' => $data['id']
            ]);
        }

        if (!$userOrder) {
            $view = $this->view(['Order not found'], 404);
            return $this->handleView($view);
        }

        //Create form and submmit data
        $form = $this->createForm(UserOrderEditType::class, $userOrder);
        $form->submit($data);

        if ($form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $view = $this->view($userOrder, 201);
                return $this->handleView($view);
            } catch (\Exception $e) {
                $view = $this->view([], 500);
                return $this->handleView($view);
            }
        }

        //The request is not valid
        $view = $this->view($form->getErrors(), 400);
        return $this->handleView($view);
    }


    /**
     * Deletes order
     *
     * @Route("", methods={"DELETE"})
     * @SWG\Response(
     *     response=200,
     *     description="Deletes order",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=UserOrder::class, groups={"full"}))
     *     )
     * )
     * @SWG\Parameter(name="id",in="request",type="integer",description="id of the order")
     * @SWG\Tag(name="order")
     * @NelmioSecurity(name="Bearer")
     * @param $request
     * @return mixed
     * @throws \Exception
     */
    public function deleteAction(Request $request)
    {
        //Find order
        $userOrder = $this->getDoctrine()->getRepository('App:UserOrder')->findOneBy([
            'id' => $request->request->get('id', null)
        ]);

        //If not found
        if (!$userOrder) {
            $view = $this->view(['Order not found'], 404);
            return $this->handleView($view);
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($userOrder);
        $em->flush();

        $view = $this->view([], 200);
        return $this->handleView($view);
    }
}
