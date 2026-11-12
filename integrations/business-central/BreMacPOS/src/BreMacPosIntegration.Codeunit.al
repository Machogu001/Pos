codeunit 50241 "BreMac POS Integration"
{
    procedure CalculateLocationInventory(ItemNumber: Code[20]; LocationCode: Code[10]): Decimal
    var
        ItemLedgerEntry: Record "Item Ledger Entry";
    begin
        ItemLedgerEntry.SetRange("Item No.", ItemNumber);
        ItemLedgerEntry.SetRange("Location Code", LocationCode);
        ItemLedgerEntry.SetRange(Open, true);
        ItemLedgerEntry.CalcSums("Remaining Quantity");

        exit(ItemLedgerEntry."Remaining Quantity");
    end;
}
