<?php
/**
 * Simulates all Shipping methods on the product page.
 * Copyright (C) 2020  Copyright (c) 2020 Fineweb (https://www.fineweb.com.br)
 *
 * This file is part of Fineweb/SimulateProductShipping.
 *
 * Fineweb/SimulateProductShipping is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fineweb\SimulateProductShipping\Controller\Simulate;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Pricing\Helper\Data as DataAlias;
use Magento\Quote\Model\QuoteFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Index
 *
 * Fineweb\SimulateProductShipping\Controller\Simulate
 */
class Index extends Action
{
    protected $jsonHelper;
    protected $request;
    protected $product_repository;
    protected $quote;
    protected $pricingHelper;
    protected $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Data $jsonHelper
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $product_repository
     * @param QuoteFactory $quote
     * @param DataAlias $pricingHelper
     */
    public function __construct(
        Context $context,
        Data $jsonHelper,
        LoggerInterface $logger,
        RequestInterface $request,
        ProductRepositoryInterface $product_repository,
        QuoteFactory $quote,
        DataAlias $pricingHelper
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->logger = $logger;
        $this->request = $request;
        $this->product_repository = $product_repository;
        $this->quote = $quote;
        $this->pricingHelper = $pricingHelper;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $_params = $this->request->getPostValue();
        $qty       = $_params['qty'];
        $response = [];

        try {
            $_product = $this->product_repository->getById($_params['product']);
            $quote = $this->quote->create();
            $options = new \Magento\Framework\DataObject();
            $options->setProduct($_product->getId());
            $options->setQty($qty);

            if (!strcmp($_product->getTypeId(), 'configurable')) {
                $superAttribute = $this->getRequest()->getParam('super_attribute');

                $options->setSuperAttribute($superAttribute);
            } elseif (!strcmp($_product->getTypeId(), 'bundle')) {
                $bundleOption    = $this->getRequest()->getParam('bundle_option');
                $bundleOptionQty = $this->getRequest()->getParam('bundle_option_qty');

                $options->setBundleOption($bundleOption);
                $options->setBundleOptionQty($bundleOptionQty);
            }

            $quote->addProduct($_product, $options);
            $quote->getShippingAddress()->setCountryId('BR');
            $quote->getShippingAddress()->setPostcode($_params['simulate']['postcode']);
            $quote->getShippingAddress()->setCollectShippingRates(true);

            $quote->collectTotals();
            $quote->getShippingAddress()->getGroupedAllShippingRates();
            $quote->getShippingAddress()->collectShippingRates();
            $rates = $quote->getShippingAddress()->getShippingRatesCollection();

            if (count($rates)>0) {
                $shipping_methods = [];

                foreach ($rates as $rate) {
                    $_message = !$rate->getErrorMessage() ? "" : $rate->getErrorMessage();
                    $shipping_methods[$rate->getCarrierTitle()][] = [
                        'title' => $rate->getMethodTitle(),
                        'price' => $this->pricingHelper->currency($rate->getPrice()),
                        'message' => $_message,
                    ];
                }

                $response = $shipping_methods;
            } else {
                $response['error']['message'] = __('There is no shipping method available at this time.');
            }
        } catch (LocalizedException $e) {
            $response['error']['message'] = $e->getMessage();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $response['error']['message'] = $e->getMessage();
        }

        return $this->jsonResponse($response);
    }

    /**
     * Create json response
     *
     * @param string $response
     * @return ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }
}
