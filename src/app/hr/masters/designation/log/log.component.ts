import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  designations;
  isView=false;
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getDesignations();
  }
  getDesignations() {
    this.service.get('hr/designation.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }
  view(index){
    this.selectedResult=this.designations[index];
    this.isView=true;
  }
}
