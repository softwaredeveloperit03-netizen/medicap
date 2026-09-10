import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css']
})
export class HistoryComponent implements OnInit {

  results;
  selectedResult = [];
  isView = false;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
}
