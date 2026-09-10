
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-receiving',
  templateUrl: './receiving.component.html',
  styleUrls: ['./receiving.component.css'],
  providers:[DatePipe]
})
export class ReceivingComponent implements OnInit {
  containers;
  labors;
  po_status='';
  isViewCheck=false;
  isContainer=false;
  isView = false;
  results;
  isChecklist=false;
  pack_size;
  selectedPO = [];
  labelList=[];
  equipments;
  selectedFile: File;
  isUpload = 0;
  isDamage = false;
  from_time;
  to_time;
  isDedYes=false;
  isDedNo=false;

  challan_qty = 0;
  qty_received;
  total_containers;
  outer_damage=0;
  inner_damage=0;
  manufacturer='';
  qtyReceived=0;
  containerTotal=0;
  units;
  tankers;
  outerDamage=0;
  innerDamage=0;
  qty_status = 0;
  result = 0;
  ismanual=false;
  isvaccume=false;
  minDate='';
  maxDate='';
  finalcontainr;
  challan_no='';
  employees;
  types;
  from_date='';
  to_date='';
  po_number='';
  storage_condition='NO';
  today='';
  material_subtype='';
  departments = [
    { name: 'Stores', "value": false},
    { name: 'Production', "value": false},
    { name: 'Quality Control', "value": false},
    { name: 'Packing', "value": false},
    { name: 'Marketing', "value": false},
    { name: 'Client', "value": false},
    { name: 'Regulatory', "value": false},
  ];

  coa_received = 'Yes';
  coa_status = 'Accept the material';

  container_type = 'Bag';

  isImapactQuality = 'YES';
  constructor(private service: DataAccessService,private datePipe : DatePipe) {
    this.maxDate=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.minDate=this.datePipe.transform(Date.now(),'yyyy-MM-dd');  
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');  
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');  
    this.today=this.datePipe.transform(Date.now(),'yyyy-MM-dd');  
   }
 
  ngOnInit() {
    this.service.observableUnit.subscribe(response=>{
      this.units=response;
    });
    this.getPendingInwords();
    this.getContainer();
    this.getTanker();
    this.getStorePersons();
    this.getMaterialType();
    
  }
  getPendingInwords() {
    this.service.get('store/raw.php?type=getPendingReceivings&material_subtype=' + this.material_subtype +'&from_date='+this.from_date +'&to_date='+this.to_date+'&po_number='+this.po_number).subscribe(response => {
      this.results = response;
    });
  }

  AllRecord() {
    this.service.get('store/raw.php?type=getAllPendingReceivings').subscribe(response => {
      this.results = response;
    });
    this.po_number='';
    this.material_subtype='';
    this.from_date='';
    this.to_date='';
  }

  getMaterialType(){
    this.service.get('master/materialtype.php?type=getRawMaterialtype').subscribe(response => {
      this.types= response;
    });
  }

  getTanker(){
    this.service.get('master/country.php?type=getTanker').subscribe(response => {
      this.tankers= response;
    })
  }

  getStorePersons(){
    this.service.get('employee.php?type=getStorePersons').subscribe(response => {
      this.employees = response;
    })
  }


  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  viewResult(index) {
    this.selectedPO = this.results[index];
    if(this.selectedPO['is_tanker'] == 'YES'){
      this.container_type='Tanker';
    }
    this.isView = true;
  }

  checkdamage(value) {
    if(value == 'Yes') {
      this.isDamage = true;
    } else {
      this.isDamage = false;
    }
  }

  close(){
    this.isView = false;
    this.container_type='Bag';
  }

  onFileChanged(event) {
    if (event.target.files == 0) {
      this.isUpload = 0;
    } else {
      this.selectedFile = event.target.files[0];
      this.isUpload = 1;
    }
  }

  getCleaner(show){
    if(show=='Manual'){
      this.ismanual=true;
      this.isvaccume=false;
    }else if(show=='Vaccume Cleaner'){
      this.ismanual=false;
      this.isvaccume=true;
    }
  }

  addlabel(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }

    let temp=data.value;

    // let qty = +this.qtyReceived + +temp['qty_received'];
    // if (+qty > +this.challan_qty) {
    //   alertify.error('Challan Qty & Received Qty not matching!');
    //   return;
    // }
    temp['unit'] = this.selectedPO['unit'];
    this.labelList[this.labelList.length]=temp;
    this.qtyReceived += +temp['qty_received'];
    this.containerTotal += +temp['total_containers'];
    this.outerDamage += +temp['outer_damage'];
    this.innerDamage += +temp['inner_damage'];

    let test= this.total_containers;
    this.finalcontainr =  Math.ceil(test);
    temp['total_containers'] = this.finalcontainr;

    data.resetForm();
    this.result = this.qtyReceived - this.selectedPO['qty'];
  }

  getdedusting(show){
    if(show=='Yes'){
      this.isDedYes=true;
      this.isDedNo=false;
    }else if(show=='No'){
      this.isDedYes=false;
      this.isDedNo=true;
    }
  }

  deleteLabel(index){  
    this.qtyReceived = this.qtyReceived-this.labelList[index].qty_received;
    this.labelList.splice(index,1);
    console.log(this.qtyReceived); 
  }

  receiveMaterial(data, data1) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    if(this.po_status){

    }
    let temp = data.value;

    // let temp2 = data1.value;
    // if (+temp2["challan_qty"] !== +temp["received_qty"]) {
    //   alertify.error('Challan Qty & Received Qty not matching!');
    //   return;
    // }

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
      if(key =='dedusting') {
        uploadData.append(key, JSON.stringify(value));
      } else if (key =='deviation') {
        uploadData.append(key, JSON.stringify(value));
      } else{
        uploadData.append(key, value);
      }
    });

    let temp1 = data1.value;
    Object.keys(temp1).forEach(key => {
      let value = temp1[key];
      uploadData.append(key, value);
    });
    
    uploadData.append("material_code", this.selectedPO['material_code']);
    uploadData.append('batches',JSON.stringify(this.labelList));
    uploadData.append("challan_no", this.selectedPO['challan_no']);

    this.service.post('store/raw.php?type=receiveMaterial&id=' + this.selectedPO['id'], uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Material Received Successfully');
        data.resetForm();
        this.labelList=[]; 
        this.isView = false;
        data.resetForm();
        data1.resetForm();
        this.getPendingInwords();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }

  number(value){
    if (isNaN(value)){
      alertify.error('Number Only');
      return false;
    }
  }


  addContainer(){
    if(this.container_type=='Add New'){
      this.isContainer=true;
    }else{
      this.isContainer=false;
    }
  }
  saveContainer(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    } 
    this.service.post('master/container.php?type=saveContainers', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        this. getContainer();
        this.isContainer=false;
        alert('Record Inserted Successfully');
        data.resetForm();
      } else {
        alert('Please try Again');
      }
    });
  }
  getContainer(){
    this.service.get('master/container.php?type=getContainers').subscribe(response=>{
      this.containers=response;
    });
  }

  getContainerNo(){
    this.total_containers=parseInt((this.qty_received*1/this.pack_size*1).toFixed(2));
  }

 




 
  saveChecklist(){
    let temp=this.selectedPO;
    temp['tanker_checklist']=this.tankers;
    this.service.post('store/raw.php?type=saveCheckList&id='+this.selectedPO['id'],JSON.stringify(this.selectedPO)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isChecklist=false;
        this. getContainer();
        alertify.success('Record Inserted Successfully');
      } else {
        alertify.error('Please try Again');
      }
    });

  }


  








} 
