import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';


@Component({
  selector: 'app-techlog',
  templateUrl: './techlog.component.html',
  styleUrls: ['./techlog.component.css'],
  providers: [DatePipe]

})
export class TechlogComponent implements OnInit {
 

  isView = false;
  selectedEntry;
  list;
  clientlist;
  document_name;
  documents = [];
  client_code = '';
  status = '';
  from_date = '';
  to_date = '';
  max_date = '';
  clients;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getDocumentlist();
    this.getClients();
  }
  
  viewPlan(index) {
    this.selectedEntry = this.list[index];
    this.isView = true;
  }

  getDocumentlist() {
    this.service.get('marketing/document.php?type=getRequests&client_code=' + this.client_code +'&status='+ this.status + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe((response: any) => {
      this.list = response;
    });
  }
  getClients() {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });
  }


}
