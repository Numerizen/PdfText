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
use Zend\Form\Element\Text;
use Zend\Debug\Debug;
use Omeka\Mvc\Controller\Plugin\Logger;
//use Zend\Log\Logger;
use Zend\Log\Writer;
use PdfText\Form\Config as ConfigForm;
use Zend\View\Renderer\PhpRenderer;

class Module extends AbstractModule
{
    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $logger = $serviceLocator->get('Omeka\Logger');
        // Don't install if the pdftotext command doesn't exist.
        // See: http://stackoverflow.com/questions/592620/check-if-a-program-exists-from-a-bash-script
        if ((int) shell_exec('hash pdftotext 2>&- || echo 1')) {
          $logger->info("pdftotext not found");
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
/*
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');
*/

        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $settings = $services->get('Omeka\Settings');
        $formElementManager = $services->get('FormElementManager');
        $form = $formElementManager->get(ConfigForm::class);
        $form->init();
      
/*
        $textarea = new Textarea('pdftotext_css');
        $textarea->setAttribute('rows', 15);
        $textarea->setLabel('Options Pdf Text');
        $textarea->setValue($settings->get('pdftotext_css'));
        $textarea->setAttribute('id', 'pdftotext_css');
*/

        $formtext = new Text('pdftotext_path');
        $formtext->setLabel('Options Pdf Text');
        $formtext->setValue($settings->get('pdftotext_path'));
        $formtext->setAttribute('id', 'pdftotext_path');
        return $renderer->render('pdftext/config-form', ['formtext' => $formtext]);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $pdftotext_css = $controller->getRequest()->getPost('pdftotext_css', '');
        $pdftotext_path = $controller->getRequest()->getPost('pdftotext_path', '');

        $site_selected = $controller->getRequest()->getPost('site', '');
        if ($site_selected) {
            $this->setSiteOption($site_selected, 'pdftotext_css', $pdftotext_css);
        } else {
            $this->setOption('pdftotext_css', $pdftotext_css);
        }
        $this->setOption('pdftotext_path', $pdftotext_path);

//         return true;
    }

    public function setOption($name, $value) {
        $serviceLocator = $this->getServiceLocator();
        return $serviceLocator->get('Omeka\Settings')->set($name,$value);
    }

    public function getOption($name) {
        $serviceLocator = $this->getServiceLocator();
        return $serviceLocator->get('Omeka\Settings')->get($name);
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
         'api.hydrate.post',          
          function (\Zend\EventManager\Event $event) {
            $entity = $event->getParam('entity');
            if (! $entity->getId()) {
              $fileExt = $entity->getExtension();
              if (in_array($fileExt, array('pdf', 'PDF'))) {           
                // Path du fichier
                $filePath = OMEKA_PATH . '/files/original/' . $entity->getStorageId() . '.' . $fileExt;
                $item = $entity->getItem(); 
                $itemId = $item->getId();
                $text = $this->pdfToText($filePath); 
                $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
                // Update item's bibo:content property                
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
          }
      );

    }

    public function pdfToText($path)
    {
        $path = escapeshellarg($path);
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');
        $pdftotext_path = $settings->get('pdftotext_path');
        $command = $pdftotext_path . "pdftotext -enc UTF-8 $path -  2>&1";
        $text = shell_exec($pdftotext_path . "pdftotext -enc UTF-8 $path -  2>&1");
        return $text;
    }    
    
}