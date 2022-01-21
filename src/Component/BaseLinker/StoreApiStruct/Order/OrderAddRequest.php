<?php


namespace Crehler\BaseLinkerShopsApi\Component\BaseLinker\StoreApiStruct\Order;


use Crehler\BaseLinkerShopsApi\Component\BaseLinker\StoreApiStruct\AssignArrayTrait;

class OrderAddRequest
{
    use AssignArrayTrait;

    /**
     *
     * @var int|null
     */
    protected ?int $date_add;

    /**
     * service name
     * @var string|null
     */
    protected ?string $service;

    /**
     * ID zamówienia (jeśli pierwszy raz dodawane do sklepu, wartość jest pusta. Peśli było już wcześniej dodane, wartość zawiera poprzedni numer zamówienia)
     * @var int|null
     */
    protected ?int $previous_order_id = null;
    /**
     * Numer zamówienia z numeracji BaseLinkera
     * @var int|null
     */
    protected ?int $baselinker_id;
    /**
     * Adres dostawy - imię i nazwisko
     * @var string|null
     */
    protected ?string $delivery_fullname;

    /**
     * Adres dostawy - firma
     * @var string|null
     */
    protected ?string $delivery_company;
    /**
     *    Adres dostawy - ulica i numer domu
     * @var string|null
     */
    protected ?string $delivery_address;

    /**
     *    Adres dostawy - kod pocztowy
     * @var string|null
     */
    protected ?string $delivery_postcode;
    /**
     * Adres dostawy - miasto
     * @var string|null
     */
    protected ?string $delivery_city;
    /**
     * Opcjonalny kod stanu, regionu, prowincji
     * @var string|null
     */
    protected ?string $delivery_state_code;
    /**
     * Adres dostawy - kraj
     * @var string|null
     */
    protected ?string $delivery_country;
    /**
     * Adres dostawy - 2-literowy kod kraju
     * @var string|null
     */
    protected ?string $delivery_country_code;
    /**
     * Dane płatnika - imię i nazwisko
     * @var string|null
     */
    protected ?string $invoice_fullname;
    /**
     * Dane płatnika - firma
     * @var string|null
     */
    protected ?string $invoice_company = null;
    /**
     * Dane płatnika - NIP
     * @var string|null
     */
    protected ?string $invoice_nip;
    /**
     *    Dane płatnika - ulica i numer domu
     * @var string|null
     */
    protected ?string $invoice_address;
    /**
     * Dane płatnika - kod pocztowy
     * @var string|null
     */
    protected ?string $invoice_postcode;
    /**
     * Dane płatnika - miasto
     * @var string|null
     */
    protected ?string $invoice_city;
    /**
     * Opcjonalny kod stanu, regionu, prowincji
     * @var string|null
     */
    protected ?string $invoice_state_code;
    /**
     * Dane płatnika - kraj
     * @var string|null
     */
    protected ?string $invoice_country;
    /**
     *    Dane płatnika - 2-literowy kod kraju
     * @var string|null
     */
    protected ?string $invoice_country_code;
    /**
     * Adres email kupującego
     * @var string|null
     */
    protected ?string $email;
    /**
     * Numer telefonu kupującego
     * @var string|null
     */
    protected ?string $phone;
    /**
     * Nazwa sposobu wysyłki
     * @var string|null
     */
    protected ?string $delivery_method;
    /**
     * Numer ID sposobu wysyłki
     * @var string|null
     */
    protected ?string $delivery_method_id;
    /**
     * Cena wysyłki
     * @var float|null
     */
    protected ?float $delivery_price;
    /**
     * Kod paczkomatu, kiosku lub innego punktu odbioru
     * @var string|null
     */
    protected ?string $delivery_point_name;
    /**
     * Sześciocyfrowy kod PNI używany w kontekście wysyłki do punktu odbioru Poczty Polskiej
     * @var string|null
     */
    protected ?string $delivery_point_pni;
    /**
     * Adres punktu odbioru
     * @var string|null
     */
    protected ?string $delivery_point_address;
    /**
     * Kod pocztowy punktu odbioru
     * @var string|null
     */
    protected ?string $delivery_point_postcode;
    /**
     * Nazwa miejscowości wybranego punktu odbioru
     * @var string|null
     */
    protected ?string $delivery_point_city;
    /**
     * Nazwa sposobu płatności
     * @var string|null
     */
    protected ?string $payment_method;
    /**
     * Czy płatność jest za pobraniem (0 - nie, 1 - tak)
     * @var bool|null
     */
    protected ?bool $payment_method_cod;
    /**
     * Komentarz kupującego
     * @var string|null
     */
    protected ?string $user_comments;
    /**
     * ID statusu zamówienia (lista statusów do pobrania metodą getOrderStatusList)
     * @var string|null
     */
    protected ?string $status_id = null;
    /**
     * ID formy płatności (jeśli zmapowano w BL)
     * @var string|null
     */
    protected ?string $payment_method_id = null;
    /**
     * Symbol waluty zamówienia
     * @var string|null
     */
    protected ?string $currency;
    /**
     * flaga określająca czy klient chce fakturę (1 - tak, 0 - nie)
     * @var bool|null
     */
    protected ?bool $want_invoice;
    /**
     * flaga określająca czy zamówienie jest zapłacone (1 - tak, 0 - nie)
     * @var bool|null
     */
    protected ?bool $paid;
    /**
     * flaga informująca, czy po stworzeniu zamówienia zmniejszony ma zostać stan zakupionych produktów (1 - tak, 0 - nie)
     * @var bool|null
     */
    protected ?bool $change_products_quantity;
    /**
     * $id - identyfikator produktu w podłączonym sklepie
     * $variant_id - identyfikator wariantu produktu (0 jeśli produkt główny)
     * $sku - SKU produktu (opcjonalnie)
     * $name - nazwa produktu (używana jeśli nie można pobrać jej z bazy na podstawie id)
     * $price - jednostkowa cena brutto produktu
     * $quantity - zakupiona ilość sztuk
     * $auction_id - numer aukcji
     *
     * Tablica zawierająca produkty zamówienia. Każdy element tablicy to również tablica, zawierająca pola:
     * @var array|null
     */
    protected ?array $products;

    /**
     * ID transakcji
     * @var string|null
     */
    protected ?string $transaction_id;
    /**
     * ID konta sprzedawcy
     * @var int|null
     */
    protected ?int $service_account;
    /**
     * Login kupującego
     * @var string|null
     */
    protected ?string $client_login;

    /**
     * @return int|null
     */
    public function getPreviousOrderId(): ?int
    {
        return $this->previous_order_id;
    }

    /**
     * @param int|null $previous_order_id
     * @return OrderAddRequest
     */
    public function setPreviousOrderId(?int $previous_order_id): OrderAddRequest
    {
        $this->previous_order_id = $previous_order_id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getBaselinkerId(): ?int
    {
        return $this->baselinker_id;
    }

    /**
     * @param int|null $baselinker_id
     * @return OrderAddRequest
     */
    public function setBaselinkerId(?int $baselinker_id): OrderAddRequest
    {
        $this->baselinker_id = $baselinker_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryFullname(): ?string
    {
        return $this->delivery_fullname;
    }

    /**
     * @param string|null $delivery_fullname
     * @return OrderAddRequest
     */
    public function setDeliveryFullname(?string $delivery_fullname): OrderAddRequest
    {
        $this->delivery_fullname = $delivery_fullname;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryCompany(): ?string
    {
        return $this->delivery_company;
    }

    /**
     * @param string|null $delivery_company
     * @return OrderAddRequest
     */
    public function setDeliveryCompany(?string $delivery_company): OrderAddRequest
    {
        $this->delivery_company = $delivery_company;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryAddress(): ?string
    {
        return $this->delivery_address;
    }

    /**
     * @param string|null $delivery_address
     * @return OrderAddRequest
     */
    public function setDeliveryAddress(?string $delivery_address): OrderAddRequest
    {
        $this->delivery_address = $delivery_address;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryPostcode(): ?string
    {
        return $this->delivery_postcode;
    }

    /**
     * @param string|null $delivery_postcode
     * @return OrderAddRequest
     */
    public function setDeliveryPostcode(?string $delivery_postcode): OrderAddRequest
    {
        $this->delivery_postcode = $delivery_postcode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryCity(): ?string
    {
        return $this->delivery_city;
    }

    /**
     * @param string|null $delivery_city
     * @return OrderAddRequest
     */
    public function setDeliveryCity(?string $delivery_city): OrderAddRequest
    {
        $this->delivery_city = $delivery_city;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryStateCode(): ?string
    {
        return $this->delivery_state_code;
    }

    /**
     * @param string|null $delivery_state_code
     * @return OrderAddRequest
     */
    public function setDeliveryStateCode(?string $delivery_state_code): OrderAddRequest
    {
        $this->delivery_state_code = $delivery_state_code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryCountry(): ?string
    {
        return $this->delivery_country;
    }

    /**
     * @param string|null $delivery_country
     * @return OrderAddRequest
     */
    public function setDeliveryCountry(?string $delivery_country): OrderAddRequest
    {
        $this->delivery_country = $delivery_country;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryCountryCode(): ?string
    {
        return $this->delivery_country_code;
    }

    /**
     * @param string|null $delivery_country_code
     * @return OrderAddRequest
     */
    public function setDeliveryCountryCode(?string $delivery_country_code): OrderAddRequest
    {
        $this->delivery_country_code = $delivery_country_code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceFullname(): ?string
    {
        return $this->invoice_fullname;
    }

    /**
     * @param string|null $invoice_fullname
     * @return OrderAddRequest
     */
    public function setInvoiceFullname(?string $invoice_fullname): OrderAddRequest
    {
        $this->invoice_fullname = $invoice_fullname;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceCompany(): ?string
    {
        return $this->invoice_company;
    }

    /**
     * @param string|null $invoice_company
     * @return OrderAddRequest
     */
    public function setInvoiceCompany(?string $invoice_company): OrderAddRequest
    {
        $this->invoice_company = $invoice_company;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceNip(): ?string
    {
        return $this->invoice_nip;
    }

    /**
     * @param string|null $invoice_nip
     * @return OrderAddRequest
     */
    public function setInvoiceNip(?string $invoice_nip): OrderAddRequest
    {
        $this->invoice_nip = $invoice_nip;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceAddress(): ?string
    {
        return $this->invoice_address;
    }

    /**
     * @param string|null $invoice_address
     * @return OrderAddRequest
     */
    public function setInvoiceAddress(?string $invoice_address): OrderAddRequest
    {
        $this->invoice_address = $invoice_address;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoicePostcode(): ?string
    {
        return $this->invoice_postcode;
    }

    /**
     * @param string|null $invoice_postcode
     * @return OrderAddRequest
     */
    public function setInvoicePostcode(?string $invoice_postcode): OrderAddRequest
    {
        $this->invoice_postcode = $invoice_postcode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceCity(): ?string
    {
        return $this->invoice_city;
    }

    /**
     * @param string|null $invoice_city
     * @return OrderAddRequest
     */
    public function setInvoiceCity(?string $invoice_city): OrderAddRequest
    {
        $this->invoice_city = $invoice_city;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceStateCode(): ?string
    {
        return $this->invoice_state_code;
    }

    /**
     * @param string|null $invoice_state_code
     * @return OrderAddRequest
     */
    public function setInvoiceStateCode(?string $invoice_state_code): OrderAddRequest
    {
        $this->invoice_state_code = $invoice_state_code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceCountry(): ?string
    {
        return $this->invoice_country;
    }

    /**
     * @param string|null $invoice_country
     * @return OrderAddRequest
     */
    public function setInvoiceCountry(?string $invoice_country): OrderAddRequest
    {
        $this->invoice_country = $invoice_country;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInvoiceCountryCode(): ?string
    {
        return $this->invoice_country_code;
    }

    /**
     * @param string|null $invoice_country_code
     * @return OrderAddRequest
     */
    public function setInvoiceCountryCode(?string $invoice_country_code): OrderAddRequest
    {
        $this->invoice_country_code = $invoice_country_code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return OrderAddRequest
     */
    public function setEmail(?string $email): OrderAddRequest
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param string|null $phone
     * @return OrderAddRequest
     */
    public function setPhone(?string $phone): OrderAddRequest
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryMethod(): ?string
    {
        return $this->delivery_method;
    }

    /**
     * @param string|null $delivery_method
     * @return OrderAddRequest
     */
    public function setDeliveryMethod(?string $delivery_method): OrderAddRequest
    {
        $this->delivery_method = $delivery_method;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryMethodId(): ?string
    {
        return $this->delivery_method_id;
    }

    /**
     * @param string|null $delivery_method_id
     * @return OrderAddRequest
     */
    public function setDeliveryMethodId(?string $delivery_method_id): OrderAddRequest
    {
        $this->delivery_method_id = $delivery_method_id;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getDeliveryPrice(): ?float
    {
        return $this->delivery_price;
    }

    /**
     * @param float|null $delivery_price
     * @return OrderAddRequest
     */
    public function setDeliveryPrice(?float $delivery_price): OrderAddRequest
    {
        $this->delivery_price = $delivery_price;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryPointName(): ?string
    {
        return $this->delivery_point_name;
    }

    /**
     * @param string|null $delivery_point_name
     * @return OrderAddRequest
     */
    public function setDeliveryPointName(?string $delivery_point_name): OrderAddRequest
    {
        $this->delivery_point_name = $delivery_point_name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryPointPni(): ?string
    {
        return $this->delivery_point_pni;
    }

    /**
     * @param string|null $delivery_point_pni
     * @return OrderAddRequest
     */
    public function setDeliveryPointPni(?string $delivery_point_pni): OrderAddRequest
    {
        $this->delivery_point_pni = $delivery_point_pni;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryPointAddress(): ?string
    {
        return $this->delivery_point_address;
    }

    /**
     * @param string|null $delivery_point_address
     * @return OrderAddRequest
     */
    public function setDeliveryPointAddress(?string $delivery_point_address): OrderAddRequest
    {
        $this->delivery_point_address = $delivery_point_address;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryPointPostcode(): ?string
    {
        return $this->delivery_point_postcode;
    }

    /**
     * @param string|null $delivery_point_postcode
     * @return OrderAddRequest
     */
    public function setDeliveryPointPostcode(?string $delivery_point_postcode): OrderAddRequest
    {
        $this->delivery_point_postcode = $delivery_point_postcode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDeliveryPointCity(): ?string
    {
        return $this->delivery_point_city;
    }

    /**
     * @param string|null $delivery_point_city
     * @return OrderAddRequest
     */
    public function setDeliveryPointCity(?string $delivery_point_city): OrderAddRequest
    {
        $this->delivery_point_city = $delivery_point_city;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    /**
     * @param string|null $payment_method
     * @return OrderAddRequest
     */
    public function setPaymentMethod(?string $payment_method): OrderAddRequest
    {
        $this->payment_method = $payment_method;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getPaymentMethodCod(): ?bool
    {
        return $this->payment_method_cod;
    }

    /**
     * @param bool|null $payment_method_cod
     * @return OrderAddRequest
     */
    public function setPaymentMethodCod(?bool $payment_method_cod): OrderAddRequest
    {
        $this->payment_method_cod = $payment_method_cod;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserComments(): ?string
    {
        return $this->user_comments;
    }

    /**
     * @param string|null $user_comments
     * @return OrderAddRequest
     */
    public function setUserComments(?string $user_comments): OrderAddRequest
    {
        $this->user_comments = $user_comments;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatusId(): ?string
    {
        return $this->status_id;
    }

    /**
     * @param string|null $status_id
     * @return OrderAddRequest
     */
    public function setStatusId(?string $status_id): OrderAddRequest
    {
        $this->status_id = $status_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaymentMethodId(): ?string
    {
        return $this->payment_method_id;
    }

    /**
     * @param string|null $payment_method_id
     * @return OrderAddRequest
     */
    public function setPaymentMethodId(?string $payment_method_id): OrderAddRequest
    {
        $this->payment_method_id = $payment_method_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string|null $currency
     * @return OrderAddRequest
     */
    public function setCurrency(?string $currency): OrderAddRequest
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getWantInvoice(): ?bool
    {
        return $this->want_invoice;
    }

    /**
     * @param bool|null $want_invoice
     * @return OrderAddRequest
     */
    public function setWantInvoice(?bool $want_invoice): OrderAddRequest
    {
        $this->want_invoice = $want_invoice;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getPaid(): ?bool
    {
        return $this->paid;
    }

    /**
     * @param bool|null $paid
     * @return OrderAddRequest
     */
    public function setPaid(?bool $paid): OrderAddRequest
    {
        $this->paid = $paid;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getChangeProductsQuantity(): ?bool
    {
        return $this->change_products_quantity;
    }

    /**
     * @param bool|null $change_products_quantity
     * @return OrderAddRequest
     */
    public function setChangeProductsQuantity(?bool $change_products_quantity): OrderAddRequest
    {
        $this->change_products_quantity = $change_products_quantity;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getProducts(): ?array
    {
        return $this->products;
    }

    /**
     * @param array|null $products
     * @return OrderAddRequest
     */
    public function setProducts(?array $products): OrderAddRequest
    {
        $this->products = $products;
        return $this;
    }

    /**
     * @param OrderAddProduct $product
     * @return OrderAddRequest
     */
    public function addProduct(OrderAddProduct $product): OrderAddRequest
    {
        $this->products[] = $product;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->transaction_id;
    }

    /**
     * @param string|null $transaction_id
     * @return OrderAddRequest
     */
    public function setTransactionId(?string $transaction_id): OrderAddRequest
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getServiceAccount(): ?int
    {
        return $this->service_account;
    }

    /**
     * @param int|null $service_account
     * @return OrderAddRequest
     */
    public function setServiceAccount(?int $service_account): OrderAddRequest
    {
        $this->service_account = $service_account;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getClientLogin(): ?string
    {
        return $this->client_login;
    }

    /**
     * @param string|null $client_login
     * @return OrderAddRequest
     */
    public function setClientLogin(?string $client_login): OrderAddRequest
    {
        $this->client_login = $client_login;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getDateAdd(): ?int
    {
        return $this->date_add;
    }

    /**
     * @param int|null $date_add
     * @return OrderAddRequest
     */
    public function setDateAdd(?int $date_add): OrderAddRequest
    {
        $this->date_add = $date_add;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getService(): ?string
    {
        return $this->service;
    }

    /**
     * @param string|null $service
     * @return OrderAddRequest
     */
    public function setService(?string $service): OrderAddRequest
    {
        $this->service = $service;
        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function assign(array $options)
    {
        foreach ($options as $key => $value) {
            if ($key === 'id' && method_exists($this, 'setId')) {
                $this->setId($value);
                continue;
            }

            if('products' === $key){
                if (is_string($value)) {
                    $value = json_decode($value, true);
                }
                foreach ($value as $product){
                    if (is_string($product)) {
                        $product = json_decode($product, true);
                    }
                    $this->addProduct((new OrderAddProduct())->assign($product));
                }
                continue;
            }

            try {
                $this->$key = $value;
            } catch (\Exception $error) {
                // nth
            }
        }

        return $this;

    }
}
