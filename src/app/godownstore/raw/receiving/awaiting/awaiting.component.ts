import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css'],
  providers: [DatePipe]
})
export class AwaitingComponent implements OnInit {

  department_name = localStorage.getItem('department'); // Fetching from localStorage

check=[];
   checkPointData1: any;
  checkPointData: any;
  related_to='Material';
  deviation_category='Unplanned Deviation';
  root_cause='COA Not Recevied With Consignment';
  containers;
  labors;
  po_status = '';
  isViewCheck = false;
  isContainer = false;
  isView = false;
  results;
  entry_time;

  isChecklist = false;
  isTanker = false;
  pack_size;
  selectedPO = [];
  labelList ;
  labelList1 = [];
  equipments;
  selectedFile: File;
  isUpload = 0;
  isDamage = false;
  from_time;
 
  type;
 
  to_time;
  isDedYes = false;
  isDedNo = false;

  challan_qty = 0;
  qty_received;
  total_containers = 0;
  outer_damage = 0;
  inner_damage = 0;
  manufacturer = '';
  qtyReceived = 0;
  containerTotal = 0;
  units;
  tankers;
  outerDamage = 0;
  innerDamage = 0;
  qty_status = 0;
  result = 0;
  tankerchecklist;
  ismanual = false;
  isvaccume = false;
  minDate = '';
  clicked = false;
  maxDate = '';
  finalcontainr;
  challan_no = '';
  employees;
  types;
  max_time;

  from_date = '';
  to_date = '';
  po_number = '';
  storage_condition = 'NO';
  today = '';
  material_subtype = '';
  departments: { name: string; value: boolean; status: boolean }[]= [
    { name: 'Stores', "value": false,"status":false },
     { name: 'Client', "value": false,"status":false },
     { name: 'Human Resource', "value": false,"status":false },
    { name: 'Quality Control', "value": false,"status":false },
    { name: 'Account', "value": false,"status":false },
    { name: 'Security', "value": false,"status":false },
    { name: 'Purchase', "value": false,"status":false },
    { name: 'Quality Assurance', "value": false,"status":false },
    { name: 'Engineering', "value": false,"status":false },
    { name: 'Store', "value": false,"status":false },
    { name: 'Packing', "value": false,"status":false },
    { name: 'R & D', "value": false,"status":false },
    { name: 'Marketing', "value": false,"status":false },
    { name: 'Vendor', "value": false,"status":false },
    { name: 'Management', "value": false,"status":false },
    { name: 'Planning', "value": false,"status":false },
    { name: 'Admin', "value": false,"status":false },
    { name: 'IPQA', "value": false,"status":false },
    { name: 'Regulatory', "value": false,"status":false },
    { name: 'EHS', "value": false,"status":false },
    { name: 'Enginering Store', "value": false,"status":false },
    { name: 'Production', "value": false,"status":false },
    { name: 'Business Development', "value": false,"status":false },
    { name: 'F & D', "value": false,"status":false },
    { name: 'IPQC', "value": false,"status":false },
  ];

  coa_received;
  coa_status = 'Accept the material';

  container_type = 'Bag';
  vendorM;
  isImapactQuality = 'YES';
  checklist: [];

  plant_id:any;
  router: any;


  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.maxDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.minDate = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {

    this.plant_id = this.service.getPlantConfigFields('plant_id');
     this.service.observableUnit.subscribe(response => {
      this.units = response;
    });
    this.getPendingInwords();
    this.getContainer();
    this.getTanker();
    this.getStorePersons();
    this.getMaterialType();
    this.getCurrentTime();
    this.getStoresupplier();
   
    this.getCheckPointData();
    // this.getDepartments();
     this.getCheckPointData1();
     this.getInitiatByData();
     this.getMaterialsByTypes();
 

  }


  materials ;
 


  getMaterialsByTypes() {
    this.service.get('common.php?type=getMaterialsByTypes')
      .subscribe((response) => {
        this.materials = response;
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




  // departments;
  // getDepartments() {
  //   this.service.get('hr/employee.php?type=get_department_by_designation')
  //     .subscribe(response => {
  //       this.departments = response;
  //     });
  // }
 
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
  isShow: boolean = false;
  toggle(){

    this.isShow = !this.isShow;
  }
  getManufactures() {
    this.service.get('common.php?type=getRAWManufactures&material_type='+this.selectedPO['material_type']).subscribe(response => {
      this.vendorM = response;
    });
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

get_save_sampling_batch() {
  this.qtyReceived=0;
  this.containerTotal=0;
  this.service.get('store/receive.php?type=get_save_sampling_batch&challan_no=' + this.selectedPO['challan_no'] +'&material_code=' + this.selectedPO['material_code']).subscribe(response => {
    this.labelList = response;
    this.Checkleverages(response);
    for (let i = 0; i < this.labelList.length; i++) {
      this.qtyReceived += +this.labelList[i]['qty_received'];
      this.containerTotal += +this.labelList[i]['total_containers'];
      this.outerDamage += +this.labelList[i]['outer_damage'];
      this.innerDamage += +this.labelList[i]['inner_damage'];
    }

    let test = this.containerTotal;
    this.finalcontainr = Math.ceil(test);
    this.labelList['total_containers'] = this.finalcontainr;

    console.log(this.qtyReceived);
    console.log(this.containerTotal);
    console.log(this.outerDamage);
    console.log(this.innerDamage);

    if (this.selectedPO['qty'] > 0) {
      this.result = this.qtyReceived - this.selectedPO['qty'];
    } else {
      this.result = this.qtyReceived - this.selectedPO['challan_qty'];
    }

    if (this.result >= 0) {
      this.po_stat = 'Extra';
      console.log('status=' + this.po_stat);
    } else {
      this.po_stat = 'Short';
      console.log('status=' + this.po_stat);
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

console.log("additionalQty"+additionalQty);
console.log("levrageQty"+levrageQty);
console.log("qtyReceived"+this.qtyReceived);

let  qtyReceived =0;

for (let i = 0; i < labelList.length; i++) {
 qtyReceived += +labelList[i]['qty_received'];
}

console.log(qtyReceived >= levrageQty);
 
if(leverages > 0){

  if(qtyReceived >= levrageQty ){

    alert('Leverages Triggred '+leverages + '% Of Po Quantity');
    this.lev = "Triggred";
  }

}



}






  getTankerchklist() {
    this.service.get('store/raw.php?type=getTankerchklist&form=Receiving(Tanker)&trans_id='+this.selectedPO['challan_no']).subscribe(response => {
      this.tankerchecklist = response;
    });
  }

  getPendingInwords() {
    this.results=[];
    this.service.get('store/raw.php?type=getPendingReceivings&material_subtype=' + this.material_subtype + '&from_date=' + this.from_date + '&to_date=' + this.to_date + '&po_number=' + this.po_number).subscribe(response => {
      this.results = response;
    });
  }
  

  AllRecord() {
    this.service.get('store/raw.php?type=getAllPendingReceivings').subscribe(response => {
      this.results = response;
    });
    this.po_number = '';
    this.material_subtype = '';
    this.from_date = '';
    this.to_date = '';
  }

  getMaterialType() {
    this.service.get('master/materialtype.php?type=getRawMaterialtype').subscribe(response => {
      this.types = response;
    });
  }
  getCurrentTime( ) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    this.entry_time = h + ':' + m;
    this.max_time = h + ':' + m;
  }

  check_point;
  getTanker() {
    this.service.get('store/raw.php?type=getCheckPointByForm&module=Receiving&form=Receiving(Tanker)').subscribe(response => {
      this.tankers = response; 
     this.check_point= this.tankers;
    })
  }

  getStorePersons() {
    this.service.get('employee.php?type=getStorePersons').subscribe(response => {
      this.employees = response;
    })
  }
  suplier;
  getStoresupplier() {
    this.service.get('purchase/vendor.php?type=getStoresupplier').subscribe(response => {
      this.suplier = response;
      // console.log('rd',this.suplier);
    })
  }


  checkValue(value) {
    if (isNaN(value)) {
      value = 0;
      alertify.error('Please Enter Numeric Value');
      return;
    } 
  }


  selectedPO_unit1 = '';

selectedPO_unit= '';
scopeItem= '';
detailsOfDev= '';
standProcedureSystem= '';


  viewResult(index) {
    this.selectedPO = this.results[index];

    this.typeOfDev = 'Planned';
    this.devScope = 'Material';

   this.selectedPO_unit = this.selectedPO['unit'];
   this.selectedPO_unit1 = this.selectedPO['unit'];


    if (this.selectedPO['is_tanker'] == 'YES') {
      this.container_type = 'Tanker';
    }
    this.isView = true;
    this.getManufactures();
    this.get_save_sampling_batch();
    this.get_save_sampling_batchfor_deviation();


    this.scopeItem = this.selectedPO['material_name']+" ("+this.selectedPO['material_code']+")";
    this.detailsOfDev = "The COA has not been received with the consignment.";
    this.standProcedureSystem = "The COA must be received along with the consignment.";


  }

  checkdamage(value) {
    if (value == 'Yes') {
      this.isDamage = true;
    } else {
      this.isDamage = false;
    }
  }

  close() {
    this.isView = false;
    this.container_type = 'Bag';
  }

  // onFileChanged(event) {
  //   if (event.target.files == 0) {
  //     this.isUpload = 0;
  //   } else {
  //     this.selectedFile = event.target.files[0];
  //     this.isUpload = 1;
  //   }
  // }

  

  getCleaner(show) {
    if (show == 'Manual') {
      this.ismanual = true;
      this.isvaccume = false;
    } else if (show == 'Vaccume Cleaner') {
      this.ismanual = false;
      this.isvaccume = true;
    }
  }
  selectedFile2: File;

  coa_file;
  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }
  mfg_by;

  addlabel(data) {
    
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (data.value.coa_received != 'Yes') {
      alertify.error('Please Fill Deviation Form');
    }

    let temp = data.value;
    temp['unit'] = this.selectedPO_unit;

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

    this.service.post('store/receive.php?type=cyclonesave_sampling_batch&material_code=' + this.selectedPO['material_code']+ '&challan_no=' + this.selectedPO['challan_no']+'&mfg_by='+this.selectedPO['vendor_no']+ '&ch_no=' + this.selectedPO['ch_no'], uploadData).subscribe(response => {
      if (response['status'] === 'success') {
      alertify.success(this.service.t('common.savedSuccess'));
      data.resetForm();
      this.get_save_sampling_batch()
      this.get_save_sampling_batchfor_deviation()
      this.coa_file='';
    } else {
      alertify.error('Failed: An error occured, Please try again!');
    }
  });
   
    
  }
 

  getdedusting(show) {
    if (show == 'Yes') {
      this.isDedYes = true;
      this.isDedNo = false;
    } else if (show == 'No') {
      this.isDedYes = false;
      this.isDedNo = true;
    }
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
  deleteLabel1(index) {
    this.qtyReceived = this.qtyReceived - this.labelList[index].qty_received;
    this.labelList.splice(index, 1);
    console.log(this.qtyReceived);
  }
  justification;

  quality_impact;
  updateDept(checked: boolean, index: number): void {
    this.departments[index].status = checked;
  }





  getClass(data){

    if(data['isOPenPO'] == 'YES'){
      return 'open';
    }else{
      return 'Jadugar';
    }


  }





  batch_no='';

  receiveMaterial(data, data1, data2) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
  
    let temp = data.value;

    console.log("data"+data);
    console.log("data1"+data1);
    console.log("data2"+data2);

    if(this.dev_labelList.length>0){
      temp["deviation"]='true';
       
     }
     
    const uploadData = new FormData();
    this.selectedCoa.Coas.forEach((data, index) => {
      if (data.file) {
        uploadData.append(`test_${data.id}`, data.file, data.file.name);
      }
    });
   
    Object.keys(temp).forEach(key => {
      let value = temp[key];
      if (key == 'dedusting') {
        uploadData.append(key, JSON.stringify(value));
      }else {
        uploadData.append(key, value);
      }
    });
 
    let temp1 = data1.value;
    Object.keys(temp1).forEach(key => {
      let value = temp1[key];
      uploadData.append(key, value);
    });

    uploadData.append("material_code", this.selectedPO['material_code']);
     uploadData.append("challan_no", this.selectedPO['challan_no']);
     uploadData.append("po_no", this.selectedPO['po_no']);
      
    // uploadData.append("po_status", this.po_status);
 
    uploadData.append("checklist", JSON.stringify(this.checkPointData));
    if (this.lev == "NotTriggred") {
      uploadData.append('inprocessStatus', 'inprocess');
    } else {
      uploadData.append('inprocessStatus', 'TO_PLANT_HEAD');
    }  
      let temp3 = this.selectedPO;
     temp3['checklist'] = this.checklist;
    temp3['checkPointData'] = this.checkPointData;
    this.service.post('store/raw.php?type=receiveMaterial&id=' + this.selectedPO['id']+ '&challan_id=' + this.selectedPO['challan_id']+'&from_dept=Stores', uploadData).subscribe(response => {
        if (response['status'] === 'success') {
        alertify.success('Material Received Successfully');
  
        data.resetForm();
        this.labelList = [];
        this.lev = "NotTriggred";
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




  reject_receiveMaterial(data,data1){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if (this.po_status) {

    }
    let temp = data.value;
     

    const uploadData = new FormData();
    if (this.isUpload === 1) {
      uploadData.append('coa', this.selectedFile, this.selectedFile.name);
    } else {
      if (temp['coa_received'] == 'Yes') {
        alertify.error('COA file is Compulsory');
        return;
      }
    }
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
 
  selectedCoa = {
    Coas: [] // Initialize your selectedTest data structure
  };

  onFileChanged(event, data) {
    data.file = event.target.files[0]; // Associate the selected file with the data object
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
  number(value) {
    if (isNaN(value)) {
      alertify.error('Number Only');
      return false;
    }
    // this.po_stat= this.selectedPO['qty'] - this.challan_qty;
    // if(this.po_stat >=0){
    //   this.po_stat='Extra';
    //   console.log('status='+this.po_stat);
    // }else{
    //   this.po_stat='Short';
    //   console.log('status='+this.po_stat);
    // }

  }


  addContainer() {
    if (this.container_type == 'Add New') {
      this.isContainer = true;
    } else {
      this.isContainer = false;
    }
  }
  saveContainer(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    this.service.post('master/container.php?type=saveContainers', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getContainer();
        this.isContainer = false;
        alert('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Please try Again');
      }
    });
  }
  getContainer() {
    this.service.get('master/container.php?type=getContainers').subscribe(response => {
      this.containers = response;
    });
  }



  getContainerNo(value) {

    if (isNaN(value)) {
      value = 0;
      alertify.error('Please Enter Numeric Value');
      return;
    } 

      if (this.qty_received % this.pack_size !== 0) {
          this.total_containers = Math.ceil(this.qty_received / this.pack_size);
      } else {
          this.total_containers = this.qty_received / this.pack_size;
      }

  }
   getContainerNoMeha(value) {

    if (isNaN(value)) {
      value = 0;
      alertify.error('Please Enter Numeric Value');
      return;
    } 

      if (this.qty_received % this.pack_size !== 0) {
          this.pack_size = Math.ceil(this.qty_received / this.total_containers);
      } else {
          this.pack_size = this.qty_received / this.total_containers;
      }

  }



  getCheckPointData(){
   
    this.service.get('store/raw.php?type=getCheckPointByForm&module=Receiving&form=Receiving').subscribe(response => {
     this.checkPointData = response;
    
   });

 }


  getCheckPointData1(){
    this.service.get('master/checklist.php?type=getCheckPointByForm&module=Receiving&form=Damage').subscribe(response => {
     this.checkPointData1 = response;
   });
 }

//  save(data2){
//   console.log(data2.value);
//   let temp = data2.value;
//   temp['checklist'] = this.checkPointData;
//   // temp['checklist1'] = this.checkPointData1;
//   this.service.post('store/raw.php?type=savechlist', JSON.stringify(temp)).subscribe(response => {
//     if (response['status'] == 'success') {
//       alertify.success(' Updated successfully');
//       this.isView = true;

//     } else {
//       alertify.error('Failed: An error occured, please try again!');
//     }
//   });

//  }





  saveChecklist() {
    let temp = this.selectedPO;
    temp['tanker_checklist'] = this.tankers;
    this.service.post('store/raw.php?type=saveCheckList&challan_no=' + this.selectedPO['challan_no'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isChecklist = false;
        this.isTanker = false;
        this.getContainer();
       
        alertify.success('Record Inserted Successfully');
      } else {
        alertify.error('Please try Again');
      }
    });
    this.getTankerchklist();

  } 



  fromlevel1=[];
   result1=[];
   
   
   
   addlabel1(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.fromlevel1=[];
      let temp1 = data.value;
    
 
 this.fromlevel1[this.fromlevel1.length] =temp1;
 
      console.log(this.fromlevel1);
  }

 
  tankerForm=[];
  tank_form=[];
  saveTank(data){
    if(!data.valid){
      alertify.error('All fields are required');
      return;
    }

    this.tankerForm =[];
     let temp=data.value

    this.tankerForm[this.tankerForm.length] = temp;
this.tank_form=this.tankerForm[0];
    console.log(this.tankerForm);

    this.service.post('store/raw.php?type=saveTank&challan_no=' + this.selectedPO['challan_no'],JSON.stringify(temp)).subscribe(response=>{
      if(response['status']==='success'){
          alertify.success('data save Successfuly');
        data.resetForm();
      }else{
        alertify.error('Error Occured');
      }
    });
  }
 


  




} 
