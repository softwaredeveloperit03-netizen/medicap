import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-production-report',
  templateUrl: './production-report.component.html',
  styleUrls: ['./production-report.component.css'],
  providers: [DatePipe]
})
export class ProductionReportComponent implements OnInit {
  selectedResult=[];
  isView=false;
  results;
  dosages;
  products;
  dosage_form= '';
  product_code='';
  from_date='';
  to_date='';

  constructor(private service: DataAccessService, private datePipe: DatePipe) { }

  ngOnInit(): void {
    this.getDosages();
    this.getCompletedBMR();
  }

  viewBatch(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }


  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getProductsByType(type) {
    this.service.get('common.php?type=getProductsByDosage&product_type=' + type).subscribe(response => {
      this.products = response;
    });
  }

  getCompletedBMR() {
    this.service.get('production/manufacturing.php?type=getCompletedBMR&product_type=' + this.dosage_form + '&product_code=' + this.product_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }
  download() {
    this.service.open('production/manufacturing.php?type=downloadProductionReport')
  }
  downloadpdf(){
    this.service.open('production/manufacturing.php?type=downloadProductionDetailsReport&id='+ this.selectedResult['id'] )
  }

}
