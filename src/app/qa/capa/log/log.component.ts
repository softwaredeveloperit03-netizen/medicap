import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {

  isView = false;
  results;
  review3_comment='';
  selectedFile:File;

  selectedDev = [];
  remark = '';
  comment='';
  extension='';
  capas=[];
  from_date='';
  to_date='';

  constructor(private service: DataAccessService,private datePipe:DatePipe) {
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');

   }

  ngOnInit(): void {
    this.getcapaLog();
  }

  getcapaLog(){
    this.service.get('qms/capa.php?type=getCapalog&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response => {
      this.results = response;
    });
  }
  viewIncident(i) {
    this.selectedDev = this.results[i];
    this.isView = true;
  }
  download(){
    this.service.open('qms/capa.php?type=downloadCapaLog&from_date='+this.from_date+'&to_date='+this.to_date);
  }
  downloadp(){
    this.service.open('qms/capa.php?type=downloadCapa&id='+this.selectedDev['id']);
  }


}
