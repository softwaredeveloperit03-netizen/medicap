import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  
  Material_type = 'Packing Material';
  constructor(private service: DataAccessService) {}
  results;
  isView = false;
  selectedResult;
  plant_type = '';
  ngOnInit(): void {
    this.getmaindata();
    this.plant_type = this.service.getPlantConfigFields('plant_type');

  }
  ar_data;
  getmaindata(){
    this.service.get('store/opening.php?type=getStockBook&Material_type='+this.Material_type).subscribe(response => {
      this.results = response;
      
     }); 
  }
  getarDataByMaterial(material_code){
    this.service.get('store/opening.php?type=getarDataByMaterial&material_code='+material_code).subscribe(response => {
      this.ar_data = response;
     
     }); 
  }

  viewAll(index){
    this.ar_data =[];
    this.selectedResult = this.results[index];
    this.getarDataByMaterial(this.selectedResult['material_code']);
    this.isView = true;
  }


  approvedStock_value = 0;
  rejectedStock_value = 0;

  
  download()
  {
    this.service.open('store/opening.php?type=getCommonlogPDF&Material_type='+this.Material_type+'&arNo='+this.selectedResult['ar_no']+'&remQty='+this.selectedResult['Issue'])
  }

}
