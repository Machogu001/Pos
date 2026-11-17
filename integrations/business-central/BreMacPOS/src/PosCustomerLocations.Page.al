page 50245 "BreMac POS Customer Locations"
{
    PageType = API;
    APIPublisher = 'bremac';
    APIGroup = 'pos';
    APIVersion = 'v1.0';
    EntityName = 'posCustomerLocation';
    EntitySetName = 'posCustomerLocations';
    SourceTable = Customer;
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
            repeater(Customers)
            {
                field(id; Rec.SystemId)
                {
                    Caption = 'Id';
                }
                field(number; Rec."No.")
                {
                    Caption = 'Number';
                }
                field(locationCode; Rec."Location Code")
                {
                    Caption = 'Location Code';
                }
                field(lastModifiedDateTime; Rec.SystemModifiedAt)
                {
                    Caption = 'Last Modified Date Time';
                }
            }
        }
    }
}
