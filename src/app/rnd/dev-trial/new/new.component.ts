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
  procedures=[];
  observations=[];
  testings=[];
  products;
  selectedProduct;
  isShowProduct=false;
  materials;
  selectedMaterial;
  materialList=[];
  equipmentList=[];
  equipments;
  batch_size;
  units='';
  total_qty = 0;
  isNew=false;
  initial;
  selectedResult=[];
  isView=false;
  optimisation='';
  
  constructor(private service:DataAccessService,private router:Router) { 

  }

  ngOnInit() {
    this.getProducts();
    this.getMaterials();
    this.getEquipment();
    this.getPendingDevTrials();

  }
  add(data){
    this.procedures[this.procedures.length]=data.value;
    data.reset();
    
  }
  getProducts() {
    this.service.get('common.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  } 


 
  add1(data){
    let temp;
   temp = data.value;
   temp['material_name'] = this.selectedMaterial['material_name'];
   temp['material_code'] = this.selectedMaterial['material_code'];
   temp['grade'] = this.selectedMaterial['grade'];
   this.selectedMaterial['qty'] = temp['qty'];
   this.selectedMaterial['unit'] = temp['unit'];
   this.selectedMaterial['contribution'] = temp['contribution'];

   
   let total_qty = this.total_qty;
   
   let qty = (+temp['qty'] * +temp["overages"] / 100).toFixed(2);
   temp['total_qty'] = +temp['qty'] + +qty;
   if (temp['unit'] == 'Mg' || temp['unit'] == 'Gram' || temp['unit'] == 'Kg') {
     let value = 0;
     if (temp['unit'] == 'Mg') {
       value = +temp['qty'] / 1000000;
       total_qty += +value;
     } else if (temp['unit'] == 'Gram') {
       value = +temp['qty'] / 1000;
       total_qty += +value;
     } else if (temp['unit'] == 'Kg') {
       value = +temp['qty'];
       total_qty += +value;
     }
     temp['qty_kg'] = +value;
   }

   let flag = 0;
   if (temp['contribution'] =='Yes') {
     if (total_qty <= this.selectedResult['batch_size']) {
       this.total_qty = total_qty;
       this.total_qty = +(this.total_qty.toFixed(5));
     } else {
       flag = 1;
       alertify.error('Total Batch Size ' + this.selectedResult['batch_size'] + 'Kg' + ' so you can not add more material');
     }
   }

   if (flag == 0) {
     this.materialList[this.materialList.length] = temp;
    
     this.selectedMaterial = [];
     data.resetForm();
     let material_code = document.getElementById('material_code') as HTMLElement;
     material_code.focus();
   }



  

  }

  getPendingDevTrials(){
    this.service.get('rnd/devtrial.php?type=getPendingDevTrials').subscribe(response=>{
      this.initial=response;
    })
  }


  getMaterials(){
    this.service.get('common.php?type=getRawMaterials').subscribe(response=>{
      this.materials=response;
    });
  }

  getEquipment(){
    this.service.get('common.php?type=getEquipments').subscribe(response=>{
      this.equipments=response
    })
  }

  selectMaterial(index) {
    index = index - 1;
    this.selectedMaterial = this.materials[index];
  }
  add2(data){
    this.equipmentList[this.equipmentList.length]=data.value;
    data.reset();
  }
  add3(data){
    this.testings[this.testings.length]=data.value;
    data.reset();
  }
  add4(data){
    this.observations[this.observations.length]=data.value;
    data.reset();
  }



  save() {
    
    let diff = this.total_qty * 0.1 / 100;
    let min = this.total_qty - diff;
    let max = this.total_qty + diff;
    if (+this.selectedResult['batch_size'] <= +min || +max <= +this.selectedResult['batch_size']) {
      alertify.error('Batch Size and Materials total Qty not match');
      return;
    }
    let temp={}
    temp['product_code'] = this.selectedResult['product_code'];
    temp['dev_no'] = this.selectedResult['dev_no'];
    temp['optimisation'] = this.optimisation;
    temp['materials'] = this.materialList;
     temp['equipments'] = this.equipmentList;
     temp['procedures'] = this.procedures;
     temp['observations'] = this.observations;
     temp['testings'] = this.testings;
    this.service.post('rnd/devtrial.php?type=saveDevTrial', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] === 'success') {
        alertify.success('Data Saved Successfully');
        this.router.navigate(['/dev-trial']);
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error has occurred, http status:' + error.status);
      }
    });
  }
  
  view(index){
    this.selectedResult = this.initial[index];
    this.isView = true; 
  }
}
