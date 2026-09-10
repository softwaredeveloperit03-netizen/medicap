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

  from_date = '';
  to_date = '';
  max_date = '';
  units; 
  company_unit='';
  product_type = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getCompletedBatches();
    this.getUnits();
  }

  getUnits() {
    this.service.get('common.php?type=getCompanyUnits').subscribe(response => {
      this.units = response;
    });
  } 

  getCompletedBatches() {
    this.service.get('production/bmr/completed.php?type=getCompletedBatches&company_unit='+this.company_unit+'&product_type=' + this.product_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  downloadfile(path) {
    window.open(this.service.url + '/upload/bmr/' + path);
  }

  download() {
    this.service.open('production/bmr/completed.php?type=downloadCompletedBatches&company_unit='+this.company_unit+'&product_type=' + this.product_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }

}
