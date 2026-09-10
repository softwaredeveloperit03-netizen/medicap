import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-stockbook',
  templateUrl: './stockbook.component.html',
  styleUrls: ['./stockbook.component.css']
})
export class StockbookComponent implements OnInit {
  isView = false;
  isconsumption = false;
  constructor() { }

  ngOnInit(): void {
  }

}
