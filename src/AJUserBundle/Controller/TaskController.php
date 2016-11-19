<?php

namespace AJUserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AJUserBundle\Entity\Task;
use AJUserBundle\Form\TaskType;

class TaskController extends Controller
{
    public function indexAction(Request $request)
    {
        $searchQuery = $request->get('q');
        //$searchQuery = new \Elastica\Query\QueryString();
       
        
        if(!empty($searchQuery)){
            
            //$searchQuery = "*".$searchQuery."*";//BUSCAR LOS QUE COINCIDEN CON LA BUSQUEDA PARCIAL O TOTALMENTE
            $finder = $this->container->get('fos_elastica.finder.app.task');
            $tasks = $finder->createPaginatorAdapter($searchQuery);
            
           
        }else{
            $em = $this->getDoctrine()->getManager();
            $dql = "SELECT t FROM AJUserBundle:Task t ORDER BY t.id DESC";
            $tasks = $em->createQuery($dql);
        }
        
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $tasks, $request->query->getInt('page',1),
            5
        );
        
        return $this->render('AJUserBundle:Task:index.html.twig', 
        array('pagination'=> $pagination));
    }
    
    public function customAction(Request $request){
        
        $idUser = $this->get('security.token_storage')->getToken()->getUser()->getId();
       
        
        
        $em = $this->getDoctrine()->getManager();
        $dql = "SELECT t FROM AJUserBundle:Task t  JOIN t.user u WHERE u.id = :idUser
        ORDER BY t.id DESC";
        $tasks = $em->createQuery($dql)->setParameter('idUser',$idUser);
         
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $tasks, $request->query->getInt('page',1),
            5
        );
        
        $updateForm = $this->createCustomForm(':TASK_ID','PUT','aj_task_process');
        
        
        return $this->render('AJUserBundle:Task:custom.html.twig', 
        array('pagination'=> $pagination,'update_form'=>$updateForm->createView()));
        
    }
    
    public function processAction($id, Request $request){
        
        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('AJUserBundle:Task')->find($id);
        
        if(!$task){
            $messageException = $this->get('translator')->trans('Task not found.');
            throw $this->createNotFoundException($messageException);
        }
        
        $form = $this->createCustomForm($task->getId(),'PUT','aj_task_process');
        $form->handleRequest($request);
        
        
        if($form->isSubmitted() && $form->isValid())
        {
            
            $successMessage = $this->get('translator')->trans('The task has been finished.');
            $warningMessage = $this->get('translator')->trans('La tarea ya estaba finalizado.');
            
            if ($task->getStatus() == 0)
            {
                $task->setStatus(1);
                $em->flush();
                
                if($request->isXMLHttpRequest())
                {
                    return new Response(
                        json_encode(array('processed' => 1, 'message' => $successMessage)),
                        200,
                        array('Content-Type' => 'application/json')
                    );
                }
            }
            else
            {
                if($request->isXMLHttpRequest())
                {
                    return new Response(
                        json_encode(array('processed' => 0, 'message' => $warningMessage)),
                        200,
                        array('Content-Type' => 'application/json')
                    );
                }            
            }
        }
        
        
    }
    
    
    public function addAction(){
        $task = new Task();
        
        $form = $this->createCreateForm($task);
        
        return $this->render("AJUserBundle:Task:add.html.twig", array('form'=>$form->createView()));
        
    }
    
    
    private function createCreateForm(Task $entity){
        $form = $this->createForm(TaskType::class, $entity, array(
            'action' => $this->generateUrl('aj_task_create'),
            'method' => 'POST'
            ));
            
        return $form;
    }
    
    public function createAction(Request $request){
        $task = new Task();
        $form = $this->createCreateForm($task);
        $form->handleRequest($request);
        
        if($form->isValid()){
            $task->setStatus(0);
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();
            
            $successMensaje = $this->get('translator')->trans('The task has been created.');
            $this->addFlash('mensaje',$successMensaje);
           
            return $this->redirectToRoute("aj_task_index");
        }
        
        return $this->render("AJUserBundle:Task:add.html.twig", array('form'=>$form->createView()));
        
    }
    
    public function viewAction($id)
    {
        $repository = $this->getDoctrine()->getManager()->getRepository("AJUserBundle:Task");
        
        $task = $repository->find($id);
        
        if(!$task){
            $messageException = $this->get('translator')->trans('The task does not exist.');
            throw $this->createNotFoundException($messageException);
        }
        
        $user = $task->getUser();
        
        $deleteForm = $this->createCustomForm($task->getId(),'DELETE','aj_task_delete');
        
        return $this->render("AJUserBundle:Task:view.html.twig", array('task'=>$task,'user'=>$user,'delete_form'=>$deleteForm->createView()));
        //return $this->render("AJUserBundle:Task:view.html.twig", array('task'=>$task,'user'=>$user));
    }
    
    public function editAction($id)
    {
       
        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('AJUserBundle:Task')->find($id);
        
        if(!$task){
            $messageException = $this->get('translator')->trans('Task not found.');
            throw $this->createNotFoundException($messageException);
        }
        
        $form = $this->createEditForm($task);
        
        return $this->render("AJUserBundle:Task:edit.html.twig", array('task'=>$task,'form'=>$form->createView()));
    }
    
    private function createEditForm(Task $entity){
        $form = $this->createForm(TaskType::class, $entity, array(
            'action' => $this->generateUrl('aj_task_update',array('id'=>$entity->getId())),
            'method' => 'PUT'
            ));
        return $form;
    }
    
    public function updateAction($id, Request $request){
        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('AJUserBundle:Task')->find($id);
         
        if(!$task){
            $messageException = $this->get('translator')->trans('Task not found.');
            throw $this->createNotFoundException($messageException);
        }
        
        $form = $this->createEditForm($task);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()){
            $task->setStatus(0);
            $em->flush();
            
            $successMensaje = $this->get('translator')->trans('The task has been modified.');
            $this->addFlash('mensaje',$successMensaje);
            return $this->redirectToRoute("aj_task_edit",array('id'=>$task->getId()));
        }
        return $this->render("AJUserBundle:Task:edit.html.twig", array('task'=>$task,'form'=>$form->createView()));
    }
    
    public function deleteAction($id, Request $request){
        
        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('AJUserBundle:Task')->find($id);
        
        if(!$task){
            $messageException = $this->get('translator')->trans('Task not found.');
            throw $this->createNotFoundException($messageException);
        }
         
        
        $form = $this->createCustomForm($task->getId(),'DELETE','aj_task_delete');
        $form->handleRequest($request);
        
      
        
        if($form->isSubmitted() && $form->isValid()){
            $em->remove($task);
            $em->flush();
            
            $successMessage = $this->get('translator')->trans('The task has been deleted.');
            $this->addFlash('mensaje', $successMessage); 
            
            return $this->redirectToRoute('aj_task_index');
        }
        
        //return $this->render("AJUserBundle:Task:edit.html.twig", array('user'=>$user,'form'=>$form->createView()));
        
    }
    
    
    private function createCustomForm($id, $method, $route){
        return $this->createFormBuilder()
            ->setAction($this->generateUrl($route,array('id'=>$id)))
            ->setMethod($method)
            ->getForm();
    }
    
}
