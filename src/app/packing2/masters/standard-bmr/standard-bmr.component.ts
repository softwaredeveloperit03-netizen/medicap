import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-standard-bmr',
  templateUrl: './standard-bmr.component.html',
  styleUrls: ['./standard-bmr.component.css']
})
export class StandardBmrComponent implements OnInit {

  isNew = false;
  isView = false;
  results;
  selectedBatch = [];

  products;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getProducts();
    this.getBatchPlanningLog();
  }

  getProducts() {
    this.service.get('production.php?type=getProducts').subscribe(response => {
      this.products = response;
    });
  }

  getBatchPlanningLog() {
    this.service.get('production.php?type=getBatchPlanningLog').subscribe(response => {
      this.results = response;
    });
  }

  viewBatch(index) {
    this.selectedBatch = this.results[index];
    this.isView = true;
  }

}
