codeunit 50243 "BreMac POS Sales Integration"
{
    Permissions = tabledata Customer = R,
        tabledata Item = R,
        tabledata "Item Unit of Measure" = R,
        tabledata Location = R,
        tabledata "Sales Header" = RIMD,
        tabledata "Sales Line" = RIMD,
        tabledata "Sales Invoice Header" = R,
        tabledata "Salesperson/Purchaser" = R;

    procedure PostSale(SaleJson: Text): Text
    var
        DraftNo: Code[20];
        ResponseJson: Text;
    begin
        ExecutePostSale(SaleJson, DraftNo, ResponseJson);
        exit(ResponseJson);
    end;

    procedure PostSaleV2(SaleJson: Text): Text
    begin
        exit(PostSale(SaleJson));
    end;

    procedure PostSaleV3(SaleJson: Text): Text
    begin
        exit(PostSale(SaleJson));
    end;

    procedure PostSaleV4(SaleJson: Text): Text
    begin
        exit(PostSale(SaleJson));
    end;

    local procedure ExecutePostSale(SaleJson: Text; var DraftNo: Code[20]; var ResponseJson: Text)
    var
        Customer: Record Customer;
        Location: Record Location;
        SalesHeader: Record "Sales Header";
        SalesInvoiceHeader: Record "Sales Invoice Header";
        Salesperson: Record "Salesperson/Purchaser";
        Lines: JsonArray;
        Payload: JsonObject;
        CustomerNumber: Code[20];
        ExternalDocumentNumber: Code[35];
        LocationCode: Code[10];
        PostingDate: Date;
        SalespersonCode: Code[20];
    begin
        Payload.ReadFrom(SaleJson);
        ExternalDocumentNumber := CopyStr(GetRequiredText(Payload, 'externalDocumentNumber'), 1, MaxStrLen(ExternalDocumentNumber));
        CustomerNumber := CopyStr(GetRequiredText(Payload, 'customerNumber'), 1, MaxStrLen(CustomerNumber));
        SalespersonCode := CopyStr(GetRequiredText(Payload, 'salespersonCode'), 1, MaxStrLen(SalespersonCode));
        LocationCode := CopyStr(GetRequiredText(Payload, 'locationCode'), 1, MaxStrLen(LocationCode));
        PostingDate := GetRequiredDate(Payload, 'postingDate');
        Lines := GetRequiredArray(Payload, 'lines');

        Customer.Get(CustomerNumber);
        Salesperson.Get(SalespersonCode);
        Salesperson.TestField(Blocked, false);
        Location.Get(LocationCode);

        if FindPostedInvoice(ExternalDocumentNumber, CustomerNumber, SalesInvoiceHeader) then begin
            ResponseJson := PostedInvoiceResponse(SalesInvoiceHeader);
            exit;
        end;

        DeleteExistingDraft(ExternalDocumentNumber, CustomerNumber);
        SalesHeader.Init();
        SalesHeader.Validate("Document Type", SalesHeader."Document Type"::Order);
        SalesHeader.Insert(true);
        DraftNo := SalesHeader."No.";
        SalesHeader.Validate("Sell-to Customer No.", CustomerNumber);
        SalesHeader.Validate("Posting Date", PostingDate);
        SalesHeader.Validate("Document Date", PostingDate);
        SalesHeader.Validate("External Document No.", ExternalDocumentNumber);
        SalesHeader.Validate("Salesperson Code", SalespersonCode);
        SalesHeader.Validate("Location Code", LocationCode);
        SalesHeader."Location Code" := LocationCode;
        SalesHeader.Ship := true;
        SalesHeader.Invoice := true;
        SalesHeader.Modify(true);
        SalesHeader.Get(SalesHeader."Document Type"::Order, DraftNo);
        SalesHeader.TestField("Location Code", LocationCode);

        InsertLines(SalesHeader, Lines, LocationCode);
        SalesHeader.Get(SalesHeader."Document Type"::Order, DraftNo);
        SalesHeader."Location Code" := LocationCode;
        SalesHeader.Modify(false);
        SalesHeader.TestField("Location Code", LocationCode);
        SalesHeader.SendToPosting(Codeunit::"Sales-Post");

        if not FindPostedInvoice(ExternalDocumentNumber, CustomerNumber, SalesInvoiceHeader) then
            Error('Business Central posted the POS sale but the posted invoice could not be resolved.');

        ResponseJson := PostedInvoiceResponse(SalesInvoiceHeader);
        DraftNo := '';
    end;

    local procedure InsertLines(SalesHeader: Record "Sales Header"; Lines: JsonArray; LocationCode: Code[10])
    var
        Item: Record Item;
        ItemUnitOfMeasure: Record "Item Unit of Measure";
        SalesLine: Record "Sales Line";
        LineObject: JsonObject;
        LineToken: JsonToken;
        DiscountAmount: Decimal;
        DiscountPercent: Decimal;
        ItemNumber: Code[20];
        LineIndex: Integer;
        LineNo: Integer;
        Quantity: Decimal;
        UnitOfMeasureCode: Code[10];
        UnitPrice: Decimal;
    begin
        if Lines.Count() = 0 then
            Error('The POS sale must contain at least one line.');

        for LineIndex := 0 to Lines.Count() - 1 do begin
            Lines.Get(LineIndex, LineToken);
            LineObject := LineToken.AsObject();
            ItemNumber := CopyStr(GetRequiredText(LineObject, 'lineObjectNumber'), 1, MaxStrLen(ItemNumber));
            UnitOfMeasureCode := CopyStr(GetOptionalText(LineObject, 'unitOfMeasureCode'), 1, MaxStrLen(UnitOfMeasureCode));
            Quantity := GetRequiredDecimal(LineObject, 'quantity');
            UnitPrice := GetRequiredDecimal(LineObject, 'unitPrice');
            DiscountPercent := GetOptionalDecimal(LineObject, 'discountPercent');
            DiscountAmount := GetOptionalDecimal(LineObject, 'discountAmount');

            if Quantity <= 0 then
                Error('POS sale quantity must be greater than zero for item %1.', ItemNumber);
            Item.Get(ItemNumber);
            if UnitOfMeasureCode = '' then
                UnitOfMeasureCode := Item."Base Unit of Measure";
            if not ItemUnitOfMeasure.Get(ItemNumber, UnitOfMeasureCode) then
                Error('POS sale unit %1 is not configured for item %2 in Business Central.', UnitOfMeasureCode, ItemNumber);

            LineNo += 10000;
            SalesLine.Init();
            SalesLine."Document Type" := SalesHeader."Document Type";
            SalesLine."Document No." := SalesHeader."No.";
            SalesLine."Line No." := LineNo;
            SalesLine."Location Code" := LocationCode;
            SalesLine.Insert(true);
            SalesLine.Validate(Type, SalesLine.Type::Item);
            SalesLine.Validate("No.", ItemNumber);
            SalesLine.Validate("Unit of Measure Code", UnitOfMeasureCode);
            SalesLine.Validate("Location Code", LocationCode);
            SalesLine.Validate(Quantity, Quantity);
            SalesLine.Validate("Unit Price", UnitPrice);
            if DiscountPercent <> 0 then
                SalesLine.Validate("Line Discount %", DiscountPercent)
            else
                if DiscountAmount <> 0 then
                    SalesLine.Validate("Line Discount Amount", DiscountAmount);
            SalesLine.Modify(true);
        end;
    end;

    local procedure DeleteExistingDraft(ExternalDocumentNumber: Code[35]; CustomerNumber: Code[20])
    var
        SalesHeader: Record "Sales Header";
    begin
        SalesHeader.SetRange("Document Type", SalesHeader."Document Type"::Order);
        SalesHeader.SetRange("External Document No.", ExternalDocumentNumber);
        SalesHeader.SetRange("Sell-to Customer No.", CustomerNumber);
        if SalesHeader.FindFirst() then
            SalesHeader.Delete(true);
    end;

    local procedure FindPostedInvoice(ExternalDocumentNumber: Code[35]; CustomerNumber: Code[20]; var SalesInvoiceHeader: Record "Sales Invoice Header"): Boolean
    begin
        SalesInvoiceHeader.Reset();
        SalesInvoiceHeader.SetRange("External Document No.", ExternalDocumentNumber);
        SalesInvoiceHeader.SetRange("Sell-to Customer No.", CustomerNumber);
        exit(SalesInvoiceHeader.FindFirst());
    end;

    local procedure PostedInvoiceResponse(SalesInvoiceHeader: Record "Sales Invoice Header"): Text
    var
        Response: JsonObject;
        ResponseText: Text;
    begin
        Response.Add('success', true);
        Response.Add('id', Format(SalesInvoiceHeader.SystemId));
        Response.Add('number', SalesInvoiceHeader."No.");
        Response.Add('externalDocumentNumber', SalesInvoiceHeader."External Document No.");
        Response.WriteTo(ResponseText);
        exit(ResponseText);
    end;

    local procedure GetRequiredText(ObjectValue: JsonObject; PropertyName: Text): Text
    var
        Token: JsonToken;
        ValueText: Text;
    begin
        if not ObjectValue.Get(PropertyName, Token) then
            Error('Required POS sale property %1 is missing.', PropertyName);
        ValueText := Token.AsValue().AsText();
        if ValueText = '' then
            Error('Required POS sale property %1 is empty.', PropertyName);
        exit(ValueText);
    end;

    local procedure GetOptionalText(ObjectValue: JsonObject; PropertyName: Text): Text
    var
        Token: JsonToken;
    begin
        if not ObjectValue.Get(PropertyName, Token) then
            exit('');
        exit(Token.AsValue().AsText());
    end;

    local procedure GetRequiredDate(ObjectValue: JsonObject; PropertyName: Text): Date
    var
        Result: Date;
        ValueText: Text;
    begin
        ValueText := GetRequiredText(ObjectValue, PropertyName);
        if not Evaluate(Result, ValueText, 9) then
            Error('POS sale property %1 must be an ISO date.', PropertyName);
        exit(Result);
    end;

    local procedure GetRequiredDecimal(ObjectValue: JsonObject; PropertyName: Text): Decimal
    var
        Token: JsonToken;
    begin
        if not ObjectValue.Get(PropertyName, Token) then
            Error('Required POS sale property %1 is missing.', PropertyName);
        exit(Token.AsValue().AsDecimal());
    end;

    local procedure GetOptionalDecimal(ObjectValue: JsonObject; PropertyName: Text): Decimal
    var
        Token: JsonToken;
    begin
        if not ObjectValue.Get(PropertyName, Token) then
            exit(0);
        exit(Token.AsValue().AsDecimal());
    end;

    local procedure GetRequiredArray(ObjectValue: JsonObject; PropertyName: Text): JsonArray
    var
        Token: JsonToken;
    begin
        if not ObjectValue.Get(PropertyName, Token) then
            Error('Required POS sale property %1 is missing.', PropertyName);
        if not Token.IsArray() then
            Error('POS sale property %1 must be an array.', PropertyName);
        exit(Token.AsArray());
    end;
}
