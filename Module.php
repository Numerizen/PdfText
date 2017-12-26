<?php 
  
namespace PdfText;
  
use Omeka\Module\AbstractModule;
use Omeka\Module\Manager as ModuleManager;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractController;
use Zend\Form\Fieldset;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Form\Element\Textarea;
use Zend\Debug\Debug;
use Omeka\Mvc\Controller\Plugin\Logger;
use PdfText\Form\Config as ConfigForm;
use Zend\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);
        $application = $event->getApplication();
        $services    = $application->getServiceManager();        
//         Debug::dump($services);        
    }
      
    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $logger = $serviceLocator->get('Omeka\Logger');
//        Debug::dump($logger);
        // Don't install if the pdftotext command doesn't exist.
        // See: http://stackoverflow.com/questions/592620/check-if-a-program-exists-from-a-bash-script
        if ((int) shell_exec('hash pdftotext 2>&- || echo 1')) {
          $logger->info("pdftotext pas trouvé");
        }
/*
            throw new Omeka_Plugin_Installer_Exception(__('The pdftotext command-line utility ' 
            . 'is not installed. pdftotext must be installed to install this plugin.'));
*/
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        // drop database schema
    }
      
    /** Module body **/

    /**
     * Get this module's configuration form.
     *
     * @param ViewModel $view
     * @return string
     */
    public function getConfigForm(\Zend\View\Renderer\PhpRenderer $renderer)
    {
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');

        $textarea = new Textarea('pdftotext');
        $textarea->setAttribute('rows', 15);
        $textarea->setLabel('Options Pdf Text');
        $textarea->setValue($settings->get('pdttext_toto'));
        $textarea->setAttribute('id', 'pdttextTOTO_value');

        return $renderer->render('pdftext/config-form', ['textarea' => $textarea]);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $pdftext_toto = $controller->getRequest()->getPost('pdftotext', '');

        $site_selected = $controller->getRequest()->getPost('site', '');
        if ($site_selected) {
            $this->setSiteOption($site_selected, 'pdttext_toto', $pdftext_toto);
        } else {
            $this->setOption('pdttext_toto', $pdftext_toto);
        }

        return true;
    }

    public function setOption($name, $value) {
        $serviceLocator = $this->getServiceLocator();
        return $serviceLocator->get('Omeka\Settings')->set($name,$value);
    }
    
    protected function setSiteOption($site_id, $name, $value) {
        $serviceLocator = $this->getServiceLocator();
        $siteSettings = $serviceLocator->get('Omeka\Settings\Site');
        $entityManager = $serviceLocator->get('Omeka\EntityManager');

        if ($site = $entityManager->find('Omeka\Entity\Site', $site_id)) {
            $siteSettings->setTargetId($site_id);
            return $siteSettings->set($name, $value);
        }

        return false;
    }
    
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
        
    /**
     * Attach listeners to events.
     *
     * @param SharedEventManagerInterface $sharedEventManager
     */

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
      

     $sharedEventManager->attach(
          'Omeka\Api\Adapter\MediaAdapter',
          'api.create.post',
          function (\Zend\EventManager\Event $event) {
              $response = $event->getParam('response');
              $file = $response->getContent();
              $fileName = $file->getSource();
              $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
              if (in_array($fileExt, array('pdf', 'PDF'))) {           
                // Path du fichier
                $filePath = OMEKA_PATH . '/files/original/' . $file->getStorageId() . '.' . $fileExt;
//                 $user = $file->getOwner(); // Propriétaire
                $item = $file->getItem(); // Item de rattachement
                $itemId = $item->getId();
                $fileId = $file->getId();

                $text = $this->pdfToText($filePath); 
                $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');                

                // Update to item's bibo:content property                
                $data = [
                	"bibo:content" => [[
                		"type"=> "literal",
                		"property_id"=> 91,
                		"@value"=> $text
                	]],
                ];
                $response = $apiManager->update('items', $itemId, $data, [], ['isPartial' => true, 'collectionAction' => 'append']);    
                         
              }
          }
      );

    }

    public function pdfToText($path)
    {
        $path = escapeshellarg($path);
        // TODO : Ne fonctionne pas sans le chemin complet
        $text = shell_exec("/usr/local/bin/pdftotext -enc UTF-8 $path -  2>&1");
        return $text;
    }
      


}