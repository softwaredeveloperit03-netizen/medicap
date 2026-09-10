import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import * as XLSX from 'xlsx';
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results1;
  results: any[] = [];
  loading = false;
  pagination;
  selectedResult ={};
  selectedResult1;
  isView = false;
  is_password = false;
  id;
  state_code = '';
  states;
  vendor_for = '';
  isVendor = false;
  vendor_type = '';
  vendor_subtype = '';
  due_days = '';
  pan_no = '';
  mfg_lic = '';
  gst_no = '';
  contact_person = '';
  email = '';
  state_code1 = '';
  city = '';
  location = '';
  address_corporate = '';
  vendor_status = '';
  vendor_name = '';
  vendor_no = '';
  material_type = '';
  contact_number = '';
  unit_name = '';
  c_unit_name = '';
  c_address = '';
  address = '';
  c_country = '';
  country = '';
  state_name = '';
  c_state = '';
  c_city = '';
  c_pincode = '';
  pincode = '';
  c_mobile_no = '';
  mobile_no = '';
  scode = '';
  c_gst_no = '';
  gst:any;
  mfg:any;
  plant_id:any;

  vendor_name1 = '';
  filteredVendor = [];

  constructor(private service: DataAccessService) {
    this.plant_id = this.service.getPlantConfigFields('plant_id');
  }

  ngOnInit() {
    this.getLogs();
  }

 

   
  getLogs() {
    this.loading = true;
    const detailVendorId = this.isView && this.selectedResult && this.selectedResult['id'] != null
      ? this.selectedResult['id']
      : null;
    this.service.getJsonArray('purchase/vendor.php?type=getVendorLog').subscribe(response => {
      this.results = (response || []).map((row: any) => {
        if (!row || typeof row !== 'object') {
          return row;
        }
        row.status_remark = this.statusRemarkText(row);
        row.terms_conditions = Array.isArray(row.terms_conditions) ? row.terms_conditions : [];
        row.paymentTerms = Array.isArray(row.paymentTerms) ? row.paymentTerms : [];
        return row;
      });
      this.loading = false;
      if (detailVendorId != null && this.isView) {
        const found = this.results.find((r: any) => r.id === detailVendorId);
        if (found) {
          this.selectedResult = found;
          this.getProducts();
        }
      }
    }, () => { this.loading = false; });
  }

  


   

  isedit = false;


  edit(data) {
    this.selectedResult = data;
    this.vendor_type = this.selectedResult['vendor_type'];
    this.contact_person = this.selectedResult['contact_person'];
    this.email = this.selectedResult['email'];
    this.vendor_name = this.selectedResult['vendor_name'];
    this.contact_number = this.selectedResult['contact_number'];
    this.unit_name = this.selectedResult['unit_name'];
    this.c_unit_name = this.selectedResult['c_unit_name'];
    this.address = this.selectedResult['address'];
    this.c_address = this.selectedResult['c_address'];
    this.country = this.selectedResult['country'];
    this.c_country = this.selectedResult['c_country'];
    this.state_name = this.selectedResult['state_name'];
    this.c_state = this.selectedResult['c_state'];
    this.city = this.selectedResult['city'];
    this.c_city = this.selectedResult['c_city'];
    this.pincode = this.selectedResult['pincode'];
    this.c_pincode = this.selectedResult['c_pincode'];
    this.mobile_no = this.selectedResult['mobile_no'];
    this.c_mobile_no = this.selectedResult['c_mobile_no'];
    this.isedit = true;
    console.log(this.state_name);
  }

  isStatus = false;
  /** Snapshot for modal (avoid mutating grid row via two-way binding) */
  statusModalSnapshot = '';
  pendingChangeStatus = '';
  /** Temporary default until backend password check is wired */
  readonly statusChangeDefaultPassword = '1234';
  isStatusPasswordModal = false;
  statusChangePassword = '';
  private pendingStatusForm: any = null;

  closeStatusModal(form?: any): void {
    this.isStatus = false;
    this.pendingChangeStatus = '';
    this.closeStatusPasswordModal();
    form?.resetForm?.();
  }

  closeStatusPasswordModal(): void {
    this.isStatusPasswordModal = false;
    this.statusChangePassword = '';
    this.pendingStatusForm = null;
  }

  onStatusPasswordModalOpenChange(open: boolean): void {
    if (!open) {
      this.closeStatusPasswordModal();
    }
  }

  promptStatusPassword(form: any): void {
    if (!form?.valid || !this.pendingChangeStatus) {
      alert('Please select the new status.');
      return;
    }
    this.pendingStatusForm = form;
    this.statusChangePassword = '';
    this.isStatusPasswordModal = true;
  }

  confirmStatusPassword(): void {
    if ((this.statusChangePassword || '').trim() !== this.statusChangeDefaultPassword) {
      alertify.error('Invalid password. Status was not changed.');
      return;
    }
    const form = this.pendingStatusForm;
    this.closeStatusPasswordModal();
    if (form) {
      this.change_status(form);
    }
  }

  onStatusModalOpenChange(open: boolean, form?: any): void {
    if (!open) {
      this.closeStatusModal(form);
    }
  }

  close() {
    this.isVendor = false;
  }

  view(data) {
    this.selectedResult = data
    this.isView = true;
     this.gst=this.selectedResult['gst_certificate']
    this.mfg=this.selectedResult['mfg_lic_file'];
    this.getProducts();
  }

  changesStatus(data) {
    this.selectedResult = data;
    this.statusModalSnapshot = data?.status ?? '—';
    this.pendingChangeStatus = '';
    this.isStatus = true;
  }

  isBankModal = false;
  bankModalRow: any = null;
  bankForm: {
    bank_name: string;
    branch_address: string;
    account_holder: string;
    account_number: string;
    ifsc_code: string;
    payment_mode: string;
  } = {
    bank_name: '',
    branch_address: '',
    account_holder: '',
    account_number: '',
    ifsc_code: '',
    payment_mode: '',
  };

  bankRoutingLabelForRow(row: any): string {
    const c = row?.c_country;
    if (c === 'India') {
      return 'IFSC code';
    }
    if (c === 'Canada') {
      return 'Institution & transit (EFT) / SWIFT';
    }
    return 'SWIFT / routing';
  }

  hasBankDetails(user: any): boolean {
    if (!user) {
      return false;
    }
    const s = (v: any) => v != null && String(v).trim() !== '';
    return (
      s(user.bank_name) ||
      s(user.branch_address) ||
      s(user.account_holder) ||
      s(user.account_number) ||
      s(user.ifsc_code) ||
      s(user.payment_mode)
    );
  }

  openBankModal(user: any): void {
    this.bankModalRow = user;
    this.bankForm = {
      bank_name: user.bank_name || '',
      branch_address: user.branch_address || '',
      account_holder: user.account_holder || '',
      account_number: user.account_number || '',
      ifsc_code: user.ifsc_code || '',
      payment_mode: user.payment_mode || '',
    };
    this.isBankModal = true;
  }

  closeBankModal(): void {
    this.isBankModal = false;
    this.bankModalRow = null;
  }

  onBankModalOpenChange(open: boolean): void {
    if (!open) {
      this.bankModalRow = null;
    }
  }

  saveBankDetails(): void {
    if (!this.bankModalRow?.id) {
      alertify.error('Invalid vendor.');
      return;
    }
    const temp = {
      id: this.bankModalRow.id,
      bank_name: (this.bankForm.bank_name || '').trim(),
      branch_address: (this.bankForm.branch_address || '').trim(),
      account_holder: (this.bankForm.account_holder || '').trim(),
      account_number: (this.bankForm.account_number || '').trim(),
      ifsc_code: (this.bankForm.ifsc_code || '').trim(),
      payment_mode: this.bankForm.payment_mode || '',
    };
    this.service.post('purchase/vendor.php?type=updateVendorBankDetails', JSON.stringify(temp)).subscribe(
      (response: any) => {
        if (response?.status === 'success') {
          alertify.success('Bank details saved.');
          this.closeBankModal();
          this.getLogs();
        } else {
          alertify.error(typeof response?.status === 'string' ? response.status : 'Could not save bank details.');
        }
      },
      () => alertify.error('Could not save bank details.')
    );
  }





  change_status(data) {
    if (!data.valid) {
      alert('Please select the new status.');
      return;
    }

    const temp = {
      id: this.selectedResult['id'],
      change_status: data.value.change_status,
      status_remark: (data.value.status_remark || '').trim()
    };

    this.service.post('purchase/vendor.php?type=changeVendorStatus', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Status saved successfully.');
        this.isStatus = false;
        data.resetForm();
        this.pendingChangeStatus = '';
        this.getLogs();
      } else {
        alertify.error(typeof response['status'] === 'string' ? response['status'] : 'Could not update status.');
      }
    });
  }
  
  exportToExcel(): void {
    const fileName = 'vendor_data.xlsx';
    const header = [
      'Sr.No',
      'Vendor No.',
      'Vendor Type',
      'Vendor Name',
      'Status',
      'Status remark',
    ];
    const data = [
      header,
      ...this.filteredMaterials.map((material, index) => [
        index + 1,
        material.vendor_no,
        material.vendor_type,
        material.vendor_name,
        material.status,
        material.status_remark || '',
      ]),
    ];

    const ws: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet(data);
    const wb: XLSX.WorkBook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Vendor Data');
    XLSX.writeFile(wb, fileName);
  }
 
 
  viewfile(link) {
    window.open(this.service.url + 'upload/vendor/' + link);
  }
 
  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }

  materials;
  getProducts() {
    this.materials = [];
    this.service.get('master/material.php?type=get_materials_by_supplier&vendor_no=' + this.selectedResult['vendor_no']).subscribe((response: any) => {
      this.materials = response;
      this.isView = true;
    });
  }

 
  download() {
    this.service.open('purchase/vendor.php?type=downloadVendorLog&vendor_for=' + this.vendor_for + '&vendor_name=' + this.vendor_name1)
  }
 
  viewfile1(url) {
      url = this.service.url + '../../upload/vendor/' + url;
    window.open(url, '_blank');
  }

  


  isdash = false;

  viewDevisions(data){ 
      this.selectedResult = data;
      this.isdash = true;
  }


  isAddTerm = false;

  addTAndC(data){ 
      this.selectedResult = data || {};
      if (!Array.isArray(this.selectedResult['terms_conditions'])) {
        this.selectedResult['terms_conditions'] = [];
      }
      this.isAddTerm = true;
      this.getTermForVendor();
      this.isEditTerms = this.selectedResult['terms_conditions'].length === 0;
  }

  isAddPayTerms = false;
  isEditPayTerms = false;

  addPayTerms(data){ 
      this.selectedResult = data || {};
      if (!Array.isArray(this.selectedResult['paymentTerms'])) {
        this.selectedResult['paymentTerms'] = [];
      }
      this.paymentTerms = this.selectedResult['paymentTerms'].map((row: any) => ({ ...row }));
      this.termValidDatePay = this.selectedResult['termValidDatePay'] || this.selectedResult['payTermValidDate'] || this.termValidDatePay || '';
      this.isAddPayTerms = true;
      this.isEditPayTerms = this.paymentTerms.length === 0;
  }







addVendor(data) {
  console.log(data);
  if (!data.valid) {
    alert('All fields are required');
    return;
  }
 
  let temp = data.value;
   temp['pr_id'] = this.selectedResult['vendor_no'];
  
 
  this.service.post('vendor.php?type=saveVendordiv', JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      alertify.success('Record Inserted successfully');
      this.isdash = false;
      data.resetForm();
      this.getLogs();
    } else {
      alertify.error(response['status']);
    }
  });
}



isEditTerms = false;

toggleEditTerms(): void {
  this.isEditTerms = !this.isEditTerms;
  if (this.isEditTerms) {
    this.getTermForVendor();
  }
}

toggleEditPayTerms(): void {
  this.isEditPayTerms = !this.isEditPayTerms;
  if (this.isEditPayTerms && (!this.paymentTerms || this.paymentTerms.length === 0)) {
    const saved = Array.isArray(this.selectedResult['paymentTerms']) ? this.selectedResult['paymentTerms'] : [];
    this.paymentTerms = saved.map((row: any) => ({ ...row }));
  }
}

saveTermsAndConditons() {

  let terms = [];
  for (let i = 0; i < this.terms.length; i++) {
    let term = this.terms[i];
    if (term['selected']) {
      terms[terms.length] = term;
    }
  }

  if (terms?.length == 0) {
    alert('Select Terms And Conditions!!');
    return;
  }
 
  let temp = {};
  temp['vendor_no'] = this.selectedResult['vendor_no'];
  temp['terms_conditions'] = terms;
  
  this.service.post('purchase/vendor.php?type=saveTermsAndConditons', JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      alertify.success('T & C Saved successfully');
      this.selectedResult['terms_conditions'] = terms;
      this.isEditTerms = false;
      this.isAddTerm = false;
      this.getLogs();
      this.getTermForVendor();
    } else {
      alertify.error(response['status']);
    }
  });

}


isPayTermsEdit = false;
 termValidDatePay = '';

savePaymentTerms() {

  
  if (this.termValidDatePay == '') {
    alert('Please Select Valid Till Date!!');
    return;
  }
  if (this.paymentTerms?.length == 0) {
    alert('Please Add Payment Terms!!');
    return;
  }
 
  let temp = {};
  temp['vendor_no'] = this.selectedResult['vendor_no'];
  temp['paymentTerms'] = this.paymentTerms;
  temp['termValidDate'] = this.termValidDatePay;
  
  this.service.post('purchase/vendor.php?type=savePaymentTerms', JSON.stringify(temp)).subscribe(response => {
    if (response['status'] == 'success') {
      alertify.success('Payment Terms Saved successfully');
      this.selectedResult['paymentTerms'] = this.paymentTerms.map((row: any) => ({ ...row }));
      this.selectedResult['termValidDatePay'] = this.termValidDatePay;
      this.isEditPayTerms = false;
      this.isAddPayTerms = false;
      this.getLogs();
      this.termValidDatePay = ''
    } else {
      alertify.error(response['status']);
    }
  });

}



updateVendor(data) {
  console.log(data);
  
  if (!data.valid) {
    alertify.error('All fields are required');
    return;
  }
  let temp = data.value;
  temp['vendor_no'] = this.vendor_no;

  this.service.post('purchase/vendor.php?type=updateVendor&vendor_no='+this.selectedResult['vendor_no'], JSON.stringify(temp)).subscribe(response => {
    if (response['status'] === 'success') {
      this.getLogs();
      this.isedit = false;
      alertify.success('Record Update successfully');
      data.resetForm();
    } else {
      alertify.error(response['status']);
    }
  });
}





searchQuery;

  formatStatusRemark(row: any): string {
    const text = this.statusRemarkText(row);
    return text || 'NA';
  }

  statusRemarkText(row: any): string {
    if (!row) {
      return '';
    }
    const direct = row.status_remark != null ? String(row.status_remark).trim() : '';
    if (direct) {
      return direct;
    }
    let log = row.correction_log;
    if (typeof log === 'string' && log.trim()) {
      try {
        log = JSON.parse(log);
      } catch {
        log = null;
      }
    }
    const qa = log && log.qa_remark != null ? String(log.qa_remark).trim() : '';
    return qa;
  }

get filteredMaterials(): any[] {
  if (!this.results || this.results.length === 0) return [];
  if (!this.searchQuery || this.searchQuery.trim() === '') return this.results;
  const query = this.searchQuery.toLowerCase().trim();
  return this.results.filter(material => {
    return Object.entries(material).some(([key, value]) => {
      if (value == null) return false;
      if (key === 'entry_date' || key === 'entryDate') {
        const dateStr = typeof value === 'string' ? value : (value instanceof Date ? value.toISOString().slice(0, 10) : '');
        return dateStr && dateStr.toLowerCase().includes(query);
      }
      return value.toString().toLowerCase().includes(query);
    });
  });
}


 




  isTerm = false;
  saveTerm(data: any) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }


    let temp = data.value;
    temp['vendor_no'] = this.selectedResult['vendor_no'];


    this.service.post('master/terms.php?type=saveTermsVendor&status=Vendor', JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          this.getTermForVendor();
          this.isTerm = false;
          this.isEditTerms = false;
          alert('Record Inserted Successfully');
          data.resetForm();
        } else {
          alert('Please try Again');
        }
    });
  }

  terms;
  getTermForVendor() {
    const vendorNo = this.selectedResult && this.selectedResult['vendor_no'] ? this.selectedResult['vendor_no'] : '';
    this.service.get('master/terms.php?type=getTermForVendor&vendor_no=' + encodeURIComponent(vendorNo)).subscribe((response) => {
      const list = Array.isArray(response) ? response : [];
      const saved = Array.isArray(this.selectedResult['terms_conditions']) ? this.selectedResult['terms_conditions'] : [];
      this.terms = list.map((term: any) => {
        const selected = saved.some((s: any) =>
          (s && term && String(s.id) === String(term.id)) ||
          (s && term && s.term_heading === term.term_heading && s.term === term.term)
        );
        return { ...term, selected };
      });
    });
  }

  isEdit = false;
  delTerm(id) {
    this.service.get('master/terms.php?type=deleteTerm&id=' + id).subscribe((response) => {
        this.getTermForVendor();
        if (response['status'] == 'success') {
          this.isEdit = false;
          alertify.success('Term Deleted Successfully');
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
    });
  }




  paymentTerms = [];
  addRowToPaymentTerms(){
    let temp = {};
    temp['pay_per'] = 0;
    temp['term'] = '';
    temp['pay_amt'] = 0;
    temp['currency'] = '';
    this.paymentTerms.push(temp);
  }











 

}

 