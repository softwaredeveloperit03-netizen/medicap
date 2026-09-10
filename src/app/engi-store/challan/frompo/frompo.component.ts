import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

declare let alertify;
@Component({
  selector: 'app-frompo',
  templateUrl: './frompo.component.html',
  styleUrls: ['./frompo.component.css'],
})
export class FrompoComponent implements OnInit {

  isView = false;
   
  plant_id = localStorage.getItem('plant_id');

  constructor( private service: DataAccessService,private router:Router, private cdr: ChangeDetectorRef) { }

  ngOnInit(): void {
    this.getPendingChallansGeneralMaterial();
    this.plant_id = localStorage.getItem('plant_id');
  }

 
  getClass(data){
    if(data['isOpenPo'] == 'OPEN'){
      return 'open';
    }else{
      return 'Jadugar';
    }
  }


 
  results;
  getPendingChallansGeneralMaterial() {
    this.service.get('store/challan.php?type=getPendingChallansGeneralMaterial').subscribe((response) => {
        this.results = response;
    });
  }

 
 
  selectedResult = [];
  weighing_procedure = '';

  view(data) {
    this.selectedResult = data;
    this.isView = true;

    this.cdr.detectChanges();

    if (this.selectedResult['material_type'] == 'Raw Material') {
      this.weighing_procedure = 'Inhouse-Weighing';
    } else {
      this.weighing_procedure = 'Counting';
    }
    this.getUploadChallans();


    //deviation Things 
    const today = new Date();
    this.initiateDate = today.toISOString().substring(0, 10);
    this.department_name = localStorage.getItem('department');
  }
 
  uploadedFileNames;
  getUploadChallans() {
    this.service.get('store/challan.php?type=getUploadedChallans&ch_no=' +this.selectedResult['ch_no'] +'&po_no=' +this.selectedResult['po_no']  +'&vendor_no=' +this.selectedResult['vendor_no']).subscribe((response) => {
        this.uploadedFileNames = response;
    });
  }
 
  updateChallan(data, status) {
    if (!data.valid) {
      alertify.error('All Field Required');
      return;
    } 

    if(this.selectedResult['material_type'] == 'Raw Material' || this.selectedResult['material_type'] == 'Packing Material'){
 
      const emptyDetCheckRecords = this.documentsChecklist.filter(item => item.detCheckAns === "");
      if (emptyDetCheckRecords.length > 0) {
        alertify.error('Please fill all Document Checklist details');
        return;
      }
 
    }
 
    if(( this.plant_id == '181' || this.plant_id == '182' || this.plant_id == '183' ) &&  ( this.selectedResult['material_type'] == 'Raw Material' || this.selectedResult['material_type'] == 'Packing Material' )){

      const emptyVehicleCheckRecords = this.vehicleChecklist.filter(item => item.value === "");
      if (emptyVehicleCheckRecords.length > 0) {
        alertify.error('Please fill all Vehicle Inspection Checklist details');
        return;
      }

    }
 
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
    console.log(selectedItems);
    console.log('notSelectedCount' + notSelectedCount);

    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
 
    uploadData.append('materials', JSON.stringify(selectedItems));
    uploadData.append('documentsChecklist', JSON.stringify(this.documentsChecklist));
    uploadData.append('vehicleChecklist', JSON.stringify(this.vehicleChecklist));
    uploadData.append('weighing_procedure', this.weighing_procedure);
    uploadData.append('notSelectedCount', notSelectedCount);
    uploadData.append('challan_no', this.selectedResult['challan_no']);
    uploadData.append('ch_no', this.selectedResult['ch_no']);
    uploadData.append('po_no', this.selectedResult['po_no']);

    this.service.post('store/challan.php?type=updateChallan&status=' + status + '&id=' + this.selectedResult['id'],uploadData).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Record updated successfully');
          this.isView = false;
          this.getPendingChallansGeneralMaterial();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });

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
 
 
 
  
  viewFile(url1) {
    let url = this.service.url + '../../upload/challan/' + url1 +'?v=1';
    window.open(url, '_blank');
  }

 
  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

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

 
    docFIle: File | null = null;
  previewUrl: string | ArrayBuffer | null = null;

  onFileChangedpsb(event: any) {
    const file = event.target.files[0];
    if (!file) return;

    this.docFIle = file;

    // Preview only for images
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.previewUrl = e.target?.result;
      };
      reader.readAsDataURL(file);
    } else {
      this.previewUrl = null;
    }

    // Reset input so same file can be reselected if needed
    event.target.value = '';
  }


  
  uploadFile(data) {
   
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    if (this.selectedResult['ch_no'] == '') {
      alert('Please Add Challan No.....');
      return;
    }

    let temp = data.value;

    let formData = new FormData();
  
    if (this.docFIle !== undefined) {
      formData.append('docFIle', this.docFIle, this.docFIle.name);
    } 
 
    formData.append('ch_no', this.selectedResult['ch_no']);
    formData.append('po_no', this.selectedResult['po_no']);
    formData.append('vendor_no', this.selectedResult['vendor_no']);
    formData.append('docName', temp['docName']);
    formData.append('docNo', temp['docNo']);
  
    this.service.post('store/challan.php?type=uploadChallan', formData ).subscribe((response) => {
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



  printBarcode(material_code,material_name,trackingId) {
    this.service.open('purchase/po/print_barcode.php?material_code='+encodeURIComponent(material_code)
    +'&material_name='+encodeURIComponent(material_code)
    +'&trackingId='+encodeURIComponent(trackingId));
  }


  isDocChecklist = false;
  isVehicleInspCheck = false;




  documentsChecklist =  [
    { "type":"Header", "field": "TAX INVOICE" , "details_to_check": "Detalis to Check", "remark": "Remark" , "detCheckAns":"NA"},
    { "type":"text", "field": "Invoice No & Date", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "GST No of Supplier & Buyer", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "Name & Address of Supplier & Buyer", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "HSN Code", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "Material Rate, Quantity & GST Check with PO", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "Stamp & Signature of Supplier on Tax Invoice", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"Header", "field": "E-WAY BILL" , "details_to_check": "-", "remark": "" , "detCheckAns":"NA"},
    { "type":"text", "field": "E-Way Bill Valid Date", "details_to_check": "", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "Place of Supply & Destination", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "Vehicle Number", "details_to_check": "", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "Name & Address of Supplier & Buyer", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"Header", "field": "B.O.E COPY" , "details_to_check": "-", "remark": "" , "detCheckAns":"NA"},
    { "type":"text", "field": "Tax Invoice No & Date", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "Name & Address of Supplier & Buyer", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"Header", "field": "LR. COPY", "details_to_check": "-", "remark": "" , "detCheckAns":"NA"},
    { "type":"text", "field": "LR No & Date", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "Place of Supply & Destination", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "Name & Address of Consignee & Consignor", "details_to_check": "YES/NO", "remark": "" , "detCheckAns":""},
    { "type":"text", "field": "Full Truck Load / Part Truck Load", "details_to_check": "", "remark": "" , "detCheckAns":""}
]



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
]


  isDeviation = false;
  selectedMat = {};
  prodMatStageDoc = '';
  isCoaChange(comp){
    if(comp['coaReceived'] == 'Under Deviation'){ this.isDeviation = true; }else{ this.isDeviation = false; }
    this.selectedMat = comp;
    this.prodMatStageDoc = comp['material_code'];
  }
 
 
  //deviation Variables
  department_name = localStorage.getItem('department');
  initiateDate = '';
  rootCauseFile: File;
  standProceSysDoc: File;

  // diviation FUnctions


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

    // Append form values to FormData

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

    console.log(formData);
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























 

}



   