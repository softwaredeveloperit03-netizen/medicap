import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-market-complaint-dashboard',
  templateUrl: './market-complaint-dashboard.component.html',
  styleUrls: ['./market-complaint-dashboard.component.css']
})
export class MarketComplaintDashboardComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit() {
  }
}
