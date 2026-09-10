import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {DatePipe} from '@angular/common';
declare let alertify;

@Component({
  selector: 'app-raw-recieving',
  templateUrl: './raw-recieving.component.html',
  styleUrls: ['./raw-recieving.component.css'],
  providers: [DatePipe]
})
export class RawRecievingComponent implements OnInit {

  challan_for='';
  from_date = '';
  today='';
  to_date = '';
  isView = false;
  isEdit = false;
  results;
  vendor_no='';
  material_type='';
  status='';
  selectedReport = [];
  vendors;
  damage;
  
  labors;
  labelList=[];
  equipments;
  selectedFile: File;
  isUpload = 0;
  isDamage = false;
  from_time;
  to_time;
  area_cleaned_from;
  area_cleaned_to;
  equip_cleaned_from;
  equip_cleaned_to;
  equip_cleaned_by='';
  area_cleaned_by='';
  isDedYes=false;
  isDedNo=false;
  qty_received=0;
  total_containers=0;
  outer_damage=0;
  inner_damage=0;
  manufacturer='';
  qtyReceived=0;
  containerTotal=0;
  units;
  outerDamage=0;
  innerDamage=0;
  qty_status = 0;
  result = 0;
  ismanual=false;
  isvaccume=false;
  minDate='';
  maxDate='';
  vehicle_condition=[];
  departments = [
    { name: 'Stores', "value": false},
    { name: 'Production', "value": false},
    { name: 'Quality Control', "value": false},
    { name: 'Packing', "value": false},
    { name: 'Marketing', "value": false},
    { name: 'Client', "value": false},
    { name: 'Regulatory Department', "value": false},
    { name: 'Management', "value": false},
    { name: 'HR', "value": false},
    { name: 'Engineering', "value": false }
  ];

  coa_received = 'Yes';
  coa_status = 'Accept the material';

  container_type = 'Bag';

  operator_type = 'Parmanent Operator';
  employees;

  manufacturers;
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.maxDate=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
    this.minDate=this.datePipe.transform(Date.now(),'yyyy-MM-dd');  
    }

  ngOnInit() {
    this.service.observableUnit.subscribe(response=>{
      this.units=response;
    });
    this.getReceivingLog();
    this.getVendors();
    this.getApprovedLabors();
    this.getSelectedEquipments();
    this.getRawMaterialManufacturers();
  }

  getReceivingLog() {
    this.service.get('store/raw.php?type=getReceivingLog&material_type='+this.material_type+'&challan_for='+this.challan_for+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedReport = this.results[index];
    this.damage = this.selectedReport['receiving_details'];
    this.isView = true;
  }

  edit(index) {
      this.selectedReport = this.results[index];
      this.vehicle_condition = this.selectedReport['receiving_details'];
      this.isEdit = true;
       this.labelList=this.selectedReport['batches'];  
    }

  viewfile(url) {
    url = this.service.url + 'upload/coa/' + url;
    window.open(url, '_blank');
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }
 

  downloadPDF(){
    this.service.open('store/raw.php?type=receivingMaterialPDF&&id=' + this.selectedReport['id']);
  }

  downloadLog(){
    this.service.open('store/raw.php?type=receivingMaterialLogPDF&material_type='+this.material_type+'&from_date='+this.from_date+'&to_date='+this.to_date+'&challan_for='+this.challan_for)
  }

  getRawMaterialManufacturers() {
    this.service.get('common.php?type=getRawMaterialManufacturers').subscribe(response => {
      this.manufacturers = response;
    });
  }

  checkValue(event) {
    if (event.target.value < 0) {
      event.target.value = 0;
    }
  }
  viewResult(index) {
    this.selectedReport = this.results[index];
    this.isView = true;
  }

  checkdamage(value) {
    if(value == 'Yes') {
      this.isDamage = true;
    } else {
      this.isDamage = false;
    }
  }

  getCurrentTime(action, value) {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    /* let time = new Date().toLocaleTimeString(); */
    if (value == "usages") {
      if (action == 'from_time') {
        this.from_time = h + ':' + m;
      } else {
        this.to_time = h + ':' + m;
      }
    } else if (value =="area_clean") {
      if (action == 'from_time') {
        this.area_cleaned_from = h + ':' + m;
      } else {
        this.area_cleaned_to = h + ':' + m;
      }
    } else if (value =="equip_clean") {
      if (action == 'from_time') {
        this.equip_cleaned_from = h + ':' + m;
      } else {
        this.equip_cleaned_to = h + ':' + m;
      }
    }
  }

  onFileChanged(event) {
    if (event.target.files == 0) {
      this.isUpload = 0;
    } else {
      this.selectedFile = event.target.files[0];
      this.isUpload = 1;
    }
  }
  getSelectedEquipments() {
    this.service.get('store/equipment.php?type=getVaccumCleaners')
    .subscribe(response => {
      this.equipments = response;
    });
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
    this.labelList[this.labelList.length]=temp;
    this.qtyReceived += +temp['qty_received'];
    this.containerTotal += +temp['total_containers'];
    this.outerDamage += +temp['outer_damage'];
    this.innerDamage += +temp['inner_damage'];
    data.reset();
    this.result = this.qtyReceived - this.selectedReport['qty'];
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

  getApprovedLabors() {
    this.service.get('common.php?type=getLabours')
    .subscribe(response => {
      this.labors = response;
    });
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
      if(key =='dedusting') {
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
    uploadData.append("material_code", this.selectedReport['material_code']);
    uploadData.append('batches',JSON.stringify(this.labelList));
    uploadData.append('equip_cleaned_by',this.equip_cleaned_by);
    this.service.post('store/raw.php?type=receiveMaterial&id=' + this.selectedReport['id'], uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Material Received Successfully');
        this.isEdit = false;
        this.getReceivingLog();
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

  getStorePersons() {
    this.service.get('employee.php?type=getStorePersons').subscribe(response => {
      this.employees = response;
    });
  }
 

}
