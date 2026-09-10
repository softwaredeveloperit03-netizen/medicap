import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  
  isView: any;
  fromdate = '';
  todate = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
  }

  getprint(){
    this.service.open('pdf1/capa.php?type=capalog&fromdate='+this.fromdate+'&todate='+this.todate);
  }
}
