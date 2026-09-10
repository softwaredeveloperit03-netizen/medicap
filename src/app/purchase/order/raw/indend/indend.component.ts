import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-indend',
  templateUrl: './indend.component.html',
  styleUrls: ['./indend.component.css'],
  providers: [DatePipe],
})
export class IndendComponent implements OnInit {
  maxDate = '';
  from_date = '';
  today = '';
  po_date = '';

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
  }
  
  generatePDF() {
    const contentToConvert = document.getElementById('contentToConvert'); //--Get the element
    this.service.convertToPDF(contentToConvert,'Po-Download');
  }

  companies;
  getCompanies() {
    this.service.get('master/company.php?type=getCompany').subscribe((response) => {
        this.companies = response;
    });
  }

  selectedBill: any = {};
  selectedShip: any = {};
  billcompany_code = '';
  shipcompany_code = '';

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
  loading = false;
  material_type = 'All';
  searchQuery = '';

  getPendingIndends() {
    this.loading = true;
    const url =
      'purchase/po/raw.php?type=getPendingIndend&For=GEN&material_type=' +
      encodeURIComponent(this.material_type);
    this.service.get(url).subscribe({
      next: (response: any) => {
        if (Array.isArray(response)) {
          this.results = response;
        } else if (response && typeof response === 'object' && response.status === 'error') {
          this.results = [];
          alertify.error(response.message || 'Failed to load pending requisitions');
        } else if (response && typeof response === 'object' && response.status === 'invalid') {
          this.results = [];
          alertify.error('Session expired — please log in again');
        } else {
          this.results = [];
        }
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
        alertify.error('Failed to load pending requisitions');
      },
    });
  }

  gstSplitData = [];
  selectedPO = [];
  purchase_type = '';
  isView = false;
  marketType = 'Imported';

  view(data) {
    this.marketType = 'Imported';
    this.gstSplitData = [];
    this.selectedPO = data;
    this.selectedBill = {};
    this.selectedShip = {};
    this.billcompany_code = '';
    this.shipcompany_code = '';
    this.taxType = '';
    this.schedule_data = [];
    this.paymentTerms = [];
    this.clicked = false;
    this.purchase_type = this.selectedPO['purchase_type'];
    this.isView = true;
    if (data['country'] == 'India') {
      this.marketType = 'Domestic';
    }
  }

  sendPO(data: any) {
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

    const temp = data.value;
    const terms: any[] = [];
    for (let i = 0; i < (this.terms || []).length; i++) {
      const term = this.terms[i];
      if (term && term['selected']) {
        terms.push(term);
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
    const checkedItems = indents
      .filter((item: any) => item.check === true)
      .map((item: any) => this.normalizePoMaterial(item));
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
    temp['rounding'] = Math.round(poAmount + Number(this.shipping_total));

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

    this.clicked = true;
    this.service.postTextResponse('purchase/po/raw.php?type=saveIndendPONew1', JSON.stringify(temp)).subscribe({
      next: (raw: string) => {
        let response: any;
        try {
          response = this.service.parsePhpJson(raw);
        } catch {
          this.clicked = false;
          alertify.error('Invalid server response. Deploy purchase/po/raw.php if not done yet.');
          return;
        }
        if (response && response['status'] === 'success') {
          alertify.success('Purchase Order is Send for Approval');
          if (data && data.resetForm) {
            data.resetForm();
          }
          this.selectedBill = {};
          this.selectedShip = {};
          this.billcompany_code = '';
          this.shipcompany_code = '';
          this.discount_In = 'Percentage';
          this.gstSplitData = [];
          this.shipping_type = 'Vendor Account';
          this.schedule_data = [];
          this.paymentTerms = [];
          this.marketType = 'Imported';
          this.isView = false;
          this.clicked = false;
          this.getPendingIndends();
        } else {
          this.clicked = false;
          alertify.error(response?.message || response?.status || 'Failed to submit PO');
        }
      },
      error: () => {
        this.clicked = false;
        alertify.error('Failed to submit PO. Check network or deploy purchase/po/raw.php.');
      }
    });
  }

  /** Ensure PO line fields are strings for PHP (avoids null/array SQL failures on live). */
  private normalizePoMaterial(item: any): any {
    const row = { ...item };
    row.quotation_no = row.quotation_no != null ? String(row.quotation_no) : '';
    row.client_code = row.client_code != null ? String(row.client_code) : '';
    row.vendor_no = row.vendor_no != null ? String(row.vendor_no) : '';
    row.clientGrpCode = row.clientGrpCode != null ? String(row.clientGrpCode) : '';
    row.clientSubGrpCode = row.clientSubGrpCode != null ? String(row.clientSubGrpCode) : '';
    if (row.required_for == null || row.required_for === '') {
      row.required_for = 'Own';
    } else if (typeof row.required_for === 'object') {
      row.required_for = JSON.stringify(row.required_for);
    } else {
      row.required_for = String(row.required_for);
    }
    return row;
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
 
    this.poAmount = 0;
    if(this.taxType == ''){
      alertify.error("Please Select Bill TO / Ship To !!!!");
      return;
    }

    const indents = this.selectedPO['indends'] || [];
    const checkedItems = indents.filter((item: any) => item.check === true);
    const taxMap: Record<string, any> = {};

    const checkedItemsWithSpecificFields = (this.selectedPO['indends'] || [])
      .filter((item: any) => item.check === true)
      .map((item: any) => ({
        id: item.id,
        material_name: item.material_name,
        material_code: item.material_code,
        splitQty: item.splitQty,
        qty: item.splitQty,
        unit: item.unit,
        isAdded:'NO',
        relationIndex : ''
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



  calulateShipping() {
    this.shipping_Gst_amt = parseFloat((this.shipping_handling * (this.shipping_gst / 100)).toFixed(2));
    this.shipping_total = parseFloat((Number(this.shipping_Gst_amt) + Number(this.shipping_handling)).toFixed(2));
  }


  paymentTerms = [];
  addRowToPaymentTerms(){
    let temp = {};
    temp['pay_per'] = 0;
    temp['term'] = '';
    temp['pay_amt'] = 0;
    temp['currency'] = this.selectedPO['currency'];
    this.paymentTerms.push(temp);
  }

   poAmount = 0;

   calculatePerAmt(i){
    let poAmt = this.poAmount + this.shipping_total;
    let percentage = Number(this.paymentTerms[i].pay_per);
    this.paymentTerms[i].pay_amt = Number(((percentage / 100) * poAmt).toFixed(2));
   }






  get filteredMaterials(): any[] {
    const rows = Array.isArray(this.results) ? this.results : [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return rows;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return rows.filter((material) => {
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

 
