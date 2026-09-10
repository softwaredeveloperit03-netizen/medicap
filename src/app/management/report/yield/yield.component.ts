import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-yield',
  templateUrl: './yield.component.html',
  styleUrls: ['./yield.component.css']
})
export class YieldComponent implements OnInit {
  results;
  isNew=false;
  isShow=false;
  dosage_form='';
  product_code='';
  dosages;
  products

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDosages();
    this.getCompletedBMR();
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
    this.service.get('production/manufacturing.php?type=getCompletedBMR&dosage_form=' + this.dosage_form + '&product_code=' + this.product_code).subscribe(response => {
      this.results = response;
    });
  }

  

  

}
