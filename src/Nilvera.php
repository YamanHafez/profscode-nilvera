<?php

namespace ProfsCode\Nilvera;

use ProfsCode\Nilvera\Services\NilveraClient;
use ProfsCode\Nilvera\Services\InvoiceMapper;
use Carbon\Carbon;

class Nilvera
{
    protected NilveraClient $client;
    protected InvoiceMapper $mapper;

    public function __construct(NilveraClient $client, InvoiceMapper $mapper)
    {
        $this->client = $client;
        $this->mapper = $mapper;
    }

    public function debug(bool $state = true): self
    {
        $this->client->debug($state);
        return $this;
    }

    /**
     * Verileri Nilvera modeline çevirir.
     * 
     * @param array{
     *  TaxNumber: string,
     *  Title: string,
     *  Address: string,
     *  District: string,
     *  City: string,
     *  items: array<int, array{
     *      Name: string,
     *      Quantity: float,
     *      Price: float,
     *      VatRate: int,
     *      UnitCode: string,
     *      UnitName: string,
     *      Description: string
     *  }>,
     *  CurrencyCode: string,
     *  ExchangeRate: float,
     *  Series: string,
     *  Scenario: string,
     *  Notes: string|array
     * } $data
     * @param bool $isArchive
     */
    public function mapToModel(array $data, bool $isArchive = false): array
    {
        return $this->mapper->map($data, $isArchive);
    }

    // Kredi bilgilerini getirir
    public function getCredits(): array|string
    {
        return $this->client->get('General/Credits') ?? [];
    }

    // VKN sorgulama (E-fatura mükellefi mi?)
    public function checkTaxNumber(string $vkn): array|string
    {
        return $this->client->get("General/GlobalCompany/Check/TaxNumber/{$vkn}", ['globalUserType' => 'Invoice']) ?? [];
    }

    // Global müşteri detaylarını getirir
    public function getGlobalCustomerInfo(string $vkn): array|string
    {
        return $this->client->get("General/GlobalCompany/GetGlobalCustomerInfo/{$vkn}", ['globalUserType' => 'Invoice']) ?? [];
    }

    // Cari bilgilerini normalize eder
    public function getCustomerDetails(string $vkn): array
    {
        $checkData = $this->checkTaxNumber($vkn);
        $infoData = $this->getGlobalCustomerInfo($vkn);

        if (empty($checkData) && empty($infoData)) {
            return [
                'TaxNumber' => $vkn,
                'Title' => '',
                'ModuleType' => 'eArchive',
                'Aliases' => []
            ];
        }

        $isEInvoice = !empty($checkData);
        $aliases = [];

        // Collect aliases from checkData
        foreach ($checkData as $item) {
            if (!empty($item['Alias'])) {
                $aliases[$item['Alias']] = ['Name' => $item['Alias'], 'Type' => $item['Type'] ?? ''];
            }
        }

        // Collect aliases from infoData
        if (!empty($infoData['Aliases']) && is_array($infoData['Aliases'])) {
            foreach ($infoData['Aliases'] as $alias) {
                $aliasName = $alias['Name'] ?? $alias['Alias'] ?? '';
                if (!empty($aliasName)) {
                    $aliases[$aliasName] = ['Name' => $aliasName, 'Type' => $alias['Type'] ?? ''];
                }
            }
        }

        if (!empty($infoData['InvoiceAlias'])) {
            $aliases[$infoData['InvoiceAlias']] = ['Name' => $infoData['InvoiceAlias'], 'Type' => 'Invoice'];
        }
        if (!empty($infoData['DespatchAlias'])) {
            $aliases[$infoData['DespatchAlias']] = ['Name' => $infoData['DespatchAlias'], 'Type' => 'Despatch'];
        }

        // Determine Title
        $title = $infoData['CompanyName'] ?? $infoData['Title'] ?? '';
        if (empty($title) || is_numeric($title) || $title == $vkn) {
            if (!empty($checkData[0]['Title']) && !is_numeric($checkData[0]['Title'])) {
                $title = $checkData[0]['Title'];
            } elseif (!empty($infoData['Name']) && !empty($infoData['Surname'])) {
                $title = trim($infoData['Name'] . ' ' . $infoData['Surname']);
            } elseif (!empty($infoData['Name']) && !is_numeric($infoData['Name'])) {
                $title = $infoData['Name'];
            }
        }

        return [
            'TaxNumber' => $vkn,
            'Title' => $title ?: $vkn,
            'TaxDepartment' => $infoData['TaxOffice'] ?? $infoData['TaxDepartment'] ?? '',
            'Email' => $infoData['Mail'] ?? $infoData['Email'] ?? '',
            'ModuleType' => $isEInvoice ? 'eInvoice' : 'eArchive',
            'Aliases' => array_values($aliases),
            'Address' => $infoData['Address'] ?? '',
            'District' => $infoData['District'] ?? '',
            'City' => $infoData['City'] ?? '',
            'Country' => $infoData['Country'] ?? '',
            'PostalCode' => $infoData['PostalCode'] ?? '',
            'Phone' => $infoData['Phone'] ?? '',
        ];
    }

    // Firma bilgilerini getirir
    public function getCompanyInfo(): array|string
    {
        return $this->client->get('General/Company') ?? [];
    }

    // Firma bilgilerini güncelle
    public function updateCompanyInfo(array $data): array|string
    {
        return $this->client->put('General/Company', $data) ?? [];
    }

    // Firmanın modüllerini getirir
    public function getCompanyModules(): array|string
    {
        return $this->client->get('General/Company/Modules') ?? [];
    }

    // Firma sertifikalarını getirir
    public function getCompanyCertificates(): array|string
    {
        return $this->client->get('General/Company/Certificate') ?? [];
    }

    // Firmaya sertifika ekler
    public function addCompanyCertificate(array $data): bool|string
    {
        return $this->client->post('General/Company/Certificate', $data) ?? false;
    }

    // Firma sertifikasını güncelle
    public function updateCompanyCertificate(array $data): bool|string
    {
        return $this->client->put('General/Company/Certificate', $data) ?? false;
    }

    // Seri numarasına göre sertifika getirir
    public function getCompanyCertificateBySerial(string $serialNumber): array|string
    {
        return $this->client->get("General/Company/Certificate/{$serialNumber}") ?? [];
    }

    // Firma sertifikasını siler
    public function deleteCompanyCertificate(int $id): bool|string
    {
        return $this->client->delete("General/Company/Certificate/{$id}") ?? false;
    }

    // Kullanıcıya tanımlı firmaları getirir
    public function getUserCompanies(): array|string
    {
        return $this->client->get('General/Company/List') ?? [];
    }

    // Firma kimlik bilgilerini listeler
    public function getCompanyIdentities(): array|string
    {
        return $this->client->get('General/CompanyIdentification') ?? [];
    }

    // Firmaya yeni kimlik bilgisi ekler
    public function addCompanyIdentity(string $name, string $value): array|string
    {
        $query = http_build_query(['Name' => $name, 'Value' => $value]);
        return $this->client->post("General/CompanyIdentification?{$query}") ?? [];
    }

    // Firmadan kimlik bilgisi siler
    public function deleteCompanyIdentity(int $id): bool|string
    {
        return $this->client->delete("General/CompanyIdentification/{$id}") ?? false;
    }

    // Aktif kampanyaları getirir
    public function getCampaigns(): array|string
    {
        return $this->client->get('General/Campaigns') ?? [];
    }

    // GİB kullanıcı adı ve parolasını getirir
    public function getGibUserCredentials(): array|string
    {
        return $this->client->get('General/GibEArchiveAccount') ?? [];
    }

    // GİB kullanıcı adı ve parolasını güncelle
    public function updateGibUserCredentials(string $username, string $password): bool|string
    {
        $query = http_build_query(['UserName' => $username, 'Password' => $password]);
        return $this->client->put("General/GibEArchiveAccount?{$query}") ?? false;
    }

    // Mail/SMS/WhatsApp bildirim ayarlarını getirir
    public function getMailSettings(): array|string
    {
        return $this->client->get('General/Mailing/Setting') ?? [];
    }

    // WhatsApp özelliğini aç/kapat
    public function setWhatsappSetting(bool $isActive): bool|string
    {
        $state = $isActive ? 'true' : 'false';
        return $this->client->post("General/Mailing/Whatsapp/Setting/{$state}") ?? false;
    }

    // Mail özelliğini aç/kapat
    public function setMailSetting(bool $isActive): bool|string
    {
        $state = $isActive ? 'true' : 'false';
        return $this->client->post("General/Mailing/Email/Setting/{$state}") ?? false;
    }

    // SMS özelliğini aç/kapat
    public function setSmsSetting(bool $isActive): bool|string
    {
        $state = $isActive ? 'true' : 'false';
        return $this->client->post("General/Mailing/Sms/Setting/{$state}") ?? false;
    }

    // Döviz kurları
    public function getExchangeRates(): array|string
    {
        return $this->client->get('General/ExchangeRate') ?? [
            'USDBuy' => '0,0000',
            'USDSale' => '0,0000',
            'EUROBuy' => '0,0000',
            'EUROSale' => '0,0000',
            'GBPBuy' => '0,0000',
            'GBPSale' => '0,0000'
        ];
    }

    // Gelen son faturalar
    public function getLastInvoices(int $pageSize = 10, int $pageIndex = 1): array|string
    {
        return $this->client->get('einvoice/Statistics/Purchase/Last', [
            'PageSize' => $pageSize,
            'PageIndex' => $pageIndex,
        ]) ?? [];
    }

    // Giden son faturalar
    public function getLastSaleInvoices(string $mode = 'einvoice', int $pageSize = 10, int $pageIndex = 1): array|string
    {
        $endpoint = $mode === 'earchive' ? 'earchive/Statistics/Last' : 'einvoice/Statistics/Sale/Last';
        return $this->client->get($endpoint, [
            'PageSize' => $pageSize,
            'PageIndex' => $pageIndex,
        ]) ?? [];
    }

    /**
     * Get daily stats for purchases.
     */
    public function getPurchaseDailyStats(string $startDate, string $endDate): array|string
    {
        return $this->client->get('einvoice/Statistics/Purchase/Daily', [
            'StartDate' => $startDate,
            'EndDate' => $endDate,
        ]) ?? [];
    }

    /**
     * Get daily stats for sales.
     */
    public function getSaleDailyStats(string $startDate, string $endDate, string $mode = 'einvoice'): array|string
    {
        $endpoint = $mode === 'einvoice' ? 'einvoice/Statistics/Sale/Daily' : 'earchive/Statistics/Daily';
        return $this->client->get($endpoint, [
            'StartDate' => $startDate,
            'EndDate' => $endDate,
        ]) ?? [];
    }

    /**
     * Get summary stats for purchases.
     */
    public function getPurchaseSummaryStats(string $startDate, string $endDate): array|string
    {
        return $this->client->get('einvoice/Statistics/Purchase', [
            'StartDate' => $startDate,
            'EndDate' => $endDate,
        ]) ?? $this->getEmptySummaryStats();
    }

    /**
     * Get summary stats for sales.
     */
    public function getSaleSummaryStats(string $startDate, string $endDate, string $mode = 'einvoice'): array|string
    {
        $endpoint = $mode === 'einvoice' ? 'einvoice/Statistics/Sale' : 'earchive/Statistics';
        return $this->client->get($endpoint, [
            'StartDate' => $startDate,
            'EndDate' => $endDate,
        ]) ?? $this->getEmptySummaryStats();
    }

    // Serileri getir
    public function getSeries(string $mode = 'einvoice', ?string $search = null, int $pageSize = 100, int $page = 1): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        $queryParams = array_merge([
            'PageSize' => $pageSize,
            'IsActive' => "true",
            'Page' => $page
        ]);
        if (!is_null($search)) {
            $queryParams['Search'] = $search;
        }
        return $this->client->get("{$prefix}/Series", $queryParams)['Content'] ?? [];
    }

    // Yeni seri oluştur
    public function createSeries(string $name, string $mode = 'einvoice', bool $isActive = true, bool $isDefault = false): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->post("{$prefix}/Series", [
            'Name' => strtoupper($name),
            'IsActive' => $isActive,
            'IsDefault' => $isDefault
        ]) ?? [];
    }

    // Seriyi güncelle
    public function updateSeries(int $id, ?bool $isActive = null, ?bool $isDefault = null, string $mode = 'einvoice'): bool|string
    {
        $prefix = $this->getPrefixByMode($mode);

        $data = ['ID' => $id];

        if (!is_null($isActive)) {
            $data['IsActive'] = $isActive;
        }

        if (!is_null($isDefault)) {
            $data['IsDefault'] = $isDefault;
        }

        return $this->client->put("{$prefix}/Series", $data) ?? false;
    }

    // Seri detayını getir
    public function getSeriesDetail(int $id, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->get("{$prefix}/Series/{$id}") ?? [];
    }

    // Adres defterine ekle
    public function createCustomer(array $payload): array|string
    {
        // Nilvera expects an array of customers
        $data = isset($payload[0]) ? $payload : [$payload];
        return $this->client->post('General/Customers', $data) ?? [];
    }

    // Müşterileri listele
    public function getCustomers(
        ?string $search = null,
        int $pageSize = 30,
        int $page = 1,
        ?string $sortColumn = null,
        ?string $sortType = null
    ): array|string {
        $queryParams = [
            'PageSize' => $pageSize,
            'Page' => $page,
        ];

        if (!empty($search)) {
            $queryParams['Search'] = $search;
        }

        if (!empty($sortColumn)) {
            $queryParams['SortColumn'] = $sortColumn;
        }

        if (!empty($sortType)) {
            $queryParams['SortType'] = $sortType;
        }

        return $this->client->get('General/Customers', $queryParams) ?? [
            'Content' => [],
            'TotalCount' => 0
        ];
    }

    // Müşteri bilgilerini güncelle
    public function updateCustomer(array $data): bool|string
    {
        return $this->client->put('General/Customers', $data) ?? false;
    }

    // Müşteriyi ID ile getir
    public function getCustomerById(int $id): array|string
    {
        return $this->client->get("General/Customers/{$id}") ?? [];
    }

    // Müşteriyi sil
    public function deleteCustomer(int $id): bool|string
    {
        return $this->client->delete("General/Customers/{$id}") ?? false;
    }

    // Müşterileri toplu sil
    public function bulkDeleteCustomers(array $ids): bool|string
    {
        return $this->client->delete('General/Customers/Bulk', $ids) ?? false;
    }

    // Müşteri listesinde ara
    public function searchCustomers(string $searchText): array|string
    {
        return $this->client->get("General/Customers/Search/{$searchText}") ?? [];
    }

    // Vergi numarası ile müşteri bilgisini getir
    public function getCustomerByTaxNumber(string $taxNumber): array|string
    {
        return $this->client->get("General/Customers/GetCustomerInfo/{$taxNumber}") ?? [];
    }

    // Stokları listele
    public function getStocks(
        ?string $search = null,
        int $pageSize = 30,
        int $page = 1,
        ?string $sortColumn = null,
        ?string $sortType = null,
        ?bool $isActive = null
    ): array|string {
        $queryParams = [
            'PageSize' => $pageSize,
            'Page' => $page,
        ];

        if (!empty($search)) {
            $queryParams['Search'] = $search;
        }

        if (!empty($sortColumn)) {
            $queryParams['SortColumn'] = $sortColumn;
        }

        if (!empty($sortType)) {
            $queryParams['SortType'] = $sortType;
        }

        if (!is_null($isActive)) {
            $queryParams['IsActive'] = $isActive ? 'true' : 'false';
        }

        return $this->client->get('General/Stocks', $queryParams) ?? [
            'Content' => [],
            'TotalCount' => 0
        ];
    }

    // Stok ekle
    public function createStocks(array $payload): array|string
    {
        // Nilvera expects an array of stocks
        $data = isset($payload[0]) ? $payload : [$payload];
        return $this->client->post('General/Stocks', $data) ?? [];
    }

    // Stok bilgilerini güncelle
    public function updateStock(array $data): bool|string
    {
        return $this->client->put('General/Stocks', $data) ?? false;
    }

    // Stoğu ID ile getir
    public function getStockById(int $id): array|string
    {
        return $this->client->get("General/Stocks/{$id}") ?? [];
    }

    // Stoğu sil
    public function deleteStock(int $id): bool|string
    {
        return $this->client->delete("General/Stocks/{$id}") ?? false;
    }

    // Stokları toplu sil
    public function bulkDeleteStocks(array $ids): bool|string
    {
        return $this->client->delete('General/Stocks/Bulk', $ids) ?? false;
    }

    // Stok listesinde ara
    public function searchStocks(string $searchText): array|string
    {
        return $this->client->get("General/Stocks/SearchStock/{$searchText}") ?? [];
    }

    // Muhasebe raporu oluştur
    public function createReport(array $data): array|string
    {
        return $this->client->post('report/Accounting', $data) ?? [];
    }

    // Muhasebe raporlarını listele
    public function getReports(
        ?string $search = null,
        int $pageSize = 30,
        int $page = 1,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $reportType = null
    ): array|string {
        $queryParams = [
            'PageSize' => $pageSize,
            'Page' => $page,
        ];

        if (!empty($search)) {
            $queryParams['Search'] = $search;
        }

        if (!empty($startDate)) {
            $queryParams['StartDate'] = $startDate;
        }

        if (!empty($endDate)) {
            $queryParams['EndDate'] = $endDate;
        }

        if (!empty($reportType)) {
            $queryParams['ReportType'] = $reportType;
        }

        return $this->client->get('report/Accounting', $queryParams) ?? [
            'Content' => [],
            'TotalCount' => 0
        ];
    }

    // Raporu indir
    public function downloadReport(string $uuid): array|string
    {
        return $this->client->post("report/Accounting/{$uuid}/download") ?? [];
    }

    // Rapor şablonu oluştur
    public function createReportTemplate(array $data): array|string
    {
        return $this->client->post('report/Accounting/Template', $data) ?? [];
    }

    // Rapor şablonlarını listele
    public function getReportTemplates(): array|string
    {
        return $this->client->get('report/Accounting/Template') ?? [
            'Content' => [],
            'TotalCount' => 0
        ];
    }

    public function getTemplates(string $mode = 'einvoice', ?string $search = null, int $pageSize = 100, int $page = 1): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        $queryParams = [
            'PageSize' => $pageSize,
            'Page' => $page,
            'IsActive' => "true"
        ];

        if (!is_null($search)) {
            $queryParams['Search'] = $search;
        }

        return $this->client->get("{$prefix}/Templates", $queryParams)['Content'] ?? [];
    }

    // Şablonu güncelle
    public function updateTemplate(int $id, ?string $name = null, ?bool $isActive = null, ?bool $isDefault = null, string $mode = 'einvoice'): bool|string
    {
        $prefix = $this->getPrefixByMode($mode);

        $data = ['ID' => $id];

        if (!is_null($name)) {
            $data['Name'] = $name;
        }

        if (!is_null($isActive)) {
            $data['IsActive'] = $isActive;
        }

        if (!is_null($isDefault)) {
            $data['IsDefault'] = $isDefault;
        }

        return $this->client->put("{$prefix}/Templates", $data) ?? false;
    }

    // Şablon detayını getir
    public function getTemplateDetail(int $id, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->get("{$prefix}/Templates/{$id}") ?? [];
    }

    // Şablonu sil
    public function deleteTemplate(int $id, string $mode = 'einvoice'): bool|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->delete("{$prefix}/Templates/{$id}") ?? false;
    }

    // Şablonu önizle
    public function previewTemplate(string $uuid, string $mode = 'einvoice')
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->getRaw("{$prefix}/Templates/Preview/{$uuid}");
    }

    // Taslakları listele
    public function getDraftInvoices(
        string $mode = 'einvoice',
        ?string $search = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $pageSize = 30,
        int $page = 1
    ): array|string {
        $prefix = $this->getPrefixByMode($mode);

        $queryParams = [
            'Page' => $page,
            'PageSize' => $pageSize,
        ];

        if (!empty($search)) {
            $queryParams['Search'] = $search;
        }

        if (!empty($startDate)) {
            $queryParams['StartDate'] = str_contains($startDate, 'T') ? $startDate : $startDate . 'T00:00:00.000Z';
        }

        if (!empty($endDate)) {
            $queryParams['EndDate'] = str_contains($endDate, 'T') ? $endDate : $endDate . 'T23:59:59.998Z';
        }

        return $this->client->get("{$prefix}/Draft", $queryParams)['Content'] ?? [
            'Content' => [],
            'TotalCount' => 0
        ];
    }

    // Fatura HTML görseli
    public function getInvoiceHtml(string $uuid, string $mode = 'einvoice', ?string $direction = null)
    {
        $prefix = $this->getPrefixByMode($mode);
        $path = $direction ? "{$prefix}/{$direction}/{$uuid}/html" : "{$prefix}/Draft/{$uuid}/html";
        return $this->client->getRaw($path);
    }

    // Fatura ekleri
    public function getInvoiceAttachments(string $uuid, string $direction = 'Sale', string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/{$direction}/{$uuid}/Attachments") ?? [];
    }

    // Fatura detayları
    public function getInvoiceDetails(string $uuid, string $direction = 'Sale', string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/{$direction}/{$uuid}/Details") ?? [];
    }

    /**
     * Get despatch info of an invoice.
     */
    public function getInvoiceDespatchInfo(string $uuid, string $direction = 'Sale', string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/{$direction}/{$uuid}/DespatchInfo") ?? [];
    }

    /**
     * Get history of an invoice.
     */
    public function getInvoiceHistories(string $uuid, string $direction = 'Sale', string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/{$direction}/{$uuid}/Histories") ?? [];
    }

    // Fatura vergi detaylarını getir
    public function getInvoiceTaxes(string $uuid, string $direction = 'Sale', string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        $segment = $mode === 'earchive' ? 'Invoices' : $direction;
        return $this->client->get("{$prefix}/{$segment}/{$uuid}/Taxes") ?? [];
    }

    // Gelen faturaya yanıt ver (kabul/red)
    public function answerInvoice(string $uuid, string $answerCode, ?string $rejectNote = null, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        $data = [
            'UUID' => $uuid,
            'AnswerCode' => $answerCode,
        ];

        if (!is_null($rejectNote)) {
            $data['RejectNote'] = $rejectNote;
        }

        return $this->client->post("{$prefix}/Purchase/SendAnswer", $data) ?? [];
    }

    // Red cevabını getir
    public function getInvoiceRejectedNote(string $uuid, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->get("{$prefix}/Purchase/{$uuid}/RejectedNote") ?? [];
    }

    // Faturanın güncel statü bilgisini getir
    public function getInvoiceStatus(string $uuid, string $direction = 'Sale', string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        $segment = $mode === 'earchive' ? 'Invoices' : $direction;
        return $this->client->get("{$prefix}/{$segment}/{$uuid}/Status") ?? [];
    }

    // Toplu dışa aktarma (PDF, XML, Zarf) — e-Fatura ve e-Arşiv destekler
    public function exportInvoices(array $uuids, string $format = 'Pdf', string $direction = 'Sale', string $mode = 'einvoice'): ?string
    {
        $prefix = $this->getPrefixByMode($mode);
        $segment = $mode === 'earchive' ? 'Invoices' : $direction;
        $response = $this->client->post("{$prefix}/{$segment}/Export/{$format}", $uuids);

        // Handle raw response for binary data
        if (is_array($response) && (isset($response['Content']) || isset($response['Result']))) {
            $content = $response['Content'] ?? $response['Result'];
        } else {
            $content = $response;
        }

        if (is_string($content)) {
            // Nilvera returns base64 string, sometimes quoted
            $content = trim($content, '" ');
            $cleanContent = preg_replace('/[^a-zA-Z0-9\/\+=]/', '', $content);
            return base64_decode($cleanContent);
        }

        return $content;
    }

    /**
     * Get PDF content (binary) of a draft invoice.
     */
    public function getInvoicePdf(string $uuid, string $mode = 'einvoice'): ?string
    {
        $prefix = $this->getPrefixByMode($mode);
        $base64 = $this->client->getRaw("{$prefix}/Draft/{$uuid}/pdf");

        if (empty($base64)) {
            return null;
        }

        $base64 = trim($base64, '" ');
        return base64_decode($base64);
    }

    /**
     * Gönderilmiş faturanın PDF içeriğini döndürür (binary).
     * e-Arşiv: EArchive/Invoices/{uuid}/pdf
     * e-Fatura: EInvoice/{direction}/{uuid}/pdf
     */
    public function getSentInvoicePdf(string $uuid, string $mode = 'einvoice', string $direction = 'Sale'): ?string
    {
        $prefix = $this->getPrefixByMode($mode);
        $segment = $mode === 'earchive' ? 'Invoices' : $direction;
        $base64 = $this->client->getRaw("{$prefix}/{$segment}/{$uuid}/pdf");

        if (empty($base64)) {
            return null;
        }

        $base64 = trim($base64, '" ');
        return base64_decode($base64);
    }

    /**
     * Gönderilmiş faturanın XML içeriğini döndürür (binary).
     * e-Arşiv: EArchive/Invoices/{uuid}/xml
     * e-Fatura: EInvoice/{direction}/{uuid}/xml
     */
    public function getSentInvoiceXml(string $uuid, string $mode = 'einvoice', string $direction = 'Sale'): ?string
    {
        $prefix = $this->getPrefixByMode($mode);
        $segment = $mode === 'earchive' ? 'Invoices' : $direction;
        $base64 = $this->client->getRaw("{$prefix}/{$segment}/{$uuid}/xml");

        if (empty($base64)) {
            return null;
        }

        $base64 = trim($base64, '" ');
        return base64_decode($base64);
    }

    // Taslak oluştur
    public function createDraft(array $model, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->post("{$prefix}/Draft", $model) ?? [];
    }

    /**
     * Preview an invoice model (returns HTML).
     */
    public function previewInvoice(array $model, string $mode = 'einvoice')
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->post("{$prefix}/Send/Model/Preview", $model);
    }

    /**
     * Download an invoice model (returns XML/UBL).
     */
    public function downloadInvoice(array $model, string $mode = 'einvoice')
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->post("{$prefix}/Send/Model/Download", $model);
    }

    /**
     * Download the PDF of a previewed invoice model.
     */
    public function downloadInvoicePdf(array $model, string $mode = 'einvoice')
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->postRaw("{$prefix}/Send/Model/Download/Pdf", $model);
    }

    // Faturayı Base64 (ZIP) olarak gönder
    public function sendInvoiceBase64(string $zipFileBase64, string $alias, ?string $templateUuid = null, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        $data = [
            'ZIPFileBase64' => $zipFileBase64,
            'Alias' => $alias,
        ];

        if (!is_null($templateUuid)) {
            $data['TemplateUUID'] = $templateUuid;
        }

        return $this->client->post("{$prefix}/Send/Base64String", $data) ?? [];
    }

    // Base64 (ZIP) faturayı önizle
    public function previewInvoiceBase64(string $zipFileBase64, string $alias, string $mode = 'einvoice')
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->postRaw("{$prefix}/Send/Base64String/Preview", [
            'ZIPFileBase64' => $zipFileBase64,
            'Alias' => $alias,
        ]);
    }

    // Yeni fatura oluştur ve gönder
    public function sendNewInvoice(array $model, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->post("{$prefix}/Send/Model", $model) ?? [];
    }

    // Taslağı onayla ve gönder
    public function sendInvoice(string $uuid, ?string $alias = null, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        // User snippet uses Draft/EditAndSend for confirmation/sending
        return $this->client->post("{$prefix}/Draft/EditAndSend", ['UUID' => $uuid]) ?? [];
    }

    // Toplu gönderim
    public function bulkSendInvoices(array $invoices, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        // If it's a single invoice, use the EditAndSend logic if preferred, 
        // but bulk usually uses ConfirmAndSend
        $payload = $invoices;
        if ($mode === 'earchive') {
            $payload = array_column($invoices, 'UUID');
        }

        return $this->client->post("{$prefix}/Draft/ConfirmAndSend", $payload) ?? [];
    }

    // Taslakları sil
    public function bulkDeleteInvoices(array $uuids, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->delete("{$prefix}/Draft", $uuids) ?? [];
    }

    /**
     * Get the data model of a draft invoice.
     */
    public function getDraftModel(string $uuid, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/Draft/{$uuid}/model") ?? [];
    }

    /**
     * Get all data required for editing a draft invoice.
     */
    public function getEditInvoiceData(string $uuid, string $mode = 'einvoice'): array
    {
        return [
            'invoice' => $this->getDraftModel($uuid, $mode),
            'series' => $this->getSeries($mode),
            'templates' => $this->getTemplates($mode),
            'invoiceType' => $mode,
            'uuid' => $uuid
        ];
    }

    // XML/UBL yükle
    public function uploadXml(string $filePath, string $fileName, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->upload("{$prefix}/Upload", $filePath, $fileName) ?? [];
    }

    /**
     * Bulk create draft invoices (commonly used for E-Archive).
     */
    public function createBulkDraft(array $invoices, string $mode = 'earchive'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        $payload = [
            'EArchiveInvoices' => $invoices
        ];

        // E-Invoice bulk structure might differ, currently porting user's E-Archive logic
        if ($mode === 'einvoice') {
            $payload = ['EInvoices' => $invoices];
        }

        return $this->client->post("{$prefix}/Draft/CreateBulk", $payload) ?? [];
    }

    // SGK PDF yükle
    public function uploadSgkPdf(string $filePath, string $fileName, string $type = 'SAGLIK_ECZ', string $registerCode = ''): array|string
    {
        return $this->client->upload('EInvoice/Upload/Sgk', $filePath, $fileName, [
            'Type' => $type,
            'RegisterCode' => $registerCode
        ]) ?? [];
    }

    // Gelen/Giden fatura listesi (Filtreli)
    public function getInvoices(
        string $direction = 'Sale',
        ?string $search = null,
        ?string $startDate = null,
        ?string $endDate = null,
        int $pageSize = 30,
        int $page = 1,
        array $extraParams = []
    ): array|string {
        $prefix = 'EInvoice'; // Based on user snippet for outbox

        // Build base query
        $queryParams = [
            'PageSize' => $pageSize,
            'Page' => $page,
        ];

        if (!empty($search)) {
            $queryParams['Search'] = $search;
        }

        if (!empty($startDate)) {
            $queryParams['StartDate'] = str_contains($startDate, 'T') ? $startDate : $startDate . 'T00:00:00.000Z';
        }

        if (!empty($endDate)) {
            $queryParams['EndDate'] = str_contains($endDate, 'T') ? $endDate : $endDate . 'T23:59:59.998Z';
        }

        // Merge with other potential parameters
        $baseParams = array_merge($queryParams, collect($extraParams)->except([
            'StatusCode',
            'InvoiceProfile',
            'InvoiceType',
            'AnswerCode',
            'IsTransfer'
        ])->toArray());

        $queryString = http_build_query($baseParams);

        // Handle repeated keys
        $filters = [
            'StatusCode' => $extraParams['StatusCode'] ?? ['succeed', 'error', 'waiting'],
            'InvoiceProfile' => $extraParams['InvoiceProfile'] ?? ['TEMELFATURA', 'TICARIFATURA', 'HKS', 'KAMU', 'IHRACAT', 'ENERJI', 'ILAC_TIBBICIHAZ', 'YATIRIMTESVIK', 'IDIS'],
            'InvoiceType' => $extraParams['InvoiceType'] ?? ['SATIS', 'IADE', 'TEVKIFAT', 'TEVKIFATIADE', 'ISTISNA', 'IHRACKAYITLI', 'OZELMATRAH', 'SGK', 'KOMISYONCU', 'KONAKLAMAVERGISI', 'SARJ', 'SARJANLIK'],
            'AnswerCode' => $extraParams['AnswerCode'] ?? ['approved', 'documentAnsweredAutomatically', 'rejected', 'waitingForApproval'],
        ];

        foreach ($filters as $key => $values) {
            $values = is_array($values) ? $values : [$values];
            foreach (array_unique($values) as $val) {
                $queryString .= "&{$key}=" . urlencode($val);
            }
        }

        if (isset($extraParams['IsTransfer'])) {
            $queryString .= "&IsTransfer=" . urlencode($extraParams['IsTransfer']);
        }

        return $this->client->get("{$prefix}/{$direction}?" . $queryString) ?? [];
    }

    // Etiketleri getir
    public function getTags(string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/Tags", ['PageSize' => 150, 'Page' => 1]) ?? [];
    }

    /**
     * Update tags for a document.
     */
    public function updateTags(string $uuid, array $tags, string $status = 'Draft', string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->put("{$prefix}/{$status}/Tags", [
            'DocumentUUID' => $uuid,
            'Tags' => $tags
        ]) ?? [];
    }

    // Yeni etiket oluştur
    public function createTag(string $name, ?string $description = null, ?string $color = null, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        $data = ['Name' => $name];

        if (!is_null($description)) {
            $data['Description'] = $description;
        }

        if (!is_null($color)) {
            $data['Color'] = $color;
        }

        return $this->client->post("{$prefix}/Tags", $data) ?? [];
    }

    // Etiketi güncelle
    public function updateTag(string $uuid, ?string $name = null, ?string $description = null, ?string $color = null, string $mode = 'einvoice'): bool|string
    {
        $prefix = $this->getPrefixByMode($mode);

        $data = ['UUID' => $uuid];

        if (!is_null($name)) {
            $data['Name'] = $name;
        }

        if (!is_null($description)) {
            $data['Description'] = $description;
        }

        if (!is_null($color)) {
            $data['Color'] = $color;
        }

        return $this->client->put("{$prefix}/Tags", $data) ?? false;
    }

    // Etiketi sil
    public function deleteTag(string $uuid, string $mode = 'einvoice'): bool|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->delete("{$prefix}/Tags/{$uuid}") ?? false;
    }

    // Satış faturalarına ait bildirim ayarlarını listele
    public function getSaleNotificationSettings(string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->get("{$prefix}/Notification/Sale") ?? [];
    }

    // Alış faturalarına ait bildirim ayarlarını listele
    public function getPurchaseNotificationSettings(string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->get("{$prefix}/Notification/Purchase") ?? [];
    }

    // Satış faturasına ait bildirim kuralı oluştur
    public function createSaleNotificationSetting(array $data, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->post("{$prefix}/Notification/Sale", $data) ?? [];
    }

    // Alış faturasına ait bildirim kuralı oluştur
    public function createPurchaseNotificationSetting(array $data, string $mode = 'einvoice'): array|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->post("{$prefix}/Notification/Purchase", $data) ?? [];
    }

    // Satış faturasına ait bildirim ayarını güncelle
    public function updateSaleNotificationSetting(array $data, string $mode = 'einvoice'): bool|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->put("{$prefix}/Notification/Sale", $data) ?? false;
    }

    // Alış faturasına ait bildirim ayarını güncelle
    public function updatePurchaseNotificationSetting(array $data, string $mode = 'einvoice'): bool|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->put("{$prefix}/Notification/Purchase", $data) ?? false;
    }

    // Satış faturasına ait bildirim ayarını sil
    public function deleteSaleNotificationSetting(int $id, string $mode = 'einvoice'): bool|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->delete("{$prefix}/Notification/Sale/{$id}") ?? false;
    }

    // Alış faturasına ait bildirim ayarını sil
    public function deletePurchaseNotificationSetting(int $id, string $mode = 'einvoice'): bool|string
    {
        $prefix = $this->getPrefixByMode($mode);

        return $this->client->delete("{$prefix}/Notification/Purchase/{$id}") ?? false;
    }

    // Toplu arşivleme
    public function archiveInvoices(array $uuids, string $direction = 'Sale', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        $endpoint = ($mode === 'earchive') ? 'Invoices' : $direction;
        return $this->client->put("{$prefix}/{$endpoint}/Operation/Archived", $uuids) ?? [];
    }

    /**
     * Create draft(s) from existing invoice(s).
     */
    public function createDraftFromInvoices(array $uuids, string $direction = 'Sale', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        $endpoint = ($mode === 'earchive') ? 'Invoices' : $direction;

        if (count($uuids) === 1) {
            return $this->client->post("{$prefix}/{$endpoint}/{$uuids[0]}/CreateDraft") ?? [];
        }

        return $this->client->post("{$prefix}/{$endpoint}/Bulk/Draft", $uuids) ?? [];
    }

    /**
     * Update special code for invoices (supports bulk).
     */
    public function updateSpecialCode($uuids, string $specialCode, string $mode = 'einvoice', string $direction = 'Sale'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        // E-Archive might use Sale or Invoices depending on the specific API version, 
        // usually E-Invoice uses Sale/Purchase
        return $this->client->put("{$prefix}/{$direction}/SpecialCode", [
            'UUID' => is_array($uuids) ? $uuids : [$uuids],
            'SpecialCode' => $specialCode
        ]) ?? [];
    }

    // Aktarıldı olarak işaretle
    public function markAsTransferred(array $uuids, string $direction = 'Sale', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        $endpoint = ($mode === 'earchive') ? 'Invoices' : $direction;
        return $this->client->put("{$prefix}/{$endpoint}/Operation/Transferred", $uuids) ?? [];
    }

    /**
     * Mark invoices as untransferred.
     */
    public function markAsUntransferred(array $uuids, string $direction = 'Sale', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        $endpoint = ($mode === 'earchive') ? 'Invoices' : $direction;
        return $this->client->put("{$prefix}/{$endpoint}/Operation/Untransferred", $uuids) ?? [];
    }

    /**
     * Mark incoming invoices as read.
     */
    public function markAsRead(array $uuids, string $direction = 'Purchase', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        $endpoint = ($mode === 'earchive') ? 'Invoices' : $direction;
        return $this->client->put("{$prefix}/{$endpoint}/Operation/Read", $uuids) ?? [];
    }

    /**
     * Mark invoices as unread.
     */
    public function markAsUnread(array $uuids, string $direction = 'Purchase', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        $endpoint = ($mode === 'earchive') ? 'Invoices' : $direction;
        return $this->client->put("{$prefix}/{$endpoint}/Operation/Unread", $uuids) ?? [];
    }

    // Taslaklara toplu yeni durum ata (örn. Print, UnPrint, Transferred, Untransferred)
    public function updateDraftsStatus(array $uuids, string $operationType, string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->put("{$prefix}/Draft/Operation/{$operationType}", $uuids) ?? [];
    }

    // İade faturası oluştur
    public function createReturnInvoice(string $uuid, string $direction = 'Purchase'): array
    {
        return $this->client->post("EInvoice/{$direction}/{$uuid}/CreateReturn") ?? [];
    }

    // GİB üzerinden sorgula
    public function queryFromGib($uuids, string $direction = 'Sale'): array
    {
        if (is_array($uuids) && count($uuids) > 1) {
            return $this->client->put("EInvoice/{$direction}/Operation/QueryFromGIB", $uuids) ?? [];
        }

        $uuid = is_array($uuids) ? $uuids[0] : $uuids;
        return $this->client->get("EInvoice/{$direction}/{$uuid}/CheckFromGib") ?? [];
    }

    // Eski faturaları getir
    public function getOldInvoices(string $mode = 'einvoice', array $params = []): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/Old", $params) ?? [];
    }

    // Eski faturanın HTML'ini getir
    public function getOldInvoiceHtml(string $uuid, string $mode = 'einvoice')
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->getRaw("{$prefix}/Old/{$uuid}/html");
    }

    // Eski faturanın PDF'ini getir
    public function getOldInvoicePdf(string $uuid, string $mode = 'einvoice'): ?string
    {
        $prefix = $this->getPrefixByMode($mode);
        $base64 = $this->client->getRaw("{$prefix}/Old/{$uuid}/pdf");

        if (empty($base64)) {
            return null;
        }

        $base64 = trim($base64, '" ');
        return base64_decode($base64);
    }

    // Eski faturanın XML'ini getir
    public function getOldInvoiceXml(string $uuid, string $mode = 'einvoice'): ?string
    {
        $prefix = $this->getPrefixByMode($mode);
        $base64 = $this->client->getRaw("{$prefix}/Old/{$uuid}/xml");

        if (empty($base64)) {
            return null;
        }

        $base64 = trim($base64, '" ');
        return base64_decode($base64);
    }

    // Eski faturaları toplu dışa aktar (Xml, Pdf, OnePagePdf)
    public function exportOldInvoices(array $uuids, string $fileType = 'Pdf', string $mode = 'einvoice'): ?string
    {
        $prefix = $this->getPrefixByMode($mode);
        $content = $this->client->postRaw("{$prefix}/Old/Export/{$fileType}", $uuids);

        if (empty($content)) {
            return null;
        }

        $content = trim($content, '" ');
        $cleanContent = preg_replace('/[^a-zA-Z0-9\/\+=]/', '', $content);
        return base64_decode($cleanContent);
    }

    // Eski faturalara toplu yeni durum ata (UnPrint, Print, Transferred, Untransferred)
    public function updateOldInvoicesStatus(array $uuids, string $operationType, string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->put("{$prefix}/Old/Operation/{$operationType}", $uuids) ?? [];
    }

    /**
     * Get E-Archive invoices.
     */
    public function getEArchiveInvoices(array $params = []): array
    {
        return $this->client->get('EArchive/Invoices', $params) ?? [];
    }

    /**
     * Get GIB E-Archive incoming invoices.
     */
    public function getGibInvoices(array $params = []): array
    {
        return $this->client->get('Gib/EArchive/Purchase', $params) ?? [];
    }

    /**
     * Cancel an E-Archive invoice.
     */
    public function cancelEArchiveInvoice(string $uuid): array
    {
        return $this->client->put("EArchive/Invoices/{$uuid}/Cancel") ?? [];
    }

    // Faturanın iptal işlemini geri al
    public function revertCancelEArchiveInvoice(string $uuid): bool|array
    {
        return $this->client->put("EArchive/Invoices/{$uuid}/RevertCancel") ?? false;
    }

    // E-Arşiv raporu gönder (dönem bazlı)
    public function sendEArchiveReport(int $periodYear, int $periodMonth): array|string
    {
        return $this->client->post('EArchive/Send/Report', [
            'PeriodYear' => $periodYear,
            'PeriodMonth' => $periodMonth,
        ]) ?? [];
    }

    // Gönderilen E-Arşiv raporlarını listele
    public function getEArchiveReports(
        int $pageSize = 30,
        int $page = 1,
        ?string $sortColumn = null,
        ?string $sortType = null,
        ?int $periodYear = null,
        ?int $periodMonth = null
    ): array|string {
        $queryParams = [
            'PageSize' => $pageSize,
            'Page' => $page,
        ];

        if (!empty($sortColumn)) {
            $queryParams['SortColumn'] = $sortColumn;
        }

        if (!empty($sortType)) {
            $queryParams['SortType'] = $sortType;
        }

        if (!is_null($periodYear)) {
            $queryParams['PeriodYear'] = $periodYear;
        }

        if (!is_null($periodMonth)) {
            $queryParams['PeriodMonth'] = $periodMonth;
        }

        return $this->client->get('EArchive/Report', $queryParams) ?? [
            'Content' => [],
            'TotalCount' => 0
        ];
    }

    // Raporlanabilecek (rapora dahil edilebilecek) belgeleri listele
    public function getEArchiveReportableDocuments(
        int $pageSize = 30,
        int $page = 1,
        ?string $search = null,
        ?int $periodYear = null,
        ?int $periodMonth = null
    ): array|string {
        $queryParams = [
            'PageSize' => $pageSize,
            'Page' => $page,
        ];

        if (!empty($search)) {
            $queryParams['Search'] = $search;
        }

        if (!is_null($periodYear)) {
            $queryParams['PeriodYear'] = $periodYear;
        }

        if (!is_null($periodMonth)) {
            $queryParams['PeriodMonth'] = $periodMonth;
        }

        return $this->client->get('EArchive/Report/ToReport', $queryParams) ?? [
            'Content' => [],
            'TotalCount' => 0
        ];
    }

    // Gönderilmiş bir rapordaki belgeleri listele
    public function getEArchiveReportDocuments(string $uuid, int $pageSize = 30, int $page = 1): array|string
    {
        return $this->client->get('EArchive/Report/List', [
            'UUID' => $uuid,
            'PageSize' => $pageSize,
            'Page' => $page,
        ]) ?? [
            'Content' => [],
            'TotalCount' => 0
        ];
    }

    // Rapor XML içeriğini indir
    public function downloadEArchiveReportXml(string $uuid)
    {
        return $this->client->getRaw("EArchive/Report/{$uuid}/xml");
    }

    // Raporun işlem geçmişini getir
    public function getEArchiveReportHistories(string $uuid): array|string
    {
        return $this->client->get("EArchive/Report/{$uuid}/Histories") ?? [];
    }

    // WhatsApp ile gönder
    public function sendWhatsapp(string $uuid, string $phone, string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->post("{$prefix}/Draft/WhatsApp/Send", [
            'UUID' => $uuid,
            'Phone' => $phone
        ]) ?? [];
    }

    // E-Arşiv faturasını mail ile gönder
    public function sendInvoiceEmail(string $uuid, array $emailAddresses): array|string
    {
        return $this->client->post('EArchive/Invoices/Email/Send', [
            'UUID' => $uuid,
            'EmailAddresses' => $emailAddresses,
        ]) ?? [];
    }

    // E-Arşiv faturasını SMS ile gönder
    public function sendInvoiceSms(string $uuid, string $phone): bool|array|string
    {
        return $this->client->post('EArchive/Invoices/Sms/Send', [
            'UUID' => $uuid,
            'Phone' => $phone,
        ]) ?? [];
    }

    // Mail aktivitelerini getir
    public function getMailActivityHistories(string $messageId): array|string
    {
        return $this->client->get("EArchive/Invoices/{$messageId}/MailActivityhistories") ?? [];
    }

    // SMS işlem geçmişini getir
    public function getSmsHistories(string $uuid): array|string
    {
        return $this->client->get("EArchive/Invoices/{$uuid}/Smshistories") ?? [];
    }

    // WhatsApp işlem geçmişini getir
    public function getWhatsappHistories(string $uuid): array|string
    {
        return $this->client->get("EArchive/Invoices/{$uuid}/Whatsapphistories") ?? [];
    }

    /**
     * Get XML content of a draft invoice.
     */
    public function getDraftXml(string $uuid, string $mode = 'einvoice'): ?string
    {
        $prefix = $this->getPrefixByMode($mode);
        // Note: XML endpoint might return raw XML string
        return $this->client->get("{$prefix}/Draft/{$uuid}/xml");
    }

    /**
     * Default empty summary stats structure.
     */
    protected function getEmptySummaryStats(): array
    {
        return [
            'BasicCount' => 0,
            'CommercialCount' => 0,
            'ApprovalPendingCount' => 0,
            'ApprovedCount' => 0,
            'RejectedCount' => 0,
            'IncorrectCount' => 0,
            'SuccessfullCount' => 0,
            'PendingCount' => 0,
            'TotalCount' => 0
        ];
    }

    /**
     * Get Dashboard Data (The "Clean" version of the user request)
     */
    public function getDashboardData(string $mode = 'einvoice', array $params = []): array
    {
        $summaryPeriod = $params['SummaryPeriod'] ?? 'today';
        $dates = $this->resolveDates($summaryPeriod);

        $start_date2 = $params['start_date2'] ?? now()->subWeek()->format('Y-m-d');
        $end_date2 = $params['end_date2'] ?? now()->format('Y-m-d');

        // Fetch Data
        $credits = $this->getCredits();
        $rates = $this->getExchangeRates();

        $pageSize = $params['PageSize'] ?? 10;
        $pageIndex = $params['PageIndex'] ?? 1;

        $lastInvoices = ($mode === 'einvoice') ? $this->getLastInvoices($pageSize, $pageIndex) : [];
        $lastSaleInvoices = $this->getLastSaleInvoices($mode, $pageSize, $pageIndex);

        $purchaseDailyStats = ($mode === 'einvoice') ? $this->getPurchaseDailyStats($start_date2, $end_date2) : [];
        $saleDailyStats = $this->getSaleDailyStats($start_date2, $end_date2, $mode);

        $purchaseSummaryStats = ($mode === 'einvoice') ? $this->getPurchaseSummaryStats($dates['start'], $dates['end']) : $this->getEmptySummaryStats();
        $saleSummaryStats = $this->getSaleSummaryStats($dates['start'], $dates['end'], $mode);

        // Map credits
        $mappedCredits = collect($credits)->keyBy(fn($item) => strtolower($item['Name']))->toArray();

        // Calculate Totals
        $pTotal = ($mode === 'einvoice') ? ($purchaseSummaryStats['SuccessfullCount'] ?? 0) : 0;
        $sTotal = 0;
        if ($mode === 'einvoice') {
            $sTotal = ($saleSummaryStats['SuccessfullCount'] ?? 0) + ($saleSummaryStats['IncorrectCount'] ?? 0) + ($saleSummaryStats['PendingCount'] ?? 0);
        } else {
            $sTotal = $saleSummaryStats['TotalCount'] ?? 0;
        }

        return [
            'credits' => $mappedCredits,
            'rates' => $rates,
            'lastInvoices' => $lastInvoices,
            'lastSaleInvoices' => $lastSaleInvoices,
            'purchaseDailyStats' => $purchaseDailyStats,
            'saleDailyStats' => $saleDailyStats,
            'summaryTotals' => [
                'purchaseCount' => $pTotal,
                'saleCount' => $sTotal,
                'total' => $pTotal + $sTotal
            ],
            'chartTotals' => [
                'purchaseCount' => array_sum(array_column($purchaseDailyStats, 'Count')),
                'saleCount' => array_sum(array_column($saleDailyStats, 'Count')),
                'purchaseAmount' => array_sum(array_column($purchaseDailyStats, 'PayableAmount')),
                'saleAmount' => array_sum(array_column($saleDailyStats, 'PayableAmount')),
            ],
            'start_date2' => $start_date2,
            'end_date2' => $end_date2,
            'summaryPeriod' => $summaryPeriod,
            'purchaseSummary' => $purchaseSummaryStats,
            'saleSummary' => $saleSummaryStats
        ];
    }

    /**
     * Resolve start and end dates based on period string.
     */
    protected function resolveDates(string $period): array
    {
        $now = now();

        $start = match ($period) {
            'month' => $now->copy()->startOfMonth(),
            'week' => $now->copy()->startOfWeek(),
            'yesterday' => $now->copy()->subDay()->startOfDay(),
            default => $now->copy()->startOfDay(),
        };

        $end = match ($period) {
            'yesterday' => $now->copy()->subDay()->endOfDay(),
            default => $now->copy(),
        };

        return [
            'start' => $start->toISOString(),
            'end' => $end->toISOString(),
        ];
    }
    /**
     * Get data required for creating a new invoice.
     */
    public function getNewInvoiceData(string $mode = 'einvoice', string $type = 'service'): array
    {
        return [
            'rates' => $this->getExchangeRates(),
            'series' => $this->getSeries($mode),
            'templates' => $this->getTemplates($mode),
            'invoiceType' => $type,
        ];
    }

    /**
     * Helper to get prefix based on mode.
     */
    protected function getPrefixByMode(string $mode): string
    {
        return match ($mode) {
            'einvoice' => 'EInvoice',
            'earchive' => 'EArchive',
            'despatch' => 'EDespatch',
            default => 'EInvoice',
        };
    }

    /**
     * Helper to get endpoint based on mode.
     */
    protected function getEndpointByMode(string $mode, string $type): string
    {
        if ($mode === 'earchive') {
            return "EArchive/Invoices";
        }

        $prefix = $this->getPrefixByMode($mode);
        return "{$prefix}/{$type}";
    }
}
