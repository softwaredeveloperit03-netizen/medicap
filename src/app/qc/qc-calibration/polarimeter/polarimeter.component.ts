import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-polarimeter',
  templateUrl: './polarimeter.component.html',
  styleUrls: ['./polarimeter.component.css']
})
export class PolarimeterComponent implements OnInit {

  isNew= false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
  }

  new() {
    this.isNew = true;
  }

}

