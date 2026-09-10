import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-yield',
  templateUrl: './yield.component.html',
  styleUrls: ['./yield.component.css'],
  providers: [DatePipe]
})
export class YieldComponent implements OnInit {

  dosages;
  products;
  results;

  dosage_form = '';
  product_code = '';
  from_date = '';
  to_date = '';
  max_date = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getYield();
    this.getDosages();
  }

  getYield() {
    this.service.get('production/product.php?type=getCompletedBMR&dosage_form=' + this.dosage_form + '&product_code=' + this.product_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
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

  download() {
    this.service.open('production/product.php?type=downloadYieldStatement&dosage_form=' + this.dosage_form + '&product_code=' + this.product_code + '&from_date=' + this.from_date + '&to_date=' + this.to_date);
  }

}
