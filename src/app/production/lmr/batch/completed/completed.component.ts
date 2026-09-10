import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-completed',
  templateUrl: './completed.component.html',
  styleUrls: ['./completed.component.css'],
  providers: [DatePipe]
})
export class CompletedComponent implements OnInit {

  
  results;
  selectedResult = [];
  selectedBMR = [];
  isNew = false;
  isView = false;
  isShow = false;
  stages = ''

  units
  company_unit='';
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
    this.getUnits();
  }

  getUnits() {
    this.service.get('common.php?type=getCompanyUnits').subscribe(response => {
      this.units = response;
    });
  } 

  getDosages() {
    this.service.get('common.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getProductsByDosage() {
    this.service.get('common.php?type=getProductsByDosage&product_type=' + this.dosage_form).subscribe(response => {
      this.products = response;
    });
  }

  getCompletedBatches() {
    this.service.get('production/lot/completed.php?type=getCompletedBatches&company_unit='+this.company_unit+'&product_type=' + this.dosage_form + '&product_code=' + this.product_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }
  download()
  {
    this.service.open('production/lot/completed.php?type=downloadCompletedBatches&company_unit='+this.company_unit+'&product_type=' + this.dosage_form + '&product_code=' + this.product_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date)
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

  doenloadBmr(){
    // window.open(this.service.url + '/upload/bmr/' + path);

  }

}
