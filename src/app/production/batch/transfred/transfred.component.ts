import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-transfred',
  templateUrl: './transfred.component.html',
  styleUrls: ['./transfred.component.css'],
  providers:[DatePipe]
})
export class TransfredComponent implements OnInit {

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

  getProductsByDosage() {
    this.service.get('common.php?type=getProductsByDosage&dosage_form=' + this.dosage_form).subscribe(response => {
      this.products = response;
    });
  }

  getCompletedBatches() {
    this.service.get('production/transfer.php?type=getPendingBMRs').subscribe(response =>{
      this.results=response;
    });
    // this.service.get('production/transfer.php?type=getPendingBMRs&dosage_form=' + this.dosage_form + '&product_code=' + this.product_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
    //   this.results = response;
    // });
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

}
