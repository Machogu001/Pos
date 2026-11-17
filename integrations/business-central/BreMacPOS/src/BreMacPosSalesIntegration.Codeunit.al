codeunit 50243 "BreMac POS Sales Integration"
{
    Permissions = tabledata Customer = R,
        tabledata "Custom Report Selection" = R,
        tabledata Item = R,
        tabledata "Item Unit of Measure" = R,
        tabledata Location = R,
        tabledata "Report Selections" = R,
        tabledata "Sales Header" = RIMD,
        tabledata "Sales Line" = RIMD,
        tabledata "Sales Invoice Header" = RM,
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

    procedure GetPostedInvoicePdf(InvoiceId: Text): Text
    var
        SalesInvoiceHeader: Record "Sales Invoice Header";
    begin
        FindPostedInvoiceById(InvoiceId, SalesInvoiceHeader);
        exit(PostedInvoicePdfResponse(SalesInvoiceHeader));
    end;

    procedure GetPostedInvoicePdfV2(InvoiceId: Text; ReportId: Integer): Text
    var
        SalesInvoiceHeader: Record "Sales Invoice Header";
    begin
        FindPostedInvoiceById(InvoiceId, SalesInvoiceHeader);
        if ReportId <= 0 then
            exit(PostedInvoicePdfResponse(SalesInvoiceHeader));
        exit(PostedInvoiceReportPdfResponse(SalesInvoiceHeader, ReportId));
    end;

    procedure GetPostedInvoicePdfV3(InvoiceId: Text; RegisteredReportId: Integer; UnregisteredReportId: Integer): Text
    var
        SalesInvoiceHeader: Record "Sales Invoice Header";
        ReportId: Integer;
    begin
        FindPostedInvoiceById(InvoiceId, SalesInvoiceHeader);
        if CustomerIsRegistered(SalesInvoiceHeader."Bill-to Customer No.") then
            ReportId := RegisteredReportId
        else
            ReportId := UnregisteredReportId;
        if ReportId <= 0 then
            exit(PostedInvoicePdfResponse(SalesInvoiceHeader));
        exit(PostedInvoiceReportPdfResponse(SalesInvoiceHeader, ReportId));
    end;

    /// <summary>
    /// Authorizes and registers a reprint of a posted sales invoice before
    /// POS requests a fresh rendered PDF, so fields such as "No. Printed"
    /// contain the newly assigned copy number. Raise an Error from the
    /// OnBeforeAuthorizePostedInvoiceReprint
    /// subscriber (or from a "Sales Inv.-Printed" subscriber) to refuse the
    /// reprint; the POS then shows the error and prints nothing.
    /// </summary>
    procedure AuthorizePostedInvoiceReprint(InvoiceId: Text): Text
    var
        SalesInvoiceHeader: Record "Sales Invoice Header";
        SalesInvPrinted: Codeunit "Sales Inv.-Printed";
        Response: JsonObject;
        ResponseText: Text;
    begin
        FindPostedInvoiceById(InvoiceId, SalesInvoiceHeader);
        OnBeforeAuthorizePostedInvoiceReprint(SalesInvoiceHeader);
        // Registers the print exactly like the posted invoice Print action:
        // increments "No. Printed" and runs the standard print subscribers.
        SalesInvPrinted.Run(SalesInvoiceHeader);
        Response.Add('success', true);
        Response.Add('id', Format(SalesInvoiceHeader.SystemId));
        Response.Add('number', SalesInvoiceHeader."No.");
        Response.Add('noPrinted', SalesInvoiceHeader."No. Printed");
        Response.WriteTo(ResponseText);
        exit(ResponseText);
    end;

    [IntegrationEvent(false, false)]
    local procedure OnBeforeAuthorizePostedInvoiceReprint(var SalesInvoiceHeader: Record "Sales Invoice Header")
    begin
    end;

    local procedure CustomerIsRegistered(CustomerNo: Code[20]): Boolean
    var
        Customer: Record Customer;
        CustomerRecRef: RecordRef;
        FlagFieldRef: FieldRef;
        FlagFieldNo: Integer;
        IsRegistered: Boolean;
    begin
        if CustomerNo = '' then
            exit(false);
        if not Customer.Get(CustomerNo) then
            exit(false);

        FlagFieldNo := FindRegisteredFlagFieldNo();
        if FlagFieldNo = 0 then
            Error('No boolean "Registered" field was found on the Business Central Customer table.');

        CustomerRecRef.GetTable(Customer);
        FlagFieldRef := CustomerRecRef.Field(FlagFieldNo);
        IsRegistered := FlagFieldRef.Value();
        exit(IsRegistered);
    end;

    local procedure FindRegisteredFlagFieldNo(): Integer
    var
        FieldRec: Record Field;
    begin
        // Resolve the custom "Registered" flag dynamically so this extension
        // does not need a compile-time dependency on the extension owning it.
        FieldRec.SetRange(TableNo, Database::Customer);
        FieldRec.SetRange(Type, FieldRec.Type::Boolean);
        FieldRec.SetRange(FieldName, 'Registered');
        if FieldRec.FindFirst() then
            exit(FieldRec."No.");

        FieldRec.SetRange(FieldName);
        FieldRec.SetFilter(FieldName, '@*registered*');
        if FieldRec.FindFirst() then
            exit(FieldRec."No.");

        exit(0);
    end;

    local procedure GetBranchCodeFromLocation(Location: Record Location): Text
    var
        LocationRecRef: RecordRef;
        BranchFieldRef: FieldRef;
        BranchFieldNo: Integer;
    begin
        BranchFieldNo := FindBranchCodeFieldNo(Database::Location);
        if BranchFieldNo = 0 then
            exit('');
        LocationRecRef.GetTable(Location);
        BranchFieldRef := LocationRecRef.Field(BranchFieldNo);
        exit(Format(BranchFieldRef.Value()));
    end;

    local procedure ApplyBranchCodeToHeader(var SalesHeader: Record "Sales Header"; BranchCode: Text)
    var
        HeaderRecRef: RecordRef;
    begin
        if BranchCode = '' then
            exit;
        HeaderRecRef.GetTable(SalesHeader);
        if SetBranchCodeField(HeaderRecRef, FindBranchCodeFieldNo(Database::"Sales Header"), BranchCode) then
            HeaderRecRef.SetTable(SalesHeader);
    end;

    local procedure SetBranchCodeField(var RecRef: RecordRef; BranchFieldNo: Integer; BranchCode: Text): Boolean
    var
        BranchFieldRef: FieldRef;
    begin
        if (BranchFieldNo = 0) or (BranchCode = '') then
            exit(false);
        BranchFieldRef := RecRef.Field(BranchFieldNo);
        BranchFieldRef.Validate(CopyStr(BranchCode, 1, BranchFieldRef.Length()));
        exit(true);
    end;

    local procedure FindBranchCodeFieldNo(TableNo: Integer): Integer
    var
        FieldRec: Record Field;
    begin
        // The Branch Code is a customization; resolve it dynamically so this
        // extension needs no compile-time dependency on the extension owning it.
        FieldRec.SetRange(TableNo, TableNo);
        FieldRec.SetRange(FieldName, 'Branch Code');
        if FieldRec.FindFirst() then
            exit(FieldRec."No.");

        FieldRec.SetRange(FieldName);
        FieldRec.SetFilter(FieldName, '@*branch*code*');
        if FieldRec.FindFirst() then
            exit(FieldRec."No.");

        exit(0);
    end;

    local procedure FindPostedInvoiceById(InvoiceId: Text; var SalesInvoiceHeader: Record "Sales Invoice Header")
    var
        InvoiceSystemId: Guid;
    begin
        InvoiceId := DelChr(InvoiceId, '<>', ' ');
        if InvoiceId = '' then
            Error('The posted sales invoice id is required.');

        if Evaluate(InvoiceSystemId, InvoiceId) then
            if SalesInvoiceHeader.GetBySystemId(InvoiceSystemId) then
                exit;

        if SalesInvoiceHeader.Get(CopyStr(InvoiceId, 1, MaxStrLen(SalesInvoiceHeader."No."))) then
            exit;

        Error('Posted sales invoice %1 was not found in Business Central.', InvoiceId);
    end;

    local procedure PostedInvoicePdfResponse(SalesInvoiceHeader: Record "Sales Invoice Header"): Text
    var
        ReportSelections: Record "Report Selections";
        TempBlob: Codeunit "Temp Blob";
    begin
        SalesInvoiceHeader.SetRecFilter();
        // Render through the same report-selection pipeline as the posted
        // invoice Print action so the PDF uses the configured report and any
        // per-customer custom report layouts, not the Print/Send API default.
        ReportSelections.GetPdfReportForCust(
            TempBlob,
            Enum::"Report Selection Usage"::"S.Invoice",
            SalesInvoiceHeader,
            SalesInvoiceHeader."Bill-to Customer No.");
        if not TempBlob.HasValue() then
            Error('Business Central rendered an empty report PDF for posted invoice %1.', SalesInvoiceHeader."No.");
        exit(BuildPdfResponse(SalesInvoiceHeader, TempBlob));
    end;

    local procedure PostedInvoiceReportPdfResponse(SalesInvoiceHeader: Record "Sales Invoice Header"; ReportId: Integer): Text
    var
        TempBlob: Codeunit "Temp Blob";
        InvoiceRecordRef: RecordRef;
        PdfOutStream: OutStream;
    begin
        SalesInvoiceHeader.SetRecFilter();
        InvoiceRecordRef.GetTable(SalesInvoiceHeader);
        TempBlob.CreateOutStream(PdfOutStream);
        // Renders the specific report object with its active layout, exactly
        // as running that report directly in Business Central.
        Report.SaveAs(ReportId, '', ReportFormat::Pdf, PdfOutStream, InvoiceRecordRef);
        if not TempBlob.HasValue() then
            Error('Business Central rendered an empty PDF with report %1 for posted invoice %2.', ReportId, SalesInvoiceHeader."No.");
        exit(BuildPdfResponse(SalesInvoiceHeader, TempBlob));
    end;

    local procedure BuildPdfResponse(SalesInvoiceHeader: Record "Sales Invoice Header"; var TempBlob: Codeunit "Temp Blob"): Text
    var
        Base64Convert: Codeunit "Base64 Convert";
        PdfInStream: InStream;
        Response: JsonObject;
        ResponseText: Text;
    begin
        TempBlob.CreateInStream(PdfInStream);
        Response.Add('success', true);
        Response.Add('id', Format(SalesInvoiceHeader.SystemId));
        Response.Add('number', SalesInvoiceHeader."No.");
        Response.Add('pdfBase64', Base64Convert.ToBase64(PdfInStream));
        Response.WriteTo(ResponseText);
        exit(ResponseText);
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
        BranchCode: Text;
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
        // The customer's assigned BC Location Code wins; the POS-mapped
        // location is only the fallback for customers without one.
        if Customer."Location Code" <> '' then
            LocationCode := Customer."Location Code";
        Salesperson.Get(SalespersonCode);
        Salesperson.TestField(Blocked, false);
        Location.Get(LocationCode);
        // The Branch Code goes hand in hand with the chosen location.
        BranchCode := GetBranchCodeFromLocation(Location);

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
        ApplyBranchCodeToHeader(SalesHeader, BranchCode);
        SalesHeader.Ship := true;
        SalesHeader.Invoice := true;
        SalesHeader.Modify(true);
        SalesHeader.Get(SalesHeader."Document Type"::Order, DraftNo);
        SalesHeader.TestField("Location Code", LocationCode);

        InsertLines(SalesHeader, Lines, LocationCode, BranchCode);
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

    local procedure InsertLines(SalesHeader: Record "Sales Header"; Lines: JsonArray; LocationCode: Code[10]; BranchCode: Text)
    var
        Item: Record Item;
        ItemUnitOfMeasure: Record "Item Unit of Measure";
        SalesLine: Record "Sales Line";
        LineRecRef: RecordRef;
        LineObject: JsonObject;
        LineToken: JsonToken;
        DiscountAmount: Decimal;
        DiscountPercent: Decimal;
        ItemNumber: Code[20];
        LineBranchFieldNo: Integer;
        LineIndex: Integer;
        LineNo: Integer;
        Quantity: Decimal;
        UnitOfMeasureCode: Code[10];
        UnitPrice: Decimal;
    begin
        if Lines.Count() = 0 then
            Error('The POS sale must contain at least one line.');

        if BranchCode <> '' then
            LineBranchFieldNo := FindBranchCodeFieldNo(Database::"Sales Line");

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
            if LineBranchFieldNo <> 0 then begin
                LineRecRef.GetTable(SalesLine);
                if SetBranchCodeField(LineRecRef, LineBranchFieldNo, BranchCode) then
                    LineRecRef.SetTable(SalesLine);
            end;
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
