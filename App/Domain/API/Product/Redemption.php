<?php

namespace App\Domain\API\Product;

use App\Domain\Domain;

class RedeemRequest extends Domain
{
    protected $structure = [
        "Id" => "ID",
        "content_id__c" => "Content ID",
        "order_id__c" => "Order ID",
        "download_date__c" => "Download Date",
        "product_image__c" => "Product Image",
        "content_title__c" => "Content Title",
        "Label_publisher_name__c" => "Network Name",
        "Feed_provider__c" => "Feed Provider",
        "Category_ID__c" => "Category ID",
        "Content_type__c" => "Content Type",
        "Transaction_Status__c" => "Transaction Status",
        "TFC_User__c" => "RTW User",
        "username__c" => "RTW Username",
        "Name__c" => "Name",
        "Email__c" => "Email",
        "User_territory__c" => "User Territory",
        "Store_Id__c" => "Store ID",
        "Shop_abbr__c" => "Store Abbr",
        "available_clubcoins__c" => "Available Points",
        "Provider_User_ID__c" => "Provider User ID",
        "Provider_Shop_Name__c" => "Provider Shop Url",
        "currency__c" => "Currency",
        "Cost__c" => "SRP Price",
        "purchasing_price__c" => "Purchasing Price",
        "transaction_price__c" => "Transaction Price",
        "Transaction_Fee__c" => "Transaction Fee",
        "Product_Discount__c" => "Product Discount",
        "Product_Fee__c" => "Product Fee",
        "Clubcoins__c" => "Points Cost",
        "Transaction_Exchange_Rate__c" => "Transaction Exchange Rate",
        "VAT__c" => "VAT",
        "Provider_Margin__c" => "Provider Margin",
        "Provider_Discount__c" => "Provider Discount",
        "Special_Offer__c" => "Special Offer",
        "Discount__c" => "Discount",
        "Offer_SRP__c" => "Offer SRP",
        "Offer_Id__c" => "Offer ID",
        "Offer_Name__c" => "Offer Name",
        "Provider_order_number__c" => "Provider Order Number",
        "download_url__c" => "Download URL",
        "Additional_info__c" => "Additional Info"
    ];
}
