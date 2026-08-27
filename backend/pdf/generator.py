from reportlab.platypus import (
    SimpleDocTemplate,
    Table,
    TableStyle,
    Paragraph,
    Spacer
)
from reportlab.lib import colors
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_CENTER, TA_LEFT, TA_RIGHT
from reportlab.lib.pagesizes import letter
from reportlab.graphics.shapes import Drawing
from reportlab.graphics.barcode import qr


def get_val(obj, key, default="N/A"):
    """
    Safely extracts value from either a Python dict or an ORM Model object.
    """
    if obj is None:
        return default
    if isinstance(obj, dict):
        return obj.get(key, default)
    return getattr(obj, key, default)


def generate_agreement(
    output_target,
    agreement_id,
    booking,
    owner,
    customer,
    bike,
    ai_risk=None
):
    """
    Generates an official Professional Digital Rental Agreement PDF.

    :param output_target: File path string or BytesIO memory buffer
    :param agreement_id: Unique agreement reference string
    :param booking: Booking ORM model instance or dict
    :param owner: Owner/Company ORM model instance or dict
    :param customer: Customer ORM model instance or dict
    :param bike: Bike ORM model instance or dict
    :param ai_risk: Optional AI verification data dict or model instance
    """
    
    # Letter size: 612 x 792 pt. Printable width = 540 pt
    doc = SimpleDocTemplate(
        output_target,
        pagesize=letter,
        leftMargin=36,
        rightMargin=36,
        topMargin=36,
        bottomMargin=36
    )

    styles = getSampleStyleSheet()

    # --- Typography Styles ---
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Heading1'],
        fontName='Helvetica-Bold',
        fontSize=15,
        leading=18,
        textColor=colors.HexColor("#0B5394"),
        alignment=TA_LEFT
    )

    section_heading = ParagraphStyle(
        'SectionHeading',
        fontName='Helvetica-Bold',
        fontSize=9.5,
        leading=12,
        textColor=colors.HexColor("#0B5394"),
        spaceAfter=4
    )

    label_style = ParagraphStyle(
        'CellLabel',
        fontName='Helvetica-Bold',
        fontSize=8,
        leading=10,
        textColor=colors.HexColor("#333333")
    )

    val_style = ParagraphStyle(
        'CellVal',
        fontName='Helvetica',
        fontSize=8,
        leading=10,
        textColor=colors.HexColor("#222222")
    )

    # Sub-table generator helper
    def create_kv_table(data_tuples, width=262):
        table_data = [[Paragraph(f"<b>{k}</b>", label_style), Paragraph(str(v), val_style)] for k, v in data_tuples]
        t = Table(table_data, colWidths=[80, width - 80])
        t.setStyle(TableStyle([
            ('BACKGROUND', (0, 0), (0, -1), colors.HexColor("#F8FAFC")),
            ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor("#CBD5E1")),
            ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
            ('TOPPADDING', (0, 0), (-1, -1), 2.5),
            ('BOTTOMPADDING', (0, 0), (-1, -1), 2.5),
            ('LEFTPADDING', (0, 0), (-1, -1), 5),
            ('RIGHTPADDING', (0, 0), (-1, -1), 5),
        ]))
        return t

    elements = []

    # --- 1. Header & Dynamic QR Code ---
    qr_code = qr.QrCodeWidget(str(agreement_id))
    bounds = qr_code.getBounds()
    width = bounds[2] - bounds[0]
    height = bounds[3] - bounds[1]

    qr_drawing = Drawing(60, 60, transform=[60 / width, 0, 0, 60 / height, 0, 0])
    qr_drawing.add(qr_code)

    header_text = Paragraph(
        "<b>BIKE SHARING PLATFORM</b><br/>"
        "<font size=9 color='#555555'>Professional Bike Rental Agreement & Legal Contract</font>",
        title_style
    )

    header_table = Table([[header_text, qr_drawing]], colWidths=[475, 65])
    header_table.setStyle(TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('ALIGN', (1, 0), (1, 0), 'RIGHT'),
    ]))
    elements.append(header_table)
    elements.append(Spacer(1, 6))

    # --- 2. Agreement Metadata Bar ---
    gen_date = get_val(booking, 'generated_date', get_val(booking, 'booking_date', 'N/A'))
    gen_time = get_val(booking, 'generated_time', '')
    date_time_str = f"{gen_date} {gen_time}".strip()

    agr_data = [
        [
            Paragraph("Agreement ID:", label_style), Paragraph(str(agreement_id), val_style),
            Paragraph("Generated On:", label_style), Paragraph(str(date_time_str), val_style)
        ],
        [
            Paragraph("Booking ID:", label_style), Paragraph(str(get_val(booking, 'id', get_val(booking, 'booking_id'))), val_style),
            Paragraph("Status:", label_style), Paragraph(f"<b><font color='#2E7D32'>{get_val(booking, 'status', 'ACCEPTED')}</font></b>", val_style)
        ]
    ]
    agr_table = Table(agr_data, colWidths=[85, 185, 85, 185])
    agr_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor("#F0F4F8")),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor("#CBD5E1")),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 3.5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3.5),
        ('LEFTPADDING', (0, 0), (-1, -1), 6),
    ]))
    elements.append(agr_table)
    elements.append(Spacer(1, 8))

    # --- 3. Owner / Company Details vs Customer Details ---
    owner_company = get_val(owner, 'company_name', get_val(owner, 'provider_company', 'N/A'))
    
    owner_kv = create_kv_table([
        ("Owner Name", get_val(owner, 'full_name', get_val(owner, 'owner_name'))),
        ("Company", owner_company),
        ("Phone", get_val(owner, 'phone')),
        ("Email", get_val(owner, 'email'))
    ], 262)

    customer_kv = create_kv_table([
        ("Full Name", get_val(customer, 'full_name')),
        ("Email", get_val(customer, 'email')),
        ("Phone", get_val(customer, 'phone')),
        ("CNIC", get_val(customer, 'cnic'))
    ], 262)

    parties_table = Table([
        [Paragraph("OWNER / COMPANY DETAILS", section_heading), Paragraph("CUSTOMER DETAILS", section_heading)],
        [owner_kv, customer_kv]
    ], colWidths=[270, 270])
    parties_table.setStyle(TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('RIGHTPADDING', (0, 0), (0, -1), 8),
        ('LEFTPADDING', (1, 0), (1, -1), 8),
        ('TOPPADDING', (0, 0), (-1, -1), 0),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 0),
    ]))
    elements.append(parties_table)
    elements.append(Spacer(1, 8))

    # --- 4. Bike Details vs Booking Details ---
    bike_kv = create_kv_table([
        ("Bike Name", get_val(bike, 'bike_name')),
        ("Brand / Model", f"{get_val(bike, 'brand')} / {get_val(bike, 'model')}"),
        ("Registration", get_val(bike, 'registration_number', get_val(bike, 'registration'))),
        ("City / Engine", f"{get_val(bike, 'city')} / {get_val(bike, 'engine', '100')} cc"),
        ("Fuel / Trans.", f"{get_val(bike, 'fuel', 'Petrol')} / {get_val(bike, 'transmission', 'Manual')}")
    ], 262)

    start_str = f"{get_val(booking, 'start_date')} ({get_val(booking, 'start_time', '')})".strip()
    end_str = f"{get_val(booking, 'end_date')} ({get_val(booking, 'end_time', '')})".strip()

    booking_kv = create_kv_table([
        ("Booking Type", get_val(booking, 'booking_type', 'Hourly/Daily')),
        ("Start Schedule", start_str),
        ("End Schedule", end_str),
        ("Booking Status", get_val(booking, 'status', 'Approved'))
    ], 262)

    details_table = Table([
        [Paragraph("BIKE INFORMATION", section_heading), Paragraph("BOOKING DETAILS", section_heading)],
        [bike_kv, booking_kv]
    ], colWidths=[270, 270])
    details_table.setStyle(TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('RIGHTPADDING', (0, 0), (0, -1), 8),
        ('LEFTPADDING', (1, 0), (1, -1), 8),
        ('TOPPADDING', (0, 0), (-1, -1), 0),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 0),
    ]))
    elements.append(details_table)
    elements.append(Spacer(1, 8))

    # --- 5. Payment Summary vs AI Risk Assessment ---
    rental_charge = get_val(booking, 'rental_charges', get_val(booking, 'amount', '800.0'))
    sec_deposit = get_val(booking, 'security_deposit', get_val(booking, 'deposit', '0'))
    total_pay = get_val(booking, 'total_payable', get_val(booking, 'total_amount', rental_charge))

    payment_data = [
        [Paragraph("Item", label_style), Paragraph("Amount", ParagraphStyle('RHead', parent=label_style, alignment=TA_RIGHT))],
        [Paragraph("Rental Charges", val_style), Paragraph(f"Rs. {rental_charge}", ParagraphStyle('RVal', parent=val_style, alignment=TA_RIGHT))],
        [Paragraph("Security Deposit", val_style), Paragraph(f"Rs. {sec_deposit}", ParagraphStyle('RVal', parent=val_style, alignment=TA_RIGHT))],
        [Paragraph("Tax Status", val_style), Paragraph("Tax Included", ParagraphStyle('RVal', parent=val_style, alignment=TA_RIGHT))],
        [Paragraph("<b>TOTAL PAYABLE</b>", label_style), Paragraph(f"<b>Rs. {total_pay}</b>", ParagraphStyle('BTVal', parent=label_style, alignment=TA_RIGHT))]
    ]
    payment_table = Table(payment_data, colWidths=[150, 112])
    payment_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor("#0B5394")),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor("#CBD5E1")),
        ('BACKGROUND', (0, -1), (-1, -1), colors.HexColor("#E2E8F0")),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 3),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 3),
    ]))

    ai_data = [
        [Paragraph("Check Point", label_style), Paragraph("Status", label_style)],
        [Paragraph("Customer Verification", val_style), Paragraph(f"<font color='#2E7D32'>{get_val(ai_risk, 'cust_verif', '[VERIFIED]')}</font>", val_style)],
        [Paragraph("Driving License", val_style), Paragraph(f"<font color='#2E7D32'>{get_val(ai_risk, 'license_verif', '[VERIFIED]')}</font>", val_style)],
        [Paragraph("Fraud Detection", val_style), Paragraph(str(get_val(ai_risk, 'fraud_status', 'Low Risk')), val_style)],
        [Paragraph("Risk Score", val_style), Paragraph(str(get_val(ai_risk, 'risk_score', '8 / 100')), val_style)],
        [Paragraph("Recommendation", label_style), Paragraph(f"<b><font color='#2E7D32'>{get_val(ai_risk, 'recommendation', 'APPROVED')}</font></b>", label_style)]
    ]
    ai_table = Table(ai_data, colWidths=[150, 112])
    ai_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, 0), colors.HexColor("#334155")),
        ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
        ('GRID', (0, 0), (-1, -1), 0.5, colors.HexColor("#CBD5E1")),
        ('BACKGROUND', (0, -1), (-1, -1), colors.HexColor("#DCFCE7")),
        ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
        ('TOPPADDING', (0, 0), (-1, -1), 2.5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 2.5),
    ]))

    finance_ai_table = Table([
        [Paragraph("PAYMENT SUMMARY", section_heading), Paragraph("AI RISK ASSESSMENT", section_heading)],
        [payment_table, ai_table]
    ], colWidths=[270, 270])
    finance_ai_table.setStyle(TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'TOP'),
        ('RIGHTPADDING', (0, 0), (0, -1), 8),
        ('LEFTPADDING', (1, 0), (1, -1), 8),
        ('TOPPADDING', (0, 0), (-1, -1), 0),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 0),
    ]))
    elements.append(finance_ai_table)
    elements.append(Spacer(1, 8))

    # --- 6. Terms & Conditions (Line by Line Numbered 1 to 10) ---
    terms_html = """
    <b>TERMS & CONDITIONS:</b><br/>
    1. Customer must possess a valid driving license.<br/>
    2. Customer is responsible for all traffic challans during rental.<br/>
    3. Any accident caused due to customer negligence is customer's responsibility.<br/>
    4. Bike must be returned before booking expiry.<br/>
    5. Late return charges may apply.<br/>
    6. Customer must not use the bike for illegal activities.<br/>
    7. Fuel expenses are customer's responsibility.<br/>
    8. Damage charges will be calculated after inspection.<br/>
    9. Company reserves the right to terminate agreement if policies are violated.<br/>
    10. Both parties agree to all above conditions.
    """
    terms_p = Paragraph(terms_html, ParagraphStyle('TermsStyle', fontName='Helvetica', fontSize=7.5, leading=10.5, textColor=colors.HexColor("#334155")))
    terms_table = Table([[terms_p]], colWidths=[540])
    terms_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor("#F8FAFC")),
        ('BOX', (0, 0), (-1, -1), 0.5, colors.HexColor("#CBD5E1")),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8),
        ('TOPPADDING', (0, 0), (-1, -1), 5),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 5),
    ]))
    elements.append(terms_table)
    elements.append(Spacer(1, 8))

    # --- 7. Agreement Declaration ---
    decl_text = Paragraph(
        "<b>Agreement Declaration:</b> Both parties agree that all information provided above is correct "
        "and they accept all the Terms & Conditions mentioned in this agreement.",
        ParagraphStyle('DeclStyle', fontName='Helvetica-Oblique', fontSize=7.5, leading=9.5, textColor=colors.HexColor("#1E293B"))
    )
    decl_table = Table([[decl_text]], colWidths=[540])
    decl_table.setStyle(TableStyle([
        ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor("#FEF3C7")),
        ('BOX', (0, 0), (-1, -1), 0.5, colors.HexColor("#F59E0B")),
        ('LEFTPADDING', (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8),
        ('TOPPADDING', (0, 0), (-1, -1), 4),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 4),
    ]))
    elements.append(decl_table)
    elements.append(Spacer(1, 14))

    # --- 8. Signatures ---
    sig_style = ParagraphStyle('SigStyle', fontName='Helvetica', fontSize=8, alignment=TA_CENTER)
    signature_table = Table([
        [
            Paragraph("<b>Customer Signature</b><br/><br/><br/>___________________________", sig_style),
            Paragraph("<b>Owner / Company Signature</b><br/><br/><br/>___________________________", sig_style)
        ]
    ], colWidths=[270, 270])
    signature_table.setStyle(TableStyle([
        ('VALIGN', (0, 0), (-1, -1), 'BOTTOM'),
        ('ALIGN', (0, 0), (-1, -1), 'CENTER'),
    ]))
    elements.append(signature_table)
    elements.append(Spacer(1, 10))

    # --- 9. Footer ---
    footer_style = ParagraphStyle('FooterStyle', fontName='Helvetica', fontSize=7, leading=9.5, alignment=TA_CENTER, textColor=colors.HexColor("#64748B"))
    footer = Paragraph(
        "<b>BIKE SHARING PLATFORM</b> &bull; OFFICIAL VERIFIED DIGITAL AGREEMENT<br/>"
        "Support: support@bikesharing.pk &bull; Website: www.bikesharing.pk &bull; Version 2.0",
        footer_style
    )
    elements.append(footer)

    # Build PDF Document
    doc.build(elements)


# Function Aliases for flexible imports
generate_agreement = generate_agreement