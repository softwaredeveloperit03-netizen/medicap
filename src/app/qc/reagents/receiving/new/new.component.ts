import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  labors;
  isView = false;
  results;
  selectedPO = [];
  labelList=[];
  equipments;
  selectedFile: File;
  isUpload = 0;
  isDrum = false;
  isBag = false;
  isBox = false;
  isCOA = false;
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
  outerDamage=0;
  innerDamage=0;
  qty_status = 0;
  result = 0;
  ismanual=false;
  isvaccume=false;
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
  constructor(private service: DataAccessService) { }
 
  ngOnInit() {
    this.getPendingInwords();
    this.getApprovedLabors();
    this.getSelectedEquipments();
  }

  getPendingInwords() {
    this.service.get('qc/indicator.php?type=getPendingReceivings').subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedPO = this.results[index];
    console.log(this.selectedPO);
    this.isView = true;
  }

  checkContainerType(value) {
    if(value === 'Drum') {
      this.isDrum = true;
      this.isBag = false;
      this.isBox = false;
    } else if(value === 'Bag') {
      this.isDrum = false;
      this.isBag = true;
      this.isBox = false;
    } else if(value === 'Boxes') {
      this.isDrum = false;
      this.isBag = false;
      this.isBox = true;
    }
  }

  checkdamage(value) {
    if(value == 'Yes') {
      this.isDamage = true;
    } else {
      this.isDamage = false;
    }
  }

  checkcoa(value) {
    if(value == 'Yes') {
      this.isCOA = false;
    } else {
      this.isCOA = true;
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

  receiveMaterial(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    data = data.value;
    const uploadData = new FormData();
    if (this.isUpload === 1) {
      uploadData.append('coa', this.selectedFile, this.selectedFile.name);
    } else {
      if (data['coa_received'] == 'Yes') {
        alertify.success('COA file is Compulsory');
        return;
      }
    }
    
    Object.keys(data).forEach(key => {
      let value = data[key];
      if (key == 'deviation') {
        let temp = [];
        for (let i = 0; i < this.departments.length; i++) {
          let department = this.departments[i];
          if (department.value == true) {
            temp[temp.length] = this.departments[i];
          }
        }
        value['departments'] = temp;
        uploadData.append(key, JSON.stringify(value));
      }else if(key =='dedusting'){
        uploadData.append(key, JSON.stringify(value));
      }else{
        uploadData.append(key, value);
      }
    });
    
    // uploadData.append('dedusting', JSON.stringify(this.dedusting));
    uploadData.append("material_code", this.selectedPO['material_code']);
    uploadData.append('batches',JSON.stringify(this.labelList));
    uploadData.append('equip_cleaned_by',this.equip_cleaned_by);
    uploadData.append('manufacturer',this.manufacturer);
   this.service.post('qc/indicator.php?type=receiveMaterial&id=' + this.selectedPO['id'], uploadData).subscribe(response => {
    if (response['status'] === 'success') {
        alertify.success('Material Received Successfully');
        this.isView = false;
        this.getPendingInwords();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }

  // checkqtn(value){
  //   this.result = value - this.selectedPO['qty'];
  //   this.qty_status = this.result;
  // }

  number(value){
    if (isNaN(value)){
      alertify.error('Number Only');
      return false;
    }
  }
 
  
}



