import { DatePipe } from '@angular/common';
import { Component, OnInit, ViewChild } from '@angular/core';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
import * as ExcelJS from 'exceljs';
declare let alertify: any;

@Component({
  selector: 'app-rmpmindend',
  templateUrl: './rmpmindend.component.html',
  styleUrls: ['./rmpmindend.component.css', '../../../shared/purchase-vapp-host.css'],
  providers: [DatePipe],
})
export class RmpmindendComponent implements OnInit {

  @ViewChild('poform') poform: NgForm;

  maxDate = '';
  from_date = '';
  today = '';
  po_date = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01') || '';
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd') || '';
    const today = new Date();
    this.po_date = today.toISOString().split('T')[0];
    this.maxDate = today.toISOString().split('T')[0];
  }

  ngOnInit(): void {
    this.getPendingIndends();
    this.getTerm();
    this.getCompanies();
  }

  generatePDF(): void {
    const contentToConvert = document.getElementById('contentToConvert');
    if (contentToConvert) this.service.convertToPDF(contentToConvert, 'Po-Download');
  }

  companies: any[] = [];
  getCompanies(): void {
    this.service.get('master/company.php?type=getCompany').subscribe({
      next: (response: any) => { this.companies = Array.isArray(response) ? response : []; },
      error: () => { this.companies = []; }
    });
  }

  selectedBill: any = {};
  selectedShip: any = {};
  billcompany_code = '';
  shipcompany_code = '';
  taxType = '';

  getBillToShippToDetails(index: number, value: string): void {
    const idx = index - 1;
    if (idx >= 0 && this.companies && this.companies[idx]) {
      if (value === 'BILLTO') {
        this.selectedBill = this.companies[idx];
      } else {
        this.selectedShip = this.companies[idx];
      }
      this.updateTaxTypeFromBillShip();
    } else {
      if (value === 'BILLTO') {
        this.selectedBill = {};
      } else {
        this.selectedShip = {};
      }
      this.taxType = '';
    }
    this.applyDiscpuntToAll();
  }

  private companyCountry(row: any): string {
    return (row?.country ?? '').toString().trim().toLowerCase();
  }

  isIndiaCompany(row: any): boolean {
    return this.companyCountry(row) === 'india';
  }

  isCanadaCompany(row: any): boolean {
    return this.companyCountry(row) === 'canada';
  }

  getStateLabel(row: any): string {
    return this.isIndiaCompany(row) ? 'State:' : 'State/Province:';
  }

  getPostalLabel(row: any): string {
    if (this.isIndiaCompany(row)) {
      return 'Postal Code:';
    }
    if (this.isCanadaCompany(row)) {
      return 'Postal Code:';
    }
    return 'Postal / ZIP Code:';
  }

  getLocationLabel(row: any): string {
    return this.isIndiaCompany(row) ? 'City:' : 'City/Location:';
  }

  getCompanyLocation(row: any): string {
    if (!row) {
      return '';
    }
    const city = (row.present_city ?? '').toString().trim();
    const area = (row.area ?? '').toString().trim();
    if (city && area && city !== area) {
      return city + ', ' + area;
    }
    return city || area || '';
  }

  private updateTaxTypeFromBillShip(): void {
    if (!this.selectedBill?.present_state || !this.selectedShip?.present_state) {
      this.taxType = '';
      return;
    }
    if (this.isIndiaCompany(this.selectedBill) && this.isIndiaCompany(this.selectedShip)) {
      this.taxType = this.selectedBill.present_state === this.selectedShip.present_state ? 'GST' : 'IGST';
      return;
    }
    this.taxType = this.selectedBill.present_state === this.selectedShip.present_state ? 'GST' : 'IGST';
  }

  results: any[] = [];
  material_type = 'Raw Material';
  loading = false;
  searchQuery = '';

  getPendingIndends(): void {
    this.loading = true;
    this.service.get('purchase/po/raw.php?type=getPendingIndend&For=RMPM&material_type=' + encodeURIComponent(this.material_type)).subscribe({
      next: (response: any) => {
        const list = Array.isArray(response) ? response : [];
        this.results = list.sort((a: any, b: any) => this.pendingRowSortKey(b) - this.pendingRowSortKey(a));
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
      }
    });
  }

  private pendingRowSortKey(row: any): number {
    const id = Number(row?.id);
    if (!isNaN(id) && id > 0) {
      return id;
    }
    const entryDate = row?.entry_date ? new Date(row.entry_date).getTime() : 0;
    return isNaN(entryDate) ? 0 : entryDate;
  }

  downloadExcel(): void {
    const list = this.filteredMaterials || [];
    if (list.length === 0) return;
    const headers = ['Sr.No', 'Material Type', 'Purchase Requisition No.', 'Date', 'Materials', 'Entered By', 'Vendor Name'];
    const rows = list.map((row: any, index: number) => {
      const matNames = this.materialsDisplay(row);
      return [
        index + 1,
        row.material_type ?? '',
        row.indend_no ?? '',
        this.formatDate(row.entry_date),
        matNames,
        this.entryByDisplay(row),
        this.vendorDisplay(row),
      ];
    });
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Pending Indents', { views: [{ rightToLeft: false }] });
    const headerRow = ws.addRow(headers);
    const headerColors = ['FF0d9488', 'FF059669', 'FF0891b2', 'FF7c3aed', 'FFdc2626', 'FFea580c', 'FF2563eb'];
    headerRow.eachCell((cell, colNumber) => {
      cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: headerColors[colNumber - 1] || 'FF64748b' } };
      cell.font = { bold: true, color: { argb: 'FFFFFFFF' }, size: 11 };
      cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
      cell.border = { top: { style: 'thin' }, bottom: { style: 'thin' }, left: { style: 'thin' }, right: { style: 'thin' } };
    });
    ws.getRow(1).height = 22;
    const colWidths = [8, 18, 14, 12, 36, 14, 28];
    ws.columns.forEach((col, i) => { if (i < headers.length) col.width = colWidths[i] ?? 14; });
    ws.addRows(rows);
    const thinBorder = { top: { style: 'thin' as const }, bottom: { style: 'thin' as const }, left: { style: 'thin' as const }, right: { style: 'thin' as const } };
    for (let r = 2; r <= rows.length + 1; r++) {
      ws.getRow(r).eachCell((cell) => { cell.alignment = { vertical: 'middle', horizontal: 'left', wrapText: true }; cell.border = thinBorder; });
      ws.getRow(r).height = 20;
    }
    wb.xlsx.writeBuffer().then((buffer) => {
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'Pending_Indents_For_PO.xlsx';
      a.click();
      URL.revokeObjectURL(a.href);
    });
  }

  formatDate(dateString: string): string {
    if (!dateString) return '';
    const d = new Date(dateString);
    const day = ('0' + d.getDate()).slice(-2);
    const month = ('0' + (d.getMonth() + 1)).slice(-2);
    return `${day}-${month}-${d.getFullYear()}`;
  }

  gstSplitData: any[] = [];
  selectedPO: any = null;
  purchase_type = '';
  isView = false;
  marketType = 'Imported';

  view(data: any): void {
    this.marketType = (data && data['country'] === 'India') ? 'Domestic' : 'Imported';
    this.gstSplitData = [];
    this.selectedPO = data;
    this.selectedBill = {};
    this.selectedShip = {};
    this.purchase_type = this.selectedPO ? this.selectedPO['purchase_type'] : '';
    this.poAmount = 0;
    this.shipping_total = 0;
    this.shipping_handling = 0;
    this.shipping_gst = 0;
    this.shipping_Gst_amt = 0;
    this.paymentTerms = [];
    this.schedule_data = [];
    this.taxType = '';
    this.sameaddress = false;
    // Pre-compute line nets and select all materials so Total Payable is ready
    const indends = (this.selectedPO && this.selectedPO['indends']) ? this.selectedPO['indends'] : [];
    indends.forEach((mat: any) => {
      this.ensureMaterialTotals(mat);
      mat.check = true;
    });
    this.isView = true;
    this.getTaxSplitUp();
  }
 
  sendPO(data: any): void {
    if (!data || !data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (!this.selectedPO || !this.selectedPO['indends']) {
      alertify.error('No indent data');
      return;
    }
    if (!this.selectedBill || !this.selectedBill['company_code']) {
      alertify.error('Please select Bill To company');
      return;
    }
    // if (!this.selectedShip || !this.selectedShip['company_code']) {
    //   alertify.error('Please select Ship To company');
    //   return;
    // }

    const temp = data.value;
    const terms: any[] = [];
    const termList = this.terms || [];
    for (let i = 0; i < termList.length; i++) {
      const term = termList[i];
      if (term && term['selected']) terms.push(term);
    }

    let gross_total = 0;
    let taxable_total = 0;
    let disc_total = 0;
    let gst_total = 0;
    let net_total = 0;
    let sgst_total = 0;
    let cgst_total = 0;
    let igst_total = 0;
    let poAmount = 0;

    const indents = this.selectedPO['indends'] || [];
    const checkedItems = indents.filter((item: any) => item.check === true);
    if (checkedItems.length === 0) {
      alertify.error('Please select materials to proceed');
      return;
    }

    checkedItems.forEach((item: any) => {
        poAmount = Number(poAmount) + Number(item?.net_total);
        gross_total += Number(item.gross_total);
        taxable_total += Number(item.taxable_amt);
        disc_total += Number(item.disc_amt);
        gst_total += Number(item.tax_total);
        net_total += Number(item.net_total);
        sgst_total += Number(item.sgst);
        cgst_total += Number(item.cgst);
        igst_total += Number(item.igst);
    });
 

    temp['gross_total'] = gross_total;
    temp['taxable_total'] = taxable_total;
    temp['disc_total'] = disc_total;
    temp['gst_total'] = gst_total;
    temp['net_total'] = net_total;
    temp['sgst_total'] = sgst_total;
    temp['cgst_total'] = cgst_total;
    temp['igst_total'] = igst_total;
    temp['final_total'] = poAmount + Number(this.shipping_total);
    temp['rounding'] =  Math.round(poAmount + Number(this.shipping_total));
 
    temp['terms_conditions'] = terms;
    temp['materials'] = checkedItems;
    temp['indent_no'] = this.selectedPO['indend_no'];
    temp['po_type'] = this.resolvePoType(this.selectedPO);
    temp['vendor_no'] = this.selectedPO['vendor_no'];
    temp['currency'] = this.selectedPO['currency'];
    temp['shipcompany_code'] = this.selectedShip && this.selectedShip['company_code'];
    temp['billcompany_code'] = this.selectedBill && this.selectedBill['company_code'];
    temp['selectedShip'] = this.selectedShip;
    temp['selectedBill'] = this.selectedBill;
    temp['gstSplitData'] = this.gstSplitData;
    temp['schedule_data'] = this.schedule_data;
    temp['paymentTerms'] = this.paymentTerms;
    temp['purchase_type'] = this.purchase_type;
    temp['taxType'] = this.taxType;
    temp['shipping_handling'] = this.shipping_handling;
    temp['shipping_gst'] = this.shipping_gst;
    temp['shipping_Gst_amt'] = this.shipping_Gst_amt;
    temp['shipping_total'] = this.shipping_total;
    temp['marketType'] = this.marketType;

 
    this.service.post('purchase/po/raw.php?type=saveIndendPONew1', JSON.stringify(temp)).subscribe({
      next: (response: any) => {
        if (response && response['status'] === 'success') {
          alertify.success('Purchase Order is Send for Approval');
          if (data && data.resetForm) data.resetForm();
          this.selectedBill = {};
          this.selectedShip = {};
          this.discount_In = 'Percentage';
          this.gstSplitData = [];
          this.shipping_type = 'Vendor Account';
          this.schedule_data = [];
          this.paymentTerms = [];
          this.isView = false;
          this.clicked = false;
          this.marketType = 'Imported';
          this.getPendingIndends();
          window.location.reload();
        } else {
          alertify.error(response['status'] || 'Failed');
        }
      },
      error: () => alertify.error('Failed to submit PO')
    });
  }

  get isFormInvalid(): boolean {
    return this.poform ? this.poform.invalid : true;
  }

  private resolvePoType(selected: any): string {
    const header = String(selected?.material_type || '').trim();
    if (header === 'Raw Material' || header === 'Packing Material') {
      return header;
    }
    const lines = Array.isArray(selected?.indends) ? selected.indends : [];
    for (const line of lines) {
      const lineType = String(line?.material_type || '').trim();
      if (lineType === 'Raw Material' || lineType === 'Packing Material') {
        return lineType;
      }
    }
    if (this.material_type === 'Raw Material' || this.material_type === 'Packing Material') {
      return this.material_type;
    }
    const probe = String(lines[0]?.material_type || header).toLowerCase();
    if (probe.includes('packing')) {
      return 'Packing Material';
    }
    if (probe.includes('raw') || probe.includes('rm/pm')) {
      return 'Raw Material';
    }
    return header || this.material_type || 'Raw Material';
  }
 


  isTerm = false;
  saveTerm(data: any) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('master/terms.php?type=saveTerms&status=PO', JSON.stringify(data.value)).subscribe((response) => {
        if (response['status'] == 'success') {
          this.getTerm();
          this.isTerm = false;
          alert('Record Inserted Successfully');
          data.resetForm();
        } else {
          alert('Please try Again');
        }
    });
  }

  terms: any[] = [];
  getTerm(): void {
    this.service.get('master/terms.php?type=getTermForPO&status=PO').subscribe({
      next: (response: any) => { this.terms = Array.isArray(response) ? response : []; },
      error: () => { this.terms = []; }
    });
  }

  isEdit = false;
  delTerm(id) {
    this.service.get('master/terms.php?type=deleteTerm&id=' + id).subscribe((response) => {
        this.getTerm();
        if (response['status'] == 'success') {
          this.isEdit = false;
          alertify.success('Term Deleted Successfully');
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
    });
  }


  clicked = false;
 

 
  sameaddress =false;

  onCheckboxChange(): void {
    if (this.sameaddress === true && this.selectedBill) {
      this.selectedShip = { ...this.selectedBill };
      this.updateTaxTypeFromBillShip();
    } else {
      this.selectedShip = {};
      this.taxType = '';
    }
    this.applyDiscpuntToAll();
  }



  discount_In = 'Percentage';
  po_discount_per = 0;
  po_discount_amt = 0;

  applyDiscount(user) {

    if(this.taxType == ''){
      alertify.error("Please Select Bill TO / Ship To !!!!");
      return;
    }

    user['disc_amt'] =   this.round2(user['gross_total'] * (user['disc_per'] / 100));
    user["taxable_amt"] = this.round2(user["gross_total"] - user["disc_amt"]);
    user["tax_total"] = this.round2(user["taxable_amt"] * (user["gst"] / 100));
    user["net_total"] = this.round2(user["taxable_amt"] + user["tax_total"]);

    if(this.taxType == 'GST'){
        user['sgst'] = user["tax_total"] / 2;
        user['sgstPer'] = user["gst"] / 2;
        user['cgst'] = user["tax_total"] / 2;
        user['cgstPer'] = user["gst"] / 2;
        user['igst'] = 0;
        user['igstPer'] = 0;
    }else{
        user['sgst'] = 0;
        user['sgstPer'] = 0;
        user['cgst'] = 0;
        user['cgstPer'] = 0;
        user['igst'] = user["tax_total"]
        user['igstPer'] = user["gst"]
    }

    this.getTaxSplitUp();
  }

  applyDiscountOnAmt(user) {
    user['disc_per'] =   this.round2((user['disc_amt'] / user['gross_total']) * 100);
    this.applyDiscount(user);
  }

  applyDiscpuntToAll(): void {
    if (this.taxType === '') {
      alertify.error('Please Select Bill TO / Ship To !!!!');
      return;
    }
    const indends = this.selectedPO && this.selectedPO['indends'] ? this.selectedPO['indends'] : [];
    for (let i = 0; i < indends.length; i++) {
      const mat = indends[i];

        if(this.discount_In == 'Percentage'){
            mat['disc_per'] = this.po_discount_per;
        }else if(this.discount_In == 'Amount'){
            mat['disc_amt'] = this.po_discount_amt;
            mat['disc_per'] =   this.round2((mat['disc_amt'] / mat['gross_total']) * 100);
        }else{
            mat['disc_per'] = 0;
        }

        mat['disc_amt'] =   this.round2(mat['gross_total'] * (mat['disc_per'] / 100));
        mat["taxable_amt"] = this.round2(mat["gross_total"] - mat["disc_amt"]);
        mat["tax_total"] = this.round2(mat["taxable_amt"] * (mat["gst"] / 100));
        mat["net_total"] = this.round2(mat["taxable_amt"] + mat["tax_total"]);
  
        if(this.taxType == 'GST'){
            mat['sgst'] = mat["tax_total"] / 2;
            mat['sgstPer'] = mat["gst"] / 2;
            mat['cgst'] = mat["tax_total"] / 2;
            mat['cgstPer'] = mat["gst"] / 2;
            mat['igst'] = 0;
            mat['igstPer'] = 0;
        }else{
            mat['sgst'] = 0;
            mat['sgstPer'] = 0;
            mat['cgst'] = 0;
            mat['cgstPer'] = 0;
            mat['igst'] = mat["tax_total"]
            mat['igstPer'] = mat["gst"]
        }



    }

    this.getTaxSplitUp();
  }

  // Helper for rounding consistently to 2 decimal places
  round2(value) {
    return Math.round((parseFloat(value) + Number.EPSILON) * 100) / 100;
  }

  /** Ensure gross / taxable / tax / net exist on a material line (from quotation). */
  ensureMaterialTotals(mat: any): void {
    if (!mat) {
      return;
    }
    const qty = parseFloat(mat.order_qty) || parseFloat(mat.splitQty) || 0;
    const rate = parseFloat(mat.quotation_amt) || 0;
    if (mat.gross_total == null || mat.gross_total === '' || isNaN(Number(mat.gross_total))) {
      mat.gross_total = this.round2(qty * rate);
    } else {
      mat.gross_total = this.round2(mat.gross_total);
    }
    const discPer = parseFloat(mat.disc_per) || 0;
    if (mat.disc_amt == null || mat.disc_amt === '' || isNaN(Number(mat.disc_amt))) {
      mat.disc_amt = this.round2(Number(mat.gross_total) * (discPer / 100));
    } else {
      mat.disc_amt = this.round2(mat.disc_amt);
    }
    mat.taxable_amt = this.round2(Number(mat.gross_total) - Number(mat.disc_amt));
    const gst = parseFloat(mat.gst) || 0;
    mat.tax_total = this.round2(Number(mat.taxable_amt) * (gst / 100));
    mat.net_total = this.round2(Number(mat.taxable_amt) + Number(mat.tax_total));
  }

  /** PO materials total + shipping (used by Payment Terms). */
  getTotalPayable(): number {
    return this.round2((Number(this.poAmount) || 0) + (Number(this.shipping_total) || 0));
  }

  getPaymentCurrency(): string {
    return (this.selectedPO && this.selectedPO['currency']) ? this.selectedPO['currency'] : 'INR';
  }

  /** Recompute every payment-term amount from its % against current Total Payable. */
  recalculateAllPaymentTerms(): void {
    const terms = this.paymentTerms || [];
    for (let i = 0; i < terms.length; i++) {
      this.calculatePerAmt(i);
    }
  }

  checkedIndices = [];

  getTaxSplitUp(): void {
    this.poAmount = 0;
    if (!this.selectedPO) {
      return;
    }
    const indents = this.selectedPO['indends'] || [];
    indents.forEach((item: any) => this.ensureMaterialTotals(item));

    const checkedItems = indents.filter((item: any) => item.check === true);
    const taxMap: Record<string, any> = {};

    const checkedItemsWithSpecificFields = checkedItems.map((item: any) => {
      const q = parseFloat(item.order_qty) || parseFloat(item.splitQty) || 0;
      return {
        id: item.id,
        material_name: item.material_name,
        material_code: item.material_code,
        splitQty: q,
        qty: q,
        unit: item.unit || '',
        isAdded: 'NO',
        relationIndex: ''
      };
    });

    this.schedule_data = checkedItemsWithSpecificFields;

    checkedItems.forEach((item: any) => {
      const gst = parseFloat(item.gst) || 0;

      if (!taxMap[gst]) {
        taxMap[gst] = {
          percentage: gst,
          taxable_amt: 0,
          totalGST: 0,
          cgst: 0,
          sgst: 0,
          igst: 0,
        };
      }

      taxMap[gst].taxable_amt = this.round2(taxMap[gst].taxable_amt + (parseFloat(item.taxable_amt) || 0));
      taxMap[gst].totalGST = this.round2(taxMap[gst].totalGST + (parseFloat(item.tax_total) || 0));
      taxMap[gst].cgst = this.round2(taxMap[gst].cgst + (parseFloat(item.cgst) || 0));
      taxMap[gst].sgst = this.round2(taxMap[gst].sgst + (parseFloat(item.sgst) || 0));
      taxMap[gst].igst = this.round2(taxMap[gst].igst + (parseFloat(item.igst) || 0));
      this.poAmount = this.round2(this.poAmount + (Number(item?.net_total) || 0));
    });

    this.gstSplitData = Object.values(taxMap);
    this.recalculateAllPaymentTerms();
  }
 
 
  schedule_data = [];
  addSchedule(data,i) {
    let temp = {...data};
    temp['qty'] = Number(data['splitQty']) - Number(data['qty']);
    temp['splitQty'] = Number(data['splitQty']) - Number(data['qty']);
    temp['delivery_schedule_date'] =  '';
    temp['relationIndex'] =  i;
    temp['isAdded'] =  'YES';
    data['splitQty'] = Number(data['qty']);
    this.schedule_data.push(temp);
  }

  delSchedule(data,i) {

    const index = Number(data['relationIndex']);
    if (!isNaN(index) && this.schedule_data && this.schedule_data[index]) {
      const qty = parseFloat(data['qty']) || 0;
      this.schedule_data[index].splitQty = Number(this.schedule_data[index].splitQty) + Number(qty);
      this.schedule_data[index].qty = Number(this.schedule_data[index].splitQty);
    }
    this.schedule_data.splice(i, 1);

  }



  shipping_type = 'Vendor Account';


  shipping_handling = 0;
  shipping_gst = 0;
  shipping_Gst_amt = 0;
  shipping_total = 0;



  paymentTerms: any[] = [];

  addRowToPaymentTerms(): void {
    const currency = this.getPaymentCurrency();
    this.paymentTerms.push({ pay_per: '', term: '', pay_amt: 0, currency });
  }

  poAmount = 0;

  calculatePerAmt(i: number): void {
    if (!this.paymentTerms || !this.paymentTerms[i]) {
      return;
    }
    const total = this.getTotalPayable();
    const percentage = Math.max(0, Number(this.paymentTerms[i].pay_per) || 0);
    this.paymentTerms[i].pay_amt = this.round2((percentage / 100) * total);
    this.paymentTerms[i].currency = this.getPaymentCurrency();
  }

  calulateShipping() {
    this.shipping_Gst_amt = parseFloat((this.shipping_handling * (this.shipping_gst / 100)).toFixed(2));
    this.shipping_total = parseFloat((Number(this.shipping_Gst_amt) + Number(this.shipping_handling)).toFixed(2));
    this.recalculateAllPaymentTerms();
  }






  get filteredMaterials(): any[] {
    const list = [...(this.results || [])].sort((a: any, b: any) => this.pendingRowSortKey(b) - this.pendingRowSortKey(a));
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) return list;
    return list.filter((row: any) => {
      if (row.entry_date && String(row.entry_date).toLowerCase().includes(q)) return true;
      if (row.material_type && String(row.material_type).toLowerCase().includes(q)) return true;
      if (row.indend_no && String(row.indend_no).toLowerCase().includes(q)) return true;
      if (row.vendor_name && String(row.vendor_name).toLowerCase().includes(q)) return true;
      if (row.vendor_no && String(row.vendor_no).toLowerCase().includes(q)) return true;
      if (this.entryByDisplay(row).toLowerCase().includes(q)) return true;
      if (this.materialsDisplay(row).toLowerCase().includes(q)) return true;
      const indends = row.indends || [];
      if (indends.some((m: any) => (m.material_name && String(m.material_name).toLowerCase().includes(q)) || (m.material_code && String(m.material_code).toLowerCase().includes(q)))) return true;
      return false;
    });
  }

  materialsDisplay(row: any): string {
    const indends = row?.indends || [];
    return indends
      .map((m: any) => (m?.material_name || m?.material_code || '').toString().trim())
      .filter((name: string) => !!name)
      .join(', ');
  }

  entryByDisplay(row: any): string {
    if (!row) {
      return '';
    }
    return (row.entry_by_name || row.entry_by || '').toString().trim();
  }

  clearFilter(): void {
    this.searchQuery = '';
  }

  vendorDisplay(row: any): string {
    if (!row) {
      return '';
    }
    if (row.vendor_name && row.vendor_no && row.vendor_no !== 'Multiple') {
      return row.vendor_name + ' - ' + row.vendor_no;
    }
    if (row.vendor_name) {
      return row.vendor_name;
    }
    const indends = row.indends || [];
    const seen = new Set<string>();
    const labels: string[] = [];
    for (const m of indends) {
      const vn = m?.vendor_no ? String(m.vendor_no).trim() : '';
      if (!vn || seen.has(vn)) {
        continue;
      }
      seen.add(vn);
      const name = m?.vendor_name ? String(m.vendor_name).trim() : vn;
      labels.push(name + ' - ' + vn);
    }
    return labels.join('; ');
  }

  materialVendor(material: any): string {
    if (!material) {
      return '';
    }
    const name = material.vendor_name ? String(material.vendor_name).trim() : '';
    const no = material.vendor_no ? String(material.vendor_no).trim() : '';
    if (name && no) {
      return name + ' - ' + no;
    }
    return name || no || 'NA';
  }













 
}

