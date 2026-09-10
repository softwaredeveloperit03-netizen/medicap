import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-daily',
  templateUrl: './daily.component.html',
  styleUrls: ['./daily.component.css']
})
export class DailyComponent implements OnInit {

  isNew= false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
  }

  new() {
    this.isNew = true;
  }

}