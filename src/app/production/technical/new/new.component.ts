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
  company;
  products;
  equipments;
  units;
  selectedEquipments=[];
  selectedcode=[];

  equipment_names;
  equipment_ids;
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
    this.getComapnyUnit();
    this.getProducts();
    this.getUnits();
    this.getEquipments();
  }

 
  getComapnyUnit(){
    this.service.get('management/unit.php?type=getUnits').subscribe(response=>{
      this.company=response;
    });
  }

  getEquipments(){
    this.service.get('equipments.php?type=getEquipmentTypes').subscribe(response=>{
      this.equipments=response;
    });
  }

  getProducts(){
    this.service.get('common.php?type=getProducts').subscribe(response=>{
      this.products=response;
    });
  }

  getUnits(){
    this.service.get('common.php?type=getUnits').subscribe(response=>{
      this.units=response;
    });
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

  saveData(data){
    if(!data.valid){
      alertify.error('all Feilds are required');
      return;
    }

    this.service.post('production/technical.php?type=saveTISheet',JSON.stringify(data.value)).subscribe(
     response=>{
       if(response['status']=='success'){
         alertify.success('data save sucessfuly');
         data.resetForm();
         this.router.navigate(['/production/lmr/technical']);
       }else{
        alertify.error('some error Occured!');
       }
     }
    );
  }

}
