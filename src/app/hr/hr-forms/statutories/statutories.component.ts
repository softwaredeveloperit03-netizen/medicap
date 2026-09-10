import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-statutories',
  templateUrl: './statutories.component.html',
  styleUrls: ['./statutories.component.css']
})
export class StatutoriesComponent implements OnInit {

  isView = false;
  results;

  selectedDev = [];
  constructor(private service: DataAccessService) {
  }

  ngOnInit() {
    this.getDeviations();
  }

  getDeviations() {
    this.service.get('deviation.php?type=getCapaDeviationsDept').subscribe(response => {
      this.results = response;
    });
  }

  viewDeviation(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }

}
