import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-raw',
  templateUrl: './raw.component.html',
  styleUrls: ['./raw.component.css']
})
export class RawComponent implements OnInit {
//   isView = false;
//   samplings;

//   selectedReport = [];

//   material_name = "";
//   constructor(private service: DataAccessService) { }

//   ngOnInit() {
//     this.getARReport();
//   }

//   getARReport() {
//     this.service.get('qc/testing/raw.php?type=getTestingReport').subscribe(response => {
//       this.samplings = response;
//     });
//   }

//   viewReport(index) {
//     this.selectedReport = this.samplings[index];
//     this.isView = true;
//   }
//   downloadReport(){
//     this.service.open('pdf1/testing.php?type=ARReportlog');
//   }
//   downloadPDF(ar_no, type){
//     if(type == 'manual'){
//       this.service.open('pdf1/testing.php?type=ARReport&ar_no='+ar_no);
//     }else{
//       this.service.open('pdf1/testing.php?type=ARReportdigital&ar_no='+ar_no);
//     }
//   }

// }
// ---------------------------------------------------------------------------------------

 stocks;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
     this.getAllStock();
  }

  material_type = 'Raw Material';
  
  getAllStock() {
    this.service.get('store/packing.php?type=getStock').subscribe(response => {
      this.stocks = response;
      console.log('this.stocks',this.stocks);
    });
  }
  


  download(){
    this.service.open('store/packing.php?type=downloadStock');
  }

}
