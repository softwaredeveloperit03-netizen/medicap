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
  departments;
  persons;

  equipment_types;
  equipment_names;
  equipments;
  selectedEquipment=[];
  checkpoints=[];
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getDepartment();
    this.getQcperson();
  }
  getQcperson(){
    this.service.get('engineering/inspection.php?type=getEngineeringPersons').subscribe(response=>{
      this.persons=response;
    });
    
  }
  getDepartment(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
    this.departments=response;
    });
  }

  getEquipments(value) {
    this.service.get('engineering/inspection.php?type=getEquipments&department_name=' + value).subscribe(response=>{
      this.equipment_types = response;
    });
  }

  getEquipmentNames(index) {
    index = index - 1;
    if(index !== -1){
      this.equipment_names = this.equipment_types[index].equipments;
    } else {
      this.equipment_names = [];
    }
  }

  getEquipmentIds(index) {
    index = index - 1;
    if(index !== -1){
      this.equipments = this.equipment_names[index].equipments;
    } else {
      this.equipments = [];
    }
  }
  add(data){
    this.checkpoints[this.checkpoints.length]=data.value;
    data.reset();
  }
  del(index){
    this.checkpoints.splice(index,1)
  }
  save(data){
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    temp['checkpoints']=this.checkpoints;
    this.service.post('engineering/inspection.php?type=saveInspection',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Data Saved Successfully!');
        this.router.navigate(['/inspection']);
      }else{
        alertify.error('Failed an error occurd,Please try again!');
      }
    });
  }

}
