import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-schedule',
  templateUrl: './schedule.component.html',
  styleUrls: ['./schedule.component.css']
})
export class ScheduleComponent implements OnInit {

  results;
  selectedStability = [];
  isView =false;
  constructor(public service: DataAccessService) { }

  ngOnInit(): void {
    this.getStabilities();
  }

  getStabilities() {
    this.service.get('stability.php?type=getStabilities').subscribe(response => {
      this.results = response;
    });
  }

  viewProtocol(index) {
    this.selectedStability = this.results[index];
    this.isView = true;
  }
  

download() {
  this.service.open('stability.php?type=schedulePdf');
}
download2() {
  this.service.open('stability.php?type=schedulePdf2');
}
download3() {
  this.service.open('stability.php?type=schedulePdf3');
}
}
