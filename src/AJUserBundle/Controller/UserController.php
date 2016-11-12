<?php

namespace AJUserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use AJUserBundle\Entity\User;
use AJUserBundle\Form\UserType;

class UserController extends Controller
{
    public function indexAction(Request $request)
    {
        $searchQuery = $request->get('q');
        
        if(!empty($searchQuery)){
            $finder = $this->container->get('fos_elastica.finder.app.user');
            $users = $finder->createPaginatorAdapter($searchQuery);
        }else{
            $em = $this->getDoctrine()->getManager();
            $dql = "SELECT u FROM AJUserBundle:User u ORDER BY u.id DESC";
            $users = $em->createQuery($dql);
        }
        
       
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $users, $request->query->getInt('page',1),
            5
        );
        
        $deleteFormAjax = $this->createCustomForm(':USER_ID','DELETE','aj_user_delete');
       
        return $this->render('AJUserBundle:User:index.html.twig', 
        array('pagination'=> $pagination,'delete_form_ajax'=>$deleteFormAjax->createView()));
        
    }
    
    public function addAction($id)
    {
        $user = new User();
        $form = $this->createCreateForm($user);
        //, array('form'=>$form->createView())
        return $this->render("AJUserBundle:User:add.html.twig", array('form'=>$form->createView()));
    }
    
    private function createCreateForm(User $entity){
        $form = $this->createForm(UserType::class, $entity, array(
            'action' => $this->generateUrl('aj_user_create'),
            'method' => 'POST'
            ));
        return $form;
    }
    
    public function createAction(Request $request){
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);
        
        if($form->isValid()){
            $password = $form->get('password')->getData();
            
            //Validacion del Password desde el lado del contralador
            $passwordConstraint = new Assert\NotBlank();
            $errorList = $this->get('validator')->validate($password,$passwordConstraint);
            
            //print(count($errorList));exit();
            if(count($errorList) == 0){
                $factory = $this->get("security.encoder_factory");
    		    $encoder = $factory->getEncoder($user);
                $encoded = $encoder->encodePassword($password,$user);
                
                $user->setPassword($encoded);
                
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();
                
                
                $successMensaje = $this->get('translator')->trans('The user has been created.');
                $this->addFlash('mensaje',$successMensaje);
               
                return $this->redirectToRoute("aj_user_index");
                
            }else{
                
                $errorMessage = new FormError($errorList[0]->getMessage());
                //$errorMessageN = $errorMessage->getMessage();
                $form->get('password')->addError($errorMessage);
                
            }
            
            
        }
        
        return $this->render("AJUserBundle:User:add.html.twig", array('form'=>$form->createView()));
        
    }
    
    public function editAction($id)
    {
       
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AJUserBundle:User')->find($id);
        
        if(!$user){
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
        }
        
        $form = $this->createEditForm($user);
        
        return $this->render("AJUserBundle:User:edit.html.twig", array('user'=>$user,'form'=>$form->createView()));
    }
    
    private function createEditForm(User $entity){
        $form = $this->createForm(UserType::class, $entity, array(
            'action' => $this->generateUrl('aj_user_update',array('id'=>$entity->getId())),
            'method' => 'PUT'
            ));
        return $form;
    }
    
    
    public function updateAction($id, Request $request){
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AJUserBundle:User')->find($id);
         
        if(!$user){
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
        }
        
        $form = $this->createEditForm($user);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $password = $form->get('password')->getData();
            if (!empty($password)) {
                $factory = $this->get("security.encoder_factory");
                $encoder = $factory->getEncoder($user);
                $encoded = $encoder->encodePassword($password,$user);
                
                $user->setPassword($encoded);
            }else{
                $recoverPass = $this->recoverPass($id);
                $user->setPassword($recoverPass[0]['password']);
            }
            
            if($form->get('role')->getData() == 'ROLE_ADMIN'){
                $user->setIsActive(1);
            }
            
            
            $em->persist($user);
            $em->flush();
            $successMensaje = $this->get('translator')->trans('The user has been modified.');
            $this->addFlash('mensaje',$successMensaje);
            return $this->redirectToRoute("aj_user_edit",array('id'=>$user->getId()));
        }
        return $this->render("AJUserBundle:User:edit.html.twig", array('user'=>$user,'form'=>$form->createView()));
    }
    
    private function recoverPass($id){
        $em = $this->getDoctrine()->getManager();
        $dql = $em->createQuery(
        "SELECT u.password FROM AJUserBundle:User u
        WHERE u.id = :id"
        )->setParameter('id',$id);
        
        $currentPass = $dql->getResult();
        
        return $currentPass;
    }
    
    
    
    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getManager()->getRepository("AJUserBundle:User");
        
        $user = $repository->find($id);
        
         if(!$user){
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
        }
        
        $deleteForm = $this->createCustomForm($user->getId(),'DELETE','aj_user_delete');
        
        return $this->render("AJUserBundle:User:view.html.twig", array('user'=>$user,'delete_form'=>$deleteForm->createView()));
    }
    
    /*
    private function createDeleteForm($user){
        return $this->createFormBuilder()
        ->setAction($this->generateUrl('aj_user_delete',array('id'=>$user->getId())))
        ->setMethod('DELETE')
        ->getForm();
    }*/
    
    public function deleteAction($id, Request $request){
        /*if($request->isXMLHttpRequest()){
            return new Response(
                json_encode(array('removed'=>'LOL')),
                200,
                array('Content-Type'=>'application/json')
            );
        }*/
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AJUserBundle:User')->find($id);
        
        if(!$user){
            $messageException = $this->get('translator')->trans('User not found.');
            throw $this->createNotFoundException($messageException);
        }
        
        $allUsers = $em->getRepository('AJUserBundle:User')->findAll();
        $countUser = count($allUsers);
        
        //$form = $this->createDeleteForm($user);
        $form = $this->createCustomForm($user->getId(),'DELETE','aj_user_delete');
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            
            if($request->isXMLHttpRequest())
            {
                $res = $this->deleteUser($user->getRole(), $em, $user);
                
                return new Response(
                    json_encode(array('removed' => $res['removed'], 'message' => $res['message'], 'countUsers' => $countUser)),
                    200,
                    array('Content-Type' => 'application/json')
                );
            }
            
            $res = $this->deleteUser($user->getRole(),$em,$user);
            
            $this->addFlash($res['alert'],$res['message']);
            return $this->redirectToRoute("aj_user_index");
        }
        
    }
    
    private function deleteUser($role, $em, $user){
        if($role == 'ROLE_USER'){
            $em->remove($user);
            $em->flush();
            
            $message = $this->get('translator')->trans('The user has been deleted.');
            
            $removed = 1;
            $alert = 'mensaje';
            
        }elseif($role == 'ROLE_ADMIN'){
            $message = $this->get('translator')->trans('The user could not be deleted.');
            $removed = 0;
            $alert = 'error';
        }
        
        return array('removed'=>$removed,'message'=>$message,'alert'=>$alert);
        
    }
    
    
    private function createCustomForm($id, $method, $route){
        return $this->createFormBuilder()
            ->setAction($this->generateUrl($route,array('id'=>$id)))
            ->setMethod($method)
            ->getForm();
    }
    
    
}
