# ProfsCode Nilvera Laravel Paketi Kullanım Kılavuzu

Bu paket, Nilvera API'si ile E-Fatura, E-Arşiv ve E-İrsaliye süreçlerinizi Laravel üzerinde kolayca yönetmenizi sağlar.

## Kurulum

1. Paketi composer üzerinden yükleyin:
```bash
composer require profscode/nilvera
```

2. Konfigürasyon dosyasını yayınlayın:
```bash
php artisan vendor:publish --tag="nilvera-config"
```

3. `.env` dosyanıza API bilgilerinizi ekleyin:
```env
NILVERA_BASE_URL=https://api.nilvera.com
NILVERA_API_KEY=your_api_key_here
```

## 1. Genel ve Dashboard İşlemleri

Dashboard için özet verileri ve kredi durumlarını takip edebilirsiniz.

```php
use ProfsCode\Nilvera\Facades\Nilvera;

// Hesap Kredilerini Al
$credits = Nilvera::getCredits();

// Güncel Döviz Kurlarını Al (Nilvera üzerinden)
$rates = Nilvera::getExchangeRates();

// Dashboard Özet Verileri (Krediler, Kurlar, Son Faturalar, Günlük İstatistikler)
// Parametre: mode ('einvoice' veya 'earchive'), params (SummaryPeriod: today, yesterday, week, month)
$dashboard = Nilvera::getDashboardData('einvoice', ['SummaryPeriod' => 'month']);
```

---

## 1.1 Firma İşlemleri (Company Operations)

Hesabınıza tanımlı firma bilgilerini, modülleri ve mali mühür/e-imza sertifikalarını yönetebilirsiniz.

```php
// Firma Bilgilerini Getir
$company = Nilvera::getCompanyInfo();

// Firma Bilgilerini Güncelle
Nilvera::updateCompanyInfo([
    'Email' => 'info@firma.com',
    'PhoneNumber' => '03121234567',
    'Address' => 'Atatürk Cad. No:1',
    'District' => 'Çankaya',
    'City' => 'Ankara',
]);

// Firmaya Tanımlı Modülleri Getir (E-Fatura, E-Arşiv vb. aktiflik durumu)
$modules = Nilvera::getCompanyModules();

// Firma Sertifikalarını (Mali Mühür/E-İmza/HSM) Listele
$certificates = Nilvera::getCompanyCertificates();

// Yeni Sertifika Ekle
Nilvera::addCompanyCertificate([
    'SerialNo' => '1234567890',
    'Type' => 'EIMZA', // MALIMUHUR, EIMZA, HSM
    'Password' => '******',
    'Description' => 'Yedek e-imza',
]);

// Sertifika Bilgilerini Güncelle
Nilvera::updateCompanyCertificate([
    'ID' => 1,
    'Type' => 'EIMZA',
    'SerialNo' => '1234567890',
    'Description' => 'Güncellenmiş açıklama',
]);

// Seri Numarasına Göre Sertifika Getir
$certificate = Nilvera::getCompanyCertificateBySerial('1234567890');

// Sertifikayı Sil
Nilvera::deleteCompanyCertificate(1);

// Kullanıcıya Tanımlı Firmaları Listele
$companies = Nilvera::getUserCompanies();
```

### Firma Kimlik Bilgileri (Company Identity)

```php
// Firma Kimlik Bilgilerini Listele (MERSİS No, Ticaret Sicil No, vb.)
$identities = Nilvera::getCompanyIdentities();

// Firmaya Yeni Kimlik Bilgisi Ekle
// Name: HIZMETNO, MUSTERINO, TESISATNO, TELEFONNO, DISTRIBUTORNO, TICARETSICILNO, TAPDKNO,
//       BAYINO, ABONENO, SAYACNO, EPDKNO, SUBENO, PASAPORTNO, URETICINO, ARACIKURUMETIKET,
//       ARACIKURUMVKN, CIFTCINO, IMALATCINO, DOSYANO, HASTANO, MERSISNO
Nilvera::addCompanyIdentity('MERSISNO', '0123456789012345');

// Firmadan Kimlik Bilgisi Sil
Nilvera::deleteCompanyIdentity(1);
```

### Kampanyalar (Campaign)

```php
// Aktif Kampanyaları Getir
$campaigns = Nilvera::getCampaigns();
```

### GİB Kullanıcı Bilgileri (GIB User)

```php
// GİB E-Arşiv Kullanıcı Adı ve Parolasını Getir
$credentials = Nilvera::getGibUserCredentials();

// GİB E-Arşiv Kullanıcı Adı ve Parolasını Güncelle
Nilvera::updateGibUserCredentials('gib_kullanici_adi', 'gib_parola');
```

### Mail / SMS / WhatsApp Ayarları

```php
// Mail/SMS/WhatsApp Bildirim Ayarlarını Getir
$settings = Nilvera::getMailSettings();

// WhatsApp Bildirimini Aç/Kapat
Nilvera::setWhatsappSetting(true);

// Mail Bildirimini Aç/Kapat
Nilvera::setMailSetting(false);

// SMS Bildirimini Aç/Kapat
Nilvera::setSmsSetting(true);
```

---

## 2. Fatura Listeleme ve Sorgulama

Farklı durum ve yönlerdeki faturaları gelişmiş filtrelerle listeleyebilirsiniz.

```php
// Gelen/Giden Faturaları Listele (Gelişmiş Filtreleme)
// Yön: 'Sale' (Giden) veya 'Purchase' (Gelen)
$invoices = Nilvera::getInvoices('Sale', [
    'StartDate' => '2024-01-01',
    'EndDate' => '2024-01-31',
    'PageSize' => 50,
    'Search' => 'Müşteri Adı',
    'StatusCode' => ['succeed', 'error'] // Opsiyonel filtre
]);

// Eski (Arşivlenmiş) Faturaları Çek
$oldInvoices = Nilvera::getOldInvoices('einvoice', ['Page' => 1]);

// Eski Faturanın HTML İçeriğini Al
$oldHtml = Nilvera::getOldInvoiceHtml($uuid, 'einvoice');

// Eski Faturanın PDF İçeriğini Al (binary)
$oldPdf = Nilvera::getOldInvoicePdf($uuid, 'einvoice');
file_put_contents('eski-fatura.pdf', $oldPdf);

// Eski Faturanın XML İçeriğini Al
$oldXml = Nilvera::getOldInvoiceXml($uuid, 'einvoice');

// Eski Faturaları Toplu Dışa Aktar (Xml, Pdf, OnePagePdf)
$exported = Nilvera::exportOldInvoices([$uuid1, $uuid2], 'Pdf', 'einvoice');
file_put_contents('eski-faturalar.pdf', $exported);

// Eski Faturalara Toplu Yeni Durum Ata (UnPrint, Print, Transferred, Untransferred)
$result = Nilvera::updateOldInvoicesStatus([$uuid1, $uuid2], 'Print', 'einvoice');

// GIB E-Arşiv Gelen Faturaları Listele
$gibInvoices = Nilvera::getGibInvoices(['Search' => 'VKN']);

// Taslak Faturaları Listele
$drafts = Nilvera::getDraftInvoices('einvoice', ['Page' => 1]);
```

---

## 3. Fatura Detayları ve Görüntüleme

Fatura içeriğine, görseline ve eklerine erişebilirsiniz.

```php
// Fatura HTML Görselini Al
$html = Nilvera::getInvoiceHtml($uuid, 'einvoice', 'Sale');

// Fatura Tarihçesini (Loglarını) Al
$history = Nilvera::getInvoiceHistories($uuid, 'Sale');

// Fatura Ek Dosyalarını Al
$attachments = Nilvera::getInvoiceAttachments($uuid, 'Sale');

// Fatura Detay Bilgilerini Al
$details = Nilvera::getInvoiceDetails($uuid, 'Sale');

// Fatura Vergi Detaylarını Al (KDV, ÖTV vb. kırılımı)
// E-Arşiv için direction parametresi yok sayılır (Invoices endpointi kullanılır)
$taxes = Nilvera::getInvoiceTaxes($uuid, 'Sale', 'einvoice');

// Gelen Faturaya Yanıt Ver (Kabul/Red)
// AnswerCode: 'approved' veya 'rejected'
Nilvera::answerInvoice($uuid, 'approved');
Nilvera::answerInvoice($uuid, 'rejected', 'Hatalı fatura tutarı');

// Red Cevabını (Red Notunu) Getir
$rejectNote = Nilvera::getInvoiceRejectedNote($uuid);

// Faturanın Güncel Statü Bilgisini Getir
// StatusCode: unknown, waiting, succeed, error | ReportStatus: NotReported, Reported
// E-Arşiv için direction parametresi yok sayılır (Invoices endpointi kullanılır)
$status = Nilvera::getInvoiceStatus($uuid, 'Sale', 'einvoice');

// E-Arşiv Faturasını İptal Et
Nilvera::cancelEArchiveInvoice($uuid);

// E-Arşiv Faturasının İptal İşlemini Geri Al
Nilvera::revertCancelEArchiveInvoice($uuid);

// E-Arşiv Raporu Gönder (Dönem Bazlı)
$report = Nilvera::sendEArchiveReport(2024, 1);

// Gönderilen E-Arşiv Raporlarını Listele
$reports = Nilvera::getEArchiveReports(pageSize: 30, page: 1, sortColumn: 'PeriodYear', sortType: 'DESC', periodYear: 2024, periodMonth: 1);

// Rapora Dahil Edilebilecek Belgeleri Listele
$reportable = Nilvera::getEArchiveReportableDocuments(pageSize: 30, page: 1, search: 'GIB', periodYear: 2024, periodMonth: 1);

// Gönderilmiş Bir Rapordaki Belgeleri Listele
$reportDocs = Nilvera::getEArchiveReportDocuments($report['UUID']);

// Rapor XML İçeriğini İndir
$reportXml = Nilvera::downloadEArchiveReportXml($report['UUID']);

// Raporun İşlem Geçmişini Getir
$reportHistories = Nilvera::getEArchiveReportHistories($report['UUID']);

// Taslak Fatura Modelini (Verisini) Al
$model = Nilvera::getDraftModel($uuid, 'einvoice');
```

---

## 4. Fatura Oluşturma ve Gönderme

Laravel verilerinizi Nilvera modeline dönüştürüp fatura oluşturabilirsiniz.

```php
// 1. Laravel Verisini Nilvera Modeline Dönüştür
$data = [
    'TaxNumber'    => '1234567890',
    'Title'        => 'Örnek Limited Şirketi',
    'Address'      => 'Atatürk Cad. No:1',
    'District'     => 'Çankaya',
    'City'         => 'Ankara',
    'CurrencyCode' => 'TRY', // Opsiyonel, varsayılan TRY
    'Series'       => 'HIZ', // Fatura Serisi
    'Scenario'     => 'Ticari', // Temel veya Ticari
    'items'        => [
        [
            'Name'     => 'Yazılım Hizmeti',
            'Quantity' => 1,
            'Price'    => 5000,
            'VatRate'  => 20,
            'UnitCode' => 'C62', // ADET için Nilvera kodu
            'Description' => 'Ocak ayı bakım bedeli'
        ]
    ],
    'Notes' => 'Fatura notu buraya yazılır.'
];

$model = Nilvera::mapToModel($data, $isArchive = false);

// 2. Taslak Oluştur
$result = Nilvera::createDraft($model, 'einvoice');

// 3. Faturayı Önizle (HTML döner)
$preview = Nilvera::previewInvoice($model, 'einvoice');

// 4. Doğrudan Gönder (Create & Send)
$sendResult = Nilvera::sendNewInvoice($model, 'einvoice');

// 5. Mevcut Taslağı Onayla ve Gönder (EditAndSend)
$confirmResult = Nilvera::sendInvoice($uuid);

// 6. Önizlenen Faturanın XML İçeriğini İndir
$xml = Nilvera::downloadInvoice($model, 'einvoice');

// 7. Önizlenen Faturanın PDF İçeriğini İndir
$pdf = Nilvera::downloadInvoicePdf($model, 'einvoice');
```

### Tüm Alanlarla Referans Örnek

`mapToModel`'in okuyabildiği **tüm opsiyonel alanları** gösteren referans bir `$data` dizisi. İhtiyacınız olmayan alanları silebilir veya boş bırakabilirsiniz.

```php
$data = [
    // --- Müşteri Bilgileri ---
    'TaxNumber'    => '1234567890', // VKN/TCKN
    'Title'        => 'Örnek Limited Şirketi',
    'TaxOffice'    => 'Kadıköy',
    'Alias'        => 'urn:mail:defaultpk@example.com', // Müşterinin GİB etiketi (e-Fatura mükellefiyse zorunlu)
    'Address'      => 'Atatürk Cad. No:1',
    'District'     => 'Çankaya',
    'City'         => 'Ankara',
    'Country'      => 'Türkiye',
    'PostalCode'   => '06000',
    'Phone'        => '02121234567',
    'Mail'         => 'musteri@example.com',

    // --- Gönderen (Firma) Bilgileri (opsiyonel, verilmezse config'den alınır) ---
    'CompanyInfo' => [
        'Name'      => 'Kendi Firmam A.Ş.',
        'TaxNumber' => '1234567801',
        'TaxOffice' => 'Erciyes',
        'Address'   => 'Merkez Mah. No:1',
        'District'  => 'Iğdır',
        'City'      => 'Iğdır',
        'Country'   => 'Türkiye',
        'PostalCode'=> '38070',
        'Phone'     => '02121234567',
        'Mail'      => 'info@kendifirmam.com',
        'WebSite'   => 'https://kendifirmam.com',
    ],

    // --- Genel Bilgiler ---
    'InvoiceUUID'  => null, // Boş bırakılırsa otomatik UUID üretilir
    'TemplateUuid' => null, // Belirli bir fatura şablonu kullanmak için
    'IssueDate'    => date('Y-m-d'), // Fatura tarihi, varsayılan bugün
    'Series'       => 'HIZ', // Fatura Serisi
    'Scenario'     => 'Ticari', // 'Temel' veya 'Ticari' -> InvoiceProfile
    'InvoiceProfile' => null, // Manuel set etmek isterseniz: TEMELFATURA, TICARIFATURA, IHRACAT, YOLCUBERABERFATURA, EARSIVFATURA, KAMU, HKS, ENERJI, ILAC_TIBBICIHAZ, OZELFATURA, YATIRIMTESVIK, IDIS
    'InvoiceType'  => null, // SATIS, IADE, ISTISNA, TEVKIFAT, IHRACKAYITLI, OZELMATRAH, SGK, KONAKLAMAVERGISI, vb. (boşsa SATIS/0)
    'CurrencyCode' => 'TRY', // Opsiyonel, varsayılan TRY
    'ExchangeRate' => null, // Döviz ise kur bilgisi

    // --- Vergi İstisna Bilgileri (Fatura Tipi = İstisna seçildiğinde) ---
    'KDVExemptionReasonCode' => null, // KDV istisna muafiyet sebep kodu
    'OTVExemptionReasonCode' => null, // ÖTV istisna muafiyet sebep kodu
    'AccommodationTaxExemptionReasonCode' => null, // Konaklama vergisi istisna sebep kodu

    // --- İrsaliye / Sevkiyat Bilgileri ---
    'DespatchDocumentReference' => [
        // ['Value' => 'IRS2024000000001', 'Date' => '2024-01-10'],
    ],

    // --- Sipariş Referansı ---
    'OrderReference' => [
        // 'ID' => 'SIP-2024-001',
        // 'Date' => '2024-01-05',
    ],

    // --- İade Faturası Bilgisi (InvoiceType = IADE iken) ---
    'ReturnInvoiceInfo' => [
        // ['InvoiceNumber' => 'GIB2024000000123', 'IssueDate' => '2024-01-15'],
    ],

    // --- Ek Masraflar ---
    'InsuranceTotal' => 0, // Sigorta tutarı
    'FreightTotal'   => 0, // Navlun tutarı

    // --- E-Arşiv İnternet Satış Bilgileri (mapToModel(..., isArchive: true) iken) ---
    'EArchiveSalesChannel' => 0, // 1 = İnternet satışı
    'EArchiveSendingType'  => 'KAGIT', // veya 'ELEKTRONIK'
    'InternetInfo' => [
        'WebSite'           => 'https://magazam.com',
        'PaymentMethod'     => 'KREDIKARTI/BANKAKARTI',
        'PaymentMethodName' => 'Kredi Kartı',
        'PaymentAgent'      => 'magazam.com',
        'PaymentDate'       => '2024-01-15',
    ],

    // --- Fatura Kalemleri ---
    'items' => [
        [
            'SellerCode'   => 'STK-001', // Satıcı stok kodu
            'Name'         => 'Yazılım Hizmeti',
            'Description'  => 'Ocak ayı bakım bedeli',
            'Quantity'     => 1,
            'Price'        => 5000,
            'VatRate'      => 20, // KDV %
            'UnitCode'     => 'C62', // ADET için Nilvera birim kodu
            'UnitName'     => 'ADET',
            'ExemptionReasonCode' => null, // Tevkifat/istisna kodu (satır bazlı)

            // Birden fazla vergi kalemi gerekiyorsa Taxes ile detaylandırılabilir
            'Taxes' => [
                // ['Code' => '0015', 'Amount' => 1000, 'Percent' => 20, 'ReasonCode' => null],
            ],

            // Özel matrah (ÖTV'li ürünler için)
            'OzelMatrahTotal'       => 0,
            'OzelMatrahReasonCode'  => null,

            // İhracat (GTİP) bilgileri
            'GTIPNo'             => null,
            'DeliveryTermCode'   => 'DAF',
            'TransportModeCode'  => '5',
            'PackageBrandName'   => '',
            'PackageID'          => '',
            'PackageQuantity'    => 0,
            'PackageTypeCode'    => 'BB',
            'CustomsTrackingNo'  => '',

            // Satır bazlı teslimat adresi
            'DeliveryAddress' => [
                // 'Address' => 'Liman Mah. No:5', 'District' => 'Mersin', 'City' => 'Mersin', 'Country' => 'Türkiye', 'PostalCode' => '33000',
            ],
        ],
    ],

    // --- Notlar ---
    'Notes' => 'Fatura notu buraya yazılır.', // string veya string[] olabilir
];
```

### E-Arşiv Faturası Oluşturma

`mapToModel`'e `isArchive: true` vererek E-Arşiv modeli oluşturabilirsiniz (root key `ArchiveInvoice` olur ve `SalesPlatform`/`SendType`/`InternetInfo` alanları eklenir).

```php
$model = Nilvera::mapToModel($data, isArchive: true);

$result = Nilvera::createDraft($model, 'earchive');
```

### İade (Return) Faturası Oluşturma

İade faturası oluşturmanın iki yolu vardır:

```php
// 1. Var olan bir faturadan iade taslağı oluşturma (Nilvera otomatik doldurur)
$returnDraft = Nilvera::createReturnInvoice($uuid, 'Purchase');

// 2. Sıfırdan model ile iade faturası oluşturma
$data = [
    // ...standart fatura alanları...
    'InvoiceType' => 'IADE',
    'ReturnInvoiceInfo' => [
        ['InvoiceNumber' => 'GIB2024000000123', 'IssueDate' => '2024-01-15'],
    ],
];

$model = Nilvera::mapToModel($data);
$result = Nilvera::createDraft($model, 'einvoice');
```

### Base64 (ZIP) İle Fatura Gönderme ve Önizleme

UBL-TR XML'i ZIP'leyip Base64'e çevirerek doğrudan gönderebilir veya önizleyebilirsiniz.

```php
$zipBase64 = base64_encode(file_get_contents('fatura.zip'));

// Base64 (ZIP) Faturayı Gönder
$result = Nilvera::sendInvoiceBase64($zipBase64, 'urn:mail:defaultpk@nilvera.com', $templateUuid = null, 'einvoice');

// Base64 (ZIP) Faturayı Önizle (HTML döner)
$preview = Nilvera::previewInvoiceBase64($zipBase64, 'urn:mail:defaultpk@nilvera.com', 'einvoice');
```

---

## 5. Müşteri (Cari) Yönetimi

VKN/TCKN üzerinden mükellef sorgulama ve adres defteri işlemleri.

```php
// Detaylı ve Normalize Edilmiş Müşteri Bilgisi Al
// (CheckTaxNumber ve GlobalCustomerInfo'yu birleştirir)
$customer = Nilvera::getCustomerDetails('1234567890');

// Nilvera Adres Defterine Müşteri Kaydet
Nilvera::createCustomer([
    'Name' => 'Müşteri Ünvanı',
    'TaxNumber' => '1234567890',
    'Email' => 'email@example.com',
    'City' => 'İstanbul'
]);

// Adres Defterindeki Müşterileri Listele (Sayfalı)
$customers = Nilvera::getCustomers(search: 'Müşteri', pageSize: 30, page: 1, sortColumn: 'Name', sortType: 'ASC');

// Müşteri Bilgilerini Güncelle
Nilvera::updateCustomer([
    'ID' => 1,
    'Name' => 'Güncellenmiş Ünvan',
    'Email' => 'yeni@example.com'
]);

// ID İle Müşteri Getir
$customer = Nilvera::getCustomerById(1);

// Müşteri Sil
Nilvera::deleteCustomer(1);

// Müşterileri Toplu Sil
Nilvera::bulkDeleteCustomers([1, 2, 3]);

// Müşteri Listesinde Arama Yap
$results = Nilvera::searchCustomers('Müşteri');

// Vergi Numarası İle Adres Defterindeki Müşteriyi Getir
$customer = Nilvera::getCustomerByTaxNumber('1234567890');
```

### Stok Yönetimi (Stock)

```php
// Stokları Listele (Sayfalı, Filtreli)
$stocks = Nilvera::getStocks(search: 'Ürün', pageSize: 30, page: 1, sortColumn: 'Name', sortType: 'ASC', isActive: true);

// Yeni Stok Ekle (Tekli veya Çoklu)
Nilvera::createStocks([
    'Name' => 'Yazılım Lisansı',
    'UnitCode' => 'C62',
    'Price' => 1000,
    'IsActive' => true,
    'TaxPercent' => 20,
]);

// Stok Bilgilerini Güncelle
Nilvera::updateStock([
    'ID' => 1,
    'Name' => 'Yazılım Lisansı (Güncel)',
    'Price' => 1200,
    'IsActive' => true,
    'TaxPercent' => 20,
]);

// ID İle Stok Getir
$stock = Nilvera::getStockById(1);

// Stok Sil
Nilvera::deleteStock(1);

// Stokları Toplu Sil
Nilvera::bulkDeleteStocks([1, 2, 3]);

// Stok Listesinde Arama Yap
$results = Nilvera::searchStocks('Yazılım');
```

### Muhasebe Raporları (Report API)

```php
// Yeni Muhasebe Raporu Oluştur
// ReportType: 'Invoice', 'Voucher' veya 'Producer'
$report = Nilvera::createReport([
    'Title' => 'Ocak Ayı Faturaları',
    'StartDate' => '2024-01-01',
    'EndDate' => '2024-01-31',
    'Type' => 1,
    'TemplateID' => 1,
    'ReportType' => 'Invoice',
    'Email' => 'rapor@firma.com',
]);

// Oluşturulan Raporları Listele (Sayfalı, Filtreli)
$reports = Nilvera::getReports(search: null, pageSize: 30, page: 1, startDate: '2024-01-01', endDate: '2024-01-31', reportType: 'Invoice');

// Raporu İndir (UUID ile)
$file = Nilvera::downloadReport($report['UUID']);

// Yeni Rapor Şablonu Oluştur
Nilvera::createReportTemplate([
    'Name' => 'Standart Fatura Şablonu',
    'Type' => 1,
    'ReportType' => 'Invoice',
    'Columns' => [
        ['Name' => 'Fatura No', 'ID' => 1],
        ['Name' => 'Tarih', 'ID' => 2],
    ],
]);

// Rapor Şablonlarını Listele
$templates = Nilvera::getReportTemplates();
```

---

## 6. Toplu İşlemler ve Operasyonlar

```php
// Toplu Fatura Gönderimi
Nilvera::bulkSendInvoices([['UUID' => '...', 'Alias' => '...']], 'einvoice');

// Toplu Taslak Silme
Nilvera::bulkDeleteInvoices(['uuid1', 'uuid2'], 'einvoice');

// Toplu Arşivleme
Nilvera::archiveInvoices(['uuid1', 'uuid2'], 'Sale');

// Toplu Okundu Olarak İşaretleme
Nilvera::markAsRead(['uuid1', 'uuid2'], 'Purchase');

// Toplu ERP Aktarıldı İşareti
Nilvera::markAsTransferred(['uuid1', 'uuid2'], 'Sale');

// Taslaklara Toplu Yeni Durum Atama
// operationType: Print, UnPrint (E-Fatura) | Transferred, Untransferred (E-Arşiv)
Nilvera::updateDraftsStatus(['uuid1', 'uuid2'], 'Print', 'einvoice');
```

---

## 7. Dışa Aktarma (Export) ve Yükleme

```php
// Faturaları PDF/XML Olarak Dışa Aktar
// Formatlar: 'Pdf', 'OnePagePdf', 'Xml', 'Envelope'
$binaryContent = Nilvera::exportInvoices($uuids, 'Pdf', 'Sale');

// XML/UBL Dosyası Yükleyerek Fatura Oluştur
Nilvera::uploadXml($filePath, $fileName, 'einvoice');

// SGK PDF Yükleme
Nilvera::uploadSgkPdf($filePath, $fileName, 'SAGLIK_ECZ', 'registerCode');
```

---

## 8. Ekstra Fonksiyonlar

```php
// WhatsApp Üzerinden Fatura Gönder (Taslaklar için)
Nilvera::sendWhatsapp($uuid, '90555XXXXXXX');

// E-Arşiv Faturasını Mail İle Gönder
$mailResult = Nilvera::sendInvoiceEmail($uuid, ['musteri@firma.com']);

// E-Arşiv Faturasını SMS İle Gönder
Nilvera::sendInvoiceSms($uuid, '90555XXXXXXX');

// Mail Aktivitelerini Getir (processed, delivered, open, click, bounce vb.)
$mailActivities = Nilvera::getMailActivityHistories($mailResult['MessageId']);

// SMS İşlem Geçmişini Getir
$smsHistories = Nilvera::getSmsHistories($uuid);

// WhatsApp İşlem Geçmişini Getir
$whatsappHistories = Nilvera::getWhatsappHistories($uuid);

// Fatura Serilerini Yönet
$series = Nilvera::getSeries('einvoice');
Nilvera::createSeries('ABC', 'einvoice');

// Seriyi Güncelle (Aktif/Pasif veya Varsayılan Yapma)
Nilvera::updateSeries(1, isActive: true, isDefault: false, mode: 'einvoice');

// Seri Detayını Getir (Yıl, Sıra No, Son Kesim Tarihi vb.)
$seriesDetail = Nilvera::getSeriesDetail(1, 'einvoice');

// Yeni Etiket Oluştur
$tag = Nilvera::createTag('Önemli', 'Önemli faturalar', '#FF0000', 'einvoice');

// Etiketi Güncelle
Nilvera::updateTag($tag['UUID'], name: 'Çok Önemli', color: '#FF6600', mode: 'einvoice');

// Etiketi Sil
Nilvera::deleteTag($tag['UUID'], 'einvoice');

// Satış/Alış Faturaları İçin Bildirim Ayarları
// Property: DocumentProfile, DocumentType, TaxNumber, PayableAmount, TaxableAmount, AllowanceTotal, SendType
// Operator: GreaterOrEqual, SmallOrEqual, Equal, NotEqual, Between
// NotificationValueType: Whatsapp, Sms, Mail
$ruleData = [
    'RuleName' => 'Yüksek Tutarlı Faturalar',
    'Rules' => [
        ['Property' => 'PayableAmount', 'Operator' => 'GreaterOrEqual', 'ValueFirst' => '1000'],
    ],
    'Contact' => [
        ['NotificationValue' => 'ornek@firma.com', 'NotificationValueType' => 'Mail'],
    ],
    'IsActive' => true,
];

// Satış Faturası Bildirim Ayarlarını Listele
$saleSettings = Nilvera::getSaleNotificationSettings('einvoice');

// Alış Faturası Bildirim Ayarlarını Listele
$purchaseSettings = Nilvera::getPurchaseNotificationSettings('einvoice');

// Satış Faturası İçin Bildirim Kuralı Oluştur
$saleRule = Nilvera::createSaleNotificationSetting($ruleData, 'einvoice');

// Alış Faturası İçin Bildirim Kuralı Oluştur
$purchaseRule = Nilvera::createPurchaseNotificationSetting($ruleData, 'einvoice');

// Satış/Alış Bildirim Ayarını Güncelle
Nilvera::updateSaleNotificationSetting(array_merge($ruleData, ['ID' => $saleRule['ID']]), 'einvoice');
Nilvera::updatePurchaseNotificationSetting(array_merge($ruleData, ['ID' => $purchaseRule['ID']]), 'einvoice');

// Satış/Alış Bildirim Ayarını Sil
Nilvera::deleteSaleNotificationSetting($saleRule['ID'], 'einvoice');
Nilvera::deletePurchaseNotificationSetting($purchaseRule['ID'], 'einvoice');

// Fatura Taslak Şablonlarını Al
$templates = Nilvera::getTemplates('einvoice');

// Şablonu Güncelle (Ad, Aktiflik, Varsayılan)
Nilvera::updateTemplate(1, name: 'Yeni Şablon Adı', isActive: true, isDefault: false, mode: 'einvoice');

// Şablon Detayını Getir
$templateDetail = Nilvera::getTemplateDetail(1, 'einvoice');

// Şablonu Sil
Nilvera::deleteTemplate(1, 'einvoice');

// Şablonu Önizle (HTML/Byte İçerik Döner)
$preview = Nilvera::previewTemplate($uuid, 'einvoice');

// Özel Kod (SpecialCode) Güncelle
Nilvera::updateSpecialCode($uuids, 'PROJE-001', 'einvoice');

// Faturayı GİB üzerinden sorgula
Nilvera::queryFromGib($uuid, 'Sale');
```

---

## Konfigürasyon

`config/nilvera.php` dosyası üzerinden API bilgilerinizi ve varsayılan şirket bilgilerinizi yönetebilirsiniz.

```php
return [
    'base_url' => env('NILVERA_BASE_URL'),
    'api_key' => env('NILVERA_API_KEY'),
    'company' => [
        'name' => 'Varsayılan Şirket Adı',
        // ... diğer bilgiler
    ]
];
```
