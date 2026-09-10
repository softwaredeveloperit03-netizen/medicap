import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results=[];

  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getIndicatorsLog()

  }
  getIndicatorsLog(){
    this.service.get('qc/indicator.php?type=getIndicatorsLog').subscribe((response:any)=>{
      this.results=response;
    })
  }
  download(){
    this.service.open('qc/indicator.php?type=downloadIndicatorsLog')
  }
}
