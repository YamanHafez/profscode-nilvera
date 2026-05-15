# ProfsCode Nilvera Laravel Paketi Kullanım Kılavuzu

Bu paket, Nilvera API'si ile E-Fatura, E-Arşiv ve E-İrsaliye süreçlerinizi Laravel üzerinde kolayca yönetmenizi sağlar.

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

// Fatura Serilerini Yönet
$series = Nilvera::getSeries('einvoice');
Nilvera::createSeries('ABC', 'einvoice');

// Fatura Taslak Şablonlarını Al
$templates = Nilvera::getTemplates('einvoice');

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
