import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css']
})
export class HistoryComponent implements OnInit {

  isView = false;
  results = [];
  selectedDev = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {

  }
 
  view(index) {
    this.selectedDev = this.results[index];
    this.isView = true;
  }

}
