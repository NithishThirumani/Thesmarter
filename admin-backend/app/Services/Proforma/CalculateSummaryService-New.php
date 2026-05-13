<?php

namespace App\Services\Proforma;

use App\Repositories\ProformaRepository;
use App\Repositories\ProductRepository;
use App\Repositories\DiscountRepository;
use App\Repositories\TaxRepository;

/**
 * Service responsible for calculating proforma invoice summaries
 * Optimized for memory efficiency and performance
 */
class CalculateSummaryService
{
    /** @var ProformaRepository */
    private $proformaRepository;

    /** @var ProductRepository */
    private $productRepository;

    /** @var DiscountRepository */
    private $discountRepository;

    /** @var TaxRepository */
    private $taxRepository;

    /** @var array Stores accumulated tax information for the entire order */
    private $taxes = [];

    /**
     * Constructor to inject required repositories
     *
     * @param ProformaRepository $proformaRepository
     * @param ProductRepository $productRepository
     * @param DiscountRepository $discountRepository
     * @param TaxRepository $taxRepository
     */
    public function __construct(
        ProformaRepository $proformaRepository,
        ProductRepository $productRepository,
        DiscountRepository $discountRepository,
        TaxRepository $taxRepository
    ) {
        $this->proformaRepository = $proformaRepository;
        $this->productRepository = $productRepository;
        $this->discountRepository = $discountRepository;
        $this->taxRepository = $taxRepository;
    }

    /**
     * Calculate the complete summary for a proforma invoice
     * Optimized for memory efficiency
     *
     * @param array $data Cart data containing items and details
     * @return array Calculated cart summary with totals and breakdown
     */
    public function calculateProformaSummary(array $data): array
    {
        // Reset taxes to prevent memory leaks from previous calculations
        $this->taxes = [];

        // Use array instead of object for better memory management
        $cart = [
            'total_amount' => 0.00,
            'net_amount' => 0.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'charge_amount' => 0.00,
            'items' => $data['items'] ?? [],
            'discount_id' => $data['discount_id'] ?? null,
            'taxes' => [],
            'meta' => [
                'total_unique_products' => 0,
                'total_quantity' => 0
            ]
        ];

        // Process each item in the cart
        foreach ($cart['items'] as &$item) {
            // Retrieve detailed product information
            $product = $this->productRepository->findProductDetails($item['product_id']);

            // Calculate base price, discounts, and taxes for the product
            $baseAmount = $this->calculateProductBaseAmount($product);
            $discount = $this->calculateProductDiscount($product, $baseAmount);

            // Calculate taxes if applicable
            $taxDetails = !empty($product->tax) 
                ? $this->calculateTaxes($product, $baseAmount, $discount, $item['quantity']) 
                : ['taxes' => [], 'total_tax_amount' => 0.00];

            // Update item details
            $this->updateItemDetails($item, $product, $baseAmount, $discount, $taxDetails);

            // Accumulate cart-level totals
            $this->accumulateTotals($cart, $item);
        }
        unset($item); // Break reference to avoid potential memory issues

        // Calculate order-level discount
        $orderDiscount = $this->calculateOrderDiscount($cart);
        
        // Adjust cart totals with order-level discount
        if (!empty($orderDiscount)) {
            $cart['discount_amount'] += $orderDiscount['amount'];
            $cart['total_amount'] -= $orderDiscount['amount'];
        }

        // Add taxes to cart and free up memory
        $cart['taxes'] = $this->taxes;
        $this->taxes = []; // Clear for next calculation

        return $cart;
    }

     /**
     * Retrieve comprehensive product details
     * 
     * @param object $product Base product object
     * @param int $productId Product identifier
     * @return array Comprehensive product details
     */
    private function getComprehensiveProductDetails(object $product): array
    {
        // Fetch additional product details
        $additionalDetails = $this->productRepository->getAdditionalProductDetails($productId);

        // Fetch product images
        $productImages = $this->productImageRepository->getProductImages($productId);

        // Fetch product flags and attributes
        $productFlags = $this->productRepository->getProductFlags($productId);

        // Fetch product categories
        $productCategories = $this->productRepository->getProductCategories($productId);

        return [
            'id' => $product->product_id,
            'name' => $product->name ?? 'Unnamed Product',
            'description' => $additionalDetails->description ?? '',
            'sku' => $additionalDetails->sku ?? '',
            'brand' => $additionalDetails->brand ?? '',
            'manufacturer' => $additionalDetails->manufacturer ?? '',
            'price' => [
                'base_amount' => $product->price->unit_price ?? 0.00,
                'currency' => $additionalDetails->currency ?? 'USD'
            ],
            'images' => array_map(function($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->url,
                    'thumbnail' => $image->thumbnail,
                    'alt_text' => $image->alt_text
                ];
            }, $productImages),
            'flags' => $productFlags,
            'categories' => $productCategories,
            'additional_attributes' => $additionalDetails->attributes ?? []
        ];
    }
    /**
     * Calculate base amount for a product, considering tax inclusivity
     * Minimizes object creation and memory usage
     *
     * @param object $product Product details
     * @return float Calculated base amount
     */
    private function calculateProductBaseAmount(object $product): float
    {
        $unitPrice = (float) ($product->price->unit_price ?? 0.00);

        // Handle tax-inclusive pricing
        if (!empty($product->taxInclusive) && 
            $product->taxInclusive->inclusive_flag === 'Y' && 
            $unitPrice !== 0.00
        ) {
            $taxComponentsWithValue = $this->taxRepository->findTaxDetails($product->tax->tax_id);
            $taxValueSum = $this->taxRepository->sumTaxValue($taxComponentsWithValue);
            
            // Calculate base price by removing tax component
            return round($unitPrice / (1 + ($taxValueSum / 100)), 2);
        }

        return $unitPrice;
    }

    /**
     * Calculate product-level discount
     *
     * @param object $product Product details
     * @param float $baseAmount Base amount for discount calculation
     * @return array Discount details
     */
    private function calculateProductDiscount(object $product, float $baseAmount): array
    {
        // Apply product-level discount if applicable
        if (!empty($product->discount) && ($product->product_discount_flag === 'Y')) {
            return $this->calculateDiscount($baseAmount, $product->discount->discount_id);
        }

        return [
            'discount_id' => null,
            'amount' => 0.00,
            'name' => null,
            'type' => null,
            'value' => 0.00
        ];
    }

    /**
     * Calculate order-level discount
     *
     * @param array $cart Cart details
     * @return array Discount details or empty array
     */
    private function calculateOrderDiscount(array $cart): array
    {
        return !is_null($cart['discount_id']) 
            ? $this->calculateDiscount($cart['total_amount'], $cart['discount_id']) 
            : [];
    }

    /**
     * Calculate discount amount based on discount type
     * Optimized for memory and performance
     *
     * @param float $amount Base amount for discount calculation
     * @param int|null $discount_id Discount identifier
     * @return array Detailed discount information
     */
    private function calculateDiscount(float $amount, ?int $discount_id): array
    {
        if (is_null($discount_id)) {
            return [];
        }

        $discount = $this->discountRepository->findDiscountDetails($discount_id);
        
        if (!$discount) {
            return [];
        }

        $discountType = $discount->discount_type;
        $discountValue = $discount->details->discount_value ?? 0;

        // Calculate discount amount based on type (Percentage or Fixed)
        $discount_amount = match($discountType) {
            'P' => round(($amount * $discountValue) / 100, 2),
            'F' => $discountValue,
            default => 0.00
        };

        return [
            'discount_id' => $discount->discount_id,
            'name' => $discount->details->discount_name,
            'level' => $discount->level,
            'type' => $discountType,
            'value' => $discountValue,
            'amount' => $discount_amount
        ];
    }

    /**
     * Calculate taxes for a product
     * Optimized for memory efficiency
     *
     * @param object $product Product details
     * @param float $baseAmount Base amount
     * @param array $discount Discount details
     * @param int $quantity Quantity of product
     * @return array Tax components and total tax amount
     */
    private function calculateTaxes(object $product, float $baseAmount, array $discount, int $quantity): array
    {
        // Get tax details and sum of tax components
        $taxComponentsWithValue = $this->taxRepository->findTaxDetails($product->tax->tax_id);
        $taxValueSum = $this->taxRepository->sumTaxValue($taxComponentsWithValue);

        // Calculate discounted price
        $product_discounted_price = round(($baseAmount - $discount['amount']), 2);
        
        // Calculate total product tax amount
        $totalProductTaxAmount = round((($product_discounted_price * $taxValueSum) / 100), 2);

        // Calculate tax for each tax component
        $taxes = [];
        foreach ($taxComponentsWithValue as $taxComponent) {
            $taxValue = $taxComponent->details[0]->tax_value;
            $taxComponentName = $taxComponent->component_name;
            $taxComponentId = $taxComponent->tc_id;

            // Calculate tax amount for this component
            $taxAmount = round(($product_discounted_price * $taxValue / 100), 2);

            // Accumulate tax amounts and details
            $taxes[$taxComponentId]['amount'] = 
                isset($taxes[$taxComponentId]['amount'])
                    ? $taxes[$taxComponentId]['amount'] + round($taxAmount, 2)
                    : round($taxAmount, 2);

            $taxes[$taxComponentId]['value'] = $taxValue;
            $taxes[$taxComponentId]['name'] = $taxComponentName;

            // Update order-level taxes
            $orderTaxAmount = round((($product_discounted_price * $quantity) * $taxValue / 100), 2);
            $this->updateOrderTaxes($taxComponentId, $taxComponentName, $taxValue, $orderTaxAmount);
        }

        return [
            'taxes' => $taxes,
            'total_tax_amount' => $totalProductTaxAmount
        ];
    }

    /**
     * Update accumulated order-level taxes
     *
     * @param int $componentId Tax component identifier
     * @param string $name Tax component name
     * @param float $value Tax percentage
     * @param float $amount Tax amount
     */
    private function updateOrderTaxes(int $componentId, string $name, float $value, float $amount): void
    {
        // Accumulate tax amounts by component
        $this->taxes[$componentId]['amount'] = 
            isset($this->taxes[$componentId]['amount'])
                ? round($this->taxes[$componentId]['amount'] + $amount, 2)
                : round($amount, 2);

        $this->taxes[$componentId]['value'] = $value;
        $this->taxes[$componentId]['name'] = $name;
    }

    /**
     * Update individual item details with calculated values
     *
     * @param array &$item Cart item (passed by reference)
     * @param object $product Product details
     * @param float $baseAmount Base product amount
     * @param array $discount Discount details
     * @param array $taxDetails Tax calculation results
     */
    private function updateItemDetails(
        array &$item, 
        object $product, 
        float $baseAmount, 
        array $discount, 
        array $taxDetails
    ): void {
        $quantity = $item['quantity'];

        $item['detail'] = [
            'price' => [
                'base_amount' => $baseAmount,
                'unit_price' => $product->price->unit_price
            ],
            'discount' => $discount,
            'tax' => $taxDetails['taxes']
        ];
        $item['net_amount'] = round($baseAmount * $quantity, 2);
        $item['tax_amount'] = round($taxDetails['total_tax_amount'] * $quantity, 2);
        $item['charge_amount'] = 0.00;
        $item['discount_amount'] = round($discount['amount'] * $quantity, 2);
        $item['total_amount'] = $item['net_amount'] + $item['tax_amount'] + $item['charge_amount'];
    }

    /**
     * Accumulate cart-level totals
     *
     * @param array &$cart Cart array (passed by reference)
     * @param array $item Processed cart item
     */
    private function accumulateTotals(array &$cart, array $item): void
    {
        $cart['total_amount'] += $item['total_amount'];
        $cart['net_amount'] += $item['net_amount'];
        $cart['tax_amount'] += $item['tax_amount'];
        $cart['charge_amount'] += $item['charge_amount'];
    }
}