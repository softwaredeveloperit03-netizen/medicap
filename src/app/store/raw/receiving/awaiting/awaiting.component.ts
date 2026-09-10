import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
import { ReceivingFormCustomisationService } from 'src/app/qa/soft-restriction/receiving-form-customisation/receiving-form-customisation.service';
declare let alertify: any;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css'],
  providers: [DatePipe]
})
export class AwaitingComponent implements OnInit {

  department_name = localStorage.getItem('department'); // Fetching from localStorage
 
  maxDate;
  minDate;
  plant_id = localStorage.getItem('plant_id');
  

  constructor(
    private service: DataAccessService,
    private datePipe: DatePipe,
    private receivingCustomisationService: ReceivingFormCustomisationService
  ) {
    this.maxDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.minDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {

    this.plant_id = localStorage.getItem('plant_id');
  
    this.getPendingInwords();
    this.getCheckPointData();
    this.getCheckPointData1();
    this.getInitiatByData();
    this.getCOntainerTypes();
    this.loadReceivingCustomisation();
 
  }
  receivingFieldMap: { [key: string]: any } = {};
  dynamicAdditionalFields: any[] = [];
  dynamicFieldValues: { [key: string]: any } = {};
  coa_received = '';
  dedusting_applicable = 'No';
  container_subtype = '';

  private loadReceivingCustomisation(): void {
    this.receivingCustomisationService.getActiveLayout().subscribe({
      next: (res: any) => {
        const rows = Array.isArray(res?.fields) ? res.fields : [];
        const map: { [key: string]: any } = {};
        rows.forEach((r: any) => {
          if (r?.field_key) {
            map[String(r.field_key)] = r;
          }
        });
        this.receivingFieldMap = map;
        this.dynamicAdditionalFields = rows.filter((r: any) => this.isAdditionalField(String(r?.field_key || '')));
        this.applyConfiguredDefaults();
      },
      error: () => {
        this.receivingFieldMap = {};
        this.dynamicAdditionalFields = [];
      }
    });
  }

  private isAdditionalField(key: string): boolean {
    const defaults = [
      'batch_no', 'qty_received', 'pack_size', 'total_containers', 'mfg_date', 'exp_date', 'coa_received',
      'container_type', 'container_subtype', 'challan_qty', 'received_qty', 'containerTotal', 'short_qty', 'po_status',
      'isdamagecontainer', 'outer_damage', 'hold_qty', 'dedusting_applicable', 'entry_time_start', 'entry_time_end'
    ];
    return defaults.indexOf(key) === -1;
  }

  getReceivingLabel(fieldKey: string, fallback: string): string {
    if (fieldKey === 'batch_no') {
      return 'Medicap Lot No';
    }
    const row = this.receivingFieldMap[fieldKey];
    const label = row?.field_label || fallback;
    if (fieldKey === 'challan_qty') {
      return 'Qty. As Per Payment Slip';
    }
    return String(label || '')
      .replace(/Packaging Slip/gi, 'Payment Slip')
      .replace(/Challan/gi, 'Payment Slip');
  }

  isReceivingApplicable(fieldKey: string): boolean {
    const row = this.receivingFieldMap[fieldKey];
    if (!row) {
      return true;
    }
    if (String(row.applicable || 'Applicable') === 'Not Applicable') {
      return false;
    }
    const conditionKey = String(row.visible_when_key || '').trim();
    if (!conditionKey) {
      return true;
    }
    const expected = String(row.visible_when_value || '').trim().toLowerCase();
    const current = this.getCurrentFieldValue(conditionKey);
    const currentStr = String(current ?? '').trim().toLowerCase();
    return currentStr === expected;
  }

  private getCurrentFieldValue(fieldKey: string): any {
    const key = String(fieldKey || '').trim();
    if (!key) {
      return '';
    }
    const hasOwn = Object.prototype.hasOwnProperty.call(this, key);
    if (hasOwn) {
      return (this as any)[key];
    }
    if (Object.prototype.hasOwnProperty.call(this.dynamicFieldValues, key)) {
      return this.dynamicFieldValues[key];
    }
    return '';
  }

  getReceivingOptions(fieldKey: string, fallback: string[]): string[] {
    const row = this.receivingFieldMap[fieldKey];
    const raw = String(row?.field_options || '').trim();
    if (!raw) {
      return fallback;
    }
    const options = raw
      .split(/[,|\n;\/]+/)
      .map((x) => String(x || '').trim())
      .filter((x) => x.length > 0);
    return options.length > 0 ? options : fallback;
  }

  getReceivingDefault(fieldKey: string, fallback = ''): any {
    const row = this.receivingFieldMap[fieldKey];
    const val = row?.default_value;
    if (val === undefined || val === null || String(val).trim() === '') {
      return fallback;
    }
    return val;
  }

  private setIfEmpty(targetKey: string, value: any): void {
    if (value === undefined || value === null || String(value).trim() === '') {
      return;
    }
    const current = (this as any)[targetKey];
    if (current === undefined || current === null || String(current).trim() === '') {
      (this as any)[targetKey] = value;
    }
  }

  private applyConfiguredDefaults(): void {
    this.setIfEmpty('coa_received', this.getReceivingDefault('coa_received', ''));
    this.setIfEmpty('isdamagecontainer', this.getReceivingDefault('isdamagecontainer', 'No'));
    this.setIfEmpty('dedusting_applicable', this.getReceivingDefault('dedusting_applicable', 'No'));
    this.setIfEmpty('po_status', this.getReceivingDefault('po_status', ''));
    this.setIfEmpty('container_type', this.getReceivingDefault('container_type', ''));
    this.setIfEmpty('container_subtype', this.getReceivingDefault('container_subtype', ''));
    this.setIfEmpty('entry_time_start', this.getReceivingDefault('entry_time_start', ''));
    this.setIfEmpty('entry_time_end', this.getReceivingDefault('entry_time_end', ''));
    this.dynamicAdditionalFields.forEach((cf: any) => {
      const key = String(cf?.field_key || '').trim();
      if (!key) {
        return;
      }
      const dv = String(cf?.default_value || '').trim();
      if (dv && (this.dynamicFieldValues[key] === undefined || this.dynamicFieldValues[key] === null || String(this.dynamicFieldValues[key]).trim() === '')) {
        this.dynamicFieldValues[key] = dv;
      }
    });
  }




  containerSubTypes;
  getSubtypesByContainer_type(container_type) {
    this.service.get('master/master.php?type=getSubtypesByContainer_type&container_type='+container_type).subscribe((response) => {
      this.containerSubTypes = response;
    });
  }

  containerTypes;
  getCOntainerTypes() {
    this.service.get('master/master.php?type=getCOntainerTypes').subscribe((response) => {
      this.containerTypes = response;
    });
  }







 
 
    typeOfDev = 'Planned';
    devScope = 'Material';
    getInitiatByData() {
      this.service.get('common.php?type=getInitiatByData').subscribe((response) => {
        this.identifiedBy = response['identifiedBy'];
      });
    }
    identifiedBy = '';

  
    
  devDetDoc: File;
  standProceSysDoc: File;

  onFileChanged0(event) {
    if (event.target.files.length === 1) {
      this.devDetDoc = event.target.files[0];
    }
  }

  onFileChanged00(event) {
    if (event.target.files.length === 1) {
      this.standProceSysDoc = event.target.files[0];
    }
  }
 
  
  selectedReport = [];
  batches=[];
  isDeviation = false;

  dev_labelList;

  get_save_sampling_batchfor_deviation() {
    this.service.get('store/receive.php?type=get_save_sampling_batchfor_deviation&challan_no=' + this.selectedPO['challan_no'] +'&vendor_no='+this.selectedPO['vendor_no']).subscribe(response => {
      this.dev_labelList = response;
      if(this.dev_labelList?.length!=0){
        this.isDeviation = true;
      }
    });
  }

  selectedPO = [];

  qtyReceived=0;
  containerTotal=0;
  finalcontainr = 0;
  result=0;

  labelList: any[] = [];
  isAddingLabel = false;

  get_save_sampling_batch() {
    this.qtyReceived=0;
    this.containerTotal=0;
    this.service.get('store/receive.php?type=get_save_sampling_batch&challan_no=' + this.selectedPO['challan_no'] +'&material_code=' + this.selectedPO['material_code']).subscribe((response: any) => {
      this.labelList = Array.isArray(response) ? response : [];
      this.Checkleverages(this.labelList);
      
      for (let i = 0; i < this.labelList.length; i++) {
        this.qtyReceived += +this.labelList[i]['qty_received'];
        this.containerTotal += +this.labelList[i]['total_containers'];
      }

      let test = this.containerTotal;
      this.finalcontainr = Math.ceil(test);
      this.labelList['total_containers'] = this.finalcontainr;

      if (this.selectedPO['qty'] > 0) {
        this.result = this.qtyReceived - this.selectedPO['qty'];
      } else {
        this.result = this.qtyReceived - this.selectedPO['challan_qty'];
      }

      if (this.result >= 0) {
        this.po_stat = 'Extra';
      } else {
        this.po_stat = 'Short';
      }
    });
  }
 
  lev = "NotTriggred";

  Checkleverages(response){

    let levrageQty = 0;
    const labelList = response;
    let poQty = this.selectedPO['qty'];
    let leverages = this.selectedPO['leverages'];
    let additionalQty =  poQty * (leverages / 100); 
    levrageQty = Number(poQty) + Number(additionalQty);
    let  qtyReceived =0;
    for (let i = 0; i < labelList.length; i++) {
      qtyReceived += +labelList[i]['qty_received'];
    }    
    if(leverages > 0){
      if(qtyReceived >= levrageQty ){
        alert('Leverages Triggred '+leverages + '% Of Po Quantity');
        this.lev = "Triggred";
      }
    }

  }
 
  isdamagecontainer = 'No';

  results: any[] = [];
  loading = false;
  getPendingInwords() {
    this.loading = true;
    this.results = [];
    this.service.get('store/raw.php?type=getPendingReceivings').subscribe({
      next: (response) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
        alertify.error('Failed to load awaiting materials.');
      }
    });
  }
  
 
  scopeItem= '';
  detailsOfDev= '';
  standProcedureSystem= '';

  challan_qty = 0;

  isView = false;
  viewResult(data) {
    this.challan_qty = 0;
    this.isAddingLabel = false;
    this.selectedPO = data;
    this.damage_batch_no = '';
    this.resetChecklistAnswers();
    this.getCheckPointData();
    this.isView = true;
  
    this.get_save_sampling_batch();
    this.get_save_sampling_batchfor_deviation();

    this.scopeItem = this.selectedPO['material_name']+" ("+this.selectedPO['material_code']+")";
    this.detailsOfDev = "The COA has not been received with the consignment.";
    this.standProcedureSystem = "The COA must be received along with the consignment.";
    this.challan_qty = this.selectedPO['qty'];
    this.applyConfiguredDefaults();
  }




  isDamage = false;
  checkdamage(value) {
    if (value == 'Yes') {
      this.isDamage = true;
    } else {
      this.isDamage = false;
    }
  }
  
  selectedFile2: File;
  coa_file;
  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }

  


  addlabel(data) {
    if (this.isAddingLabel) {
      return;
    }

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (String(data.value.coa_received || '').toLowerCase() === 'no') {
      this.isDeviation = true;
      alertify.error('COA is No, please initiate Deviation first.');
      return;
    }

    const batchNo = String(data.value.batch_no || '').trim();
    if (batchNo && this.labelList?.some((item) => String(item?.batch_no || '').trim() === batchNo)) {
      alertify.error('This batch is already added');
      return;
    }

    let temp = data.value;
    temp['unit'] = this.selectedPO['unit'];

    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile2 !== undefined) {
      uploadData.append('coa_file', this.selectedFile2, this.selectedFile2.name);
    }

    uploadData.append('materialForName', this.selectedPO['materialForName']);
    uploadData.append('materialFor', this.selectedPO['materialFor']);
    uploadData.append('clientGrpCode', this.selectedPO['clientGrpCode']);
    uploadData.append('clientSubGrpCode', this.selectedPO['clientSubGrpCode']);
    uploadData.append('tax_invoice', this.selectedPO['tax_invoice']);

    this.isAddingLabel = true;
    this.service.post('store/receive.php?type=cyclonesave_sampling_batch&material_code=' + this.selectedPO['material_code']+ '&challan_no=' + this.selectedPO['challan_no']+'&mfg_by='+this.selectedPO['vendor_no']+ '&ch_no=' + this.selectedPO['ch_no'], uploadData).subscribe({
      next: (response) => {
        this.isAddingLabel = false;
        if (response['status'] === 'success' || response['status'] === 'duplicate') {
          if (response['status'] === 'duplicate') {
            alertify.warning(response['msg'] || 'This batch is already added');
          } else {
            alertify.success(this.service.t('common.savedSuccess'));
          }
          data.resetForm();
          this.selectedFile2 = undefined;
          this.coa_file = '';
          this.get_save_sampling_batch();
          this.get_save_sampling_batchfor_deviation();
        } else {
          alertify.error('Failed: An error occured, Please try again!');
        }
      },
      error: () => {
        this.isAddingLabel = false;
        alertify.error('Network error. Please check connection and try again.');
      }
    });
  }
 
 


  deleteLabel(batch_no) {
    this.service.post('store/receive.php?type=delete_save_sampling_batch&batch_no=' + batch_no, JSON.stringify(batch_no)).subscribe(response => {
      if(response['status']=='success') {
        alertify.success(response['msg']);
        this.get_save_sampling_batch(); 
      } else{
        alertify.error(response['msg']);
      }
    });
  }


 
  justification;

  quality_impact;
 


  getClass(data){
    if(data['isOPenPO'] == 'YES'){
      return 'open';
    }else{
      return 'Jadugar';
    }
  }
 
  batch_no='';
  damage_batch_no='';
  checkPointData: any[] = [];
  checklist;

  receiveMaterial(data, checkListForm?) {

    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    if (checkListForm && !checkListForm.valid) {
      alertify.error('Please complete all required checklist answers');
      return;
    }


  
    if (this.labelList?.length == 0) {
      alertify.error('Please Add Batch Details');
      return;
    }
  
    let temp = data.value;

    const uploadData = new FormData();
  
    Object.keys(temp).forEach(key => {
      let value = temp[key];
      uploadData.append(key, value);
    });
 
    uploadData.append("material_code", this.selectedPO['material_code']);
    uploadData.append("challan_no", this.selectedPO['challan_no']);
    uploadData.append("po_no", this.selectedPO['po_no']);
       
    uploadData.append("receiving_details", JSON.stringify(temp));
    uploadData.append("receiving_dynamic_fields_json", JSON.stringify(this.dynamicFieldValues || {}));

    const checklistPayload = this.getChecklistPayload();
    if (this.isChecklistComplete() && checklistPayload.length === 0) {
      alertify.error('Checklist data is invalid. Please refresh the page and try again.');
      return;
    }
    uploadData.append("checklist", JSON.stringify(checklistPayload));
    uploadData.append("damageChecklist", JSON.stringify(this.checkPointData1));
    if (this.damage_batch_no) {
      uploadData.append('batch_no', this.damage_batch_no);
    }

    if (this.lev == "NotTriggred") {
      uploadData.append('inprocessStatus', 'inprocess');
    } else {
      uploadData.append('inprocessStatus', 'TO_PLANT_HEAD');
    }  
    
    this.service.post('store/raw.php?type=receiveMaterial&id=' + this.selectedPO['id']+ '&challan_id=' + this.selectedPO['challan_id']+'&from_dept=Stores', uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Material Received Successfully');
  
        data.resetForm();
        this.labelList = [];
        this.lev = "NotTriggred";
        this.isView = false;
        data.resetForm();
        this.getPendingInwords();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }




  po_status = '';
 


  reject_receiveMaterial(data,data1){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (this.po_status) {

    }
    let temp = data.value;
     

    const uploadData = new FormData();
  
    Object.keys(temp).forEach(key => {
      let value = temp[key];
      if (key == 'dedusting') {
        uploadData.append(key, JSON.stringify(value));
      } else if (key == 'deviation') {
        uploadData.append(key, JSON.stringify(value));
      } else {
        uploadData.append(key, value);
      }
    });

    let temp1 = data1.value;
    Object.keys(temp1).forEach(key => {
      let value = temp1[key];
      uploadData.append(key, value);
    });

    uploadData.append("material_code", this.selectedPO['material_code']);
    uploadData.append('batches', JSON.stringify(this.labelList));
    uploadData.append("challan_no", this.selectedPO['challan_no']);
 
    this.service.post('store/raw.php?type=reject_receiveMaterial&id=' + this.selectedPO['id']+ '&challan_id=' + this.selectedPO['challan_id'], uploadData).subscribe(response => {
      if (response['status'] === 'success') {
      alertify.success('Material Received Successfully');
      data.resetForm();
      this.labelList = [];
     
      this.isView = false;
      data.resetForm();
      data1.resetForm();
      this.getPendingInwords();
    } else {
      alertify.error('Failed: An error occured, Please try again!');
    }
  });
  }
 
 

    devScope1 = '';

  saveDeviation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let formData = new FormData();
    const temp = data.value;

    // Append form values to FormData

    for (let key in temp) {
      if (temp.hasOwnProperty(key)) {
        formData.append(key, temp[key]);
      }
    }

    if(temp['devScope'] == 'Other'){
      formData.append('devScope', this.devScope1);
    }
  
    if (this.devDetDoc) {
      formData.append('devDetDoc', this.devDetDoc, this.devDetDoc.name);
    }
    if (this.standProceSysDoc) {
      formData.append('standProceSysDoc', this.standProceSysDoc, this.standProceSysDoc.name);
    }

    console.log(formData);
    this.service
      .post('deviation1.php?type=saveQmsDeviations', formData)
      .subscribe(
        (response) => {
          if (response['status'] === 'success') {
            
            alert('Deviation Initiated Successfully. Proceed...');
            this.isDeviation = false;
            data.resetForm();
          } else {
            alert('Failed: An error occurred, please try again!');
          }
        }
        
      );
  }








  selectedBatch =[];
  hold_qty =0;

  selectBat(index){
    index =index -1;
    this.selectedBatch = this.labelList[index];
  }

  calculate_qty(value){

    if (isNaN(value)) {
      alertify.error('Number Only');
      return false;
    } 

    if (value > Number(this.selectedBatch['total_containers'])) {
      alertify.error('cant Exceed Container Than '+ this.selectedBatch['total_containers']);
      return false;
    }

    this.hold_qty =0;
    let pack_size = this.selectedBatch['pack_size'];
    this.hold_qty = Number(pack_size) * Number(value);

  }

 

  po_stat
 
 qty_received = 0;
 pack_size = 0;
 total_containers = 0;
  getContainerNo(value) {

    if (value !== undefined && value !== null && isNaN(Number(value))) {
      alertify.error('Please Enter Numeric Value');
      return;
    }
    const qty = Number(this.qty_received) || 0;
    const pack = Number(this.pack_size) || 0;
    if (pack <= 0) {
      this.total_containers = 0;
      return;
    }
    if (qty % pack !== 0) {
      this.total_containers = Math.ceil(qty / pack);
    } else {
      this.total_containers = qty / pack;
    }
  }

 

  getCheckPointData(){
    this.service.get('store/raw.php?type=getCheckPointByForm&module=Receiving&form=Receiving').subscribe(response => {
      this.checkPointData = (Array.isArray(response) ? response : []).map((section) => ({
        ...section,
        checkpoint: (section.checkpoint || []).map((item: any, idx: number) =>
          this.prepareCheckPointItem({
            ...item,
            id: item.id,
          })
        ),
      }));
      this.buildCheckPointRows();
    });
  }

  getChecklistValue(item: any): string {
    if (this.hasInlineOptions(item)) {
      const options = this.getChecklistOptionList(item);
      const selected = options
        .map((opt, idx) => (item.optionChecks?.[idx] ? opt : null))
        .filter(Boolean);
      if (item.hasOtherOption && String(item.otherOption || '').trim()) {
        selected.push('Other: ' + String(item.otherOption).trim());
      }
      if (selected.length > 0) {
        return selected.join(', ');
      }
    }
    return String(item.check || '').trim();
  }

  isChecklistAnswerRequired(item: any): boolean {
    if (this.isChecklistFooterNote(item) || this.isChecklistSectionHeader(item)) {
      return false;
    }
    if (this.po_status === 'Reject / Return' || this.po_status === 'Rejected') {
      return false;
    }
    if (!this.hasInlineOptions(item) && !this.isObservationsRow(item)) {
      const evl = String(item?.evl_pr || '').trim();
      if (!evl || evl === '0') {
        return false;
      }
    }
    return true;
  }

  isChecklistComplete(): boolean {
    let complete = true;
    (this.checkPointData || []).forEach((section) => {
      (section.checkpoint || []).forEach((item: any) => {
        if (!this.isChecklistAnswerRequired(item)) {
          return;
        }
        if (!this.getChecklistValue(item)) {
          complete = false;
        }
      });
    });
    return complete;
  }

  getChecklistPayload(): any[] {
    const rows: any[] = [];
    (this.checkPointData || []).forEach((section) => {
      (section.checkpoint || []).forEach((item: any) => {
        if (this.isChecklistFooterNote(item) || this.isChecklistSectionHeader(item)) {
          return;
        }
        const chkId = Number(item.id);
        if (!chkId || Number.isNaN(chkId)) {
          return;
        }
        const answer = this.getChecklistValue(item);
        if (!String(answer || '').trim()) {
          return;
        }
        rows.push({
          id: chkId,
          module: section.module || 'Receiving',
          form_name: section.form_name || 'Receiving',
          department: section.department || 'Store',
          check_point: item.check_point,
          evl_pr: String(item.evl_pr || ''),
          check: answer,
          heading: section.heading || '',
        });
      });
    });
    return rows;
  }

  prepareCheckPointItem(item: any): any {
    const prepared = { ...item };
    const options = this.getChecklistOptionList(prepared);
    prepared.optionChecks = prepared.optionChecks || options.map(() => false);
    prepared.hasOtherOption = options.some((opt) => /other/i.test(opt));
    return prepared;
  }

  checkPointRows: any[] = [];

  buildCheckPointRows(): void {
    const rows: any[] = [];

    (this.checkPointData || []).forEach((section) => {
      const heading = String(section.heading || '').trim();
      if (heading) {
        rows.push({
          type: 'header',
          title: heading,
          showPoNo: heading.toLowerCase().includes('po#'),
        });
      }

      (section.checkpoint || []).forEach((item: any, idx: number) => {
        if (this.isChecklistFooterNote(item)) {
          rows.push({ type: 'footer', title: item.check_point });
          return;
        }

        rows.push({
          type: 'item',
          item,
          rowKey: `${section.id}_${item.id || idx}`,
        });
      });
    });

    this.checkPointRows = rows;
  }

  trackCheckPointRow(index: number, row: any): string {
    if (row?.rowKey) {
      return row.rowKey;
    }
    if (row?.type === 'header' || row?.type === 'footer') {
      return `${row.type}_${row.title || index}`;
    }
    return String(index);
  }

  resetChecklistAnswers(): void {
    (this.checkPointData || []).forEach((section) => {
      (section.checkpoint || []).forEach((item: any) => {
        item.check = '';
        if (Array.isArray(item.optionChecks)) {
          item.optionChecks = item.optionChecks.map(() => false);
        }
        item.otherOption = '';
      });
    });
  }

  isChecklistSectionHeader(item: any): boolean {
    const evlType = String(item?.evl_type || '').trim().toUpperCase();
    const evlPr = String(item?.evl_pr || '').trim();
    const label = String(item?.check_point || '').trim();
    return evlType === 'SECTION' || evlType === 'HEADER' || evlPr === '0' || /^[A-D]\.\s/.test(label);
  }

  isChecklistFooterNote(item: any): boolean {
    const evlType = String(item?.evl_type || '').trim().toUpperCase();
    const label = String(item?.check_point || '').toLowerCase();
    return evlType === 'FOOTER' || label.includes('contact purchasing associate');
  }

  hasChecklistFooterInData(): boolean {
    return (this.checkPointData || []).some((section) =>
      (section.checkpoint || []).some((item) => this.isChecklistFooterNote(item))
    );
  }

  isObservationsRow(item: any): boolean {
    return String(item?.check_point || '').toLowerCase().includes('observation');
  }

  hasInlineOptions(item: any): boolean {
    return this.getChecklistOptionList(item).length > 0;
  }

  getChecklistOptionList(item: any): string[] {
    const options = String(item?.options || '').trim();
    if (!options) {
      return [];
    }
    return options.split(',').map((opt) => opt.trim()).filter(Boolean);
  }

  checkPointData1: any[] = [];
  getCheckPointData1(){
    this.service.get('master/checklist.php?type=getCheckPointByForm&module=Receiving&form=Damage').subscribe(response => {
      this.checkPointData1 = Array.isArray(response) ? response : [];
    });
  }
  
  materialTypeFilter = 'Raw Material';
  searchQuery = '';

  get filteredMaterials(): any[] {
    if (!this.results || !Array.isArray(this.results)) {
      return [];
    }

    let list = this.results;
    if (this.materialTypeFilter) {
      const type = this.materialTypeFilter.toLowerCase().trim();
      list = list.filter((material) => {
        const matType = String(material?.material_type || '').toLowerCase().trim();
        return matType === '' || matType === type;
      });
    }

    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return list;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return list.filter((material) => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date' || key === 'inward_date' || key === 'po_date' || key === 'challan_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            !isNaN(dateValue.getTime()) &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        }
        return value && value.toString().toLowerCase().includes(query);
      });
    });
  }


  




} 
