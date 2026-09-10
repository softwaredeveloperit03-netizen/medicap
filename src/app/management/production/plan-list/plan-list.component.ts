import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-plan-list',
  templateUrl: './plan-list.component.html',
  styleUrls: ['./plan-list.component.css'],
})
export class PlanListComponent implements OnInit {
  isView = false;
  results;
  selectedResult = [];
  raw_materials = [];
  packing_materials = [];
  plant_type = '';
  from_date = '';
  to_date = '';
  today = '';
  product_name = '';
  showTailingBatches;
  constructor(private service: DataAccessService) {
    
  }

  ngOnInit() {
    this.getPlans();
  }

  getPlans() {
    this.service
      .get(
        'production/plan.php?type=getPlans&from_date=' +
          this.from_date +
          '&to_date=' +
          this.to_date
      )
      .subscribe((response) => {
        this.results = response;
      });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.packing_materials =
      this.selectedResult['packing_configuration'][0]['packing_materials'];
    this.raw_materials = this.selectedResult['raw_materials'];
    this.isView = true;
    console.log(this.packing_materials);
  }

  download() {
    this.service.open(
      'production/bmr/plan.php?type=downloadPlans&from_date=' +
        this.from_date +
        '&to_date=' +
        this.to_date
    );
  }
}
