import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css'],
  providers:[DatePipe]
})
export class LogComponent implements OnInit {
  pendingpo;
  selectresult = [];
  isView=false;
  selectresultproduct =[];
  client_code='';
  status='';
  from_date;
  to_date;
  clients;
  po_type='';

  constructor(private service:DataAccessService ,private datePipe:DatePipe, private router: Router) { 
    this.from_date=this.datePipe.transform(Date.now(),'yyyy-MM-01');
    this.to_date=this.datePipe.transform(Date.now(),'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getPOsLog();
    this.getClients();
  }

  getPOsLog(){
    this.service.get('planning/workorder.php?type=getWorkOrdersLog&po_type='+this.po_type+'&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.pendingpo =response;
    });
  }

  view(index){
    this.selectresult = this.pendingpo[index];
    this.isView = true;
  }

  viewfile(file) {
    window.open(file);
  }
  downloadP(){
    this.service.open('planning/workorder.php?type=downloadWorkorderLog&po_type='+this.po_type+'&from_date='+this.from_date+'&to_date='+this.to_date);
  }
  download(){
    this.service.open('marketing/po.php?type=downloadPO&order_no='+this.selectresult['order_no']);
  }
  downloadpo() {
    window.open(this.service.url + this.selectresult['file']);
  }
  getClients(){
    this.service.get('common.php?type=getClients').subscribe(response=>{
      this.clients=response;
    })
  }

  edit() {
    this.router.navigate(['/po/edit/' + this.selectresult['id']]);
  }
}
