<?php
/**
 * Chronopost
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Chronopost
 * @package   Chronopost_Chronorelais
 * @copyright Copyright (c) 2021 Chronopost
 */
declare(strict_types=1);

namespace Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression;

use Chronopost\Chronorelais\Helper\Data;
use Chronopost\Chronorelais\Helper\Data as HelperData;
use Chronopost\Chronorelais\Lib\PDFMerger\PDFMerger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class AbstractImpression
 *
 * @package Chronopost\Chronorelais\Controller\Adminhtml\Sales\Impression
 */
abstract class AbstractImpression extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var PDFMerger
     */
    private $PDFMerger;

    /**
     * AbstractImpression constructor.
     *
     * @param Context          $context
     * @param DirectoryList    $directoryList
     * @param PageFactory      $resultPageFactory
     * @param HelperData       $helperData
     * @param PDFMerger        $PDFMerger
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Context $context,
        DirectoryList $directoryList,
        PageFactory $resultPageFactory,
        HelperData $helperData,
        PDFMerger $PDFMerger,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->directoryList = $directoryList;
        $this->resultPageFactory = $resultPageFactory;
        $this->helperData = $helperData;
        $this->PDFMerger = $PDFMerger;
        $this->messageManager = $messageManager;
    }

    /**
     * Process download mass
     *
     * @param array $pdfContents
     *
     * @return Redirect
     * @throws FileSystemException
     */
    public function _processDownloadMass($pdfContents)
    {
        $paths = [];
        $this->createMediaChronopostFolder();

        $type = 'L';
        $mode = $this->helperData->getConfig('chronorelais/skybillparam/mode');
        if ($mode === 'THE') {
            $type = 'P';
        }

        $indiceFile = 0;
        foreach ($pdfContents as $pdf_content) {
            $fileName = 'tmp-etiquette-' . date('H-i-s-' . $indiceFile);
            $path = $this->directoryList->getPath('media') . '/chronopost/' . $fileName . '.pdf';
            file_put_contents($path, $pdf_content);
            $this->PDFMerger->addPDF($path, 'all', $type);
            $paths[] = $path;
            $indiceFile++;
        }

        // Creation of a single pdf
        $pdfMergeFileName = 'merged-' . date('YmdHis') . '.pdf';
        $pathMerge = $this->directoryList->getPath('media') . '/chronopost/' . $pdfMergeFileName;

        try {
            $this->PDFMerger->merge('file', $pathMerge);
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());

            return $this->redirectImpressionGrid();
        }

        // Deleting temp pdf
        foreach ($paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $this->prepareDownloadResponse($pdfMergeFileName, file_get_contents($pathMerge));
        unlink($pathMerge);
    }

    /**
     * Create media folder
     *
     * @throws FileSystemException
     */
    protected function createMediaChronopostFolder()
    {
        $path = $this->directoryList->getPath('media') . '/chronopost';
        if (!is_dir($path) && !mkdir($path, 0777)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }
    }

    /**
     * Redirect impression grid
     *
     * @return Redirect
     */
    public function redirectImpressionGrid(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        return $resultRedirect->setPath('chronorelais/sales/impression');
    }

    /**
     * Prepare download response
     *
     * @param string      $fileName
     * @param string      $content
     * @param string      $contentType
     * @param null|string $contentLength
     *
     * @return $this
     */
    public function prepareDownloadResponse(
        $fileName,
        $content,
        $contentType = 'application/octet-stream',
        $contentLength = null
    ) {
        $isFile = false;
        $file = null;
        if (is_array($content)) {
            if (!isset($content['type']) || !isset($content['value'])) {
                return $this;
            }

            if ($content['type'] === 'filename') {
                $isFile = true;
                $file = $content['value'];
                $contentLength = filesize($file);
            }
        }

        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', is_null($contentLength) ? strlen($content) : $contentLength, true)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"', true)
            ->setHeader('Last-Modified', date('r'), true);

        if (!is_null($content)) {
            if ($isFile) {
                $content = file_get_contents($file);
            }

            $this->getResponse()->setBody($content);
        }

        return $this;
    }

    /**
     * Check is the current user is allowed to access this section
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Chronopost_Chronorelais::sales');
    }
}
