import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-lmr',
  templateUrl: './lmr.component.html',
  styleUrls: ['./lmr.component.css'],
  providers: [DatePipe]
})
export class LmrComponent implements OnInit {

  results;
  selectedResult = [];
  selectedBMR = [];
  isNew = false;
  isView = false;
  isShow = false;
  stages = ''


  dosages;
  products;

  dosage_form = '';
  product_code = '';
  from_date = '';
  to_date = '';
  max_date = '';
  selectedStage;
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getCompletedBatches();
    this.getDosages();
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

  getCompletedBatches() {
    this.service.get('production/lot/completed.php?type=getCompletedBatches&product_type=' + this.dosage_form + '&product_code=' + this.product_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  viewBatch(index) {
    this.selectedResult = this.results[index];
    this.isNew = true;
  }
  view(index) {
    this.selectedBMR = this.results[index];
    this.isView = true;
  }

  show(index){
    this.stages = this.selectedResult['stages'];
    this.selectedStage = this.stages[index];
    this.isShow = true;
  }

  download(){
    this.service.open('production/lot/completed.php?type=downloadCompletedBatches&product_type=' + this.dosage_form + '&product_code=' + this.product_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }

  downloadLmr(path){
    window.open(this.service.url + 'upload/lmr/' + path);
  }

}
