<?php declare(strict_types=1);


namespace Crehler\BaseLinkerShopsApi\Storefront\Controller;

use Crehler\BaseLinkerShopsApi\Component\BaseLinker\StoreApiStruct\Order\OrderAddRequest;
use Crehler\BaseLinkerShopsApi\Services\ConfigService;
use Crehler\BaseLinkerShopsApi\Services\Readers\CategoryReader;
use Crehler\BaseLinkerShopsApi\Services\Readers\OrderReader;
use Crehler\BaseLinkerShopsApi\Services\Readers\ProductReader;
use Monolog\Logger;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class BaseLinkerController extends StorefrontController
{
    public const BASE_LINKER_PASS_KEY = 'bl_pass';
    public const ACTION_KEY = 'action';

    protected ConfigService $configService;
    protected ProductReader $productReader;
    protected CategoryReader $categoryReader;
    protected OrderReader $orderReader;

    public function __construct(
        ConfigService $configService,
        ProductReader $productReader,
        CategoryReader $categoryReader,
        OrderReader $orderReader
    ) {
        $this->configService = $configService;
        $this->productReader = $productReader;
        $this->categoryReader = $categoryReader;
        $this->orderReader = $orderReader;
    }

    /**
     * @Route("/baselinker", name="frontend.crehler.baselinker", methods={"GET", "POST"}, defaults={"XmlHttpRequest": true, "csrf_protected"=false})
     */
    public function index(Request $request, RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        if ($request->getMethod() === Request::METHOD_GET || $request->get(self::BASE_LINKER_PASS_KEY) === null) {
            return $this->errorResponse('no_password', 'Communication attempt without password!');
        }

        $config = $this->configService->getShopsConfig($salesChannelContext->getSalesChannelId());

        if ($request->get(self::BASE_LINKER_PASS_KEY) !== $config->getShopsApiPassword()) {
            return $this->errorResponse('incorrect_password', 'Incorrect password!');
        }

        $action = $request->get(self::ACTION_KEY);

        if (!$this->supportedMethod($action)) {
            return $this->errorResponse('unsupported_action', 'Unsupported action ' . $action);
        }

        $this->getLogger()->info('BL ' . $action . ' ' . ' request: ' . $request->getContent());

        try {
            return $this->$action($data, $salesChannelContext);
        } catch (\Error|\Throwable $e) {
            $this->getLogger()->error('BL ERROR: ' . $e->getMessage());
            return $this->json([]);
        }
    }

    protected function productsList(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $response = $this->productReader->getProductList(
            $data->get('category_id', 'all'),
            $data->get('filter_limit'),
            $data->get('filter_sort'),
            $data->get('filter_id'),
            $data->get('filter_ids_list'),
            $data->get('filter_ean'),
            $data->get('filter_sku'),
            $data->get('filter_name'),
            $data->get('filter_price_from'),
            $data->get('filter_price_to'),
            $data->get('filter_quantity_from'),
            $data->get('filter_quantity_to'),
            $data->get('filter_available'),
            $data->get('page'),
            $salesChannelContext
        );

        return $this->json($response);
    }

    protected function productsData(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $response = $this->productReader->getProductsData(
            $data->get('products_id'),
            $salesChannelContext
        );

        return $this->json($response);
    }

    protected function productsPrices(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $response = $this->productReader->getProductsPrices(
            $data->get('page'),
            $salesChannelContext
        );

        return $this->json($response);
    }

    protected function productsQuantity(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $response = $this->productReader->getProductsQuantity(
            $data->get('page'),
            $salesChannelContext
        );

        return $this->json($response);
    }

    protected function productsCategories(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $response = $this->categoryReader->getProductsCategories($salesChannelContext);
        return $this->json($response);
    }

    protected function ordersGet(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $config = $this->configService->getShopsConfig($salesChannelContext->getSalesChannelId());
        $response = $this->orderReader->ordersGet(
            $data->get('time_from'),
            $data->get('id_from'),
            $data->get('only_paid'),
            $config,
            $salesChannelContext
        );

        return $this->json($response);
    }

    protected function orderUpdate(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $response = $this->orderReader->orderUpdate(
            $data->get('orders_ids'),
            $data->get('update_type'),
            $data->get('update_value'),
            $salesChannelContext
        );

        return $this->json($response);
    }

    protected function orderAdd(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $this->getLogger()->info('START orderAdd');

        try {
            $orderAddRequest = (new OrderAddRequest())->assign($data->all());

            $response = $this->orderReader->orderAdd(
                $orderAddRequest,
                $salesChannelContext
            );
        } catch (\Error|\Throwable $e) {
            $this->getLogger()->error('orderAdd error: ' . $e->getMessage());
            $response = ['order_id' => null];
        }

        return $this->json($response);
    }


    protected function deliveryMethodsList(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $response = $this->orderReader->deliveryMethodsList(
            $salesChannelContext
        );

        return $this->json($response);
    }

    protected function paymentMethodsList(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $response = $this->orderReader->paymentMethodsList(
            $salesChannelContext
        );

        return $this->json($response);
    }

    protected function statusesList(RequestDataBag $data, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $response = $this->orderReader->statusesList(
            $salesChannelContext
        );

        return $this->json($response);
    }


    protected function supportedMethods(): JsonResponse
    {
        return $this->json([
            'ProductsCategories',
            'ProductsList',
            'ProductsData',
            'ProductsPrices',
            'ProductsQuantity',
            'OrdersGet',
            'OrderUpdate',
            'OrderAdd',
            'StatusesList',
            'DeliveryMethodsList',
            'PaymentMethodsList',
        ]);
    }

    protected function fileVersion(): JsonResponse
    {
        return $this->json([
            'platform' => 'Shopware',
            'version' => '1.0.0',
            'standard' => '4'
        ]);
    }

    protected function errorResponse(string $errorCode, string $errorMessage): JsonResponse
    {
        return $this->json([
            'error' => true,
            'error_code' => $errorCode,
            'error_text' => $errorMessage
        ]);
    }

    protected function supportedMethod(string $methodName): bool
    {
        //lover first letter and check exist method
        return method_exists($this, lcfirst($methodName));
    }

    protected function getLogger(): Logger
    {
        return $this->get('crehler_base_linker.logger');
    }
}
