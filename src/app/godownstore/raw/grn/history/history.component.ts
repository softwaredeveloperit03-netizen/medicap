import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css']
})
export class HistoryComponent implements OnInit {

  results;
  selectedResult = [];
  isView = false;

  constructor() { }

  ngOnInit(): void {
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }
}
