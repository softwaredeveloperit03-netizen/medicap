import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-sprequest',
  templateUrl: './sprequest.component.html',
  styleUrls: ['./sprequest.component.css']
})
export class SprequestComponent implements OnInit {

  isView = false;
  results;
  remarks = '';
  stocks;
  material_type = '';
  selectedResult = [];
  plant_id:any;
  constructor(private service: DataAccessService) { 
   
  }


  ngOnInit() {
    this.getDispensingActivities();
    this.getMaterialOutDetails();
    this.plant_id = this.service.getPlantConfigFields("plant_id");
  }

  getDispensingActivities() {
    this.service.get('store/dispensing.php?type=getDispensingRequests&material_type=Raw Material').subscribe(response => {
      this.results = response;
    });
  }


  getMaterialOutDetails() {
    this.service.get('store/bincard.php?type=getMaterials&material_type=' + this.material_type).subscribe(response => {
      this.stocks = response;
    });
  }


  view(index) {
    this.selectedResult = this.results[index];
    console.log(this.selectedResult);
    this.isView = true;
  }
  updatePhysicalStock(status, idx) {
    this.selectedResult['materials'][idx]['physical_stock'] = status;
  }
  save(remark) {
    if (remark != 'Accept') {
      alertify.error('Please enter remarks');
      return;
    }
    let materials = [];
    for (let x = 0; x < this.selectedResult['materials'].length; x++) {
      let mat = {
        "id": this.selectedResult['materials'][x]['id'],
        "physical_stock_status": this.selectedResult['materials'][x]['physical_stock']
      }
      if(mat['physical_stock_status']=='' || mat['physical_stock_status']==null || mat['physical_stock_status']==undefined){
        alertify.error('Please select physical stock status');
        return;
      }
      materials.push(mat);
    }
    let obj = {
      "id": this.selectedResult['id'],
      "status": remark,
      "remarks": this.remarks,
      "materials": materials
    }
    this.service.post('store/dispensing.php?type=saveRequestRM&id=' + this.selectedResult['id'], JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('data save successfuly');
        this.getDispensingActivities();
        this.isView = false;
      } else {
        alertify.error('some error occured!');
      }
    });
  }
}
