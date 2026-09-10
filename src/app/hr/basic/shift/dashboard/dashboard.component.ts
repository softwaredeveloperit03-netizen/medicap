import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getShiftTiming();
  }

  getShiftTiming() {
    this.service.get('hr/shift.php?type=getShiftTiming').subscribe(response => {
      this.results = response;
    });
  }

}
