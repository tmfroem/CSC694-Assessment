<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Plans\Controller;

use Plans\Form\CollectionUpload;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Zend\View\Model\JsonModel;
use Zend\Session\Container;



class PlansController extends AbstractActionController
{

   protected $tableResults;
   protected $tableResultsPlans;
   
       // get these values from the session namespace
//    protected $userRole = null;
    protected $userRole = 3;
    protected $userID = 19;   //9 = ACC, 19 = CSC

    /**
     * @var Container
     */
    protected $sessionContainer;

    
        public function __construct()
    {
        $this->sessionContainer = new Container('fileUpload');
    }

// Sample dump logic used for debugging, use as needed   
//      foreach ($this->getDatabaseData()->getAllYears() as $data) :
//          var_dump($data);
//      endforeach;
//      exit();

//      var_dump($request);
//      exit();

   /**
    * Used to access the plans specific SQL statements
    */
   public function getDatabaseData()
   {
       if (!$this->tableResultsPlans) {
           $this->tableResultsPlans = $this->getServiceLocator()
                                       ->get('Plans\Model\DatabaseSql');
       }
       return $this->tableResultsPlans;
   }

   /**
    * Used to access the generic SQL statements
    */  
   public function getGenericQueries()
   {
      if (!$this->tableResults) {
           $this->tableResults = $this->getServiceLocator()
                                      ->get('Application\Model\AllTables');
        }
        return $this->tableResults;
   }
      
   /**
    * This is the controller that gets called upon loading the plans page
    *
    * Post Request
    *
    * Get Request
    *  1)
    * 
    */
   public function indexAction()
   {
      
      // get and check the request type
      $request = $this->getRequest();
      if ($request->isPost()) {
         // process post request
 
         // get the data from the form        
         $action = $request->getPost('action-menu');
         $unit = $request->getPost('unit-menu');
         $programs = $request->getPost('prog-menu');
         $year = $request->getPost('year-menu');
            
         // create session variable used to populate the titles on the next page
	 $planSession = new Container('planSession');
	 $planSession->action = $action;
         $planSession->unit = $unit;
         $planSession->programs = $programs;
         $planSession->year = $year;
            
         // determine where to go next
         if ($action == "View" || $action == "Modify") {
            return $this->redirect()->toRoute('plans', array('action'=>'listplans'));                
         }
         else {
            return $this->redirect()->toRoute('plans', array('action'=>'addplan'));
         }
      }
      else {
         // process get request
            
         // create session variable used to populate the titles on the next page
	 $planSession = new Container('planSession');
         
         // if general user - only view
         // get all units, since only view option is displayed
         if ($this->userRole == null){
            $results = $this->getGenericQueries()->getUnits();
         
            // iterate over database results forming a php array
            foreach ($results as $result){
               $unitarray[] = $result;
            }
                        
            $useractions = array('View');
            $planSession->useractions = $useractions;
                   
            return new ViewModel(array(
               'useractions' => array('View'),
               'units' => $unitarray,
            ));
         }
         else{
            
            $useractions = array('View', 'Add', 'Modify');
            $planSession->useractions = $useractions;
            
            // user in table with role - show actions
            // wait to populate units until action chosen
            return new ViewModel(array(
               'useractions' => array('View', 'Add', 'Modify'),
            ));
         }
      }
   }
   
   
       // Creates list of available units (departments/programs)
    // based on user role and privileges.
    public function getUnitsAction()
    {
        // get action from id in url
        $actionChosen = $this->params()->fromRoute('id', 0);
     
        // get units for that action
        if ($actionChosen == 'View'){
            $results = $this->getGenericQueries()->getUnits();
        }
        else{
            $results = $this->getGenericQueries()->getUnitsByPrivId($this->userID);
        }
      
        // iterate through results forming a php array
        foreach ($results as $result){
            $unitData[] = $result;
        }
      
        // encode results as json object
        $jsonData = new JsonModel($unitData);
        return $jsonData;
    }
    
    public function getProgramsAction()
    {
        // get unit from id in url
        $unitChosen = $this->params()->fromRoute('id', 0);
        // get programs for that unit
        $results = $this->getGenericQueries()->getProgramsByUnitId($unitChosen);
      
        // iterate through results forming a php array
        foreach ($results as $result){
            $programData[] = $result;
        }
      
        // encode results as json object
        $jsonData = new JsonModel($programData);
        return $jsonData;
    }
    
    public function getYearsAction()
    {
        // get unit from id in url
        $yearsChosen = $this->params()->fromRoute('id', 0);
        // get programs for that unit
        $results = $this->getGenericQueries()->getYears();
      
        // iterate through results forming a php array
        foreach ($results as $result){
            $yearsData[] = $result;
        }
      
        // encode results as json object
        $jsonData = new JsonModel($yearsData);
        return $jsonData;
    }
    
    
    
    
    
    
   
   public function listPlansAction()
   {
      $request = $this->getRequest();
            
        if ($request->isPost()) {

         }
         else {
            
            // get session data
       		$planSession = new Container('planSession');
		$action = $planSession->action; 
                $unit = $planSession->unit;
                $programs = $planSession->programs;
                $year = $planSession->year;
                $useractions = $planSession->useractions;
                
            
            // Initial Page Load, get request
            // get units
            $results = $this->getGenericQueries()->getUnits();
            // iterate over database results forming a php array
            foreach ($results as $result){
               $unitarray[] = $result;
            }
           
            // get years
            $results = $this->getGenericQueries()->getYears();
            // iterate over database results forming a php array
            foreach ($results as $result){
               $yeararray[] = $result;
            }         
                  
            // pass array to view
            return new ViewModel(array(
               'units' => $unitarray,
               'years' => $yeararray,
                              
               'action' => $action,
               'unit' => $unit,
               'programs' => $programs,
               'year' => $year,
               'useractions' => $useractions,
            
               // get outcome and plans data
               'outcomes' => $this->getGenericQueries()->getOutcomes($unit, $programs, $year),
               'plans' => $this->getGenericQueries()->getPlans($unit, $programs, $year),   
            ));
         }
   }


   public function viewOnlyPlanAction()
   {
      // pull data from the route url
      $planId = (int) $this->params()->fromRoute('id', 0);                      
            
      $request = $this->getRequest();
      if ($request->isPost()) {
         /* perfomr post request action here */   
      }
      else {
                           // get session data
       		$planSession = new Container('planSession');
		$action = $planSession->action; 
                $unit = $planSession->unit;
                $programs = $planSession->programs;
                $year = $planSession->year;
                $useractions = $planSession->useractions;
         
                     // Initial Page Load, get request
            // get units
            $results = $this->getGenericQueries()->getUnits();
            // iterate over database results forming a php array
            foreach ($results as $result){
               $unitarray[] = $result;
            }
           
            // get years
            $results = $this->getGenericQueries()->getYears();
            // iterate over database results forming a php array
            foreach ($results as $result){
               $yeararray[] = $result;
            }
            
         // Initial Page Load, get request
         return new ViewModel(array(
            'units' => $unitarray,
            'years' => $yeararray,
               
            'planId' => $planId,
            'action' => $action,
            'unit' => $unit,
            'programs' => $programs,
            'year' => $year,
            'useractions' => $useractions,
            
            'outcomes' => $this->getGenericQueries()->getOutcomesByPlanId($planId),
            'plan' => $this->getGenericQueries()->getPlanByPlanId($planId),
         ));
      }
   }
   
   
   public function modifyPlanAction()
   {
      // pull data from the route url
      $planId = (int) $this->params()->fromRoute('id', 0);
      
      $request = $this->getRequest();      
      
      if ($request->isPost()) {
          
         $button = $request->getPost('formSubmit');             
      
         if ($button == "formSavePlan" || $button == "formSaveDraft") {

               // set the draft flag
               $draftFlag = 0;
               if ($button == "formSaveDraft") {
                  $draftFlag = 1;
               }
         
            $planId = trim($request->getPost('planId'));
            $assessmentMethod = trim($request->getPost('textAssessmentMethod'));
            $population = trim($request->getPost('textPopulation'));
            $sampleSize = trim($request->getPost('textSamplesize'));
            $assessmentDate = trim($request->getPost('textAssessmentDate'));
            $cost = trim($request->getPost('textCost'));
            $analysisType = trim($request->getPost('textAnalysisType'));
            $administrator = trim($request->getPost('textAdministrator'));
            $analysisMethod = trim($request->getPost('textAnalysisMethod'));
            $scope = trim($request->getPost('textScope'));
            $feedback = trim($request->getPost('textFeedback'));
            $feedbackFlag = trim($request->getPost('textFeedbackFlag'));
            $planStatus = trim($request->getPost('textPlanStatus'));

            $this->getDatabaseData()->updatePlan($planId,0,"",$assessmentMethod,$population,$sampleSize,$assessmentDate,$cost,$analysisType,$administrator,$analysisMethod,$scope,$feedback,$feedbackFlag,$planStatus,$draftFlag,$this->userID);
            return $this->redirect()->toRoute('plans');
         }
         else {
            return $this->redirect()->toRoute('plans');
         }
      }
      else {
               // get session data
       		$planSession = new Container('planSession');
		$action = $planSession->action; 
                $unit = $planSession->unit;
                $programs = $planSession->programs;
                $year = $planSession->year;               
                $useractions = $planSession->useractions;
         
                     // Initial Page Load, get request
            // get units
            $results = $this->getGenericQueries()->getUnits();
            // iterate over database results forming a php array
            foreach ($results as $result){
               $unitarray[] = $result;
            }
           
            // get years
            $results = $this->getGenericQueries()->getYears();
            // iterate over database results forming a php array
            foreach ($results as $result){
               $yeararray[] = $result;
            }         
            
         // Initial Page Load, get request
         return new ViewModel(array(
            'units' => $unitarray,
            'years' => $yeararray,
               
            'planId' => $planId,
            'action' => $action,
            'unit' => $unit,
            'programs' => $programs,
            'year' => $year,
            'useractions' => $useractions,
            
            'outcomes' => $this->getGenericQueries()->getOutcomesByPlanId($planId),
            'plan' => $this->getGenericQueries()->getPlanByPlanId($planId),
         ));
      }
   }
   
   public function addplanAction()
   {
       
      $form = new CollectionUpload('file-form');
       
      $request = $this->getRequest();
      
      if ($request->isPost()) {
         
         // get session data
       	 $planSession = new Container('planSession');	 
         $year = $planSession->year;
         $useractions = $planSession->useractions;
         
         // get button form data       
         $button = $request->getPost('formSubmit');
         $outcomeCount = $request->getPost('outcomeCount');

         // load the checked outcome box values into an array
         for ($x = 1; $x <= $outcomeCount; $x++)
         {
            $checkboxName = "checkboxOutcomes" . $x;
            $checkboxValue = $request->getPost($checkboxName);
            
            if ($checkboxValue != null) {
               $outcomeIds[] = $checkboxValue;
            }
         }
                  
         
                   
                   
                   
//        if ($this->getRequest()->isPost()) {
//            // Postback
//            $data = array_merge_recursive(
//                $this->getRequest()->getPost()->toArray(),
//                $this->getRequest()->getFiles()->toArray()
//            );
            
//            $form->setData($data);            
            
//            if ($form->isValid()) {
//                // ...Save the form...
//                return $this->redirectToSuccessPage($form->getData());
//            }
//        }
         
         $metaFlag = $request->getPost('metaFlag');        
         if ($metaFlag == "yes") {
            return $this->redirect()->toRoute('plans', array('action'=>'addplanmeta'));
         }
         else {
         
            if ($button == "formSavePlan" || $button == "formSaveDraft") {
   
               $metaFlag = $request->getPost('metaFlag');
   
                  // set the draft flag
                  $draftFlag = 0;
                  if ($button == "formSaveDraft") {
                     $draftFlag = 1;
                  }
               
                  // get form data    
                  $assessmentMethod = trim($request->getPost('textAssessmentMethod'));
                  $population = trim($request->getPost('textPopulation'));
                  $sampleSize = trim($request->getPost('textSamplesize'));
                  $assessmentDate = trim($request->getPost('textAssessmentDate'));
                  $cost = trim($request->getPost('textCost'));
                  $analysisType = trim($request->getPost('textAnalysisType'));
                  $administrator = trim($request->getPost('textAdministrator'));
                  $analysisMethod = trim($request->getPost('textAnalysisMethod'));
                  $scope = trim($request->getPost('textScope'));
                  $feedback = trim($request->getPost('textFeedback'));
                  $feedbackFlag = trim($request->getPost('textFeedbackFlag'));
                  $planStatus = trim($request->getPost('textPlanStatus'));
   
                                
                  // insert into plan table and obtain the primary key of the insert
                  $planId = $this->getDatabaseData()->insertPlan(0, "", $year, $assessmentMethod,$population,$sampleSize,$assessmentDate,$cost,$analysisType,$administrator,$analysisMethod,$scope,$feedback,$feedbackFlag,$planStatus,$draftFlag,$this->userID);                  
   
                  // insert one entry for each outcome id selected              
                  foreach ($outcomeIds as $outcomeId) :
                     // insert into the outcome table
                     $this->getDatabaseData()->insertPlanOutcome($outcomeId, $planId);
                  endforeach;
                                          
                  return $this->redirect()->toRoute('plans');
            }
         }
      }
      else {
                  // get session data
                   $planSession = new Container('planSession');
                   $action = $planSession->action; 
                   $unit = $planSession->unit;
                   $programs = $planSession->programs;
                   $year = $planSession->year;
                   $useractions = $planSession->useractions;
                   
                        // Initial Page Load, get request
               // get units
               $results = $this->getGenericQueries()->getUnits();
               // iterate over database results forming a php array
               foreach ($results as $result){
                  $unitarray[] = $result;
               }
              
               // get years
               $results = $this->getGenericQueries()->getYears();
               // iterate over database results forming a php array
               foreach ($results as $result){
                  $yeararray[] = $result;
               }
               
               foreach ($programs as $data) :
                  $dbData = $this->getGenericQueries()->getUniqueOutcomes($unit, $data, $year);
                  $outcomes[] = $dbData;
               endforeach;
               
               // Initial Page Load, get request
               return new ViewModel(array(
                  'form' => $form ,
                  'units' => $unitarray,
                  'years' => $yeararray,
                     
                  'action' => $action,
                  'unit' => $unit,
                  'programs' => $programs,
                  'year' => $year,
                  'useractions' => $useractions,
                  'outcomes' => $outcomes,
               ));
         }
   }
   
   
   protected function redirectToSuccessPage($formData = null)
    {
        $this->sessionContainer->formData = $formData;
        $response = $this->redirect()->toRoute('plans');
        $response->setStatusCode(303);
        return $response;
    }
    
    
   public function addplanmetaAction()
   {
               
      $request = $this->getRequest();
      
      if ($request->isPost()) {
         
         // get session data
       	 $planSession = new Container('planSession');	 
         $year = $planSession->year;
         
         // get button form data       
         $button = $request->getPost('formSubmitMeta');
                                  
         if ($button == "formSavePlan" || $button == "formSaveDraft") {

            $outcomeId = $request->getPost('outcomeId');
          
            // set the draft flag
            $draftFlag = "N";
            if ($button == "formSaveDraft") {
               $draftFlag = "Y";
            }
               // get form data    
               $metaDescription = $request->getPost('textMetaDescription');
                                 
               // insert into plan table and obtain the primary key of the insert
               $planId = $this->getDatabaseData()->insertPlan(1, $metaDescription, $year, "","","","","","","","","","","","",$draftFlag);                  
              
               // insert into the outcome table
               $this->getDatabaseData()->insertPlanOutcome($outcomeId, $planId['maxId']);
                     
               return $this->redirect()->toRoute('plans');
         }
      }
      else {
               // get session data
       		$planSession = new Container('planSession');
		$action = $planSession->action; 
                $unit = $planSession->unit;
                $programs = $planSession->programs;
                $year = $planSession->year;
                $outcomeId = $planSession->outcomeId;
              $useractions = $planSession->useractions;
         
                     // Initial Page Load, get request
            // get units
            $results = $this->getGenericQueries()->getUnits();
            // iterate over database results forming a php array
            foreach ($results as $result){
               $unitarray[] = $result;
            }
           
            // get years
            $results = $this->getGenericQueries()->getYears();
            // iterate over database results forming a php array
            foreach ($results as $result){
               $yeararray[] = $result;
            }
            
         // Initial Page Load, get request
         return new ViewModel(array(
            'units' => $unitarray,
            'years' => $yeararray,
               
            'action' => $action,
            'unit' => $unit,
            'programs' => $programs,
            'year' => $year,
            'useractions' => $useractions,
            'outcomeId' => $outcomeId,
         ));
      }
   }
}
