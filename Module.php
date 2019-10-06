<?php

namespace PdfText;

use Omeka\Module\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
//use Zend\Log\Logger;

class Module extends AbstractModule
{
    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $logger = $serviceLocator->get('Omeka\Logger');
        // Don't install if the pdftotext command doesn't exist.
        // See: http://stackoverflow.com/questions/592620/check-if-a-program-exists-from-a-bash-script
        if ((int) shell_exec('hash pdftotext 2>&- || echo 1')) {
            $logger->info("pdftotext not found");
            $t = $serviceLocator->get('MvcTranslator');
            throw new ModuleCannotInstallException($t->translate('The pdftotext command-line utility is not installed. pdftotext must be installed to install this plugin.')); // @translate
        }
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
                    if (in_array($fileExt, ['pdf', 'PDF'])) {
                        // Path du fichier
                        $basePath = $this->getServiceLocator()->get('Config')['file_store']['local']['base_path'] ?: (OMEKA_PATH . '/files');
                        $filePath = $basePath . '/original/' . $entity->getStorageId() . '.' . $fileExt;
                        $item = $entity->getItem();
                        $itemId = $item->getId();
                        $text = $this->pdfToText($filePath);
                        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
                        // Update item's bibo:content property
                        $data = [
                            "bibo:content" => [[
                                "type" => "literal",
                                "property_id" => 91,
                                "@value" => $text,
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
        $text = shell_exec($command);
        return $text;
    }
}
