import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from '@angular/router'
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  products;
  Units;
  equipments;
  plants;
  tanks;
  equipment_id='';
  sample_from ='Equipment';
  constructor(private service: DataAccessService, private router : Router) { 
   
  }

  ngOnInit() {
    this.getProducts();
    this.getUnits();
    this.getEquipments();
    this.getPlants();
    this.getTanks();
    }
    getProducts()
    {
      this.service.get('common.php?type=getProducts').subscribe(response =>{
        this.products =response
      });
    }
    getUnits()
    {
      this.service.get('common.php?type=getUnits').subscribe(response =>{
        this.Units =response
      });
    }
    getEquipments()
    {
      this.service.get('common.php?type=getEquipments').subscribe(response =>{
        this.equipments =response
      });
    }
    checkType(value) {
      if (value == 'Equipment') {
        this.sample_from = 'Equipment';
      
      } else if (value == 'Tank') {
        this.sample_from = 'Tank';
 
      }
    }
    getTanks(){
      this.service.get('engineering/watertank.php?type=getTanks').subscribe(response=>{
        this.tanks=response;
      })
    }
    getPlants()
    {
      this.service.get('common.php?type=getCompanyUnits').subscribe(response =>{
        this.plants =response
      });
    }
    saveData(data){
      let temp = data.value;
      temp['sample_id'] = 'EFFLUENT Water Sample';
      console.log(temp)
      this.service.post('ipqc/etp.php?type=saveRequest',JSON.stringify(temp)).subscribe(response =>{
        if (response['status'] == 'success') {
          data.resetForm();
          this.router.navigate(['/ehs/inprocess'])
          alertify.success(this.service.t('common.savedSuccess'));
        } else {
          alertify.error(this.service.t('common.errorOccurred'));
        }
      });
    }
}
