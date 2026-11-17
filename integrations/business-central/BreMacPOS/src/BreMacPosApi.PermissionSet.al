permissionset 50240 "BreMac POS API"
{
    Assignable = true;
    Caption = 'BreMac POS API';

    Permissions =
        tabledata Customer = R,
        tabledata "Custom Report Selection" = R,
        tabledata "Item Ledger Entry" = R,
        tabledata "Item Unit of Measure" = R,
        tabledata "Report Selections" = R,
        page "BreMac POS Customer Locations" = X,
        page "BreMac POS Inventory Entries" = X,
        page "BreMac POS Item Units" = X,
        codeunit "BreMac POS Integration" = X,
        codeunit "BreMac POS Sales Integration" = X;
}
