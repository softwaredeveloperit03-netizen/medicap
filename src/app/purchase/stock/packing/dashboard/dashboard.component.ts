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
  searchText:any
  ngOnInit(): void {
    this.getmaindata();
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

  viewAll(material_code: any) {
    this.ar_data = [];
    const index = this.results.findIndex((item) => item.material_code == material_code);
    if (index != -1) {
      this.selectedResult = this.results[index];
    }
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
