import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {
 
  Material_type = 'Raw Material';
  constructor(private service: DataAccessService) {}
  results;
  isView = false;
  selectedResult;
  searchText;
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

  viewAll(material_code: any){
    this.ar_data =[];
    // this.selectedResult = this.results[index];

  const index = this.results.findIndex((data) => data.material_code === material_code);
    if (index !== -1) {
        this.selectedResult = this.results[index];
        // Do something with this.selectedResult
    } else {
        console.error('Index not found');
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
