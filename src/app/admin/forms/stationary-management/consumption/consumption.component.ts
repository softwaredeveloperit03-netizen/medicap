import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-consumption',
  templateUrl: './consumption.component.html'
})
export class ConsumptionComponent implements OnInit {

  isView = false;
  isNew = false;
  selectedEntry;
  list;

  constructor(private service: DataAccessService, private router: Router) {
  }

  ngOnInit() {
    this.getStationarylist();
  }

  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  open(url) {
    window.open(url, '_blank');
  }

  getStationarylist() {
    this.service.get('admin.php?type=getStationarylist').subscribe(response => {
      this.list = response;
    });
  }

  close() {
    this.router.navigate(['/stationary-dashboard']);
  }

}

