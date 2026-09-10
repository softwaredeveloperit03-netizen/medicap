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
  equipment_names
    equipments;
    vendors;
    units;
    clients;
    isClients = false;
    equipment_for='';
    equipmentList = [];
    selectedEquipment = [];
    equipment_name;
    constructor(private service: DataAccessService, private router: Router) { }
  
    ngOnInit() {
      this.service.observableUnit.subscribe(response =>{
        this.units = response;
      })
      this.getEngVendors();
      this.getEquipment();    
    }
  
    getEquipment() {
      this.service.get('/equipments.php?type=getEquipmentTypes').subscribe(response => {
        this.equipments = response;/* 
        this.selectedEquipment = this.equipments */
      });
    }
  
  getEngVendors(){
    this.service.get('common.php?type=getEngineeringVendors').subscribe(response =>{
      this.vendors = response;
    })
  }
    checkRequiredfor(value) {
      if (value !== 'Own') {/* 
        this.getClients(); */
        this.isClients = true;
      } else {
        this.isClients = false;
      }
    }
  /* 
    getClients() {
      this.service.get('common.php?type=getClients').subscribe(response => {
        this.clients = response;
      });
    } */
  
    add(data) {
      if (!data.valid) {
       alertify.error('All fields are required');
        return;
      }
      let temp = data.value;
     // temp['equipment_name'] = this.selectedEquipment['equipment_name'];
      this.equipmentList[this.equipmentList.length] = temp;
      data.resetForm();
      this.isClients = false;
    }
  
    del(index) {
      this.equipmentList.splice(index, 1);
    }
  
    save() {
      if (this.equipmentList.length == 0) {
       alertify.error('At least 1 equipment required in list');
        return;
      }
      this.service.post('purchase/indend/equipment.php?type=saveIndend', JSON.stringify(this.equipmentList)).subscribe(response => {
        if (response['status'] == 'success') {
         alertify.success('Purchase Requisition records saved successfully');
          this.equipmentList = [];
          this.router.navigate(['/engineering/indend/equipment']);
        } else {
         alertify.error('Failed: An error occured, please try again!');
        }
      });
    }
   
    selectEquipment(index) {
      index = index - 1;
      if (index !== -1) {
        this.selectedEquipment = this.equipments[index];
      } else {
        this.selectedEquipment = [];
      }
    } 
  
  }
  