<?php

namespace ProfsCode\Nilvera\Services;

use Illuminate\Support\Str;

class InvoiceMapper
{
    // Ana mapleme metodu
    public function map(array $data, bool $isArchive = false): array
    {
        $lines = $this->mapLines($data['items'] ?? []);
        $invoiceInfo = $this->mapInvoiceInfo($data, $isArchive);
        $customerInfo = $this->mapCustomerInfo($data, $isArchive);
        $companyInfo = $this->mapCompanyInfo($data['CompanyInfo'] ?? []);

        $notes = $data['Notes'] ?? [];
        if (is_string($notes) && !empty($notes)) {
            $notes = [$notes];
        } elseif (!is_array($notes)) {
            $notes = [];
        }

        $model = [
            'InvoiceInfo' => $invoiceInfo,
            'CompanyInfo' => $companyInfo,
            'CustomerInfo' => $customerInfo,
            'InvoiceLines' => $lines,
            'Notes' => $notes
        ];

        $rootKey = $isArchive ? 'ArchiveInvoice' : 'EInvoice';

        return [
            $rootKey => $model,
            'CustomerAlias' => (string) ($customerInfo['Alias'] ?? '')
        ];
    }

    protected function mapLines(array $items): array
    {
        $lines = [];
        foreach ($items as $item) {
            $qty = (float) ($item['Quantity'] ?? 1);
            $price = (float) ($item['Price'] ?? 0);
            $vatRate = (int) ($item['VatRate'] ?? 20);

            $taxes = [];
            if (!empty($item['Taxes']) && is_array($item['Taxes'])) {
                foreach ($item['Taxes'] as $tax) {
                    $taxes[] = [
                        'TaxCode' => (string) ($tax['Code'] ?? '0015'),
                        'Total' => (float) round(($tax['Amount'] ?? 0), 4),
                        'Percent' => (int) ($tax['Percent'] ?? 20),
                        'ReasonCode' => (string) ($tax['ReasonCode'] ?? null),
                        'ReasonDesc' => null
                    ];
                }
            } elseif ($vatRate > 0) {
                $taxes[] = [
                    'TaxCode' => '0015',
                    'Total' => (float) round($qty * $price * ($vatRate / 100), 4),
                    'Percent' => $vatRate,
                    'ReasonCode' => (string) ($item['ExemptionReasonCode'] ?? null),
                    'ReasonDesc' => null
                ];
            }

            $exportInfo = null;
            if (!empty($item["GTIPNo"])) {
                $exportInfo = [
                    "GTIPNo" => (string) ($item["GTIPNo"] ?? ""),
                    "DeliveryTermCode" => (string) ($item["DeliveryTermCode"] ?? "DAF"),
                    "TransportModeCode" => (string) ($item["TransportModeCode"] ?? "5"),
                    "PackageBrandName" => (string) ($item["PackageBrandName"] ?? ""),
                    "PackageID" => (string) ($item["PackageID"] ?? ""),
                    "PackageQuantity" => (int) ($item["PackageQuantity"] ?? 0),
                    "PackageTypeCode" => (string) ($item["PackageTypeCode"] ?? "BB"),
                    "CustomsTrackingNo" => (string) ($item["CustomsTrackingNo"] ?? "")
                ];
            }

            $deliveryAddress = null;
            if (!empty($item["DeliveryAddress"]["Address"])) {
                $deliveryAddress = [
                    "Address" => (string) $item["DeliveryAddress"]["Address"],
                    "District" => (string) ($item["DeliveryAddress"]["District"] ?? ""),
                    "City" => (string) ($item["DeliveryAddress"]["City"] ?? ""),
                    "Country" => (string) ($item["DeliveryAddress"]["Country"] ?? ""),
                    "PostalCode" => (string) ($item["DeliveryAddress"]["PostalCode"] ?? "")
                ];
            }

            $lines[] = [
                "SellerCode" => (string) ($item["SellerCode"] ?? ""),
                "Name" => (string) ($item["Name"] ?? "Ürün/Hizmet"),
                "Description" => (string) ($item["Description"] ?? ""),
                "Quantity" => $qty,
                "UnitType" => (string) ($item["UnitCode"] ?? "C62"),
                "UnitName" => (string) ($item["UnitName"] ?? "ADET"),
                "Price" => $price,
                "AllowanceTotal" => 0,
                "KDVPercent" => $vatRate,
                "KDVTotal" => (float) round($qty * $price * ($vatRate / 100), 4),
                "Taxes" => $taxes,
                "OzelMatrahTotal" => (float) ($item["OzelMatrahTotal"] ?? 0),
                "OzelMatrahReasonCode" => !empty($item["OzelMatrahReasonCode"]) ? (string) $item["OzelMatrahReasonCode"] : null,
                "ExportInfo" => $exportInfo,
                "DeliveryAddress" => $deliveryAddress,
                "MedicineAndMedicalDevice" => new \stdClass(),
                "AdditionalItemIdentification" => new \stdClass()
            ];
        }
        return $lines;
    }

    protected function mapInvoiceInfo(array $data, bool $isArchive): array
    {
        $despatchRefs = $data['DespatchDocumentReference'] ?? $data['DespatchAdvices'] ?? [];
        $despatchMapped = [];
        foreach ($despatchRefs as $ref) {
            $val = (string) ($ref['Value'] ?? $ref['ID'] ?? '');
            $date = $ref['Date'] ?? $ref['IssueDate'] ?? '';
            if ($val) {
                $despatchMapped[] = [
                    'Value' => $val,
                    'IssueDate' => $date ? (str_contains($date, 'T') ? (str_contains($date, 'Z') ? str_replace('Z', '', $date) : $date) : $date . 'T00:00:00') : null
                ];
            }
        }

        $returnInvoices = [];
        if (!empty($data['ReturnInvoiceInfo'])) {
            foreach ($data['ReturnInvoiceInfo'] as $ret) {
                if (!empty($ret['InvoiceNumber'])) {
                    $returnInvoices[] = [
                        'InvoiceNumber' => (string) $ret['InvoiceNumber'],
                        'IssueDate' => !empty($ret['IssueDate']) ? $ret['IssueDate'] . (str_contains($ret['IssueDate'], 'T') ? '' : 'T00:00:00') : null
                    ];
                }
            }
        }

        $invoiceInfo = [
            'UUID' => $data['InvoiceUUID'] ?? (string) Str::uuid(),
            'TemplateUUID' => $data['TemplateUuid'] ?? $data['TemplateUUID'] ?? null,
            'InvoiceProfile' => ($data['Scenario'] ?? '') == 'Ticari' ? 2 : 1,
            'InvoiceType' => (int) ($data['mInvoiceType'] ?? 0),
            'InvoiceSerieOrNumber' => $data['Series'] ?? null,
            'IssueDate' => ($data['IssueDate'] ?? date('Y-m-d')) . 'T' . date('H:i:s'),
            'CurrencyCode' => $data['CurrencyCode'] ?? 'TRY',
            'ExchangeRate' => isset($data['ExchangeRate']) ? (float) $data['ExchangeRate'] : null,
            'DespatchDocumentReference' => $despatchMapped,
            'OrderReference' => !empty($data['OrderReference']['ID']) ? [
                'Value' => (string) $data['OrderReference']['ID'],
                'IssueDate' => $data['OrderReference']['Date'] ?? null
            ] : null,
            'OrderReferenceDocument' => null,
            'AdditionalDocumentReferences' => [],
            "TaxExemptionReasonInfo" => [
                "KDVExemptionReasonCode" => (string) ($data["KDVExemptionReasonCode"] ?? $data["ExemptionReasonCode"] ?? ""),
                "OTVExemptionReasonCode" => (string) ($data["OTVExemptionReasonCode"] ?? $data["OtvExemptionReasonCode"] ?? ""),
                "AccommodationTaxExemptionReasonCode" => (string) ($data["AccommodationTaxExemptionReasonCode"] ?? "")
            ],
            'PaymentTermsInfo' => null,
            'PaymentMeansInfo' => null,
            'OKCInfo' => null,
            'ReturnInvoiceInfo' => $returnInvoices,
            'AccountingCost' => null,
            'InvoicePeriod' => null,
            'SGKInfo' => null,
            'ESUReportInfo' => new \stdClass(),
            'InvestmentIncentive' => new \stdClass(),
            'ShipmentNumber' => null,
            "Expenses" => $this->mapExpenses($data)
        ];

        if ($isArchive) {
            $invoiceInfo['SalesPlatform'] = (int) ($data['EArchiveSalesChannel'] ?? 0);
            $invoiceInfo['SendType'] = ($data['EArchiveSendingType'] ?? "KAGIT");
            $invoiceInfo['InternetInfo'] = $this->mapInternetInfo($data);
        }

        return $invoiceInfo;
    }

    protected function mapExpenses(array $data): array
    {
        $expenses = [];
        if (($data["InsuranceTotal"] ?? 0) > 0) {
            $expenses[] = [
                "Description" => "Sigorta",
                "Amount" => (float) $data["InsuranceTotal"]
            ];
        }
        if (($data["FreightTotal"] ?? 0) > 0) {
            $expenses[] = [
                "Description" => "Navlun",
                "Amount" => (float) $data["FreightTotal"]
            ];
        }
        return $expenses;
    }

    protected function mapInternetInfo(array $data): ?array
    {
        if (($data['EArchiveSalesChannel'] ?? '') != '1') {
            return null;
        }

        return [
            'WebSite' => (string) ($data['InternetInfo']['WebSite'] ?? ''),
            'PaymentMethod' => (string) ($data['InternetInfo']['PaymentMethod'] ?? ''),
            'PaymentMethodName' => (string) ($data['InternetInfo']['PaymentMethodName'] ?? ''),
            'PaymentAgent' => (string) ($data['InternetInfo']['PaymentAgent'] ?? ''),
            'PaymentDate' => !empty($data['InternetInfo']['PaymentDate']) ? $data['InternetInfo']['PaymentDate'] . 'T00:00:00' : null,
        ];
    }

    protected function mapCustomerInfo(array $data, bool $isArchive): array
    {
        return [
            'TaxNumber' => (string) ($data['TaxNumber'] ?? $data['VKNTCKN'] ?? ''),
            'Alias' => (string) ($data['Alias'] ?? $data['CustomerAlias'] ?? ''),
            'Name' => (string) ($data['Title'] ?? ''),
            'TaxOffice' => (string) ($data['TaxOffice'] ?? ''),
            'PartyIdentifications' => [],
            'AgentPartyIdentifications' => [],
            'Address' => (string) ($data['Address'] ?? ''),
            'District' => (string) ($data['District'] ?? ''),
            'City' => (string) ($data['City'] ?? 'Mersin'),
            'Country' => (string) ($data['Country'] ?? 'Türkiye'),
            'PostalCode' => (string) ($data['PostalCode'] ?? ''),
            'Phone' => (string) ($data['Phone'] ?? ''),
            'Fax' => null,
            'Mail' => (string) ($data['Mail'] ?? ''),
            'WebSite' => null
        ];
    }

    protected function mapCompanyInfo(array $companyInfo): array
    {
        // Use provided info or fallback to config/defaults
        return [
            'Name' => $companyInfo['Name'] ?? config('nilvera.company.name', 'Test Kurum Bir'),
            'TaxNumber' => $companyInfo['TaxNumber'] ?? config('nilvera.company.tax_number', '1234567801'),
            'TaxOffice' => $companyInfo['TaxOffice'] ?? config('nilvera.company.tax_office', 'Erciyes'),
            'Address' => $companyInfo['Address'] ?? config('nilvera.company.address', ''),
            'District' => $companyInfo['District'] ?? config('nilvera.company.district', ''),
            'City' => $companyInfo['City'] ?? config('nilvera.company.city', 'Iğdır'),
            'Country' => $companyInfo['Country'] ?? config('nilvera.company.country', 'Türkiye'),
            'PostalCode' => $companyInfo['PostalCode'] ?? config('nilvera.company.postal_code', '38070'),
            'Phone' => $companyInfo['Phone'] ?? config('nilvera.company.phone', ''),
            'Mail' => $companyInfo['Mail'] ?? config('nilvera.company.mail', ''),
            'WebSite' => $companyInfo['WebSite'] ?? config('nilvera.company.website', ''),
            'PartyIdentifications' => $companyInfo['PartyIdentifications'] ?? [],
            'AgentPartyIdentifications' => $companyInfo['AgentPartyIdentifications'] ?? null
        ];
    }
}
