permissionset 50240 "BreMac POS API"
{
    Assignable = true;
    Caption = 'BreMac POS API';

    Permissions =
        tabledata "Item Ledger Entry" = R,
        page "BreMac POS Inventory Entries" = X,
        codeunit "BreMac POS Integration" = X;
}
