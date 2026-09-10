import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;
isView: any;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingPlans();
  }

  getPendingPlans() {
    this.service.get('packing/plan.php?type=getPlans').subscribe(response=>{
      this.results = response;
    });
  }

}
