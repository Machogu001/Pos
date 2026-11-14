page 50244 "BreMac POS Item Units"
{
    PageType = API;
    APIPublisher = 'bremac';
    APIGroup = 'pos';
    APIVersion = 'v1.0';
    EntityName = 'posItemUnit';
    EntitySetName = 'posItemUnits';
    SourceTable = "Item Unit of Measure";
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
            repeater(Units)
            {
                field(id; Rec.SystemId)
                {
                    Caption = 'Id';
                }
                field(itemNumber; Rec."Item No.")
                {
                    Caption = 'Item Number';
                }
                field(code; Rec.Code)
                {
                    Caption = 'Code';
                }
                field(qtyPerUnitOfMeasure; Rec."Qty. per Unit of Measure")
                {
                    Caption = 'Qty. per Unit of Measure';
                }
                field(lastModifiedDateTime; Rec.SystemModifiedAt)
                {
                    Caption = 'Last Modified Date Time';
                }
            }
        }
    }
}
