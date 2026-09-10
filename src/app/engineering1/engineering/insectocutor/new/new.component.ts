import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  equipments;
  sections;
  selectedEquipment = [];
  departments;
  company;
  products;
  units;
  section = '';
  area = '';
  isNewLocation = false;
  selectedEquipments=[];
  selectedcode=[];

  equipment_names;
  equipment_ids;

  start_time = '';
  end_time='';
  start_date: Date;
  end_date:Date;
  constructor(private service:DataAccessService,private router:Router) { }


  ngOnInit() {
    this.getDepartmentSections();
    this.getEquipments();
    this.getDepartment();
  }
  
  getDepartment(){
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
  getDepartmentSections(){
    this.service.get('common.php?type=getDepartmentSections').subscribe(response => {
      this.sections = response;
    });
  }

  getEquipments(){
    this.service.get('equipments.php?type=getEquipmentTypes').subscribe(response => {
      this.equipments = response;
    });
  }
  getCurrentTime() {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    let time = new Date().toLocaleTimeString();
    this.start_time = h + ':' + m;
    this.start_date = new Date();
  }
getEndTime() {
    var d = new Date(),
    h = (d.getHours()<10?'0':'') + d.getHours(),
    m = (d.getMinutes()<10?'0':'') + d.getMinutes();
    let time = new Date().toLocaleTimeString();
    this.end_time = h + ':' + m;
     this.end_date = new Date();
  }

  getEquipment(index){
    index=index-1;
    if(index != -1){
      let selectedEquipments=this.equipments[index];
      this.equipment_names = selectedEquipments['equipments'];
    }
  }

  getEquipmentId(index) {
    index=index-1;
    if(index != -1){
      let selectedEquipments = this.equipment_names[index];
      this.equipment_ids = selectedEquipments['equipments'];
    }
  }

  getCode(index){
    index=index-1;
    if(index != -1){
      this.selectedcode=this.selectedEquipments[index];
      console.log(this.selectedEquipments);
    }
  }
  newSection(value) {
    if (value == 'ADD NEW') {
      this.section = this.area;
      this.isNewLocation = true;
    } else {
      this.isNewLocation = false;
    }
  }
  saveArea(data) {
    if (!data.valid) {
      alertify.error('All fields are required!');
      return;
    }
    this.section = this.area;
    this.service.get('master/section.php?type=saveSection&section=' + this.area).subscribe(response => {
      if (response['status'] == 'success') {
        this.area = '';
        this.isNewLocation = false;
        this.getDepartmentSections();
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
  }

  saveInsecure(data){
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    this.service.post('engineering/maintenance.php?type=saveMaintenance',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Saved Successfully!');
        this.router.navigate(['/engineering/maintenance']);
      }else{
        alertify.error('Failed an error occurd,Please try again!');
      }
    });
  }

}
