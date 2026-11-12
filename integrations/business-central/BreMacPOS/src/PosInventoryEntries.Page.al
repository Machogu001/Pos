page 50242 "BreMac POS Inventory Entries"
{
    PageType = API;
    APIPublisher = 'bremac';
    APIGroup = 'pos';
    APIVersion = 'v1.0';
    EntityName = 'posInventoryEntry';
    EntitySetName = 'posInventoryEntries';
    SourceTable = "Item Ledger Entry";
    SourceTableView = where(Open = const(true));
    DataAccessIntent = ReadOnly;
    Editable = false;
    InsertAllowed = false;
    ModifyAllowed = false;
    DeleteAllowed = false;
    ODataKeyFields = SystemId;

    layout
    {
        area(Content)
        {
            repeater(Entries)
            {
                field(id; Rec.SystemId)
                {
                    Caption = 'Id';
                }
                field(entryNo; Rec."Entry No.")
                {
                    Caption = 'Entry No.';
                }
                field(itemNumber; Rec."Item No.")
                {
                    Caption = 'Item Number';
                }
                field(locationCode; Rec."Location Code")
                {
                    Caption = 'Location Code';
                }
                field(remainingQuantity; Rec."Remaining Quantity")
                {
                    Caption = 'Remaining Quantity';
                }
                field(postingDate; Rec."Posting Date")
                {
                    Caption = 'Posting Date';
                }
                field(lastModifiedDateTime; Rec.SystemModifiedAt)
                {
                    Caption = 'Last Modified Date Time';
                }
            }
        }
    }
}
