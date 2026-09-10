import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers: [DatePipe]
})
export class LogComponent implements OnInit {

  orders;
  selectedClient =[];
  isView = false;

  clients;

  client_code = '';
  status = '';
  from_date = '';
  to_date = '';
  max_date = '';
  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    let date = new Date();
    this.from_date = this.datePipe.transform(date, 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getWorkOrders();
    this.getClients();
  }

  getWorkOrders() {
    this.service.get('marketing/workorder.php?type=getWorkOrders&client_code=' + this.client_code +'&status='+ this.status + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe((response: any) => {
      this.orders = response;
    });
  }

  getClients() {
    this.service.get('common.php?type=getClients').subscribe(response => {
      this.clients = response;
    });
  }

  view(index) {
    this.selectedClient = this.orders[index];
    this.isView = true;
  }

  download() {
    this.service.open('marketing/workorder.php?type=printworkorder&id='+this.selectedClient['id']);
  }
}
