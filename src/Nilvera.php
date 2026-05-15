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
    public function getCredits(): array
    {
        return $this->client->get('General/GetCredits') ?? [];
    }

    // VKN sorgulama (E-fatura mükellefi mi?)
    public function checkTaxNumber(string $vkn): array
    {
        return $this->client->get("General/GlobalCompany/Check/TaxNumber/{$vkn}", ['globalUserType' => 'Invoice']) ?? [];
    }

    // Global müşteri detaylarını getirir
    public function getGlobalCustomerInfo(string $vkn): array
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

    // Döviz kurları
    public function getExchangeRates(): array
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
    public function getLastInvoices(int $pageSize = 10, int $pageIndex = 1): array
    {
        return $this->client->get('EInvoice/GetInvoices', [
            'PageSize' => $pageSize,
            'PageIndex' => $pageIndex,
        ])['Items'] ?? [];
    }

    // Giden son faturalar
    public function getLastSaleInvoices(string $mode = 'einvoice', int $pageSize = 10, int $pageIndex = 1): array
    {
        $endpoint = $this->getEndpointByMode($mode, 'Sale');
        return $this->client->get($endpoint, [
            'PageSize' => $pageSize,
            'PageIndex' => $pageIndex,
        ])['Items'] ?? [];
    }

    /**
     * Get daily stats for purchases.
     */
    public function getPurchaseDailyStats(string $startDate, string $endDate): array
    {
        return $this->client->get('EInvoice/GetPurchaseDailyStats', [
            'StartDate' => $startDate,
            'EndDate' => $endDate,
        ]) ?? [];
    }

    /**
     * Get daily stats for sales.
     */
    public function getSaleDailyStats(string $startDate, string $endDate, string $mode = 'einvoice'): array
    {
        $endpoint = $this->getEndpointByMode($mode, 'SaleDailyStats');
        return $this->client->get($endpoint, [
            'StartDate' => $startDate,
            'EndDate' => $endDate,
        ]) ?? [];
    }

    /**
     * Get summary stats for purchases.
     */
    public function getPurchaseSummaryStats(string $startDate, string $endDate): array
    {
        return $this->client->get('EInvoice/GetPurchaseSummaryStats', [
            'StartDate' => $startDate,
            'EndDate' => $endDate,
        ]) ?? $this->getEmptySummaryStats();
    }

    /**
     * Get summary stats for sales.
     */
    public function getSaleSummaryStats(string $startDate, string $endDate, string $mode = 'einvoice'): array
    {
        $endpoint = $this->getEndpointByMode($mode, 'SaleSummaryStats');
        return $this->client->get($endpoint, [
            'StartDate' => $startDate,
            'EndDate' => $endDate,
        ]) ?? $this->getEmptySummaryStats();
    }

    // Serileri getir
    public function getSeries(string $mode = 'einvoice', int $pageSize = 100, int $page = 1, array $params = []): array
    {
        $prefix = $this->getPrefixByMode($mode);

        $queryParams = array_merge([
            'PageSize' => $pageSize,
            'IsActive' => true,
            'Page' => $page
        ], $params);

        // Map 'search' to 'Name' if provided in params
        if (isset($params['search']) && !isset($queryParams['Name'])) {
            $queryParams['Name'] = $params['search'];
        }

        return $this->client->get("{$prefix}/Series", $queryParams)['Content'] ?? [];
    }

    // Yeni seri oluştur
    public function createSeries(string $name, string $mode = 'einvoice', bool $isActive = true, bool $isDefault = false): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->post("{$prefix}/Series", [
            'Name' => strtoupper($name),
            'IsActive' => $isActive,
            'IsDefault' => $isDefault
        ]) ?? [];
    }

    // Adres defterine ekle
    public function createCustomer(array $payload): array
    {
        // Nilvera expects an array of customers
        $data = isset($payload[0]) ? $payload : [$payload];
        return $this->client->post('General/Customers', $data) ?? [];
    }
    public function getTemplates(string $mode = 'einvoice', array $params = []): array
    {
        $prefix = $this->getPrefixByMode($mode);
        $queryParams = array_merge([
            'PageSize' => 100,
            'IsActive' => true
        ], $params);

        return $this->client->get("{$prefix}/Templates", $queryParams)['Content'] ?? [];
    }

    // Taslakları listele
    public function getDraftInvoices(string $mode = 'einvoice', array $params = []): array
    {
        $prefix = $this->getPrefixByMode($mode);

        $queryParams = [
            'Page' => $params['Page'] ?? 1,
            'PageSize' => $params['PageSize'] ?? 30,
        ];

        if (!empty($params['Search'])) {
            $queryParams['Search'] = $params['Search'];
        }

        if (!empty($params['StartDate'])) {
            $queryParams['StartDate'] = $params['StartDate'] . 'T00:00:00.000Z';
        }

        if (!empty($params['EndDate'])) {
            $queryParams['EndDate'] = $params['EndDate'] . 'T23:59:59.998Z';
        }

        return $this->client->get("{$prefix}/Draft", $queryParams) ?? [
            'Content' => [],
            'TotalCount' => 0
        ];
    }

    // Fatura HTML görseli
    public function getInvoiceHtml(string $uuid, string $mode = 'einvoice', ?string $direction = null)
    {
        $prefix = $this->getPrefixByMode($mode);
        $path = $direction ? "{$prefix}/{$direction}/{$uuid}/html" : "{$prefix}/Draft/{$uuid}/html";
        return $this->client->get($path);
    }

    // Fatura ekleri
    public function getInvoiceAttachments(string $uuid, string $direction = 'Sale', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/{$direction}/{$uuid}/Attachments") ?? [];
    }

    // Fatura detayları
    public function getInvoiceDetails(string $uuid, string $direction = 'Sale', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/{$direction}/{$uuid}/Details") ?? [];
    }

    /**
     * Get despatch info of an invoice.
     */
    public function getInvoiceDespatchInfo(string $uuid, string $direction = 'Sale', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/{$direction}/{$uuid}/DespatchInfo") ?? [];
    }

    /**
     * Get history of an invoice.
     */
    public function getInvoiceHistories(string $uuid, string $direction = 'Sale', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/{$direction}/{$uuid}/Histories") ?? [];
    }

    // Toplu dışa aktarma (PDF, XML, Zarf)
    public function exportInvoices(array $uuids, string $format = 'Pdf', string $direction = 'Sale'): ?string
    {
        $response = $this->client->post("EInvoice/{$direction}/Export/{$format}", $uuids);

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
        $base64 = $this->client->get("{$prefix}/Draft/{$uuid}/pdf");

        if (empty($base64)) {
            return null;
        }

        // Nilvera returns base64 string, sometimes quoted
        $base64 = trim($base64, '" ');
        return base64_decode($base64);
    }

    // Taslak oluştur
    public function createDraft(array $model, string $mode = 'einvoice'): array
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

    // Yeni fatura oluştur ve gönder
    public function sendNewInvoice(array $model, string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->post("{$prefix}/Send", $model) ?? [];
    }

    // Taslağı onayla ve gönder
    public function sendInvoice(string $uuid, ?string $alias = null, string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        // User snippet uses Draft/EditAndSend for confirmation/sending
        return $this->client->post("{$prefix}/Draft/EditAndSend", ['UUID' => $uuid]) ?? [];
    }

    // Toplu gönderim
    public function bulkSendInvoices(array $invoices, string $mode = 'einvoice'): array
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
    public function bulkDeleteInvoices(array $uuids, string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->delete("{$prefix}/Draft", $uuids) ?? [];
    }

    /**
     * Get the data model of a draft invoice.
     */
    public function getDraftModel(string $uuid, string $mode = 'einvoice'): array
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
    public function uploadXml(string $filePath, string $fileName, string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->upload("{$prefix}/Upload", $filePath, $fileName) ?? [];
    }

    /**
     * Bulk create draft invoices (commonly used for E-Archive).
     */
    public function createBulkDraft(array $invoices, string $mode = 'earchive'): array
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
    public function uploadSgkPdf(string $filePath, string $fileName, string $type = 'SAGLIK_ECZ', string $registerCode = ''): array
    {
        return $this->client->upload('EInvoice/Upload/Sgk', $filePath, $fileName, [
            'Type' => $type,
            'RegisterCode' => $registerCode
        ]) ?? [];
    }

    // Gelen/Giden fatura listesi (Filtreli)
    public function getInvoices(string $direction = 'Sale', array $params = []): array
    {
        $prefix = 'EInvoice'; // Based on user snippet for outbox

        // Build base query
        $baseParams = collect($params)->except([
            'StatusCode',
            'InvoiceProfile',
            'InvoiceType',
            'AnswerCode',
            'IsTransfer'
        ])->toArray();

        $queryString = http_build_query($baseParams);

        // Handle repeated keys
        $filters = [
            'StatusCode' => $params['StatusCode'] ?? ['succeed', 'error', 'waiting'],
            'InvoiceProfile' => $params['InvoiceProfile'] ?? ['TEMELFATURA', 'TICARIFATURA', 'HKS', 'KAMU', 'IHRACAT', 'ENERJI', 'ILAC_TIBBICIHAZ', 'YATIRIMTESVIK', 'IDIS'],
            'InvoiceType' => $params['InvoiceType'] ?? ['SATIS', 'IADE', 'TEVKIFAT', 'TEVKIFATIADE', 'ISTISNA', 'IHRACKAYITLI', 'OZELMATRAH', 'SGK', 'KOMISYONCU', 'KONAKLAMAVERGISI', 'SARJ', 'SARJANLIK'],
            'AnswerCode' => $params['AnswerCode'] ?? ['approved', 'documentAnsweredAutomatically', 'rejected', 'waitingForApproval'],
        ];

        foreach ($filters as $key => $values) {
            $values = is_array($values) ? $values : [$values];
            foreach (array_unique($values) as $val) {
                $queryString .= "&{$key}=" . urlencode($val);
            }
        }

        if (isset($params['IsTransfer'])) {
            $queryString .= "&IsTransfer=" . urlencode($params['IsTransfer']);
        }

        return $this->client->get("{$prefix}/{$direction}?" . $queryString) ?? [];
    }

    // Etiketleri getir
    public function getTags(string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->get("{$prefix}/Tags", ['PageSize' => 150, 'Page' => 1]) ?? [];
    }

    /**
     * Update tags for a document.
     */
    public function updateTags(string $uuid, array $tags, string $status = 'Draft', string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->put("{$prefix}/{$status}/Tags", [
            'DocumentUUID' => $uuid,
            'Tags' => $tags
        ]) ?? [];
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

    // WhatsApp ile gönder
    public function sendWhatsapp(string $uuid, string $phone, string $mode = 'einvoice'): array
    {
        $prefix = $this->getPrefixByMode($mode);
        return $this->client->post("{$prefix}/Draft/WhatsApp/Send", [
            'UUID' => $uuid,
            'Phone' => $phone
        ]) ?? [];
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
        $prefix = $this->getPrefixByMode($mode);
        return "{$prefix}/Get{$type}";
    }
}
