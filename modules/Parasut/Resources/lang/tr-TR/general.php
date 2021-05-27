<?php

return [

    'name'              => 'Paraşüt',
    'description'       => 'Tüm verilerinizi hızlı ve eksiksiz bir şekilde aktarın',

    'form' => [
        'client_id'     => 'Client Id',
        'client_secret' => 'Client Secret',
        'redirect_uri'  => 'Redirect Url',
        'username'      => 'Kullanıcı Adı',
        'password'      => 'Şifre',
        'company_id'    => 'Firma Id',

        'sync' => [
            'title'    => 'Mevcut Verileri Getir',
            'contact'  => 'Müşteri ve Tedarikçileri Getir',
            'category' => 'Kategorileri Getir',
            'customer' => 'Müşterileri Getir',
            'product'  => 'Ürünleri Getir',
            'account'  => 'Kasa ve Bankaları Getir',
            'order'    => 'Sync Orders',
            'invoice'  => 'Satış Faturalarını Getir',
            'employee' => 'Çalışanları Getir',
            'bill'     => 'Fiş / Fatura-ları Getir',
        ]
    ],

    'types' => [
        'customers'  => 'Müşteri|Müşteriler',
        'supplier'   => 'Tedarikçi|Tedarikçiler',
        'contacts'   => 'Müşteri/Tedarikçi|Müşteriler/Tedarikçiler',
        'categories' => 'Kategori|Kategoriler',
        'products'   => 'Ürün/Hizmet|Ürünler/Hizmetler',
        'accounts'   => 'Kasa ve Banka|Kasalar ve Bankalar',
        'invoices'   => 'Satış Faturası|Satış Faturaları',
        'employees'  => 'Çalışan|Çalışanlar',
        'bills'      => 'Fiş / Fatura|Fiş / Fatura-lar',
    ],

    'success' => [
        'transactions_synced' => 'İşlemler aktarıldı'
    ],

    'custom_fields' => [
        'title'    => 'Daha sağlıklı bir entegrasyon için Custom Fields uygulamasına ihtiyacınız var',
        'contacts' => [
            'short_name'   => 'KISA İSİM',
            'contact_type' => 'HESAP TÜRÜ',
            'tax_office'   => 'VERGİ DAİRESİ',
            'city'         => 'İL',
            'district'     => 'İLÇE',
            'fax'          => 'FAX',
            'iban'         => 'İBAN',
            'ibans'        => 'DİĞER İBANALAR',
            //'balances'     => 'BAKİYELER',
        ],
        'products' => [
            'barcode' => 'BARCODE',
        ],
        'accounts' => [
            'account_type' => 'HESAP TURU',
            'bank_branch'  => 'BANKA ŞUBESİ',
            'iban'         => 'İBAN',
        ],
        'invoices' => [
            'account_type' => 'HESAP TURU',
            'bank_branch'  => 'BANKA ŞUBESİ',
            'iban'         => 'İBAN',
        ],
    ],

    'sync_text' => ' İçeri Aktarılacak :value: :type var.',
    'finished'  => ':type içerik aktarım bitti.',
    'total'     => 'Toplam :type tane: :count',
    'total_all' => 'Toplam Müşteri ve Tedarikçileri : :contacts </br>
Toplam Kategoriler: :categories </br>
Toplam Ürünler: :products </br>
Toplam Kasa ve Bankaları Hesapları: :accounts </br>
Toplam Satış Faturası: :invoices </br>
Toplam Çalışanlar: :employees </br>
Toplam Fiş / Fatular: :bills',

    'buttons' => [
        'buy'      => [
            'custom_fields' => 'Özel Alanları Satın Al',
            'inventory' => 'Stok Yönetimi Satın Al',
            'payroll' => 'Bordro Satın Al',
        ],
        'continue' => 'Devam Et',
    ],

    'error' => [
        'nothing_to_sync' => 'İçeri aktarılacak bir veri yok.',
        'no_settings'     => 'Lütfen önce ayarlarınızı yapınız.',
    ],

    'coupon' => [
        'description' => 'Bu eksik alanlar için Custom Fields uygulamasını satın alabilirsin. </br>
        Paraşüt ten gelen kullanıcılara %30 indirim.',
        'code' => 'Coupon: PARASUT30',
        'inventory' => 'Stok takibini yapabilmek için Stok Yönetimi uygulamasını alabilirsin. </br>
        Paraşüt ten gelen kullanıcılara %30 indirim.',
        'payroll' => 'Çalışanların Bodrolarını yönetmek için Bodro uygulamasını satın alabilirsin. </br>
        Paraşüt ten gelen kullanıcılara %30 indirim.'
    ],

    
    'coupon' => [
        'description' => 'Bu eksik alanlar için Custom Fields uygulamasını satın alabilirsin. </br>
                              Paraşüt ten gelen kullanıcılara %30 indirim.',
        'code' => 'Coupon: PARASUT30',
    ],
];
