import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
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
  constructor(public service: DataAccessService,private router:Router) { }

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

  isClose(){
    this.router.navigate(['/rnd/qa/stability'])
  }
}
