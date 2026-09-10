import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {

  isView = false;
  results;
  closeLink = '/production';

  selectedResult = [];

  dosages;
  products;
  dosage_form = '';
  product_code = '';
  from_date = '';
  to_date = '';
  max_date = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe, private router: Router) {
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    const url = this.router.url || '';
    this.closeLink = url.includes('prod-f-ebmr') ? '/fproduction' : '/production';
    this.getTechnicalInfoLog();
    this.getDosages();
  }

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getProductsByDosage() {
    this.service.get('common.php?type=getProductsByDosage&dosage_form=' + this.dosage_form).subscribe(response => {
      this.products = response;
    });
  }

  getTechnicalInfoLog(){
    this.service.get('production/technical.php?type=getTechnicalInfoLog&dosage_form='+ this.dosage_form +'&product_code='+ this.product_code +'&from_date='+ this.from_date +'&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  downloadlog(){
    this.service.open('production/technical.php?type=TechnicalInfoLogPDF&dosage_form='+ this.dosage_form +'&product_code='+ this.product_code +'&from_date='+ this.from_date +'&to_date=' + this.to_date);
  }

  downloadPDF(type){
    if(type == 'manual'){
      this.service.open('production/technical.php?type=TechnicalInfoPDF&id='+this.selectedResult['id']);
    }else{
      this.service.open('production/technical.php?type=TechnicalInfodigitalPDF&id='+this.selectedResult['id']);
    }
  }

}
