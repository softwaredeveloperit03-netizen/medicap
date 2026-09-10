import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-bmr',
  templateUrl: './bmr.component.html',
  styleUrls: ['./bmr.component.css'],
  providers: [DatePipe]
})
export class BmrComponent implements OnInit {

  results;

  from_date = '';
  to_date = '';
  max_date = '';
  lots=[];
  product_type = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getCompletedBatches();
    this.lots=this.results['lots'].length;
    console.log(this.lots);
  }


  getCompletedBatches() {
    this.service.get('production/bmr/completed.php?type=getCompletedBatches&product_type=' + this.product_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }

  downloadfile(path) {
    window.open(this.service.url + '/upload/bmr/' + path);
  }

  download() {
    this.service.open('production/bmr/completed.php?type=downloadCompletedBatches&product_type=' + this.product_type + '&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }

}
