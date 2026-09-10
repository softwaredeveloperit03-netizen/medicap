import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-ph-meter',
  templateUrl: './ph-meter.component.html',
  styleUrls: ['./ph-meter.component.css']
})
export class PhMeterComponent implements OnInit {
  isNew= false;
  results;

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getQcPercent();
  }
  

  new() {
    this.isNew = true;
  }

  getQcPercent(){
    this.service.get('employee.php?type=getQcPercent').subscribe(response => {
      this.results = response;
      
    });
  }

}
