import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-ri-detector',
  templateUrl: './ri-detector.component.html',
  styleUrls: ['./ri-detector.component.css']
})
export class RiDetectorComponent implements OnInit {

  isNew= false;
  

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    
  }
  

  new() {
    this.isNew = true;
  }

 
}
