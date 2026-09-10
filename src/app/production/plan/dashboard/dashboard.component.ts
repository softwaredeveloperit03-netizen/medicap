import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers: [DatePipe]
})
export class DashboardComponent implements OnInit {

  isNew = false;
  results;
  dosages;
  products;
  dosage_form = '';
  product_code = '';

  from_date = '';
  to_date = '';
  max_date = '';

  selectedResult = [];
  constructor(private service: DataAccessService, private datePipe: DatePipe) { 
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getBatchPlanLog();
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

  getBatchPlanLog(){
    this.service.get('production/plan.php?type=getBatchPlanLog&dosage_form='+ this.dosage_form +'&product_code='+ this.product_code +'&from_date='+ this.from_date +'&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }
  /* isUser = false;
  isChecker = false;
  isApprover = false;
  constructor() {
    this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }

  ngOnInit() {
  } */

  download() {
    this.service.open('production/plan.php?type=downloadBatchPlanLog&dosage_form='+ this.dosage_form +'&product_code='+ this.product_code +'&from_date='+ this.from_date +'&to_date=' + this.to_date);
  }

}
