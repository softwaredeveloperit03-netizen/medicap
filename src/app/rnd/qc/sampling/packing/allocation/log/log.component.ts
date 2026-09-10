import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;

  selectedSampling =[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getAllocationLog();
  }

  getAllocationLog(){
    this.service.get('qc/sampling/packing.php?type=getAllocationLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedSampling = this.results[index];
    this.isView = true;
  }

}
