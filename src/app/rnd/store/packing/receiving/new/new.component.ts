import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  manufacturer='';
  isView = false;
  results;
  equipments;
  selectedPO = [];
  labors;
  selectedFile: File;
  isUpload = 0;
  isDrum = false;
  isBag = false;
  isBox = false;
  isCOA = false;
  isDamage = false;
  isDedYes=true;
  isDedNo=false;
  from_time;
  to_time;
  area_cleaned_from;
  area_cleaned_to;
  equip_cleaned_from;
  equip_cleaned_to;
  equip_cleaned_by='';
  area_cleaned_by='';
  qtyReceived=0;
  containerTotal=0;
  outerDamage=0;
  innerDamage=0;
  result=0;
  ismanual=true;
  isvaccume=false;
  labelList=[];
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

  ngOnInit(): void {
    this.getPendingReceivings();
    this.getApprovedLabors();
    this.getSelectedEquipments();
  }

  getPendingReceivings(){
    this.service.get('store/packing.php?type=getPendingReceivings').subscribe(response => {
      this.results = response;
    });
  }

  viewResult(index) {
    this.selectedPO = this.results[index];
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

  getApprovedLabors() {
    this.service.get('common.php?type=getLabours')
    .subscribe(response => {
      this.labors = response;
    });
  }

  getSelectedEquipments() {
    this.service.get('store/equipment.php?type=getVaccumCleaners')
    .subscribe(response => {
      this.equipments = response;
    });
  }

  checkcoa(value) {
    if(value == 'Yes') {
      this.isCOA = false;
    } else {
      this.isCOA = true;
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

  deleteLabel(index){  
    this.qtyReceived = this.qtyReceived-this.labelList[index].qty_received;
    this.labelList.splice(index,1);
    console.log(this.qtyReceived); 
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

  getdedusting(show){
    if(show=='Yes'){
      this.isDedYes=true;
      this.isDedNo=false;
    }else if(show=='No'){
      this.isDedYes=false;
      this.isDedNo=true;
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
        alertify.error('COA file is Compulsory');
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
      } else if(key =='dedusting'){
        uploadData.append(key, JSON.stringify(value));
      }else {
        uploadData.append(key, value);
      }
    });
    uploadData.append("material_code", this.selectedPO['material_code']);
    uploadData.append("manufacturer", this.manufacturer);
    uploadData.append('batches', JSON.stringify(this.labelList));
    this.service.post('store/packing.php?type=saveReceivingDetails&id=' + this.selectedPO['id'], uploadData).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Record Inserted Successfully');
        this.isView = false;
        this.getPendingReceivings();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }


}
