<?php
/**
 * DISCLAIMER :
 *
 * Do not edit or add to this file if you wish to upgrade Smile Elastic Suite to newer
 * versions in the future.
 *
 * @category  Smile_ElasticSuite
 * @package   Smile\ElasticSuiteCore
 * @author    Aurelien FOUCRET <aurelien.foucret@smile.fr>
 * @copyright 2016 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\ElasticSuiteCore\Index\Bulk;

use Smile\ElasticSuiteCore\Api\Index\Bulk\BulkResponseInterface;

/**
 * Default implementation for ES bulk (Smile\ElasticSuiteCore\Api\Index\BulkInterface).
 *
 * @category Smile_ElasticSuite
 * @package  Smile\ElasticSuiteCore
 * @author   Aurelien FOUCRET <aurelien.foucret@smile.fr>
 */
class BulkResponse implements BulkResponseInterface
{
    /**
     * @var array
     */
    private $rawResponse;

    /**
     * Constructor.
     *
     * @param array $rawResponse ES raw response.
     */
    public function __construct(array $rawResponse)
    {
        $this->rawResponse = $rawResponse;
    }

    /**
     * {@inheritDoc}
     */
    public function hasErrors()
    {
        return $this->rawResponse['errors'];
    }

    /**
     * {@inheritDoc}
     */
    public function getErrorItems()
    {
        $errors = array_filter($this->rawResponse['items'], function ($item) {
            return isset(current($item)['error']);
        });

        return $errors;
    }

    /**
     * {@inheritDoc}
     */
    public function getSuccessItems()
    {
        $successes = array_filter($this->rawResponse['items'], function ($item) {
            return !isset(current($item)['error']);
        });

        return $successes;
    }

    /**
     * {@inheritDoc}
     */
    public function countErrors()
    {
        return count($this->getErrorItems());
    }

    /**
     * {@inheritDoc}
     */
    public function countSuccess()
    {
        return count($this->getSuccessItems());
    }

    /**
     * {@inheritDoc}
     */
    public function aggregateErrorsByReason()
    {
        $errorByReason = [];

        foreach ($this->getErrorItems() as $item) {
            $operationType = current(array_keys($item));
            $itemData      = $item[$operationType];
            $index         = $itemData['_index'];
            $documentType  = $itemData['_type'];
            $errorData     = $itemData['error'];
            $errorKey      = $operationType . $errorData['type'] . $errorData['reason'] . $index . $documentType;

            if (!isset($errorByReason[$errorKey])) {
                $errorByReason[$errorKey] = [
                    'index'         => $itemData['_index'],
                    'document_type' => $itemData['_type'],
                    'operation'     => $operationType,
                    'error'         => ['type' => $errorData['type'], 'reason' => $errorData['reason']],
                    'count'         => 0,
                ];
                $errorByReason[$errorKey]['count'] = 0;
            }

            $errorByReason[$errorKey]['count']++;
            $errorByReason[$errorKey]['document_ids'][] = $itemData['_id'];
        }

        return array_values($errorByReason);
    }
}
