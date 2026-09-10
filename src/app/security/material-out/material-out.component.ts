import { DataAccessService } from 'src/app/data-access.service';
import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-material-out',
  templateUrl: './material-out.component.html',
  styleUrls: ['./material-out.component.css'],
  providers:[DatePipe]
})
export class MaterialOutComponent implements OnInit {
  isNewOutword = false;
  materials;
  vehicle_no='';
  isPickup = false;
  isVehicle = false;
  from_date='';
  to_date='';
  today;
  department ='';
  departments;
  item=[];
  material_name='';
  selectedResult: any = {};
  viewMaterials = [];
  constructor(private service: DataAccessService,private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
   }

  ngOnInit() {
    this.getMaterialOutDetails();
    this.getDepartemnts();
  }

    clear(){
      this.material_name='';
      this.vehicle_no='';
    }
  view(i){
    this.isNewOutword = true;
    this.selectedResult = this.materials[i] || {};
    this.viewMaterials = [];
    try {
      const mats = this.selectedResult.materials;
      if (Array.isArray(mats)) {
        this.viewMaterials = mats;
      } else if (typeof mats === 'string' && mats) {
        const parsed = JSON.parse(mats);
        this.viewMaterials = Array.isArray(parsed) ? parsed : [];
      }
    } catch (e) {
      this.viewMaterials = [];
    }
  }

  getDepartemnts(){
    this.service.get('common.php?type=getDepartments').subscribe(response=>{
      this.departments = response;
    });
  }

  getMaterialOutDetails() {
    this.service.get('security/materialoutward.php?type=getOutwordLog&from_date='+this.from_date+'&to_date='+this.to_date)
    .subscribe(response => {
      this.materials = response;
    //  this.filterItem();
    });
  }

  saveMaterialOut(materialForm) {
    if (!materialForm.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('security.php?type=saveMaterialOutForm', JSON.stringify(materialForm.value))
    .subscribe(response => {
      if (response['status'] === 'success') {
        this.isNewOutword = false;
        materialForm.resetForm();
        this.getMaterialOutDetails();
        alert("save successfully");
      } else {
        alertify.error('Please Try Again');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alertify.error('An error has occurred.');
      } else {
        alertify.error('An error occured');
      }
    });
  }

  number(value){
    if (isNaN(value)){
      alertify.error('Number Only');
      return false;
    }
  }

  getTransportVal(val) {
    if (val === 'By Transport') {
      this.isPickup = false;
      this.isVehicle = true;
    } else if (val === 'By Courier') {
      this.isVehicle = false;
      this.isPickup = true;
    }
  }


  verify(id){
    this.service.get('security/materialoutward.php?type=UpdateOutwordLog&id='+id).subscribe(Response=>{
      if(Response['status']=='success'){
        this.getMaterialOutDetails();
        alertify.success("Update Successfully");
      }else{
        alertify.error("Failed:an error occured!")
      }
    });
  }
  
// filterItem() {
//   this.item=[];
//   for (let i = 0; i < this.materials.length; i++) {
//     let material = this.materials[i];
//     if (material['material_name'].toUpperCase().includes(this.material_name.toUpperCase())&&material['vehicle_no'].toUpperCase().includes(this.vehicle_no.toUpperCase())) {
//       this.item[this.item.length] = material;
//     }
//   }
// }


download() {
  this.service.open('security/materialoutward.php?type=downloadMaterialLog');
}
}
