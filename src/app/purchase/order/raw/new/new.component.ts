import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers: [DatePipe]
})
export class NewComponent implements OnInit {
  maxDate = '';
  from_date = '';
  today = '';
  po_date = '';
  vendors: any[] = [];
  billcompany_code = '';
  shipcompany_code = '';
  vendor_no = '';
  /** When the PO has materials, only this vendor can be selected; prevents adding multiple vendors in one PO. */
  poLockedVendorNo: string | null = null;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    const today = new Date();
    this.po_date = today.toISOString().split('T')[0];
    this.maxDate = today.toISOString().split('T')[0]; 
  }

  ngOnInit() {
    this.getPendingIndends();
    this.getTerm();
    this.getCompanies();
    this.getVendors();
    this.sub_type();
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe((response: any) => {
      this.vendors = Array.isArray(response) ? response : [];
    });
  }
  
  generatePDF() {
    const contentToConvert = document.getElementById('contentToConvert'); //--Get the element
    this.service.convertToPDF(contentToConvert,'Po-Download');
  }


  isAddNewMaterial = false;
  po_type = 'Raw Material';
  subTypes: any[] = [];
  sub_type() {
    this.service.get('common.php?type=sub_type&material_type=' + this.po_type).subscribe((response: any) => {
      this.subTypes = Array.isArray(response) ? response : [];
    });
  }

  materials: any[] = [];
  material_subtype = '';
  /** Load materials mapped to selected vendor, filtered by Raw/Packing (po_type). Call when opening Add Material or when vendor/po_type changes. */
  getMappedMaterialsByVendor() {
    if (!this.vendor_no || !this.vendor_no.toString().trim()) {
      this.materials = [];
      if (typeof alertify !== 'undefined') alertify.warning('Please select Vendor first.');
      return;
    }
    this.service.get('master/material.php?type=get_materials_by_supplier&vendor_no=' + encodeURIComponent(this.vendor_no)).subscribe((response: any) => {
      const list = Array.isArray(response) ? response : [];
      const matType = (this.po_type || '').toLowerCase();
      this.materials = list.filter((m: any) => {
        const mt = (m.material_type || m.material_Type || '').toLowerCase();
        return (mt === 'raw material' && matType === 'raw material') ||
          (mt === 'packing material' && matType === 'packing material') ||
          mt === 'api';
      });
      this.selMat = {};
    });
  }

  getMaterialSuibType() {
    if (!this.material_subtype) {
      this.materials = [];
      return;
    }
    this.service.get('common.php?type=getMaterialByTypeAndSubtype&material_type=' + this.po_type + '&material_subtype=' + this.material_subtype).subscribe((response: any) => {
      this.materials = Array.isArray(response) ? response : [];
      this.selMat = {};
    });
  }

  companies: any[] = [];
  getCompanies() {
    this.service.get('master/company.php?type=getCompany').subscribe((response: any) => {
      this.companies = Array.isArray(response) ? response : [];
    });
  }

  selMat: any = {};
  addMaterialOrderQty: number | null = null;
  addMaterialUnit = '';
  /** List of materials to add in the modal; user adds multiple rows here, then "Add all to PO" pushes all to main form. */
  pendingMaterialsToAdd: any[] = [];
  /** Quotations from purchase/quotation/log for selected vendor + material (for Quotation Amt. dropdown). */
  quotationList: any[] = [];
  /** Selected quotation row from quotationList (quotation_amt, quotation_per, etc.). */
  selectedQuotation: any = null;
  /** Manual/editable quotation: amount, currency, and UOM (Qty Per). */
  addMaterialQuotationAmt: number | null = null;
  addMaterialCurrency = 'INR';
  addMaterialQuotationPer = 'Kg';
  /** Currency options for quotation. */
  quotationCurrencies: string[] = ['INR', 'USD'];
  /** UOM options for Quotation Qty Per (Kg, Liter, Nos). */
  quotationUomOptions: string[] = ['Kg', 'Liter', 'Nos'];

  selectedMat(i: number) {
    const index = i - 1;
    this.selMat = index >= 0 && this.materials && this.materials[index] ? this.materials[index] : {};
    this.addMaterialUnit = this.selMat.uom || this.selMat.unit || '';
    this.selectedQuotation = null;
    this.addMaterialQuotationAmt = null;
    this.addMaterialCurrency = 'INR';
    this.addMaterialQuotationPer = 'Kg';
    this.getQuotationsForVendorMaterial();
  }

  /** Load quotations for selected vendor and material (from purchase/quotation log). */
  getQuotationsForVendorMaterial() {
    const v = this.vendor_no != null ? String(this.vendor_no).trim() : '';
    const mc = this.selMat && this.selMat.material_code ? String(this.selMat.material_code).trim() : '';
    if (!v || !mc) {
      this.quotationList = [];
      this.selectedQuotation = null;
      return;
    }
    this.service.get('purchase/quotation.php?type=getvendorquatationmaterialforshort&vendor_no=' + encodeURIComponent(v) + '&material_code=' + encodeURIComponent(mc)).subscribe((response: any) => {
      this.quotationList = Array.isArray(response) ? response : [];
      this.selectedQuotation = null;
    });
  }

  onQuotationSelect(selectedIndex: number) {
    const i = selectedIndex - 1;
    const q = (i >= 0 && this.quotationList && this.quotationList[i]) ? this.quotationList[i] : null;
    this.selectedQuotation = q;
    if (q) {
      this.addMaterialQuotationAmt = parseFloat(q.quotation_amt) || parseFloat(q.quotation_amt_per) || null;
      this.addMaterialCurrency = (q.currency || 'INR').toString().trim() || 'INR';
      this.addMaterialQuotationPer = (q.quotation_per || q.uom || 'Kg').toString().trim() || 'Kg';
      if (this.quotationUomOptions.indexOf(this.addMaterialQuotationPer) === -1) {
        this.quotationUomOptions = [...this.quotationUomOptions, this.addMaterialQuotationPer];
      }
    }
  }

  openAddMaterialModal() {
    this.isAddNewMaterial = true;
    this.selMat = {};
    this.addMaterialOrderQty = null;
    this.addMaterialUnit = '';
    this.addMaterialQuotationAmt = null;
    this.addMaterialCurrency = 'INR';
    this.addMaterialQuotationPer = 'Kg';
    this.pendingMaterialsToAdd = [];
    this.quotationList = [];
    this.selectedQuotation = null;
    this.getMappedMaterialsByVendor();
  }

  /** Add current selection (material + qty + unit + quotation) to the pending table. If same material code exists, add qty to that row. Uses dropdown quotation or manual entry when no quotation available. */
  addOneToPendingList() {
    if (!this.selMat || !this.selMat.material_code) {
      if (typeof alertify !== 'undefined') alertify.error('Please select a material.');
      return;
    }
    const qty = this.addMaterialOrderQty != null ? Number(this.addMaterialOrderQty) : 0;
    if (!qty || qty <= 0) {
      if (typeof alertify !== 'undefined') alertify.error('Please enter valid quantity.');
      return;
    }
    const hasQuotationList = this.quotationList && this.quotationList.length > 0;
    let quotationAmt: number;
    let quotationPer: string;
    let quotationNo: string;
    let gst: number;

    if (hasQuotationList && this.selectedQuotation) {
      const src = this.selectedQuotation;
      quotationAmt = this.addMaterialQuotationAmt != null ? Number(this.addMaterialQuotationAmt) : (parseFloat(src.quotation_amt) || parseFloat(src.quotation_amt_per) || 0);
      quotationPer = (this.addMaterialQuotationPer || '').trim() || src.quotation_per || src.uom || 'Kg';
      quotationNo = src.vendor_quotation_no || src.quotation_no || '';
      gst = parseFloat(src.gst) || parseFloat(src.gst_per) || parseFloat(this.selMat.gst) || parseFloat(this.selMat.gst_per) || 0;
    } else if (hasQuotationList && !this.selectedQuotation) {
      const manualAmt = this.addMaterialQuotationAmt != null ? Number(this.addMaterialQuotationAmt) : 0;
      if (!manualAmt || manualAmt <= 0) {
        if (typeof alertify !== 'undefined') alertify.error('Select a quotation from the dropdown or enter Quotation Amt.');
        return;
      }
      quotationAmt = manualAmt;
      quotationPer = (this.addMaterialQuotationPer || 'Kg').trim() || this.selMat.quotation_per || 'Kg';
      quotationNo = '';
      gst = parseFloat(this.selMat.gst) || parseFloat(this.selMat.gst_per) || 0;
    } else {
      quotationAmt = this.addMaterialQuotationAmt != null ? Number(this.addMaterialQuotationAmt) : (parseFloat(this.selMat.quotation_amt) || parseFloat(this.selMat.quotation_amt_per) || 0);
      if (!quotationAmt || quotationAmt <= 0) {
        if (typeof alertify !== 'undefined') alertify.error('Please enter Quotation Amt.');
        return;
      }
      quotationPer = (this.addMaterialQuotationPer || 'Kg').trim() || this.selMat.quotation_per || 'Kg';
      quotationNo = '';
      gst = parseFloat(this.selMat.gst) || parseFloat(this.selMat.gst_per) || 0;
    }

    const unit = this.addMaterialUnit || this.selMat.uom || this.selMat.unit || '';
    const currency = (this.addMaterialCurrency || 'INR').toString().trim() || 'INR';
    const code = (this.selMat.material_code || '').toString().trim();
    const existing = this.pendingMaterialsToAdd.find((p: any) => (p.material_code || '').toString().trim() === code);
    if (existing) {
      existing.order_qty = (parseFloat(existing.order_qty) || 0) + qty;
      if (typeof alertify !== 'undefined') alertify.success('Quantity added to existing row.');
    } else {
      this.pendingMaterialsToAdd.push({
        material_code: this.selMat.material_code,
        material_name: this.selMat.material_name || this.selMat.material_Name || '',
        order_qty: qty,
        unit,
        quotation_amt: quotationAmt,
        quotation_per: quotationPer,
        quotation_no: quotationNo,
        currency,
        gst,
      });
      if (typeof alertify !== 'undefined') alertify.success('Added to list.');
    }
    this.selMat = {};
    this.addMaterialOrderQty = null;
    this.addMaterialUnit = '';
    this.selectedQuotation = null;
    this.addMaterialQuotationAmt = null;
    this.addMaterialCurrency = 'INR';
    this.addMaterialQuotationPer = 'Kg';
  }

  removeFromPendingList(i: number) {
    this.pendingMaterialsToAdd.splice(i, 1);
  }

  /** Build one PO line from a pending item; uses quotation_amt and quotation_per (mg/kg) for gross_total. */
  private buildPORowFromPending(p: any): any {
    const qty = p.order_qty || 0;
    const rate = p.quotation_amt || 0;
    const per = (p.quotation_per || '').toString().trim().toLowerCase();
    let grossTotal: number;
    if (per === 'mg') {
      grossTotal = this.round2(qty * (rate * 1000));
    } else {
      grossTotal = this.round2(qty * rate);
    }
    const newRow: any = {
      check: true,
      material_code: p.material_code,
      material_name: p.material_name || '',
      order_qty: p.order_qty,
      unit: p.unit || '',
      disc_per: 0,
      quotation_amt: p.quotation_amt || 0,
      quotation_per: p.quotation_per || '',
      quotation_no: p.quotation_no || '',
      currency: p.currency || 'INR',
      gross_total: grossTotal,
      disc_amt: 0,
      taxable_amt: grossTotal,
      gst: p.gst || 0,
      tax_total: 0,
      net_total: grossTotal,
      sgst: 0,
      cgst: 0,
      igst: 0,
    };
    if (this.taxType) this.applyDiscount(newRow);
    return newRow;
  }

  /** Add all materials from the pending table to the main PO form and close modal. Locks PO to current vendor so only same vendor's materials can be added. */
  addAllPendingToPO() {
    if (!this.pendingMaterialsToAdd.length) {
      if (typeof alertify !== 'undefined') alertify.warning('Add at least one material to the list below.');
      return;
    }
    if (!this.selectedPO || typeof this.selectedPO !== 'object') this.selectedPO = { indends: [] };
    if (!Array.isArray(this.selectedPO['indends'])) this.selectedPO['indends'] = [];
    for (const p of this.pendingMaterialsToAdd) {
      this.selectedPO['indends'].push(this.buildPORowFromPending(p));
    }
    this.poLockedVendorNo = this.vendor_no != null ? String(this.vendor_no).trim() : null;
    if (this.taxType) {
      this.applyDiscpuntToAll();
    } else {
      this.getTaxSplitUp();
    }
    this.pendingMaterialsToAdd = [];
    this.selMat = {};
    this.addMaterialOrderQty = null;
    this.addMaterialUnit = '';
    this.isAddNewMaterial = false;
    if (typeof alertify !== 'undefined') alertify.success('All materials added to PO.');
  }

  /** Called when vendor dropdown value changes. Prevents selecting another vendor if PO already has materials. */
  onVendorChange(newValue: string) {
    const indends = this.selectedPO && this.selectedPO['indends'];
    const hasMaterials = Array.isArray(indends) && indends.length > 0;
    if (!hasMaterials) {
      this.poLockedVendorNo = null;
      return;
    }
    const newV = (newValue != null ? String(newValue).trim() : '');
    const locked = (this.poLockedVendorNo != null ? String(this.poLockedVendorNo).trim() : '');
    if (locked && newV !== locked) {
      if (typeof alertify !== 'undefined') {
        alertify.warning('You can add only one vendor\'s material at a time. Prepare PO for this vendor first, then select another vendor.');
      } else {
        alert('You can add only one vendor\'s material at a time. Prepare PO for this vendor first, then select another vendor.');
      }
      this.vendor_no = this.poLockedVendorNo;
    }
  }















  selectedBill =[];
  selectedShip =[];

  taxType = '';

  getBillToShippToDetails(index,value) {
    index = index - 1;
    if (index !== -1) {
      if(value == 'BILLTO'){
          this.selectedBill = this.companies[index];
      }else{
          this.selectedShip = this.companies[index];
      }

      if(this.selectedBill['present_state'] == this.selectedShip['present_state']){
        this.taxType = 'GST';
      }else{
        this.taxType = 'IGST';
      }

    }else{
      this.taxType = '';
    }
    this.applyDiscpuntToAll();
  }
 
 
  results: any[] = [];
  material_type = 'Raw Material';
  loading = false;
  getPendingIndends() {
    this.loading = true;
    this.service.get('purchase/po/raw.php?type=getPendingIndend&material_type=' + this.material_type).subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
      this.loading = false;
    }, () => { this.loading = false; });
  }

  gstSplitData = [];
  selectedPO: any = [];
  purchase_type = '';
  isView = false;

  view(data) {
    this.gstSplitData = [];
    this.selectedPO = data;
    this.purchase_type = this.selectedPO['purchase_type'];
    this.isView = true;
  }
 
  sendPO(data: any) {



    console.log('Form valid:', data.valid);
    console.log('Form value:', data.value);

    if (data.invalid) {
      console.warn('❌ Some fields are invalid:');

      // Loop through all form controls
      Object.keys(data.controls).forEach(field => {
        const control = data.controls[field];
        if (control.invalid) {
          console.warn(`Field "${field}" is invalid. Errors:`, control.errors);
        }
      });

      // Optional: show a user-friendly message
      alert('Please fill all required fields correctly.');
      return;
    }

    // ✅ If valid, proceed with your submit logic
    console.log('✅ Form is valid! Sending PO...');
    console.log(data.value);





    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp = data.value;
  
    let terms = [];
    for (let i = 0; i < this.terms.length; i++) {
      let term = this.terms[i];
      if (term['selected']) {
        terms[terms.length] = term;
      }
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
    if (checkedItems.length == 0) {
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
    temp['po_type'] = this.selectedPO['material_type'];
    temp['vendor_no'] = this.selectedPO['vendor_no']; 
    temp['currency'] = this.selectedPO['currency'];
    temp['shipcompany_code'] = this.selectedShip['company_code'];
    temp['billcompany_code'] = this.selectedBill['company_code'];
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

 
    this.service.post('purchase/po/raw.php?type=saveIndendPONew1', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] === 'success') {
          alertify.success('Purchase Order is Send for Approval');
          data.resetForm();
          this.selectedBill = [];
          this.selectedShip = [];
          this.discount_In = 'Percentage';
          this.gstSplitData = [];
          this.shipping_type = 'Vendor Account';
          this.schedule_data = [];
          this.paymentTerms = [];
          this.isView = false;
          this.clicked = false;
          this.getPendingIndends();
          window.location.reload();
        } else {
          alertify.error(response['status']);
        }
    }); 
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

  terms;
  getTerm() {
    this.service.get('master/terms.php?type=getTermForPO&status=PO').subscribe((response) => {
      this.terms = response;
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

  onCheckboxChange(){
    if(this.sameaddress == true){
      this.selectedShip  = this.selectedBill;
      if(this.selectedBill['present_state'] == this.selectedShip['present_state']){
        this.taxType = 'GST';
      }else{
        this.taxType = 'IGST';
      }
    }else{
      this.selectedShip =[];
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

  applyDiscpuntToAll(){

    if(this.taxType == ''){
      alertify.error("Please Select Bill TO / Ship To !!!!");
      return;
    }
 
    for (let i = 0; i < this.selectedPO['indends'].length; i++) {
        let mat = this.selectedPO['indends'][i];

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


  checkedIndices = [];


 
getTaxSplitUp() {
    if (!this.selectedPO || !this.selectedPO['indends'] || this.selectedPO['indends'].length === 0) {
      this.poLockedVendorNo = null;
    }
    this.poAmount = 0;
    if(this.taxType == ''){
      alertify.error("Please Select Bill TO / Ship To !!!!");
      return;
    }

    const indents = this.selectedPO['indends'] || [];
    const checkedItems = indents.filter((item: any) => item.check === true);
    const taxMap: Record<string, any> = {};

    const indends = this.selectedPO['indends'] || [];
    const checkedItemsWithSpecificFields = indends
      .filter((item: any) => item.check === true)
      .map((item: any) => ({
        id: item.id,
        material_name: item.material_name,
        material_code: item.material_code,
        splitQty: parseFloat(item.order_qty) || parseFloat(item.splitQty) || 0,
        qty: parseFloat(item.order_qty) || parseFloat(item.splitQty) || 0,
        unit: item.unit || '',
        delivery_schedule_date: '',
        isAdded: 'NO',
        relationIndex: ''
      }));

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
      this.poAmount = this.poAmount + Number(item?.net_total);
    });

    // ✅ Convert object to array for your template
    this.gstSplitData = Object.values(taxMap);
  }
 
 
  schedule_data: any[] = [];

  /** Total order qty for a material from the main materials table (cannot be exceeded in delivery schedule). */
  getOrderQtyForMaterial(material_code: string): number {
    const indends = this.selectedPO && this.selectedPO['indends'] ? this.selectedPO['indends'] : [];
    const row = indends.find((m: any) => (m.material_code || '').toString().trim() === (material_code || '').toString().trim());
    return row ? (parseFloat(row.order_qty) || 0) : 0;
  }

  /** Sum of scheduled qty for a material across all delivery schedule rows. */
  getScheduledTotalForMaterial(material_code: string, excludeIndex?: number): number {
    let sum = 0;
    (this.schedule_data || []).forEach((row: any, idx: number) => {
      if ((row.material_code || '').toString().trim() === (material_code || '').toString().trim() && excludeIndex !== idx) {
        sum += parseFloat(row.qty) || 0;
      }
    });
    return sum;
  }

  /** Validate and cap schedule qty so total scheduled for this material does not exceed order_qty. */
  onScheduleQtyChange(item: any, index: number) {
    const code = (item.material_code || '').toString().trim();
    const maxQty = this.getOrderQtyForMaterial(code);
    const otherTotal = this.getScheduledTotalForMaterial(code, index);
    const current = parseFloat(item.qty) || 0;
    if (current > maxQty - otherTotal) {
      const allowed = Math.max(0, maxQty - otherTotal);
      item.qty = this.round2(allowed);
      if (typeof alertify !== 'undefined') alertify.warning('Qty cannot exceed total order qty for this material. Set to ' + allowed);
    }
    if (current > (item.splitQty || 0)) {
      item.qty = parseFloat(item.splitQty) || 0;
    }
  }

  addSchedule(data: any, i: number) {
    const total = parseFloat(data.splitQty) || 0;
    const scheduled = parseFloat(data.qty) || 0;
    const remaining = this.round2(total - scheduled);
    if (remaining <= 0) {
      if (typeof alertify !== 'undefined') alertify.warning('No remaining qty to add. Reduce scheduled qty or use ADD to split.');
      return;
    }
    const temp: any = { ...data };
    temp.qty = remaining;
    temp.splitQty = remaining;
    temp.delivery_schedule_date = '';
    temp.relationIndex = i;
    temp.isAdded = 'YES';
    data.splitQty = scheduled;
    data.qty = scheduled;
    this.schedule_data.push(temp);
  }

  delSchedule(data: any, i: number) {
    const index = Number(data.relationIndex);
    if (!isNaN(index) && this.schedule_data && this.schedule_data[index]) {
      const qty = parseFloat(data.qty) || 0;
      this.schedule_data[index].splitQty = (parseFloat(this.schedule_data[index].splitQty) || 0) + qty;
      this.schedule_data[index].qty = this.schedule_data[index].splitQty;
    }
    this.schedule_data.splice(i, 1);
  }



  shipping_type = 'Vendor Account';


  shipping_handling = 0;
  shipping_gst = 0;
  shipping_Gst_amt = 0;
  shipping_total = 0;



  calulateShipping() {
    this.shipping_Gst_amt = parseFloat((this.shipping_handling * (this.shipping_gst / 100)).toFixed(2));
    this.shipping_total = parseFloat((Number(this.shipping_Gst_amt) + Number(this.shipping_handling)).toFixed(2));
  }


  paymentTerms: any[] = [];
  poAmount = 0;

  /** Total payable (PO amount + shipping). Payment terms total must not exceed this. */
  getTotalPayable(): number {
    return this.round2((this.poAmount || 0) + (this.shipping_total || 0));
  }

  /** Total payable amount in words (e.g. for display below materials table). */
  getTotalPayableInWords(): string {
    const n = this.getTotalPayable();
    if (n === 0) return 'Zero Rupees Only';
    return this.numberToWords(n) + ' Only';
  }

  /** Convert number to Indian Rupees-and-Paise words (handles decimals). */
  numberToWords(num: number): string {
    const currency = (this.selectedPO && this.selectedPO['currency']) || 'INR';
    const isINR = (currency + '').toUpperCase() === 'INR';
    const whole = Math.floor(num);
    const frac = Math.round((num - whole) * 100);
    const wholeStr = this.toWordsIndian(whole);
    const unit = isINR ? 'Rupees' : 'Only';
    if (frac === 0) {
      return wholeStr + ' ' + unit;
    }
    const paiseStr = this.toWordsIndian(frac);
    return wholeStr + ' ' + unit + ' And ' + paiseStr + (isINR ? ' Paise' : '');
  }

  private toWordsIndian(n: number): string {
    if (n === 0) return 'Zero';
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
      'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    if (n < 20) return ones[n];
    if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + ones[n % 10] : '');
    if (n < 1000) return ones[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + this.toWordsIndian(n % 100) : '');
    if (n < 100000) return this.toWordsIndian(Math.floor(n / 1000)) + ' Thousand' + (n % 1000 ? ' ' + this.toWordsIndian(n % 1000) : '');
    if (n < 10000000) return this.toWordsIndian(Math.floor(n / 100000)) + ' Lakh' + (n % 100000 ? ' ' + this.toWordsIndian(n % 100000) : '');
    return this.toWordsIndian(Math.floor(n / 10000000)) + ' Crore' + (n % 10000000 ? ' ' + this.toWordsIndian(n % 10000000) : '');
  }

  /** Sum of all payment term amounts. */
  getPaymentTermsSum(): number {
    return (this.paymentTerms || []).reduce((s, r) => s + (parseFloat(r.pay_amt) || 0), 0);
  }

  /** Set last row amount to balance so total = getTotalPayable(). Call after any amount change. */
  adjustLastRowToBalance(): void {
    const total = this.getTotalPayable();
    const terms = this.paymentTerms || [];
    if (terms.length < 2) return;
    const lastIdx = terms.length - 1;
    const sumOthers = terms.slice(0, lastIdx).reduce((s, r) => s + (parseFloat(r.pay_amt) || 0), 0);
    const balance = this.round2(total - sumOthers);
    terms[lastIdx].pay_amt = balance >= 0 ? balance : 0;
    terms[lastIdx].pay_per = total > 0 ? this.round2((terms[lastIdx].pay_amt / total) * 100) : 0;
    if (balance < 0 && typeof alertify !== 'undefined') {
      alertify.warning('Total payment terms exceed total payable. Amounts adjusted.');
    }
  }

  addRowToPaymentTerms(): void {
    const total = this.getTotalPayable();
    if (this.getPaymentTermsSum() >= total) {
      if (typeof alertify !== 'undefined') alertify.warning('Total already matches total payable. Cannot add more rows.');
      return;
    }
    const temp: any = {
      pay_per: 0,
      term: '',
      pay_amt: 0,
      currency: (this.selectedPO && this.selectedPO['currency']) || 'INR'
    };
    this.paymentTerms.push(temp);
    this.adjustLastRowToBalance();
  }

  removePaymentTerm(index: number): void {
    if (index >= 0 && index < (this.paymentTerms || []).length) {
      this.paymentTerms.splice(index, 1);
      this.adjustLastRowToBalance();
    }
  }

  calculatePerAmt(i: number): void {
    const total = this.getTotalPayable();
    const terms = this.paymentTerms || [];
    if (!terms[i]) return;
    const percentage = Math.max(0, Number(terms[i].pay_per) || 0);
    let payAmt = this.round2((percentage / 100) * total);
    const n = terms.length;
    const isLastRow = i === n - 1;
    if (isLastRow && n >= 2) {
      // Last row is balance; don't set from %, adjust last row to balance after others
      this.adjustLastRowToBalance();
      return;
    }
    if (n >= 2) {
      const sumOthers = terms.slice(0, n - 1).reduce((s, r, idx) => s + (idx === i ? 0 : (parseFloat(r.pay_amt) || 0)), 0);
      const maxAllowed = this.round2(total - sumOthers);
      if (payAmt > maxAllowed) {
        payAmt = Math.max(0, maxAllowed);
        if (typeof alertify !== 'undefined') alertify.warning('Amount exceeds total payable. Capped to balance.');
      }
    } else if (n === 1 && payAmt > total) {
      payAmt = total;
      if (typeof alertify !== 'undefined') alertify.warning('Amount cannot exceed total payable.');
    }
    terms[i].pay_amt = payAmt;
    if (n >= 2) this.adjustLastRowToBalance();
  }






  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.results || this.results.length === 0) return [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }
    const query = this.searchQuery.toLowerCase().trim();
    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }













 
}

 
