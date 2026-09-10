import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-reqlog',
  templateUrl: './reqlog.component.html',
  styleUrls: ['./reqlog.component.css']
})
export class ReqlogComponent implements OnInit {

 
  materials: any;
 
  units: any;
  
  selectedMaterial =[];
  allPlants:any;
 
  reqPlantId = '';


  constructor(private service: DataAccessService) {
    //this.loggedInDept = localStorage.getItem('department');

  }

    ngOnInit() {
      this.getreqLog();
      
     this.allPlants =  JSON.parse(localStorage.getItem('all_plants'));
   }
 
   reqData;

  getAllMaterial() {
    this.service.get('store/stocktransfer.php?type=getStockBook&plantID='+this.reqPlantId).subscribe((response: any) => {
      this.materials = response;
     });
  }
  getreqLog() {
    this.service.get('store/stocktransfer.php?type=getreqLog').subscribe((response: any) => {
      this.reqData = response;
     });
  }


  selectedPlant;
  unit ='';
  isselectedmat(i){
    this.selectedMaterial = this.materials[i-1];
    this.unit = this.selectedMaterial['uom'];
  }

  onChangeUnitName(i){
    this.selectedPlant = this.allPlants[i];
    this.getAllMaterial();
  }
 
 
 
  isNew = false;

  saverequest(data) {
 
    if(!data.valid){
      alertify.error('All Field Required!!!!!!');
      return;
    }

    let temp = data.value;
    temp['material_name'] = this.selectedMaterial['material_name'];
    temp['sub_type'] = this.selectedMaterial['sub_type'];
    temp['vendor_no'] = this.selectedMaterial['vendor_no'];
     temp['grade'] = this.selectedMaterial['grade'];
    temp['material_subtype'] = this.selectedMaterial['material_subtype'];
    temp['material_type'] = this.selectedMaterial['material_type'];
    temp['reqPlantName'] = this.selectedPlant['display_name'];
 
    this.service.post('store/stocktransfer.php?type=saveStockTranferRequest', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        data.resetForm();
        alertify.success('Request Send Successfully');
        this.isNew = false;
        this.getreqLog();
      } else {
        alertify.error('An error occured, please try again');
      }
    });
  
  }

 
  
}
