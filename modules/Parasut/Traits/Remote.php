<?php

namespace Modules\Parasut\Traits;

use Date;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use yedincisenol\Parasut\Client;
use GuzzleHttp\Exception\RequestException;

trait Remote
{
    protected static $client = null;

    private $base_url = 'https://api.parasut.com/v4/';

    // Satış Fatura-ları
    public function getInvoices($parameters = [])
    {
        return $this->v1('get', 'sales_invoices', $parameters);
    }

    // Satış Fatura
    public function getInvoice($id)
    {
        return $this->v1('get', 'sales_invoices/' . $id);
    }

    // Müşteri/Tedarikçi-ler
    public function getContacts($parameters = [])
    {
        return $this->get('contacts', $parameters);
    }

    // Müşteri/Tedarikçi
    public function getContact($id)
    {
        return $this->get('contacts/' . $id);
    }

    // Fiş/Fatura-lar
    public function getBills($parameters = [])
    {
        return $this->get('purchase_bills', $parameters);
    }

    // Fiş/Fatura
    public function getBill($id, $parameters = [])
    {
        return $this->get('purchase_bills/' . $id, $parameters);
    }

    // E-fatura gelen kutusu
    public function getEfatura()
    {
        return $this->get('e_invoice_inboxes');
    }

    // Kasa ve Banka-lar
    public function getAccounts($parameters = [])
    {
        return $this->get('accounts', $parameters);
    }

    // Kasa ve Banka
    public function getAccount($id)
    {
        return $this->get('accounts/' . $id);
    }

    // Ürün-ler
    public function getProducts($parameters = [])
    {
        return $this->get('products', $parameters);
    }

    // Ürün
    public function getProduct($id)
    {
        return $this->get('products/' . $id);
    }

    // Kategori-ler
    public function getCategories($parameters = [])
    {
        return $this->get('item_categories', $parameters);
    }

    // Kategori
    public function getCategory($id)
    {
        return $this->get('item_categories/' . $id);
    }

    // Çalışan-lar
    public function getEmployees($parameters = [])
    {
        return $this->get('employees', $parameters);
    }

    // Çalışan
    public function getEmployee($id)
    {
        return $this->get('employees/' . $id);
    }

    protected function get($method, $parameters = [])
    {
        $results = [];
        $response = false;
        $path = $this->base_url . setting('parasut.c_id') . '/' . $method;

        if (empty(self::$client)) {
            try {
                self::$client = $this->connect();
            } catch (RequestException $e) {
                return json_decode($e->getResponse()->getBody());
            }
        }

        try {
            $response = self::$client->get(
                $path,
                $parameters
            );
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody());
        }

        if ($response && ($response->getStatusCode() == 200)) {
            $results = json_decode($response->getBody());
        }

        return $results;
    }

    protected function v1($method, $path, $parameters = [])
    {
        $results = [];
        $response = false;
        $path = 'https://api.parasut.com/v1/' . setting('parasut.c_id') . '/' . $path;

        if (empty(self::$client)) {
            try {
                self::$client = $this->connect();
            } catch (RequestException $e) {
                return json_decode($e->getResponse()->getBody());
            }
        }

        try {
            $response = self::$client->$method(
                $path,
                $parameters
            );
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody());
        }

        if ($response && ($response->getStatusCode() == 200)) {
            $results = json_decode($response->getBody());
        }

        return $results;
    }

    protected function connect()
    {
        $client = new Client(
            setting('parasut.client_id'),
            setting('parasut.client_secret'),
            setting('parasut.redirect_uri'),
            setting('parasut.username'),
            setting('parasut.password'),
            setting('parasut.c_id')
        );

        return  $client->login();
    }
}
