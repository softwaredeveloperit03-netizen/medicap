import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { finalize } from 'rxjs/operators';
declare let alertify;
@Component({
  selector: 'app-po',
  templateUrl: './po.component.html',
  styleUrls: ['./po.component.css'],
  providers:[DatePipe]
})
export class PoComponent implements OnInit {
  results;
  selectedCountry='';
  isView=false;
  max='';
  min=''; 
  vendors;
  transports;
  selectedResult: any = {};
  selectedmaterial= [];
  entry_time;
  max_time;
  min_time='00:00 AM'
  vehicle_type='';
  is_tanker="NO";
  plant_id = localStorage.getItem('plant_id');
  weighing_procedure = '';
  remark = '';
  isUploadingChallanDoc = false;
  isVehicleInspCheck = false;
  isDeviation = false;
  selectedMat = {};
  prodMatStageDoc = '';
  department_name = localStorage.getItem('department');
  initiateDate = '';
  rootCauseFile: File;
  standProceSysDoc: File;

  vehicleChecklist = [
    {"value": "", "item": "Condition of the vehicle","options": "Good/Not Good"},
    {"value": "", "item": "Condition of the Consignment","options": "OK/Not OK"},
    {"value": "", "item": "Any Spillage of Material","options": "Spillage Found/No Spillage"},
    {"value": "", "item": "Any Rusted Drums Found","options": "Yes/No"},
    {"value": "", "item": "Any Mix-up with other partys material","options": "Yes/No"},
    {"value": "", "item": "Any Presence of Rodents / Animals","options": "Yes/No"},
    {"value": "", "item": "Any obnoxious Odor","options": "Yes/No"},
    {"value": "", "item": "Whether Vehicle is Covered to Protect the Material from Direct Sun & Rain","options": "Yes/No"},
    {"value": "", "item": "Seal Integrity", "options": "OK/Not OK"},
    {"value": "", "item": "Remark :- ", "options": "textArea"},
  ];

  constructor(
    private service: DataAccessService,
    private router: Router,
    private datePipe: DatePipe,
    private cdr: ChangeDetectorRef
  ) {
    this.max = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.min = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');  
   }
 
  ngOnInit() {
    this.getPendingPO();
    this.getVendors();
    this.getTransportersLog();
    this.getCurrentTime();
    this.plant_id = localStorage.getItem('plant_id');
  }
  
 
  getVendors(){
    this.service.get('common.php?type=getVendors&vendor_name=').subscribe(response=>{
      this.vendors=response;
    });
  }

  getTransportersLog(){
    this.service.get('master/transport.php?type=getTransport').subscribe(response=>{
      this.transports = response;
    });
  }

  
  getPendingPO() {
    this.service.get('security/inward.php?type=getPendingPO').subscribe(response => {
      this.results = response;
      this.rebuildListRows();
    });
  }

  getClass(data){
    if(data['isOpenPo'] == 'OPEN'){
      return 'open';
    }else{
      return 'Jadugar';
    }
  }

  
   
  po_type = 'All';
  searchQuery = '';
  listRows: any[] = [];

  onListFilterChange(): void {
    this.rebuildListRows();
  }

  rebuildListRows(): void {
    let list = Array.isArray(this.results) ? this.results : [];

    if (this.po_type && this.po_type !== 'All') {
      list = list.filter((row: any) => row.po_type === this.po_type);
    }

    const query = (this.searchQuery || '').toLowerCase().trim();
    if (query) {
      list = list.filter((material) => {
        return Object.entries(material).some(([key, value]) => {
          if (key === 'entry_date') {
            const dateValue = typeof value === 'string' ? new Date(value) : value;
            return (
              dateValue instanceof Date &&
              dateValue.toISOString().slice(0, 10).includes(query)
            );
          } else {
            return value && value.toString().toLowerCase().includes(query);
          }
        });
      });
    }

    const flat: any[] = [];
    list.forEach((po: any) => {
      const mats = Array.isArray(po.materials) ? po.materials : [];
      if (!mats.length) {
        flat.push({ ...po, material_name: '—', _po: po });
        return;
      }
      mats.forEach((m: any) => {
        const name = String(m?.material_name || '').trim();
        flat.push({
          ...po,
          material_name: name || '—',
          material_code: m?.material_code,
          materials: po.materials,
          _po: po,
          _line: m,
        });
      });
    });
    this.listRows = flat;
  }


  view(data, ev?: Event) {
    if (ev) {
      ev.preventDefault();
      ev.stopPropagation();
    }
    this.selectedResult = data && data._po ? data._po : data;
    this.selectedmaterial = data['materials'];
    this.isView = true;
    this.cdr.detectChanges();

    if (this.selectedResult['po_type'] == 'Raw Material') {
      this.weighing_procedure = 'Inhouse-Weighing';
    } else if (this.selectedResult['po_type'] == 'Packing Material') {
      this.weighing_procedure = 'Counting';
    } else {
      this.weighing_procedure = '';
    }

    if (this.selectedResult['materials']) {
      this.selectedResult['materials'].forEach((mat) => {
        mat.received_rate = mat.received_rate || mat.rate || mat.quotation_amt;
        mat.diff = mat.diff || '0';
        mat.purMatId = mat.purMatId || mat.id;
      });
    }

    const today = new Date();
    this.initiateDate = today.toISOString().substring(0, 10);
    this.department_name = localStorage.getItem('department');
    this.getUploadChallans();
  }


  uploadedFileNames;
  getUploadChallans() {
    this.service.get('store/challan.php?type=getUploadedChallans&ch_no=' +this.selectedResult['challan_no'] +'&po_no=' +this.selectedResult['po_no']  +'&vendor_no=' +this.selectedResult['vendor_no']).subscribe((response) => {
        this.uploadedFileNames = response;
    });
  }


  viewFile(url1) {
    let url = this.service.url + '../../upload/challan/' + url1 +'?v=1';
   window.open(url, '_blank');
  }

  docFIle: File | null = null;
  previewUrl: string | ArrayBuffer | null = null;

  onFileChangedpsb(event: any) {
    const file = event.target.files[0];
    if (!file) return;

    this.docFIle = file;

    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.previewUrl = e.target?.result;
      };
      reader.readAsDataURL(file);
    } else {
      this.previewUrl = null;
    }

    event.target.value = '';
  }


  
  uploadFile(data) {

    if (!data.valid) {
      const missing = this.getInvalidFields(data);
      alertify.error(
        missing.length
          ? 'Please fill required field(s): ' + missing.join(', ')
          : 'All fields are required'
      );
      return;
    }
    if (this.selectedResult['challan_no'] == '') {
      alert('Please Add Challan No.....');
      return;
    }

    let temp = data.value;

    let formData = new FormData();
  
    if (this.docFIle !== undefined) {
      formData.append('docFIle', this.docFIle, this.docFIle.name);
    } 
 
    formData.append('ch_no', this.selectedResult['challan_no']);
    formData.append('po_no', this.selectedResult['po_no']);
    formData.append('vendor_no', this.selectedResult['vendor_no']);
    formData.append('docName', temp['docName']);
    formData.append('docNo', temp['docNo']);
  
    this.isUploadingChallanDoc = true;
    this.service.post('store/challan.php?type=uploadChallan', formData ).pipe(
      finalize(() => { this.isUploadingChallanDoc = false; })
    ).subscribe((response) => {
        if (response['status'] == 'success') {
          data.reset();
          this.previewUrl = null;
          this.getUploadChallans();
          alertify.success('Data Saved Successfully!');
        } else {
          alertify.error('An error occured, please try again!');
        }
      });
  }


  delDoc(id) {

    let userConfirmed = confirm("Are you sure you want to delete this Document....");
    if (userConfirmed) {
      
    let temp = {};
    temp['id'] = id;  

    this.service.post('store/challan.php?type=deluploadChallan', JSON.stringify(temp) ).subscribe((response) => {
        if (response['status'] == 'success') {
          this.getUploadChallans();
          alertify.success('Doc. Delected Successfully!');
        } else {
          alertify.error('An error occured, please try again!');
        }
      });

    }  
  }


  checkValue(value){
    if(value == 'NO'){
      alert("You Can Not Enter Vehicle Without COA!!!!!!!");
      alertify.error("You Can Not Enter Vehicle Without COA!!!!!!!");
    }
  }

  calculation(index) {
    let materials = this.selectedResult['materials'];
    let selectedMaterial = materials[index];

    selectedMaterial['diff'] = (
      parseFloat(selectedMaterial['received_rate']) -
      parseFloat(selectedMaterial['rate'])
    ).toFixed(2);

    selectedMaterial['diff_amt'] =
      +selectedMaterial['diff'] * +selectedMaterial['qty'];

    materials[index] = selectedMaterial;
    this.selectedResult['materials'] = materials;
  }

  isCoaChange(comp){
    if(comp['coaReceived'] == 'Under Deviation'){ this.isDeviation = true; }else{ this.isDeviation = false; }
    this.selectedMat = comp;
    this.prodMatStageDoc = comp['material_code'];
  }

  onFileChanged(event) {
    if (event.target.files.length === 1) {
      this.rootCauseFile = event.target.files[0];
    }
  }
  onFileChanged1(event) {
    if (event.target.files.length === 1) {
      this.standProceSysDoc = event.target.files[0];
    }
  }

  saveDeviation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let formData = new FormData();
    const temp = data.value;
    temp['prodMatStageDoc'] = this.prodMatStageDoc;

    for (let key in temp) {
      if (temp.hasOwnProperty(key)) {
        formData.append(key, temp[key]);
      }
    }
 
    if (this.rootCauseFile) {
      formData.append('rootCauseFile', this.rootCauseFile, this.rootCauseFile.name);
    }
    if (this.standProceSysDoc) {
      formData.append('standProceSysDoc', this.standProceSysDoc, this.standProceSysDoc.name);
    }

    this.service
      .post('pDeviation.php?type=saveQmsDeviations', formData)
      .subscribe(
        (response) => {
          if (response['status'] === 'success') {
            this.router.navigate(['/qa/qms/deviation']);
            alert('Deviation Initiated Successfully. Proceed...');
            data.resetForm();
            this.prodMatStageDoc= '';
          } else {
            alert('Failed: An error occurred, please try again!');
          }
        }
      );
  }



  fieldLabels: { [key: string]: string } = {
    challan_no: 'Packaging Slip No',
    challan_date: 'Packaging Slip Date',
    coaReceived: 'COA Received',
    transport: 'Transport',
    vehicle_no: 'Vehicle No.',
    driver_name: 'Driver / Person Name',
    transport_company: 'Transport / Courrier Agency Name',
    driver_contact: 'Driver / Person Mobile No.',
    vehicle_type: 'Vehicle Type',
    lrNo: 'L.R. No.',
    lrDate: 'L.R. Date',
    entry_time: 'Entry Time',
    entry_date: 'Entry Date',
    docName: 'Document Name',
    docNo: 'Receiving no',
    tax_invoice: 'Tax Invoice No.',
    tax_invoice_date: 'Tax Invoice Date',
    eway_bill_no: 'Eway Bill No.',
    e_way: 'Eway Bill Date',
  };

  fieldLabel(name: string): string {
    return this.fieldLabels[name] || name;
  }

  getInvalidFields(form: any): string[] {
    const invalid: string[] = [];
    const controls = form && form.controls ? form.controls : {};
    Object.keys(controls).forEach((name) => {
      const control = controls[name];
      if (control && control.errors && control.errors['required']) {
        invalid.push(this.fieldLabel(name));
      }
    });
    return invalid;
  }

  save(poForm, verifyForm) {
    // if (!poForm.valid) {
    //   const missing = this.getInvalidFields(poForm);
    //   alertify.error(
    //     missing.length
    //       ? 'Please fill required field(s): ' + missing.join(', ')
    //       : 'All fields are required'
    //   );
    //   return;
    // }

    // if (this.selectedResult['po_type'] == 'Raw Material' || this.selectedResult['po_type'] == 'Packing Material') {
    //   if (!verifyForm.valid) {
    //     const missing = this.getInvalidFields(verifyForm);
    //     alertify.error(
    //       missing.length
    //         ? 'Please fill required field(s): ' + missing.join(', ')
    //         : 'All fields are required'
    //     );
    //     return;
    //   }

    //   const emptyDetCheckRecords = this.documentsChecklist.filter(item => item.detCheckAns === "");
    //   if (emptyDetCheckRecords.length > 0) {
    //     alertify.error('Please fill all Document Checklist details');
    //     return;
    //   }

    //   if(( this.plant_id == '181' || this.plant_id == '182' || this.plant_id == '183' )) {
    //     const emptyVehicleCheckRecords = this.vehicleChecklist.filter(item => item.value === "");
    //     if (emptyVehicleCheckRecords.length > 0) {
    //       alertify.error('Please fill all Vehicle Inspection Checklist details');
    //       return;
    //     }
    //   }
    // }

    const selectedItems = this.selectedResult['materials'].filter(
      (term) => term.selected
    );
    const notSelectedCount = this.selectedResult['materials'].filter(
      (term) => !term.selected
    ).length;

    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }

    let temp = poForm.value || {};
    temp['materials'] = selectedItems;
    temp['po_no'] = this.selectedResult['po_no'];
    temp['po_date'] = this.selectedResult['entry_date'];
    temp['vendor_no'] = this.selectedResult['vendor_no'];
    temp['po_type'] = this.selectedResult['po_type'];
    temp['challan_no'] = this.selectedResult['challan_no'];
    temp['challan_date'] = this.selectedResult['challan_date'];
    temp['coaReceived'] = this.selectedResult['coaReceived'] || 'NA';
    temp['transport'] = this.selectedResult['transport'] || temp['transport'] || '';
    temp['transport_company'] = this.selectedResult['transport_company'] || temp['transport_company'] || '';
    temp['vehicle_no'] = this.selectedResult['vehicle_no'] || temp['vehicle_no'] || '';
    temp['driver_name'] = this.selectedResult['driver_name'] || temp['driver_name'] || '';
    temp['driver_contact'] = this.selectedResult['driver_contact'] || temp['driver_contact'] || '';
    temp['lrNo'] = this.selectedResult['lrNo'] || temp['lrNo'] || '';
    temp['lrDate'] = this.selectedResult['lrDate'] || temp['lrDate'] || '';
    temp['vehicle_type'] = this.vehicle_type;
    temp['is_tanker'] = this.is_tanker;
    temp['invoice_data'] = this.invoice_data;
    temp['vehicleChecklist'] = this.vehicleChecklist;
    temp['weighing_procedure'] = this.weighing_procedure;
    temp['notSelectedCount'] = notSelectedCount;
    temp['remark'] = this.remark;
    temp['eway_bill_no'] = (verifyForm && verifyForm.value) ? (verifyForm.value['eway_bill_no'] || '') : '';
    temp['e_way'] = (verifyForm && verifyForm.value) ? (verifyForm.value['e_way'] || '') : '';

    this.service.post('security/inward.php?type=saveChallanToReceiving&id=' + this.selectedResult['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('PO verified and sent to receiving successfully');
        poForm.resetForm();
        verifyForm.resetForm();
        this.isView = false;
        this.invoice_data = [];
        this.selectedResult = {};
        this.selectedmaterial = [];
        this.getPendingPO();
      } else {
        alertify.error(response['status']);
      }
    });
  }

 


  invoice_data =[];
  delADDTAXINV(index){
    this.invoice_data.splice(index,1);
  }

  ADDTAXINV(data){
    if (!data.valid) {
      const missing = this.getInvalidFields(data);
      alertify.error(
        missing.length
          ? 'Please fill required field(s): ' + missing.join(', ')
          : 'All fields are required'
      );
      return;
    }
     let temp =  data.value;
     this.invoice_data.push(temp);
     data.reset();
  }

  getCurrentTime( ) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    this.entry_time = h + ':' + m;
    this.max_time = h + ':' + m;
  }
  
  
}
