import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-potentiometer',
  templateUrl: './potentiometer.component.html',
  styleUrls: ['./potentiometer.component.css']
})
export class PotentiometerComponent implements OnInit {
  isNew= false;

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
  }

  new() {
    this.isNew = true;
  }

}
