import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-audit-log-report',
  templateUrl: './audit-log-report.component.html',
  styleUrls: ['./audit-log-report.component.css'],
  providers: [DatePipe]
})
export class AuditLogReportComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  from_date='';
  to_date='';
  
  constructor(private service:DataAccessService ,private datePipe:DatePipe) {   
       this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');  
     this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');   }


  ngOnInit(): void {
    this.getAuditLog();
  }

  getAuditLog(){
    this.service.get('common.php?type=get_audit_log').subscribe(response=>{
      this.results=response;
    });
  }
 

  downloadRecord(){
    this.service.open('qms/deviation.php?type=downloadDeviationRecord&id='+this.selectedResult['id']+'&deviation_no='+this.selectedResult['deviation_no']);
  }

  downloadLog(){
    this.service.open('qms/deviation.php?type=downloadDeviationLog&from_date='+this.from_date+'&to_date='+this.to_date);
  }
 

}
