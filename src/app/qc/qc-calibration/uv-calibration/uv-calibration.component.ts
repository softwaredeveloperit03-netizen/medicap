import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-uv-calibration',
  templateUrl: './uv-calibration.component.html',
  styleUrls: ['./uv-calibration.component.css']
})
export class UvCalibrationComponent implements OnInit {

  
  isNew= false;
  

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    
  }
  

  new() {
    this.isNew = true;
  }

 
}

