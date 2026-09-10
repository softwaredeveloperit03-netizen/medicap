import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  reports;
  selectedReview = [];
  isView = false;

  constructor() { }

  ngOnInit(): void {
  }
 
  
  viewReviews(index) {
    this.selectedReview = this.reports[index];
    this.isView = true;
  }
  
}
