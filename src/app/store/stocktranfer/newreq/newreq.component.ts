import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-newreq',
  templateUrl: './newreq.component.html',
  styleUrls: ['./newreq.component.css']
})
export class NewreqComponent implements OnInit {

  
  materials: any;
 
  isNew = false;
  selectedMaterial =[];
  
 

  constructor(private service: DataAccessService) {
    //this.loggedInDept = localStorage.getItem('department');

  }

    ngOnInit() {
      this.getAllMaterial();
      this.getApprovedLabors();
    }
 

  getAllMaterial() {
    this.service.get('store/stocktransfer.php?type=forDespensing').subscribe((response: any) => {
      this.materials = response;
     });
  }

  getmatData(material_code) {
    this.service.get('store/stocktransfer.php?type=getarDataByMaterial&material_code='+material_code).subscribe((response: any) => {
      this.matData = response;
     });
  }
  labors;

  getApprovedLabors() {
    this.service.get('hr/employee.php?type=getOperators').subscribe(response => {
      this.labors = response;
    });
  } 


  matData;
  desQty =0;

  selectedPlant;
   isselectedmat(i){
    this.desQty =0;
    this.selectedMaterial = this.materials[i];
    this.desQty = this.selectedMaterial['qty'];
    
    this.getmatData(this.selectedMaterial['material_code']);
    this.isNew = true;
   }


   addSelected(i: number) {
    let desQty = Number(this.desQty);

    if(desQty == 0){
       alert('Despensing Qty Completed');
      return;
    }

    
    let balance_qty = Number(this.matData[i].balance_qty);
    let disensedpQty = Number(this.matData[i].disensedpQty || 0);
  
    if (this.matData[i].selected) {
      if (desQty >= balance_qty) {
        this.matData[i].disensedpQty = balance_qty;
        this.desQty = desQty - balance_qty;
      } else {
        this.matData[i].disensedpQty = desQty;
        this.desQty = 0;
      }
    } else {
      this.desQty = desQty + disensedpQty;
      this.matData[i].disensedpQty = 0;
    }



  }
  
 
  
  item;
  status;
  isDIGI = false;
  emp_id;

  ardata=[];

  openDigiSign(data){

    if (!data.valid) {
      alert('All Field Required!!!!');
      return;
    }

    let selectedIndices = [];
    this.matData.forEach((material, index) => {
      if (material.selected && material.disensedpQty > 0) {
        selectedIndices.push(material);
      }
    });
 
    this.ardata = selectedIndices;
 
    console.log('Selected indices:', this.ardata);
  
    if(selectedIndices.length==0){
      alertify.error('Please Select AR. To Despensing!!!!!');
      return;
    }
 
     this.isDIGI = true;
    
  }


  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.loginPassward ='';
        this.saverequest()
      }
      else{
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
   
  operator;
  saverequest() {
 
    let temp = {};
    temp['arData'] = this.ardata;
    temp['operator'] = this.operator;

    this.service.post('store/stocktransfer.php?type=SaveDespensing&id='+this.selectedMaterial['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.getAllMaterial();
        this.isNew = false;
        alertify.success('test successfully send for approval');
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  
  }

 
  
}
